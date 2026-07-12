createjs.Sound.registerSound("/assets/packages/static/openvk/audio/notify.mp3", "notification");

function __actualPlayNotifSound() {
    createjs.Sound.play("notification");
}

window.playNotifSound = Function.noop;

function incrementNotificationsCounter() {
    document.querySelectorAll('a[href="/notifications"]').forEach(link => {

        let counterObject = link.querySelector('object');

        if (!counterObject) {
            counterObject = document.createElement('object');
            counterObject.type = 'internal/link';
            counterObject.innerHTML = ' (<b>1</b>)';
            link.appendChild(counterObject);
        } else {
            counterObject.classList.remove('zero_counter');
            const bTag = counterObject.querySelector('b');
            if (bTag) {
                let currentCount = parseInt(bTag.textContent) || 0;
                bTag.textContent = currentCount + 1;
            } else {
                counterObject.innerHTML = ' (<b>1</b>)';
            }
        }
    });
}

async function setupNotificationListener() {
    console.info("Notifications | Setting up notifications listener...");

    const POLL_INTERVAL = 10000;
    const CHECK_MORE_INTERVAL = 250;
    const ERROR_RETRY_INTERVAL = 60000;
    let isFirstRequest = true;

    while(true) {
        try {
            const notif = await API.Notifications.fetch();

            if (notif) {
                if (!isFirstRequest) {
                    playNotifSound();
                    console.info("Notifications | New notification", notif);
                    NewNotification(notif.title, notif.body, notif.ava, Function.noop, (notif.priority || 1) * 6000);
                    incrementNotificationsCounter();
                } else {
                    console.info("Notifications | First request: skipping alert (syncing cursor)");
                }
            }

            await new Promise(resolve => setTimeout(resolve, CHECK_MORE_INTERVAL));
        } catch(rejection) {
            if (rejection.message === "Nothing to report" || rejection.code === 1983) {
                if (isFirstRequest) {
                    console.info("Notifications | Cursor synced. Real-time notifications enabled.");
                    isFirstRequest = false;
                } else {
                    console.info("Notifications | No new notifications found, sleeping for " + POLL_INTERVAL/1000 + "s...")
                }
                await new Promise(resolve => setTimeout(resolve, POLL_INTERVAL));
            } else if (rejection.message === "Disabled" || rejection.code === 1999) {
                console.error("Notifications | Real-time notifications are disabled. Aborting RPC polling until next page load", rejection);
                break;
            } else {
                console.error("Notifications | Poll error, we'll try again in a minute...", rejection);
                await new Promise(resolve => setTimeout(resolve, ERROR_RETRY_INTERVAL));
            }
        }
    }
};

async function triggerMessageNotification(conv, text, timestamp) {
    try {
        const fields = typeof ChatGeneralForm !== 'undefined' ? ChatGeneralForm.base_fields : 'photo_50';

        const peer = conv.peer;
        const title = peer.full_name;
        const ava = peer.avatar_any || peer.photo_max || '';

        const notif = {
            title: escapeHtml(title),
            body: "<b>" + escapeHtml(title) + ":</b> " + (escapeHtml(ovk_proc_strtr(text, 95)) || "[Attachment]"),
            ava: ava,
            priority: 1,
        };

        if (typeof NewNotification === 'function') {
            playNotifSound();
            NewNotification(
                notif.title,
                notif.body,
                notif.ava,
                () => {
                  window.im.initImPage(document.querySelector('.page_content'));
                  window.im.selectChat(conv);
                },
                (notif.priority || 1) * 6000
            );
            window.im.updateCounter(window.im.getCounter() + 1);
        } else {
            console.log("Msg notifs | Got a new message but NewNotification not found:", notif);
        }

    } catch (error) {
        console.error("Msg notifs | Error occurred while forming notification:", error);
    }
}

(async function() {
    await setupNotificationListener();
})();

u(document.body).on("click", () => window.playNotifSound = window.__actualPlayNotifSound);
