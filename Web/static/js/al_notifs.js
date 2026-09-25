createjs.Sound.registerSound("/assets/packages/static/openvk/audio/notification.mp3", "notification");
createjs.Sound.registerSound("/assets/packages/static/openvk/audio/newmsg.mp3", "newmsg");

let isAudioUnlocked = false;

function __actualPlayNotifSound(type = "notification") {
    try {
        if (typeof createjs !== "undefined" && createjs.Sound && typeof createjs.Sound.play === "function") {
            createjs.Sound.play(type);
        } else {
            const audio = new Audio(`/assets/packages/static/openvk/audio/${type}.mp3`);
            audio.play().catch(err => console.warn("Audio fallback playback error", err));
        }
    } catch (e) {
        console.error("Notification sound playback error", e);
    }
}

const unlockAudio = () => {
    isAudioUnlocked = true;
};
['click', 'keydown', 'touchstart', 'pointerdown', 'focus'].forEach(evt => {
    window.addEventListener(evt, unlockAudio, { once: true, capture: true });
});

// --- Межвкладочная синхронизация звуков и уведомлений ---
const playedSoundIds = new Set();
const syncChannel = typeof BroadcastChannel !== 'undefined' ? new BroadcastChannel('ovk_notifs_sync') : null;

function markSoundPlayed(uniqueId) {
    playedSoundIds.add(uniqueId);
    setTimeout(() => playedSoundIds.delete(uniqueId), 15000);
}

function isTabVisible() {
    return window.im?.state?.is_tab_visible ?? (typeof document !== 'undefined' && !document.hidden);
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

function getActiveChatPeerIds() {
    const ids = [];
    const activeMessengerId = getActiveChatPeerId();
    if (activeMessengerId != null) {
        ids.push(Number(activeMessengerId));
    }
    if (window.im?.fastChats?.openedChats && Array.isArray(window.im.fastChats.openedChats)) {
        window.im.fastChats.openedChats.forEach(c => {
            if (!c.isMinimized && c.peerId != null) {
                ids.push(Number(c.peerId));
            }
        });
    }
    return ids;
}

function isChatOpenInCurrentTab(peerId) {
    if (peerId == null) return false;
    const targetId = Number(peerId);

    // 1. FastChat is open and NOT minimized
    if (window.im?.fastChats?.openedChats && Array.isArray(window.im.fastChats.openedChats)) {
        const isFastChatOpen = window.im.fastChats.openedChats.some(c => Number(c.peerId) === targetId && !c.isMinimized);
        if (isFastChatOpen) return true;
    }

    // 2. Messenger page /im is open and this specific dialog is open
    const isMessengerPage = location.pathname === '/im' || Boolean(window.im?.state?.is_opened);
    if (isMessengerPage) {
        const activeMessengerId = getActiveChatPeerId();
        if (activeMessengerId != null && Number(activeMessengerId) === targetId) {
            return true;
        }
    }

    return false;
}

// --- Кросс-вкладочное подавление звука ---
const TAB_ID = Date.now() + '_' + Math.random().toString(36).slice(2);
const CHAT_KEY_PREFIX = 'ovk_chat_';
const CHAT_STALE_MS = 15000;

function updateActiveChatForTab() {
    const key = CHAT_KEY_PREFIX + TAB_ID;
    const peerIds = getActiveChatPeerIds();
    if (peerIds.length > 0 && isTabVisible()) {
        localStorage.setItem(key, JSON.stringify({ peers: peerIds, ts: Date.now() }));
    } else {
        localStorage.removeItem(key);
    }
}

function cleanupTabChat() {
    localStorage.removeItem(CHAT_KEY_PREFIX + TAB_ID);
}

function isChatOpenInAnyTab(peerId) {
    if (peerId == null) return false;
    const targetId = Number(peerId);
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
            if (Array.isArray(data.peers)) {
                if (data.peers.map(Number).includes(targetId)) return true;
            } else if (Number(data.peer) === targetId) {
                return true;
            }
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
window.addEventListener('im:visibility_change', updateActiveChatForTab);
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

    if (!isTabVisible()) {
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

function showMessageNotification(conv, msg) {
    try {
        if (typeof NewNotification !== 'function') return;

        const peer = conv?.peer;
        if (!peer) return;

        const peerId = Number(peer.id || 0);
        const senderId = Number(msg.sender?.id || msg.from_id?.id || msg.from_id || msg.data?.from_id?.id || msg.data?.from_id || 0);

        let senderName = '';
        if (msg.sender?.getName) {
            senderName = msg.sender.getName(false, true) || msg.sender.getName();
        } else if (msg.sender?.name) {
            senderName = msg.sender.name;
        } else if (msg.author_name) {
            senderName = msg.author_name;
        } else if (window.im?.cached_profiles && senderId) {
            const prof = window.im.cached_profiles._findCachedProfileById(senderId);
            if (prof) {
                senderName = prof.getName ? (prof.getName(false, true) || prof.getName()) : prof.name;
            }
        }

        const isChat = (typeof peer.isChat === 'function' && peer.isChat()) || (peerId >= 2000000000);
        const peerTitle = (typeof peer.getName === 'function' ? peer.getName() : null) || peer.title || peer.name || peer.data?.title || '';

        let title = '';
        if (isChat) {
            title = peerTitle || (typeof tr === 'function' ? tr('chat') : 'Беседа');
        } else {
            title = senderName || peerTitle || (typeof tr === 'function' ? tr('message') : 'Сообщение');
        }

        let bodyText = (typeof msg.getText === 'function' ? msg.getText(true, true) : (msg.data?.text || msg.text || msg.body || '')) || '';
        if (!bodyText) {
            const atts = (msg.data && msg.data.attachments) || msg.attachments || [];
            if (atts.length > 0 && typeof get_attachment_text === 'function') {
                bodyText = get_attachment_text(atts[0]);
            } else if (atts.length > 0) {
                bodyText = typeof tr === 'function' ? tr('attachment') : 'Вложение';
            } else {
                bodyText = typeof tr === 'function' ? tr('new_message') : 'Новое сообщение';
            }
        }

        bodyText = String(bodyText).replace(/<[^>]*>/g, '').trim();
        if (bodyText.length > 120) {
            bodyText = bodyText.substring(0, 117) + '...';
        }

        if (isChat && senderName) {
            bodyText = `${senderName}: ${bodyText}`;
        }

        let ava = null;
        if (msg.sender?.getAvatar) {
            ava = msg.sender.getAvatar('tiny') || msg.sender.getAvatar();
        } else if (peer.getAvatar) {
            ava = peer.getAvatar('tiny') || peer.getAvatar();
        } else if (peer.photo || peer.avatar || peer.data?.photo_50) {
            ava = peer.photo || peer.avatar || peer.data?.photo_50;
        } else if (msg.author_photo) {
            ava = msg.author_photo;
        }

        if (!ava && window.im?.cached_profiles && senderId) {
            const prof = window.im.cached_profiles._findCachedProfileById(senderId);
            if (prof?.getAvatar) {
                ava = prof.getAvatar('tiny') || prof.getAvatar();
            }
        }

        const onClick = () => {
            if (!isChat && window.im?.fastChats && (!window.im?.state?.is_opened && location.pathname !== '/im')) {
                window.im.fastChats.openChat(peerId);
            } else if (window.router && typeof window.router.route === 'function') {
                window.router.route('/im?sel=' + peerId);
            } else {
                window.location.href = '/im?sel=' + peerId;
            }
        };

        NewNotification(title, bodyText, ava, onClick, 5000, false);
    } catch (e) {
        console.error("Msg balloon notif error:", e);
    }
}

async function triggerMessageNotification(conv, msg, timestamp) {
    try {
        const peer = conv?.peer;
        if (!peer) return;

        const currentUserId = Number(window.openvk ? window.openvk.current_id : (window.im?.state?.getId() || 0));
        const senderId = Number(msg.sender?.id || msg.from_id?.id || msg.from_id || msg.data?.from_id?.id || msg.data?.from_id || 0);

        if (senderId && currentUserId && senderId === currentUserId) {
            return;
        }

        const isMuted = (peer.isMuted && peer.isMuted()) || msg.data?.attachments?.muted || msg.attachments?.muted;
        const isMentioned = Boolean(
            msg.mention ||
            msg.is_mentioned ||
            msg.data?.mention ||
            msg.data?.is_mentioned ||
            msg.data?.attachments?.mention ||
            msg.attachments?.mention
        );

        if (isMuted && !isMentioned) {
            return;
        }

        const soundId = 'msg_' + (msg.id || msg.random_id || (Date.now() + '_' + Math.random()));
        if (isChatOpenInAnyTab(peer.id)) {
            markSoundPlayed(soundId);
            if (syncChannel) {
                syncChannel.postMessage({ type: 'SOUND_PLAYED', id: soundId });
            }
        } else {
            playNotifSoundOnce(soundId, "newmsg");
        }

        if (!isChatOpenInCurrentTab(peer.id)) {
            showMessageNotification(conv, msg);
        }
    } catch (error) {
        console.error("Msg notifs | Error occurred while forming notification:", error);
    }
}

(async function () {
    await setupNotificationListener();
})();