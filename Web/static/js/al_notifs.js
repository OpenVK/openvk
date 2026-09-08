createjs.Sound.registerSound("/assets/packages/static/openvk/audio/notification.mp3", "notification");
createjs.Sound.registerSound("/assets/packages/static/openvk/audio/newmsg.mp3", "newmsg");

let isAudioUnlocked = false;

function __actualPlayNotifSound(type = "notification") {
    try {
        createjs.Sound.play(type);
    } catch (e) {
        console.error("Notification sound playback error", e);
    }
}

u(document.body).on("click", () => {
    isAudioUnlocked = true;
}, { once: true });

// --- Межвкладочная синхронизация звуков и уведомлений ---
const playedSoundIds = new Set();
const syncChannel = typeof BroadcastChannel !== 'undefined' ? new BroadcastChannel('ovk_notifs_sync') : null;

function markSoundPlayed(uniqueId) {
    playedSoundIds.add(uniqueId);
    setTimeout(() => playedSoundIds.delete(uniqueId), 15000);
}

function isTabFocused() {
    return typeof document !== 'undefined' && !document.hidden
        && (typeof document.hasFocus === 'function' ? document.hasFocus() : true);
}

function getActiveChatPeerId() {
    try {
        if (!window.im?.state?.is_active) return null;
        const chat = window.im?.messenger?.getCurrentChat?.();
        return chat?.peer?.id || null;
    } catch (e) {
        return null;
    }
}

// --- Кросс-вкладочное подавление звука ---
const TAB_ID = Date.now() + '_' + Math.random().toString(36).slice(2);
const CHAT_KEY_PREFIX = 'ovk_chat_';
const CHAT_STALE_MS = 15000;

function updateActiveChatForTab() {
    const key = CHAT_KEY_PREFIX + TAB_ID;
    const peerId = getActiveChatPeerId();
    if (peerId != null) {
        localStorage.setItem(key, JSON.stringify({ peer: peerId, ts: Date.now() }));
    } else {
        localStorage.removeItem(key);
    }
}

function cleanupTabChat() {
    localStorage.removeItem(CHAT_KEY_PREFIX + TAB_ID);
}

function isChatOpenInAnyTab(peerId) {
    const now = Date.now();
    for (let i = localStorage.length - 1; i >= 0; i--) {
        const key = localStorage.key(i);
        if (!key || !key.startsWith(CHAT_KEY_PREFIX)) continue;
        try {
            const data = JSON.parse(localStorage.getItem(key));
            if (now - data.ts > CHAT_STALE_MS) {
                localStorage.removeItem(key);
                continue;
            }
            if (Number(data.peer) === Number(peerId)) return true;
        } catch (e) { continue; }
    }
    return false;
}

setInterval(updateActiveChatForTab, 5000);
updateActiveChatForTab();
window.addEventListener('pagehide', cleanupTabChat);
window.addEventListener('beforeunload', cleanupTabChat);

window.addEventListener('focus', updateActiveChatForTab);
window.addEventListener('visibilitychange', updateActiveChatForTab);
window.addEventListener('hashchange', updateActiveChatForTab);
window.addEventListener('popstate', updateActiveChatForTab);

try {
    const imObserverTarget = document.querySelector('#im_container') || document.body;
    const _chatObserver = new MutationObserver(() => updateActiveChatForTab());
    _chatObserver.observe(imObserverTarget, { childList: true, subtree: true });
} catch (e) { /* noop */ }

if (syncChannel) {
    syncChannel.onmessage = (e) => {
        const data = e.data;
        if (!data) return;

        if (data.type === 'SOUND_PLAYED') {
            markSoundPlayed(data.id);
        } else if (data.type === 'SHOW_GLOBAL_NOTIF') {
            displayGlobalNotification(data.notif, false);
        }
    };
}

async function playNotifSoundOnce(uniqueId, type = "notification") {
    if (!isAudioUnlocked || !uniqueId || playedSoundIds.has(uniqueId)) {
        return;
    }

    if (!isTabFocused()) {
        await new Promise(r => setTimeout(r, 120 + Math.floor(Math.random() * 80)));
        if (playedSoundIds.has(uniqueId)) return;
    }

    markSoundPlayed(uniqueId);
    if (syncChannel) {
        syncChannel.postMessage({ type: 'SOUND_PLAYED', id: uniqueId });
    }
    __actualPlayNotifSound(type);
}

window.playNotifSound = function (type = "notification") {
    playNotifSoundOnce(Date.now() + "_" + Math.random(), type);
};
window.playUniqueSound = playNotifSoundOnce;

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

function displayGlobalNotification(notif, shouldBroadcast = true) {
    const notifKey = 'notif_' + (notif.id || (notif.title + notif.body));

    if (shouldBroadcast) {
        playNotifSoundOnce(notifKey, "notification");
    }
    NewNotification(notif.title, notif.body, notif.ava, Function.noop, (notif.priority || 1) * 6000);
    incrementNotificationsCounter();

    if (shouldBroadcast && syncChannel) {
        syncChannel.postMessage({ type: 'SHOW_GLOBAL_NOTIF', notif });
    }
}

async function setupNotificationListener() {
    console.info("Notifications | Setting up notifications listener...");

    const POLL_INTERVAL = 10000;
    const CHECK_MORE_INTERVAL = 250;
    const ERROR_RETRY_INTERVAL = 60000;
    let isFirstRequest = true;

    while (true) {
        try {
            const notif = await API.Notifications.fetch();

            if (notif) {
                if (!isFirstRequest) {
                    displayGlobalNotification(notif, true);
                } else {
                    console.info("Notifications | First request: skipping alert (syncing cursor)");
                }
            }

            await new Promise(resolve => setTimeout(resolve, CHECK_MORE_INTERVAL));
        } catch (rejection) {
            if (rejection.message === "Nothing to report" || rejection.code === 1983) {
                if (isFirstRequest) {
                    console.info("Notifications | Cursor synced. Real-time notifications enabled.");
                    isFirstRequest = false;
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
}

async function triggerMessageNotification(conv, msg, timestamp) {
    try {
        const peer = conv.peer;
        const sender = msg.sender;
        const title = peer.getName();
        const ava = peer.getAvatar();

        if (peer.id === window.openvk.current_id || sender.id === window.openvk.current_id) {
            return;
        }

        const notif = {
            title: escapeHtml(title),
            body: "<b>" + escapeHtml(sender.getName()) + ":</b> " + (ovk_proc_strtr(msg.getText(false, true), 95)),
            ava: ava,
            priority: 1,
        };

        const soundId = 'msg_' + (msg.id || msg.random_id);
        if (isChatOpenInAnyTab(peer.id)) {
            markSoundPlayed(soundId);
            if (syncChannel) {
                syncChannel.postMessage({ type: 'SOUND_PLAYED', id: soundId });
            }
        } else {
            playNotifSoundOnce(soundId, "newmsg");
        }

        const thisTabHasChat = getActiveChatPeerId() != null && Number(getActiveChatPeerId()) === Number(peer.id);
        if (!thisTabHasChat && typeof NewNotification === 'function') {
            NewNotification(
                notif.title,
                notif.body,
                notif.ava,
                () => {
                    if (window.im?.messenger && typeof window.im.messenger.selectConversationByPeerId === 'function' && document.querySelector('#im_container')) {
                        window.im.messenger.selectConversationByPeerId(peer.id);
                    } else if (window.router && typeof window.router.route === 'function') {
                        window.router.route(`/im?sel=${peer.id}`);
                    } else {
                        window.location.href = `/im?sel=${peer.id}`;
                    }
                },
                (notif.priority || 1) * 6000
            );
        }
    } catch (error) {
        console.error("Msg notifs | Error occurred while forming notification:", error);
    }
}

(async function () {
    await setupNotificationListener();
})();