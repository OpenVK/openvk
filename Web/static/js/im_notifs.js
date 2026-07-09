class OVKLongPollListener {
    constructor() {
        this.server = null;
        this.key = null;
        this.ts = null;
        this.isLooping = false;
    }

    async initServer() {
        try {
            console.log("Msg notifs | Fetching server parameters...");
            const data = await window.OVKAPI.call('messages.getLongPollServer', { lp_version: 2 });

            if (data) {
                this.server = data.server;
                this.key = data.key;
                this.ts = data.ts;
                return true;
            }
            throw new Error("Longpoll server parameters fetch failed");
        } catch (error) {
            console.error("Msg notifs | Initialization error:", error);
            return false;
        }
    }

    async start() {
        if (this.isLooping) return;
        this.isLooping = true;

        let initialized = await this.initServer();
        while (!initialized) {
            console.log("Msg notifs | Retrying init in 5s...");
            await new Promise(resolve => setTimeout(resolve, 5000));
            initialized = await this.initServer();
        }

        console.log("Msg notifs | Started up listener.");

        while (this.isLooping) {
            try {
                console.log("Msg notifs | Fetching updates...");
                const url = `${this.server}?act=a_check&key=${this.key}&ts=${this.ts}&wait=25&mode=2&version=3`;
                
                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();

                if (data.failed) {
                    if (data.failed === 1) {
                        this.ts = data.ts;
                    } else if (data.failed === 2 || data.failed === 3) {
                        console.warn(`Msg notifs | Error ${data.failed}. Reconnecting...`);
                        await this.initServer();
                    }
                    continue;
                }

                this.ts = data.ts;

                if (data.updates && data.updates.length > 0) {
                    for (const update of data.updates) {
                        await this.handleUpdate(update);
                    }
                }

            } catch (error) {
                console.error("Msg notifs | Connection error. Retrying in 5s...", error);
                await new Promise(resolve => setTimeout(resolve, 5000));
            }
        }
    }

    stop() {
        this.isLooping = false;
        console.log("Msg notifs | Listener stopped.");
    }

    async handleUpdate(update) {
        const code = update[0];

        if (code === 4) {
            const messageId = update[1];
            const flags = update[2];
            const peerId = update[7].from;
            const timestamp = update[4];
            const subject = update[5];
            const text = update[6];

            const isOutbox = (flags & 2) !== 0;

            if (!isOutbox) {
                await this.triggerNotification(peerId, text, timestamp);
            }
        }
    }

    async triggerNotification(peerId, text, timestamp) {
        try {
            const fields = typeof ChatGeneralForm !== 'undefined' ? ChatGeneralForm.base_fields : 'photo_50';
            
            const userRes = await window.OVKAPI.call('users.get', {
                'user_ids': peerId,
                'fields': fields
            });

            const from = userRes[0] || {};

            if (!from) {
                console.warn(`Msg notifs | Failed to fetch user data with ID ${peerId}`);
                return;
            }

            let title = window.lang.im_notif_new_message || "New message";
            let ava = from.photo_50 || from.photo_100 || from.photo_200;

            if (userRes && userRes.response && userRes.response[0]) {
                const user = userRes.response[0];
                title = `${user.first_name} ${user.last_name}`;
                ava = user.photo_50 || user.photo_max || '';
            }

            const notif = {
                title: title,
                body: "<b>" + from.first_name + " " + from.last_name + ":</b> " + (escapeHtml(text) || "[Attachment]"),
                ava: ava,
                priority: 1,
            };

            if (typeof NewNotification === 'function') {
                playNotifSound();
                NewNotification(
                    notif.title,
                    notif.body,
                    notif.ava,
                    (typeof Function !== 'undefined' && Function.noop) ? Function.noop : () => {},
                    (notif.priority || 1) * 6000
                );
            } else {
                console.log("Msg notifs | Got a new message but NewNotification not found:", notif);
            }

        } catch (error) {
            console.error("Msg notifs | Error occurred while forming notification:", error);
        }
    }
}

function escapeHtml(unsafe) {
  return unsafe
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

const ovkListener = new OVKLongPollListener();

function updateListenerState() {
    const path = window.location.pathname;
    const isChatPage = path.includes('/im') || path.includes('/gim');

    if (isChatPage) {
        if (ovkListener.isLooping) {
            console.log("Msg notifs | Chats loaded -> Listener stopped.");
            ovkListener.stop();
        }
    } else {
        if (!ovkListener.isLooping) {
            console.log("Msg notifs | Transition from chats -> Listener started.");
            ovkListener.start();
        }
    }
}

window.addEventListener('popstate', updateListenerState);

setInterval(updateListenerState, 1000);

updateListenerState();