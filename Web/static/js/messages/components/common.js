import { html, render } from './render.js';
import { ChatGeneralForm } from './messages.js';
import { imLog } from '../logger.js';

export function getAppLocale() {
    if (window.openvk && window.openvk.locale) {
        const raw = window.openvk.locale.split(';')[0].split('.')[0].replace('_', '-');
        if (raw) return raw;
    }
    if (typeof tr === "function") {
        const raw = tr("__locale");
        if (raw && !raw.startsWith("@")) {
            const tag = raw.split(";")[0].split(".")[0].replace("_", "-");
            if (tag) return tag;
        }
    }
    if (window.openvk && window.openvk.lang) {
        return window.openvk.lang;
    }
    const htmlLang = document.documentElement?.lang;
    if (htmlLang) return htmlLang;
    return "ru-RU";
}

export function is24HourFormat() {
    const override = localStorage.getItem("tw.im.24h");
    if (override !== null) {
        return override === "1";
    }
    const loc = getAppLocale().toLowerCase();
    if (loc.startsWith("ru") || loc.startsWith("uk") || loc.startsWith("be") || loc.startsWith("kk")) {
        return true;
    }
    return true;
}

export function getTimeFormatOptions(withSeconds = false) {
    const is24 = is24HourFormat();
    const opts = {
        hour: "2-digit",
        minute: "2-digit",
        hour12: !is24,
        hourCycle: is24 ? "h23" : "h12",
    };
    if (withSeconds) {
        opts.second = "2-digit";
    }
    return opts;
}

export function formatTime(date, withSeconds = false) {
    if (!date) return "";
    const d = (date instanceof Date) ? date : new Date(typeof date === "number" && date < 10000000000 ? date * 1000 : date);
    if (isNaN(d.getTime())) return "";
    return d.toLocaleTimeString(getAppLocale(), getTimeFormatOptions(withSeconds));
}

export function formatDate(date, options = {}) {
    if (!date) return "";
    const d = (date instanceof Date) ? date : new Date(typeof date === "number" && date < 10000000000 ? date * 1000 : date);
    if (isNaN(d.getTime())) return "";
    return d.toLocaleDateString(getAppLocale(), options);
}

export const PeerAvatar = ({ peer, className = "", loading = "lazy", saved_messages_ava = true, orig_ava = true, size = "mid", onClick = null }) => {
    if (!peer) {
        return html`<img class="${className}" src="/assets/packages/static/openvk/img/im/chat_meaningless.jpg" loading="${loading}" onClick=${onClick} />`;
    }

    if (peer.id === window.im.state.getId()) {
        if (!saved_messages_ava && !orig_ava) {
            return html`<div class="${className} chat-table-avatar-empty" onClick=${onClick}></div>`;
        }

        if (!orig_ava) {
            return html`<img class="${className}" src=${ChatGeneralForm.SAVED_MESSAGES_AVATAR} loading="${loading}" onClick=${onClick} />`;
        }
    }

    if (peer.supposed_type === 'chat' && !peer.has_custom_avatar) {
        const avatars = peer.getMosaicAvatars() || [];
        const cell0 = avatars[0] || null;
        const cell1 = avatars[1] || null;
        const cell2 = avatars[2] || null;
        const cell3 = avatars[3] || null;

        if (avatars.length == 1 || (!cell0 && !cell1 && !cell2 && !cell3)) {
            return html`<img class="${className}" src="/assets/packages/static/openvk/img/im/chat_meaningless.jpg" loading="${loading}" onClick=${onClick} />`;
        }

        if (avatars.length == 2) {
            // "object-position: left;" для парных аватарочек ^_^
            return html`
            <div class="chat_table_avatar chat_table_avatar_double ${className}" onClick=${onClick}>
                ${cell0 ? html`<img class="chat_table_avatar_cell pos-left" src="${cell0}" loading="${loading}" />` : ''}
                ${cell1 ? html`<img class="chat_table_avatar_cell pos-right" src="${cell1}" loading="${loading}" />` : ''}
            </div>
            `;
        }

        return html`
            <div class="chat_table_avatar chat_table_avatar_more3 ${className}">
                ${cell0 ? html`<img class="chat_table_avatar_cell" src="${cell0}" loading="${loading}" />` : ''}
                ${cell1 ? html`<img class="chat_table_avatar_cell" src="${cell1}" loading="${loading}" />` : ''}
                ${cell2 ? html`<img class="chat_table_avatar_cell" src="${cell2}" loading="${loading}" />` : ''}
                ${cell3 ? html`<img class="chat_table_avatar_cell" src="${cell3}" loading="${loading}" />` : ''}
            </div>
        `;
    }

    const src = peer.getAvatar(size, orig_ava == false);
    return html`<img class="${className}" src=${src} loading="${loading}" onClick=${onClick} />`;
};

export const PeerTab = ({ conv, active, page }) => {
    const peerName = conv?.peer?.getName ? conv.peer.getName(true, true) : (conv?.name || conv?.id || "");
    const unreadCount = conv?.unread_count || 0;
    const isUnread = conv && typeof conv.isRead === 'function' ? !conv.isRead() : false;

    return html`
        <div class="messages--peers-tab${active ? ' selected' : ''} ${isUnread ? 'unread' : ''}">
            <a onClick=${(e) => {
            if (e && e.preventDefault) e.preventDefault();
            window.im?.messenger.selectConversation(conv);
        }}>${peerName}</a>
            <span class="messages--peers-tab-counter">+${unreadCount}</span>
            <span class="messages--peers-tab-close" onClick=${(e) => {
            if (e) {
                if (e.preventDefault) e.preventDefault();
                if (e.stopPropagation) e.stopPropagation();
            }
            window.im?.messenger.closeChat(conv, page);
        }}>
                <div class="cross ${active ? "white" : ""}"></div>
            </span>
        </div>
    `;
};

export const PeerTabsView = ({ had_more_one_tab, tabs, currentChat, page, convo }) => {
    //if (tabs.length < 2 && had_more_one_tab) { return html`` }

    return html`
        <div class="messages--peers-header-wrap">
            <div class="messages--peers-tabs">
                ${(tabs || []).map((tab, idx) => html`
                    <${PeerTab} key=${tab?.peer ? tab.peer.id : (tab?.id || idx)} conv=${tab} active=${idx === currentChat} page=${page} />
                `)}
            </div>
            <${PinnedMessageBar} convo=${convo} />
        </div>
    `;
};

export const PinnedMessageBar = ({ convo }) => {
    if (!convo || !convo.hasPinned()) return null;
    const pinMsg = convo.getPinnedMessageObject();
    if (!pinMsg) return null;

    let senderName = tr("pinned_message");
    try {
        const currentUserId = window.openvk ? window.openvk.current_id : window.im?.state?.getId();
        const senderId = pinMsg.from_id || (pinMsg.data ? pinMsg.data.from_id : null);
        const isMine = (pinMsg.isMine && pinMsg.isMine()) || (senderId && senderId === currentUserId);

        if (isMine) {
            senderName = tr("you");
        } else {
            const sender = pinMsg.sender || (window.im?.cached_profiles && window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(senderId));
            if (sender && typeof sender.getName === 'function') {
                senderName = sender.getName(false, true) || sender.getName(false);
            } else {
                senderName = tr("pinned_message");
            }
        }
    } catch (e) {
        console.error(e);
    }

    let textPreview = "";
    try {
        if (typeof pinMsg.getText === 'function') {
            textPreview = pinMsg.getText(true, true);
        } else if (pinMsg.data && pinMsg.data.text) {
            textPreview = pinMsg.data.text.replace(/\[([a-zA-Z0-9_]+)(?:\|([^\]]*))?\]/g, (m, target, title) => (title && title.trim()) ? title.trim() : target);
        }
    } catch (e) {
        textPreview = "";
    }

    if (!textPreview || textPreview.length === 0) {
        const atts = typeof pinMsg.getAttachments === 'function' ? pinMsg.getAttachments() : [];
        if (atts && atts.length > 0) {
            textPreview = "[" + (typeof tr === 'function' ? tr("attachment") : "Вложение") + "]";
        } else {
            textPreview = "...";
        }
    }

    const canUnpin = Boolean(
        (convo.peer && typeof convo.peer.can === 'function' && convo.peer.can("pin")) ||
        (convo.peer && typeof convo.peer.isAdmin === 'function' && convo.peer.isAdmin()) ||
        (pinMsg && typeof pinMsg.can === 'function' && pinMsg.can("pin"))
    );

    const handleClick = (e) => {
        e.preventDefault();
        window.im.messenger.showPinnedModal(convo);
    };

    const handleUnpin = (e) => {
        e.stopPropagation();
        window.im.messenger.unpinMessage(convo);
    };

    const titleText = tr("pinned_message");
    const unpinTitle = tr("unpin_message");
    const previewTrimmed = typeof ovk_proc_strtr === 'function' ? ovk_proc_strtr(String(textPreview), 65) : String(textPreview).substring(0, 65);

    return html`
        <div class="messenger-pinned-bar" onClick=${handleClick}>
            <div class="messenger-pinned-bar--content">
                <div class="messenger-pinned-bar--title">${titleText}</div>
                <div class="messenger-pinned-bar--text">
                    <b>${senderName}:</b> <span>${previewTrimmed}</span>
                </div>
            </div>
            ${canUnpin ? html`
                <div class="messenger-pinned-bar--close" onClick=${handleUnpin} title="${unpinTitle}">
                    <div class="cross"></div>
                </div>
            ` : ""}
        </div>
    `;
};

export const ActionsBar = ({ selectedMessages, count, onDelete, onUnselect, onReply, onForwardClick, onViewers }) => {
    if (count === 0) return null;
    let canDeleteThemAll = true;
    let canForward = count < 500;

    selectedMessages.forEach(msg => {
        if (!msg || typeof msg.can !== "function" || msg.can("delete") == false) {
            canDeleteThemAll = false;
        }
        if (!msg || typeof msg.can !== "function" || msg.can("forward") == false) {
            canForward = false;
        }
    });

    const firstMsg = selectedMessages && selectedMessages.length === 1 ? selectedMessages[0] : null;
    const canReply = firstMsg && (typeof firstMsg.can === "function" ? firstMsg.can("reply") : !firstMsg.isDeleted());
    const canViewers = firstMsg && (typeof firstMsg.can === "function" ? firstMsg.can("viewers") : false);

    return html`
        <div class="messages--actions shown">
            <div>
                <div class="message-tab-counter message-tab"><a onClick=${onUnselect}>${tr("selected_messages", count)}</a></div>
            </div>
            <div>
                ${canForward == true && html`
                <div class="message-tab"><a onClick=${onForwardClick}>${tr("forward_messages")}</a></div>
                `}
                ${count === 1 && canReply && html`
                    <div class="message-tab"><a onClick=${onReply}>${tr("reply_to_message")}</a></div>
                `}
                ${count === 1 && canViewers && html`
                    <div class="message-tab"><a onClick=${() => { if (onViewers) onViewers(firstMsg); else window.im?.messenger?.view?.onViewersButtonClick(null, firstMsg); }}>${tr("message_viewers")}</a></div>
                `}
                ${canDeleteThemAll == true && html`
                <div class="message-tab"><a onClick=${onDelete}>${tr("delete_message")}</a></div>
                `}
            </div>
        </div>
    `;
};

export const AttachmentMenu = () => {
    return html`
        <div class="attachmentMenu">
            <a class="menu_toggler">${tr('attach')}</a>
            <div id="wallAttachmentMenu" class="up_direction hidden">
                <a class="header menu_toggler">${tr('attach')}</a>
                <div class="_wrap">
                    <a id="__photoAttachment">
                        <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/application-x-egon.png" />
                        ${tr('photo')}
                    </a>
                    <a id="__videoAttachment">
                        <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/application-vnd.rn-realmedia.png" />
                        ${tr('video')}
                    </a>
                    <a id="__audioAttachment">
                        <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/audio-ac3.png" />
                        ${tr('audio')}
                    </a>
                    <a id="__documentAttachment">
                        <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/application-octet-stream.png" />
                        ${tr('document')}
                    </a>
                    <a onClick=${(e) => typeof initGraffiti !== 'undefined' && initGraffiti(e)}>
                        <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/actions/draw-brush.png" />
                        ${tr('graffiti')}
                    </a>
                </div>
            </div>
        </div>
  `;
};

export const WriteBar = ({ convo }) => {
    if (!convo) return null;
    let cls = ["messenger-app-status"];
    const a = typeof convo.getActivityMsg === "function" ? convo.getActivityMsg() : ["", []];
    const isTyping = a && a[1] && a[1].length > 0;
    let content = "";
    let barType = "";

    if (isTyping) {
        content = a[0];
        barType = "is-typing";
        cls.push("shown");
    } else if (convo.peer && typeof convo.peer.getOfflineBarString === "function") {
        const offlineMsg = convo.peer.getOfflineBarString();
        if (offlineMsg) {
            content = offlineMsg;
            barType = "is-offline";
            cls.push("shown");
        }
    }

    /*if (!content) {
        return null;
    }*/

    return html`
        <div class="${cls.join(' ')}">
            <div class="write-bar ${barType}">
                ${content}
            </div>
        </div>
    `;
};

export const getReplySnippet = (msg) => {
    if (!msg) return "";
    let text = "";
    if (typeof msg.getText === 'function') {
        text = msg.getText(true, true) || "";
    } else if (msg.data?.text) {
        text = msg.data.text.replace(/\[([a-zA-Z0-9_]+)(?:\|([^\]]*))?\]/g, (m, target, title) => (title && title.trim()) ? title.trim() : target);
    }

    text = text.replace(/[\r\n]+/g, ' ').trim();

    if (!text && msg.data?.attachments) {
        let atts = msg.data.attachments;
        if (!Array.isArray(atts)) {
            atts = typeof atts === 'object' ? Object.values(atts) : [atts];
        }
        if (atts.length > 0 && atts[0]) {
            const t = atts[0].type;
            if (t === 'photo') text = '[' + tr('attachment_photo') + ']';
            else if (t === 'video') text = '[' + tr('attachment_video') + ']';
            else if (t === 'audio') text = '[' + tr('attachment_audio') + ']';
            else if (t === 'doc') {
                const d = atts[0].doc;
                const isGif = d && (d.type === 3 || (d.ext && d.ext.toLowerCase() === 'gif') || (d.title && d.title.toLowerCase().endsWith('.gif')));
                text = isGif ? '[GIF]' : ('[' + tr('attachment_doc') + ']');
            }
            else if (t === 'sticker') text = '[' + tr('attachment_sticker') + ']';
            else text = '[' + tr('attachment') + ']';
        }
    }

    if (!text) {
        const fwdCount = (typeof msg.getFwdCount === 'function') ? msg.getFwdCount() : (
            (msg.getFwdMessages && msg.getFwdMessages().length) ||
            (msg.data?.fwd_messages && (Array.isArray(msg.data.fwd_messages) ? msg.data.fwd_messages.length : Object.keys(msg.data.fwd_messages).length)) ||
            (msg.data?.forward_messages && (Array.isArray(msg.data.forward_messages) ? msg.data.forward_messages.length : Object.keys(msg.data.forward_messages).length)) ||
            0
        );
        if (fwdCount > 0) {
            text = '[' + (typeof tr === 'function' ? tr('forwarded_messages_noun', fwdCount) : `Пересланные сообщения (${fwdCount})`) + ']';
        }
    }

    return text;
};

export function getEmojiHex(emoji) {
    if (typeof encode_emoji === 'function') return encode_emoji(emoji);
    let hex = '';
    for (let i = 0; i < emoji.length; i++) {
        hex += emoji.charCodeAt(i).toString(16).padStart(4, '0').toUpperCase();
    }
    return hex;
}

export function getDisplayRecentSmiles() {
    const DEFAULT_SMILES = ['😊', '😃', '😉', '😄', '👍', '❤', '🔥', '😂'];
    let recents = [];
    if (typeof getRecentSmiles === 'function') {
        recents = getRecentSmiles();
    } else {
        try {
            recents = JSON.parse(localStorage.getItem('recent_smiles') || '[]');
        } catch (e) { }
    }
    const list = (recents || []).filter(s => s && s.trim()).slice(0, 8);
    for (const def of DEFAULT_SMILES) {
        if (list.length >= 8) break;
        if (!list.includes(def)) list.push(def);
    }
    return list.slice(0, 8);
}

export function onRecentSmileClick(s, e) {
    if (e) {
        if (e.preventDefault) e.preventDefault();
        if (e.stopPropagation) e.stopPropagation();
    }

    let target = null;
    if (e && e.target) {
        const btn = e.target.closest ? (e.target.closest('.im-recent-smile-btn') || e.target) : e.target;
        const box = btn.closest ? btn.closest('#write, .messenger-app--input, .model_content_textarea, .messenger-app-end, .im_page') : null;
        if (box) {
            target = box.querySelector('.content-editable, .small-textarea, textarea');
        }
    }

    if (!target) {
        const activePage = document.querySelector('#im_page_containers .im_page:not(.hidden)')
            || document.querySelector('.im_page:not(.hidden)');
        if (activePage) {
            target = activePage.querySelector('#write .content-editable, #write .small-textarea, .content-editable, .small-textarea, textarea');
        }
    }

    if (!target && window.ContentEditable && window.ContentEditable.lastFocused) {
        const focusedEl = window.ContentEditable.lastFocused.el;
        if (focusedEl && document.contains(focusedEl) && !focusedEl.closest('.im_page.hidden, .hidden')) {
            target = focusedEl;
        }
    }

    if (!target) {
        const allEditables = document.querySelectorAll('.content-editable, .small-textarea, textarea');
        for (const el of allEditables) {
            if (document.contains(el) && !el.closest('.im_page.hidden, .hidden')) {
                target = el;
                break;
            }
        }
    }

    if (!target) {
        target = document.querySelector('#write .content-editable')
            || document.querySelector('#write .small-textarea')
            || document.querySelector('.content-editable')
            || document.querySelector('.small-textarea');
    }

    if (target) {
        if (!target.insertEmoji && !target._contentEditable && window.ContentEditable && target.classList && target.classList.contains('content-editable')) {
            new window.ContentEditable(target, { submitOnEnter: true, placeholder: target.getAttribute('data-placeholder') || '' });
        }
        if (typeof target.insertEmoji === 'function') {
            target.insertEmoji(s);
        } else if (target._contentEditable && typeof target._contentEditable.insertEmoji === 'function') {
            target._contentEditable.insertEmoji(s);
        } else {
            const start = typeof target.selectionStart !== 'undefined' ? target.selectionStart : target.value.length;
            const end = typeof target.selectionEnd !== 'undefined' ? target.selectionEnd : target.value.length;
            const val = target.value || '';
            target.value = val.substring(0, start) + s + val.substring(end);
            target.selectionStart = target.selectionEnd = start + s.length;
            target.focus();
            target.dispatchEvent(new Event('input', { bubbles: true }));
            target.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (typeof target.focus === 'function') {
            try { target.focus(); } catch (err) { }
        }
    }
    if (typeof addSmile === 'function') {
        addSmile(s);
    }
    if (typeof updateRecentSmilesInPicker === 'function') {
        updateRecentSmilesInPicker();
    }
    if (typeof window.updateRecentSmilesBar === 'function') {
        window.updateRecentSmilesBar();
    }
}

if (typeof window !== 'undefined' && !window._imRecentSmilesInit) {
    window._imRecentSmilesInit = true;
    window.updateRecentSmilesBar = () => {
        const bars = document.querySelectorAll('.im-recent-smiles-bar');
        if (!bars || bars.length === 0) return;
        const smiles = getDisplayRecentSmiles();
        bars.forEach(bar => {
            bar.innerHTML = smiles.map(s => {
                const hex = getEmojiHex(s);
                return `<span class="im-recent-smile-btn" title="${s}" data-emoji="${s}"><span class="emoji emoji_${hex}">${s}</span></span>`;
            }).join('');
        });
    };

    document.addEventListener('mousedown', (e) => {
        const btn = e.target.closest ? e.target.closest('.im-recent-smile-btn') : null;
        if (btn && btn.closest('.im-recent-smiles-bar')) {
            e.preventDefault();
        }
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest ? e.target.closest('.im-recent-smile-btn') : null;
        if (btn && btn.closest('.im-recent-smiles-bar')) {
            const emoji = btn.dataset.emoji || btn.getAttribute('title');
            if (emoji) {
                onRecentSmileClick(emoji, e);
            }
        }
    });
}

export const MentionAutocomplete = ({ items, selectedIndex, onSelect }) => {
    if (!items || items.length === 0) return null;

    return html`
        <div class="im-mention-autocomplete">
            ${items.map((item, idx) => html`
                <div
                    class="im-mention-item ${idx === selectedIndex ? 'selected' : ''}"
                    onMouseDown=${(e) => { e.preventDefault(); e.stopPropagation(); onSelect(item); }}
                >
                    ${item.avatar ? html`
                        <img src="${item.avatar}" class="im-mention-ava" onError=${(e) => { e.target.onerror = null; e.target.src = '/assets/packages/static/openvk/img/camera_50.png'; }} />
                    ` : html`
                        <div class="im-mention-ava-empty"></div>
                    `}
                    <div class="im-mention-info">
                        <span class="im-mention-name">${item.name}</span>
                        ${item.type === 'all' || item.type === 'online' ? html`
                            <span class="im-mention-type">@${item.type}</span>
                        ` : html`
                            <span class="im-mention-slug">${item.displaySlug || (item.slug ? (item.slug.startsWith('@') ? item.slug : '@' + item.slug) : (item.id > 0 ? `@id${item.id}` : ''))}</span>
                        `}
                    </div>
                </div>
            `)}
        </div>
    `;
};

export const InputArea = ({ editMsg, replyTo, onRemoveReply, onSend, onKeyPress, currentDraft, onInput, togglePeerInfo, clickOnReply, convo, forwarded_msg, onRemoveForward, mentionActive, mentionMatches, mentionSelectedIndex, onApplyMention }) => {
    const is_editing = editMsg != null;
    const current_user = window.im.state.getOperator();
    const corresponder = window.im.state.getCurrentConvo();
    const isForwarded = forwarded_msg && forwarded_msg.length && forwarded_msg.length > 0;
    const recentSmiles = getDisplayRecentSmiles();

    const cantWriteInfo = (convo && typeof convo.getCantWriteInfo === 'function')
        ? convo.getCantWriteInfo()
        : (convo?.peer && typeof convo.peer.getCantWriteInfo === 'function'
            ? convo.peer.getCantWriteInfo()
            : (corresponder && typeof corresponder.getCantWriteInfo === 'function'
                ? corresponder.getCantWriteInfo()
                : { allowed: true, text: "" }));
    const canWrite = cantWriteInfo.allowed !== false;

    const cls = [
        "messenger-app-end",
        (canWrite && (replyTo || editMsg || isForwarded)) ? 'm-selected' : '',
    ];

    return html`
    <div class="${cls.join(" ")}">
        ${canWrite && replyTo && html`
            <div class="input-reply input-m" onclick=${(e) => {
                if (!e.target.closest('.input-close')) {
                    clickOnReply(replyTo);
                }
            }}>
                <div class="input-reply-content">
                    <div class="input-reply-title">${(() => {
                const sName = replyTo.sender?.getName ? replyTo.sender.getName() : (replyTo.data?.from_id ? `id${replyTo.data.from_id}` : '');
                const t = typeof tr === 'function' ? tr('reply_to_message_user', sName) : '';
                return (t && !t.startsWith('@')) ? t : `В ответ ${sName}`;
            })()}:</div>
                    <div class="input-reply-text" dangerouslySetInnerHTML=${{ __html: typeof replyTo.getText === 'function' ? replyTo.getText(false, true, false) : (replyTo.data?.text || '') }} />
                </div>
                <div class="input-close" onclick=${(e) => {
                e.stopPropagation();
                onRemoveReply();
            }}>×</div>
            </div>
        `}
        ${canWrite && isForwarded && html`
            <div class="input-reply input-m">
                <div class="input-reply-content">
                    <div class="input-reply-title">${tr('forwarded_messages_noun', forwarded_msg.length)}</div>
                    <div class="input-reply-text">
                        ${forwarded_msg.map((f, i) => html`
                            <div class="input-fwd-item" key=${f.id || i}>
                                <b>${f.sender?.getName ? f.sender.getName() : `id${f.from_id}`}:</b> ${typeof f.getText === 'function' ? f.getText(true, true) : (f.data?.text ? f.data.text.replace(/\[([a-zA-Z0-9_]+)(?:\|([^\]]*))?\]/g, (m, target, title) => (title && title.trim()) ? title.trim() : target) : (f.text || ''))}
                            </div>
                        `)}
                    </div>
                </div>
                <div class="input-close" onclick=${(e) => {
                e.stopPropagation();
                onRemoveForward();
            }}>×</div>
            </div>
        `}
        ${canWrite && editMsg && html`
            <div class="input-reply input-m">
                <div class="input-reply-content">
                    <span class="input-type">${tr("edit_of_message")}:</span>
                    <span class="input-reply-text">${getReplySnippet(editMsg)}</span>
                </div>
                <div class="input-close" onClick=${(e) => {
                e.stopPropagation();
                window.im.messenger.cancelEdit();
            }}>×</div>
            </div>
        `}
        <div class="messenger-mountain" onClick=${(e) => {
            if (window.im?.messenger?.view?.scrollToEndOfChat) {
                window.im.messenger.view.scrollToEndOfChat(e, convo);
            }
        }}>
            ${tr("viewing_old_messages")}
        </div>
        ${!canWrite ? html`
            <div class="post-buttons im-cant-write-container">
                <div class="messenger-app--cant-write">
                    <div class="im-cant-write-text">${cantWriteInfo.text || tr('cannot_write_default')}</div>
                </div>
            </div>
        ` : html`
            <div class="post-buttons">
                <div class="model_content_textarea messenger-app--input has_emoji_picker expanded-textarea" id="write">
                    <img class="ava" src=${current_user.getAvatar("mid", false)} alt=${current_user.getName()} />
                    <div class="messenger-app--input---messagebox">
                        ${(mentionActive && mentionMatches && mentionMatches.length > 0) ? html`
                            <${MentionAutocomplete}
                                items=${mentionMatches}
                                selectedIndex=${mentionSelectedIndex}
                                onSelect=${onApplyMention}
                            />
                        ` : ""}
                        <div class="textareas has_emoji_picker">
                            ${(typeof window !== 'undefined' && window.ContentEditable && typeof window.ContentEditable.isSupported === 'function' && window.ContentEditable.isSupported()) ? html`
                                <div
                                    class="small-textarea content-editable"
                                    contenteditable="true"
                                    role="textbox"
                                    aria-multiline="true"
                                    data-placeholder=${tr('enter_message')}
                                    onInput=${onInput}
                                    onKeyDown=${onKeyPress}
                                    ref=${(el) => {
                    if (!el) return;
                    if (!el._contentEditable && window.ContentEditable) {
                        new window.ContentEditable(el, { submitOnEnter: true, placeholder: tr('enter_message') });
                        if (currentDraft) {
                            el.setText(currentDraft);
                        }
                        el._lastConvoId = convo?.id;
                    } else if (el._contentEditable) {
                        if (convo && convo.id !== el._lastConvoId) {
                            el._lastConvoId = convo.id;
                            el.setText(currentDraft || '');
                        }
                    }
                }}
                                ></div>
                            ` : html`
                                <textarea
                                    class="small-textarea"
                                    placeholder=${tr('enter_message')}
                                    value=${currentDraft}
                                    onInput=${onInput}
                                    onKeyDown=${onKeyPress}
                                ></textarea>
                            `}
                            <div class="emoji_picker_entrypoint"></div>
                        </div>
                        <div class="post-horizontal"></div>
                        <div class="post-vertical"></div>
                        <div class="input--messagebox-buttons">
                            <div class="input--messagebox-left">
                                <button class="button" onClick=${onSend}>${!is_editing ? tr('send') : tr('edit_action_lr')}</button>
                                <div class="im-recent-smiles-bar">
                                    ${recentSmiles.map(s => html`
                                        <span
                                            class="im-recent-smile-btn"
                                            title="${s}"
                                            data-emoji="${s}"
                                            onMouseDown=${(e) => { e.preventDefault(); }}
                                            onClick=${(e) => onRecentSmileClick(s, e)}
                                        >
                                            <span class="emoji emoji_${getEmojiHex(s)}">${s}</span>
                                        </span>
                                    `)}
                                </div>
                            </div>
                            <${AttachmentMenu} />
                        </div>
                    </div>
                    <${PeerAvatar}
                        peer=${replyTo ? replyTo.sender : (corresponder ? corresponder.peer : null)}
                        className="ava ava2"
                        loading="eager"
                        saved_messages_ava=${false}
                        orig_ava=${false}
                        onClick=${() => { window.im.openTabByName("contact") }} />
                </div>
            </div>
        `}
    </div>
  `;
};

export const ConversationItem = ({ conv, isForward = false, page = null }) => {
    const cls1 = ["crp-entry"];
    const last_msg = conv.last_message;
    const peer = conv.peer;
    const has_activity = conv.hasActivity();
    const lastFromId = Number(last_msg?.data ? (last_msg.data.from_id?.id || last_msg.data.from_id) : (last_msg?.from_id || 0));
    if (last_msg && (lastFromId === Number(peer?.id) || (peer && typeof peer.isSavedMessages === 'function' && peer.isSavedMessages()))) {
        cls1.push("crp-entry-replied-same");
    }
    if (!conv.isRead()) {
        cls1.push("unread");
    }

    const isOutgoingUnread = Boolean(
        last_msg &&
        !has_activity &&
        (typeof last_msg.isMine === 'function' ? last_msg.isMine() : false) &&
        (peer && typeof peer.isSavedMessages === 'function' ? !peer.isSavedMessages() : true) &&
        (typeof last_msg.isRead === 'function' ? !last_msg.isRead(conv) : false)
    );

    const messageCls = ["crp-entry--message"];
    if (isOutgoingUnread) {
        messageCls.push("unread");
    }

    // здесь появился соблазн добавить && peer.data.members_count > 3 чтобы число участников показывалось только если в беседе много людей
    // с одной стороны по названию или аватарке и так понятно, что это беседа, но название и аватарка могут быть изменены
    const d = last_msg != null && has_activity == false;
    let last_sender_ava = "";
    let last_sender_name = "";
    if (last_msg && last_msg.sender) {
        try {
            last_sender_ava = last_msg.sender.getAvatar("mid", false);
            last_sender_name = last_msg.sender.getName ? last_msg.sender.getName() : (last_msg.sender.first_name ? `${last_msg.sender.first_name} ${last_msg.sender.last_name || ''}`.trim() : (last_msg.sender.name || ''));
        } catch (e) {
            console.error(e);
        }
    }
    const isChat = peer && peer.supposed_type === "chat";
    return html`
        <div class="${cls1.join(' ')}" onClick=${() => window.im?.messenger.onConversationsClick(conv, isForward, page)}>
        <div class="crp-entry--main">
            <div class="crp-entry--image">
                <${PeerAvatar} peer=${peer} orig_ava=${false} />
            </div>
            <div class="crp-entry--info">
                <div class="crp-entry--info-wrap">
                    <a>${ovk_proc_strtr(peer.getName(true), 30)}</a>
                    <div class="crp-entry--excess">
                        ${peer.supposed_type == "chat" && peer.data.members_count ? html`<span>${tr("members_count", peer.data.members_count)}</span>` : ""}
                        ${last_msg && html`<span>${last_msg.getDate(2)}</span>`}
                    </div>
                </div>
                ${peer && typeof peer.isMuted === 'function' && peer.isMuted() ? html`<span class="im-mute-indicator" title="${tr('chat_mute_notifications')}"></span>` : ""}
            </div>
        </div>
        <div class="${messageCls.join(' ')}">
            ${d && html`
            <div class="crp-entry--message---av">
                <img src="${last_sender_ava}" />
            </div>
            <div class="crp-entry--message---content">
                ${isChat && last_sender_name ? html`
                    <div class="crp-entry--message---author">${last_sender_name}</div>
                ` : ""}
                <div class="crp-entry--message---text">
                    <span dangerouslySetInnerHTML=${{ __html: last_msg.getText(false, true, true) }} />
                </div>
            </div>`}
            ${has_activity == true && html`
                <div class="crp-entry--message---content">
                    <div class="crp-entry--message---text">
                        <span>${(conv.getActivityMsg()[0] || "")}</span>
                    </div>
                </div>
            `}
        </div>
        <div class="unread-msgs-count">+${conv.unread_count}</div>
        </div>
    `;
};

export const ConversationListView = ({ conversations, hasMore, onLoadMore, onCreateChat, onSearch, isForward, page, unreadMode, isLoadingMore }) => {
    const is_group = window.im.state.is_group;
    const total_convs = window.im.conversations ? Number(window.im.conversations.total_convs || conversations.length) : conversations.length;

    let rafId = null;
    const handleScroll = (e) => {
        if (rafId) return;
        const target = e.currentTarget || e.target;
        if (!target) return;
        rafId = requestAnimationFrame(() => {
            rafId = null;
            if (!hasMore || isLoadingMore || window.im?.conversations?.isLoadingMore) return;
            const remaining = target.scrollHeight - target.scrollTop - target.clientHeight;
            if (remaining <= 250) {
                if (typeof onLoadMore === 'function') {
                    onLoadMore();
                }
            }
        });
    };

    return html`
        <div id="conversations-top-buttons">
            ${!isForward ? html`
            <div id="conversations-search-bar">
                <input class="search_input cool" type="text" placeholder="${tr('search_messages')}" onChange=${onSearch} />
            </div>
            ${!is_group ? html`
                <input type="button" class="button excess" value="${tr('saved_messages')}" onClick=${() => {
                    const myId = window.openvk ? window.openvk.current_id : window.im?.state?.getId();
                    if (myId) {
                        window.im.messenger.selectConversationByPeerId(myId);
                    }
                }} />
                <input type="button" class="button" value="${tr('create_chat')}" onClick=${onCreateChat} />
            ` : ""}
            ` : html`
            <b>${tr("forward_messages_msg")}</b>
            <a>${tr("cancel")}</a>
            `}
        </div>
        <div class="crp-list" onScroll=${handleScroll} ref=${(el) => {
            if (!el) return;
            if (hasMore && !isLoadingMore && !window.im?.conversations?.isLoadingMore) {
                if (el.scrollHeight <= el.clientHeight) {
                    if (typeof onLoadMore === 'function') {
                        onLoadMore();
                    }
                }
            }
        }}>
            ${conversations.length > 0 ? conversations.map((conv) => html`<${ConversationItem} key=${conv.peer ? conv.peer.id : (conv.id || conv._conversation?.peer?.id)} conv=${conv} isForward=${isForward} page=${page} />`) : (!isLoadingMore ? html`<${ConversationsListError} unreadMode=${unreadMode} is_group=${is_group} />` : "")}
            ${(hasMore || isLoadingMore) && html`
            <div class="crp-lazy-loader ${isLoadingMore ? 'loading' : 'idle'}">
                ${isLoadingMore ? html`
                    <img src="/assets/packages/static/openvk/img/loading_mini.gif" alt="..." />
                ` : ""}
            </div>
            `}
        </div>
        <div class="crp-bottom">
            <div class="crp-bottom--count">
                ${total_convs > 0 ? tr("conversations_count_title", total_convs) : ""}
            </div>
            <div class="crp-bottom--actions">
                ${isForward ? html`
                    <a onClick=${() => { window.im.messenger.onConversationsClick(window.openvk.current_id, isForward, page); }}>${tr("saved_messages")}</a>
                ` : html`
                ${!unreadMode ? html`
                    <a onClick=${() => { window.im.conversations.toggleMode("unread") }}>${tr("conversations_show_unread")}</a> |<span> </span>
                ` : html`
                    <a onClick=${() => { window.im.conversations.toggleMode("all") }}>${tr("conversations_show_all")}</a> |<span> </span>
                `}
                <a onclick=${() => { window.im.openTabByName("settings") }}>${tr("messenger_tab_settings")}</a> |<span> </span>
                <a onClick=${(event) => { imSwitchCurrent(event) }}>${tr("messenger_switch_current")}</a>
                ${!is_group ? html` |<span> </span><a onClick=${() => { window.im.openTabByName("important") }}>${tr("important_messages")}</a>` : ""}
                `}
            </div>
        </div>
    `;
};

export const MessagesNewInterfaceBanner = ({ onClose }) => {
    return html`
        <div class="im-new-interface-banner">
            <div class="im-new-interface-banner--mascot">
                <img src="/assets/packages/static/openvk/img/im/im_new_banner.png?v=1" alt="" />
            </div>
            <div class="im-new-interface-banner--content">
                <div class="im-new-interface-banner--title">${tr('messages_new_interface_title')}</div>
                <div class="im-new-interface-banner--text">
                    ${tr('messages_new_interface_text')}
                </div>
                <div class="im-new-interface-banner--actions">
                    <button class="button im-new-interface-banner--close-btn" onClick=${onClose}>${tr('messages_new_interface_hide')}</button>
                </div>
            </div>
        </div>
    `;
};

export const TabBar = ({ tabs, activeTab, onTabSelect }) => {
    let activeTabName = "";

    try {
        activeTabName = activeTab ? activeTab.getPageId() : "";
    } catch (e) {
        console.error(e);
    }

    const curChat = window.im?.messenger?.getCurrentChat();
    const showContactButton = activeTabName == "messenger" && Boolean(curChat && curChat.peer);
    let contactText = tr('about_peer');
    try {
        if (showContactButton && curChat.peer.supposed_type == "chat") {
            contactText = tr("about_peer_chat");
        }
    } catch (e) {
        console.error(e);
    }
    const showFriendsButton = !window.im?.state?.is_group && activeTabName != "friends";
    const showSettingsButton = false;
    const showSpecActions = showSettingsButton || showContactButton || showFriendsButton;

    const showBanner = !window.im?.state?.isFastchat && activeTabName === "conversations" && localStorage.getItem("tw.im.hide_new_interface_banner") !== "1";

    const handleDismissBanner = (e) => {
        if (e && e.preventDefault) e.preventDefault();
        localStorage.setItem("tw.im.hide_new_interface_banner", "1");
        if (window.im && window.im.updateTabs) {
            window.im.updateTabs();
        }
    };

    const sortedTabs = (tabs || []).slice(0).sort((a, b) => {
        if (a.getPageId() === "important") return 1;
        if (b.getPageId() === "important") return -1;
        return 0;
    });

    return html`
        <div class="messenger-app--tabbar-wrap">
            ${showBanner ? html`<${MessagesNewInterfaceBanner} onClose=${handleDismissBanner} />` : ""}
            <div class="messenger-app--global-tabs tabs">
                <div class="inner-tabs">
                    ${sortedTabs.map((tab) => html`
                    <a data-tab="${tab.getId()}"
                        id="${tab.isActive() ? 'activetabs' : ''}"
                        class="tab ${tab.getPageId() === 'important' ? 'tab-important' : ''}"
                        onClick=${() => onTabSelect(tab)}>
                        ${tab.getName()}
                    </a>
                    `)}
                </div>
                <div class="${showSpecActions == false ? 'hidden' : ''}" id="spec-actions">
                    ${showContactButton ? html`
                        <a onclick=${() => { window.im.openTabByName("contact") }}>${contactText}</a>
                        <span class="tab-divider">|</span>
                    ` : ''}
                    ${showSettingsButton ? html`
                        <a onclick=${() => { window.im.openTabByName("settings") }}>${tr("messenger_tab_settings")}</a>
                        <span class="tab-divider">|</span> 
                    ` : ""}
                    ${showFriendsButton ? html`<a onclick=${() => { window.im.openTabByName("friends") }}>${tr('to_friendslist')}</a>` : ""}
                </div>
            </div>
        </div>
    `;
};

export const PeerWindow = ({ fromConvo, convo, togglePeerInfo }) => {
    let peer = convo?.peer || convo;
    if (typeof peer === 'number' || (peer && typeof peer.hasAvatar !== 'function')) {
        const peerId = typeof peer === 'number' ? peer : peer?.id;
        peer = window.im.cached_profiles?._findProfile(peerId) || window.im.state?.getCurrentConvo()?.peer || new ChatGeneralForm(typeof peer === 'object' && peer !== null ? peer : { id: peerId });
    }
    if (!peer) return null;

    const isChat = peer.supposed_type == "chat";
    const canEditTitle = isChat && (typeof peer.can !== 'function' || peer.can("update_title"));
    const supposed_type = peer.supposed_type;
    const isOnline = peer.online == 1;
    const avatar = peer.getAvatar ? peer.getAvatar("big") : "";
    const is_from_chat = fromConvo?.supposed_type == "chat" && peer.supposed_type != "chat";
    const is_club_related = peer.supposed_type == "club" || window.im.state.getOperator()?.supposed_type == "club";
    const members = peer.members ? peer.members.items : null;
    const currentUserId = window.openvk ? window.openvk.current_id : window.im.state.getId();
    const isChatAdmin = peer.isAdmin ? peer.isAdmin() : false;
    const membersCount = peer.members?.total_count || peer.data?.members_count || (members ? members.length : 0);

    const updateContactTab = () => {
        if (window.im.getTab("contact")?.render_class) {
            window.im.getTab("contact").render_class.update();
        }
    };

    const saveChatTitle = async () => {
        const newTitle = (peer._tempTitle ?? '').trim();
        peer._titleEditing = false;
        if (newTitle && newTitle !== (peer.getName ? peer.getName() : peer.name)) {
            await peer.updateTitle(newTitle);
        } else {
            updateContactTab();
        }
    };

    const cancelChatTitle = (e) => {
        if (e) e.preventDefault();
        peer._titleEditing = false;
        peer._tempTitle = null;
        updateContactTab();
    };

    return html`
    <div class="peer-window">
        <div class="peer-back" onClick=${(e) => { window.im.messenger.view.togglePeerInfo() }}>${tr("back")}</div>
        <div class="peer-side">
            <div class="peer-info">
                <div class="peer-avatar sliding-thing-wrapper ${!peer.hasAvatar() ? "no-avatar" : ""}">
                    <${PeerAvatar} saved_messages_ava=${false} peer=${peer} orig_ava=${true} size="big" />
                    ${peer.hasAvatar() ? html`
                    <a onClick=${(event) => { window.OpenChatAvatar ? window.OpenChatAvatar(event, peer) : null }} class="avatar-opener sliding-thing">
                        <div class="lupa"></div>
                    </a>
                    ` : ""}
                </div>
                <div class="peer-name">
                    <div class="peer-name-1">
                        ${isChat && peer._titleEditing ? html`
                            <div class="peer-title-edit-wrap">
                                <input
                                    type="text"
                                    class="peer-title-input"
                                    value=${peer._tempTitle ?? (peer.getName ? peer.getName() : (peer.name || ''))}
                                    onInput=${(e) => { peer._tempTitle = e.target.value; }}
                                    onKeyDown=${(e) => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    saveChatTitle();
                } else if (e.key === "Escape") {
                    e.preventDefault();
                    cancelChatTitle(e);
                }
            }}
                                />
                                <button class="button peer-title-save-btn" onClick=${saveChatTitle}>${tr("save")}</button>
                                <a class="peer-title-cancel-btn" onClick=${cancelChatTitle} title="${tr('cancel')}"><span class="chats-close-icon"></span></a>
                            </div>
                        ` : html`
                            <a
                                class="peer-link ${isChat && canEditTitle ? 'peer-title-editable' : ''}"
                                href=${!isChat && peer.getPageUrl ? peer.getPageUrl() : '#'}
                                title=${isChat && canEditTitle ? tr("change_chat_title") : ""}
                                onClick=${(e) => {
                e.preventDefault();
                if (isChat) {
                    if (canEditTitle) {
                        peer._titleEditing = true;
                        peer._tempTitle = peer.getName ? peer.getName() : (peer.name || '');
                        updateContactTab();
                        setTimeout(() => {
                            const input = document.querySelector(".peer-title-input");
                            if (input) {
                                input.focus();
                                input.select();
                            }
                        }, 50);
                    }
                } else {
                    const url = peer.getPageUrl ? peer.getPageUrl() : null;
                    if (url) {
                        if (window.router && typeof window.router.route === 'function') {
                            window.router.route(url);
                        } else {
                            location.href = url;
                        }
                    }
                }
            }}
                            >
                                ${peer.getName ? peer.getName() : (peer.name || '')}
                            </a>
                        `}

                        <div class="peer-status">
                            ${peer.supposed_type == "chat" ? html`
                                <span>${tr("members_count", membersCount)}</span>
                            ` : html`
                                <span dangerouslySetInnerHTML=${{ __html: peer.getOnlineStatusString ? peer.getOnlineStatusString() : '' }} />
                            `}
                        </div>
                    </div>

                    <div class="peer-actions-1">
                        <ul>
                            ${isChat && peer.can("update_avatar") ? html`
                                <li id="ava"><a onClick=${(e) => { window.updateChatAvatar ? window.updateChatAvatar(e, peer) : null }}>${tr("change_chat_avatar")}</a></li>
                            ` : ""}
                            ${isChat && (peer.can("promote_users") || peer.can("change_admins") || peer.isOwner()) ? html`
                                <li id="rights"><a onClick=${(e) => { e.preventDefault(); openChatPermissionsModal(peer); }}>${tr("chat_permissions_settings")}</a></li>
                            ` : ""}
                            <li id="notify_toggle"><a onClick=${(e) => { e.preventDefault(); openChatMuteModal(peer); }}>${peer && typeof peer.isMuted === 'function' && peer.isMuted() ? tr("chat_unmute_notifications") : tr("chat_mute_notifications")}</a></li>
                            <li id="search"><a onClick=${(e) => {
            window.im.openTabByName("search", true, {
                "q": "",
                "peer_id": peer.id,
                "referrer": window.im?.getSelectedTabId() || "contact"
            });
        }}>${tr("convo_search_messages")}</a></li>
                            <li id="dm_files"><a onClick=${(e) => {
            e.preventDefault();
            window.im.openTabByName("materials", true, { peer: peer, initialType: 'photo' });
        }}>${tr("conversation_materials")}</a></li>
                            ${convo && typeof convo.hasPinned === 'function' && convo.hasPinned() ? html`
                                <li id="pinned"><a onClick=${(e) => { window.im.messenger.viewPinned(e, convo); }}>${tr("chat_view_pinned_single")}</a></li>
                            ` : ""}
                            ${peer.can("return_to_chat") ? html`
                                <li id="return_to_chat"><a onClick=${async (e) => {
                e.preventDefault();
                try {
                    await window.OVKAPI.call("messages.addChatUser", {
                        "peer_id": peer.id,
                        "user_id": currentUserId
                    });
                    peer.data.left = 0;
                    peer.data.kicked = 0;
                    peer.data.chat_settings = peer.data.chat_settings || {};
                    peer.data.chat_settings.state = 'in';
                    peer.data.state = 'in';
                    peer.data.can_write = { allowed: true };

                    try {
                        const convById = await window.OVKAPI.call("messages.getConversationsById", { peer_ids: peer.id });
                        if (convById && convById.items && convById.items[0]?.conversation) {
                            const cConv = convById.items[0].conversation;
                            const s = cConv.chat_settings;
                            if (s) {
                                peer.data.photo_50 = s.photo_50 || s.photo?.photo_50 || "";
                                peer.data.photo_100 = s.photo_100 || s.photo?.photo_100 || "";
                                peer.data.photo_200 = s.photo_200 || s.photo?.photo_200 || "";
                                peer.data.avatar_max = s.avatar_max || "";
                                peer.data.photo_id = s.photo_id || null;
                            }
                        }
                    } catch (errConv) {
                        console.warn("Could not fetch updated conv on return_to_chat:", errConv);
                    }

                    const conv = window.im.conversations?._findConv(peer.id);
                    if (conv) {
                        if (conv._conversation) {
                            conv._conversation.can_write = { allowed: true };
                            if (conv._conversation.chat_settings) {
                                conv._conversation.chat_settings.state = 'in';
                            }
                        }
                        if (conv.peer) {
                            conv.peer.data.left = 0;
                            conv.peer.data.kicked = 0;
                            conv.peer.data.can_write = { allowed: true };
                            conv.peer.data.photo_50 = peer.data.photo_50;
                            conv.peer.data.photo_100 = peer.data.photo_100;
                            conv.peer.data.photo_200 = peer.data.photo_200;
                            conv.peer.data.avatar_max = peer.data.avatar_max;
                            conv.peer.data.photo_id = peer.data.photo_id;
                        }
                    }

                    if (window.im?.fastChats) {
                        const fc = window.im.fastChats.openedChats?.find(c => Number(c.peerId) === Number(peer.id));
                        if (fc) {
                            fc.canWrite = true;
                            fc.cantWriteReason = null;
                            fc.cantWriteText = null;
                            fc.photo = peer.getAvatar ? peer.getAvatar() : "";
                            window.im.fastChats.render();
                        }
                    }

                    window.im.openTabByName("messenger");
                    window.im.messenger.update();
                    if (window.im.conversations) {
                        window.im.conversations.update();
                    }
                } catch (err) {
                    fastError(String(err));
                }
            }}><b>${tr("return_to_chat")}</b></a></li>
                            ` : ""}
                            <li id="clean"><a onClick=${(e) => {
            e.preventDefault();
            new CMessageBox({
                title: tr("clear_history"),
                body: tr("clear_history_confirm"),
                buttons: [tr("yes"), tr("no")],
                callbacks: [async () => {
                    try {
                        await window.OVKAPI.call("messages.deleteConversation", {
                            peer_id: peer.id
                        });
                        if (peer._chunks) {
                            peer._chunks.chunks = [];
                            peer._chunks._map = new Map();
                            peer._chunks._messagesInited = true;
                            peer._chunks._invalidateCache();
                        }
                        if (convo) {
                            convo.last_message = null;
                            convo._last_message = null;
                        }
                        window.im.openTabByName("messenger");
                        window.im.messenger.update();
                        if (window.im.conversations) {
                            window.im.conversations.update();
                        }
                        if (window.im?.event_handler && typeof window.im.event_handler.updateGlobalUnreadCounter === 'function') {
                            window.im.event_handler.updateGlobalUnreadCounter();
                        }
                    } catch (err) {
                        fastError(String(err));
                    }
                }, () => { }]
            });
        }}>${tr("clear_history")}</a></li>
                            ${is_from_chat === true ? html`
                                <li id="kick_user" class="chat-actions-usr">
                                    <a><b>${tr("convo_action_kick")}</b></a>
                                </li>
                            ` : ""}
                            ${(is_club_related && peer.isClubMessagesBlocked()) ? html`
                                <li id="club_allow" class="chat-actions-usr" onClick="${async (e) => { await peer.toggleClubMessagesBlockness(e, "enable"); }}">
                                    <a>${tr("group_allow_messages")}</a>
                                </li>
                            ` : ""}
                            ${(is_club_related && !peer.isClubMessagesBlocked()) ? html`
                                <li id="club_deny" class="chat-actions-usr" onClick="${async (e) => { await peer.toggleClubMessagesBlockness(e, "disable"); }}">
                                    <a>${tr("group_deny_messages")}</a>
                                </li>
                            ` : ""}
                            ${window.im.state.is_debug ? html`
                                <li id="debug"><a onClick=${(e) => { fastError(`<textarea>${JSON.stringify(peer.data, null, 4)}</textarea>`); }}>JSON</a></li>
                            ` : ""}
                        </ul>
                    </div>
                </div>
            </div>
            <div class="peer-actions-container">
                <${PeerInviteLinkSection} peer=${peer} />

                ${peer.supposed_type == "chat" && !peer.isILeft() ? html`
                    <div class="peer-members-section">
                        <div class="chat-tab-2-header">
                            <b>${tr("participants")} (${membersCount})</b>
                            <div class="chat-header-actions">
                                ${peer.can("invite_new") ? html`
                                    <a onClick=${(e) => {
                    window.im.openTabByName("friends", true, {
                        "referrer": "add_new",
                        "convo_id": peer.id
                    })
                }}>${tr("chat_add_members_ext")}</a>
                                ` : ""}
                                ${peer.can("leave_chat") ? html`
                                    <a onClick=${(e) => {
                    e.preventDefault();
                    new CMessageBox({
                        title: tr("leave_chat"),
                        body: tr("leave_chat_confirm"),
                        buttons: [tr("yes"), tr("no")],
                        callbacks: [async () => {
                            try {
                                await window.OVKAPI.call("messages.removeChatUser", {
                                    "peer_id": peer.id,
                                    "user_id": currentUserId
                                });
                                peer.data.left = 1;
                                peer.data.kicked = 0;
                                peer.data.photo_50 = "";
                                peer.data.photo_100 = "";
                                peer.data.photo_200 = "";
                                peer.data.avatar_max = "";
                                peer.data.photo_id = null;
                                peer.data.chat_settings = peer.data.chat_settings || {};
                                peer.data.chat_settings.state = 'left';
                                peer.data.state = 'left';
                                peer.data.can_write = { allowed: false, reason: 916 };

                                const conv = window.im.conversations?._findConv(peer.id);
                                if (conv) {
                                    if (conv._conversation) {
                                        conv._conversation.can_write = { allowed: false, reason: 916 };
                                        if (conv._conversation.chat_settings) {
                                            conv._conversation.chat_settings.state = 'left';
                                        }
                                    }
                                    if (conv.peer) {
                                        conv.peer.data.left = 1;
                                        conv.peer.data.kicked = 0;
                                        conv.peer.data.can_write = { allowed: false, reason: 916 };
                                        conv.peer.data.photo_50 = "";
                                        conv.peer.data.photo_100 = "";
                                        conv.peer.data.photo_200 = "";
                                        conv.peer.data.avatar_max = "";
                                        conv.peer.data.photo_id = null;
                                    }
                                }

                                if (window.im?.fastChats) {
                                    const fc = window.im.fastChats.openedChats?.find(c => Number(c.peerId) === Number(peer.id));
                                    if (fc) {
                                        fc.canWrite = false;
                                        fc.cantWriteReason = 916;
                                        fc.cantWriteText = window.im.fastChats.getCantWriteText(fc);
                                        fc.photo = "";
                                        window.im.fastChats.render();
                                    }
                                }

                                window.im.openTabByName("messenger");
                                window.im.messenger.update();
                                if (window.im.conversations) {
                                    window.im.conversations.update();
                                }
                            } catch (err) {
                                fastError(String(err));
                            }
                        }, () => { }]
                    });
                }}>${tr("leave_chat")}</a>
                                ` : ""}
                            </div>
                        </div>
                        <div class="chat-members-list">
                            ${members && members.length > 0 ? members.map(item => {
                    const memberId = item.member_id || item.id;
                    const profile = item.profile || {};
                    const isClub = memberId < 0;
                    const memberObj = (profile && (profile.first_name || profile.name)) ? new ChatGeneralForm(profile) : null;
                    const name = memberObj ? memberObj.getName() : (profile.first_name ? `${profile.first_name} ${profile.last_name}`.trim() : (profile.name || `id${memberId}`));
                    const defaultAva = isClub ? "/assets/packages/static/openvk/img/community_100.png" : "/assets/packages/static/openvk/img/camera_200.png";
                    const avatarSrc = (memberObj && memberObj.getAvatar("mid")) || profile.photo_50 || profile.photo_100 || profile.photo_200 || defaultAva;
                    const profileUrl = isClub ? `/club${Math.abs(memberId)}` : `/id${memberId}`;
                    const isModerator = (item.is_moderator === true || item.is_moderator === 1 || (item.is_admin && !item.is_owner)) && !(item.is_owner === true || item.is_owner === 1);
                    const isOwner = (item.is_owner === true || item.is_owner === 1 || (peer.data?.admin_id == memberId && !isModerator)) && !isModerator;
                    const isAdmin = item.is_admin === true || item.is_admin === 1 || isOwner;
                    const isSelf = memberId == currentUserId;
                    const canKick = (item.can_kick !== undefined ? item.can_kick : (isChatAdmin && !isSelf && !isOwner));
                    const currentMember = (members || []).find(m => (m.member_id || m.id) == currentUserId);
                    const isCurrentOwner = (currentMember && (currentMember.is_owner === true || currentMember.is_owner === 1)) || (peer.data?.admin_id == currentUserId && (!currentMember || !currentMember.is_moderator)) || (peer.isOwner ? peer.isOwner() : false);
                    const canManageModerator = (peer.can("promote_users") || peer.can("change_admins") || isCurrentOwner) && !isSelf && !isOwner;

                    const toggleModerator = (e) => {
                        e.preventDefault();
                        const confirmBody = isModerator
                            ? tr("remove_moderator_confirm", escapeHtml(name))
                            : tr("set_moderator_confirm", escapeHtml(name));

                        new CMessageBox({
                            title: tr("confirmation"),
                            body: confirmBody,
                            buttons: [tr("yes"), tr("no")],
                            callbacks: [async () => {
                                try {
                                    try {
                                        await window.OVKAPI.call("messages.setMemberRole", {
                                            "peer_id": peer.id,
                                            "member_id": memberId,
                                            "role": isModerator ? "member" : "admin"
                                        });
                                    } catch (errSetRole) {
                                        const method = isModerator ? "messages.removeChatModerator" : "messages.setChatModerator";
                                        await window.OVKAPI.call(method, {
                                            "peer_id": peer.id,
                                            "user_id": memberId
                                        });
                                    }
                                    if (peer.members) {
                                        peer.members = null;
                                    }
                                    await peer.checkMembers();
                                    if (window.im.getTab("contact") && window.im.getTab("contact").render_class) {
                                        window.im.getTab("contact").render_class.update();
                                    }
                                } catch (err) {
                                    fastError(String(err));
                                }
                            }, () => { }]
                        });
                    };

                    let roleText = "";
                    if (isOwner) {
                        roleText = tr("chat_owner");
                    } else if (isModerator) {
                        roleText = tr("chat_moderator");
                    } else if (isAdmin) {
                        roleText = tr("chat_admin");
                    }

                    let onlineText = "";
                    if (memberObj && !isClub) {
                        onlineText = memberObj.getOnlineStatusString();
                    } else if (!isClub) {
                        if (profile.online == 1) {
                            onlineText = tr("online");
                        } else if (profile.last_seen && profile.last_seen.time) {
                            const d = new Date(profile.last_seen.time * 1000);
                            onlineText = d.toLocaleDateString();
                        }
                    }

                    return html`
                                    <div class="chat-member-item" key=${memberId}>
                                        <div class="inf">
                                            <a href=${profileUrl}>
                                                <img class="chat-member-ava" src=${avatarSrc} onError=${(e) => { e.target.onerror = null; e.target.src = defaultAva; }} alt="" />
                                            </a>
                                            <div class="chat-member-info">
                                                <a class="chat-member-name" href=${profileUrl}>${name}</a>
                                                ${roleText ? html`<span class="chat-member-badge">${roleText}</span>` : ""}
                                                ${onlineText ? html`<span class="chat-member-online">${onlineText}</span>` : ""}
                                            </div>
                                        </div>
                                        <div class="chat-member-actions">
                                            ${!isSelf && html`
                                                <a class="chat-member-action-btn" onClick=${async () => {
                                await window.im.messenger.selectConversationByPeerId(memberId);
                            }}>${tr("write_message")}</a>
                                            `}
                                            ${canManageModerator && html`
                                                <a class="chat-member-action-mod ${isModerator ? 'active' : ''}" title=${isModerator ? tr("remove_moderator") : tr("set_as_moderator")} onClick=${toggleModerator}>
                                                    <span class="chat-mod-star-icon"></span>
                                                </a>
                                            `}
                                            ${canKick && html`
                                                <a class="chat-member-action-kick" title=${tr("remove_from_chat")} onClick=${(e) => {
                                e.preventDefault();
                                new CMessageBox({
                                    title: tr("confirmation"),
                                    body: tr("kick_confirm", escapeHtml(name)),
                                    buttons: [tr("yes"), tr("no")],
                                    callbacks: [async () => {
                                        try {
                                            await window.OVKAPI.call("messages.removeChatUser", {
                                                "peer_id": peer.id,
                                                "user_id": memberId
                                            });
                                            if (Number(memberId) === Number(currentUserId)) {
                                                peer.data.kicked = 1;
                                                peer.data.chat_settings = peer.data.chat_settings || {};
                                                peer.data.chat_settings.state = 'kicked';
                                                peer.data.can_write = { allowed: false, reason: 915 };

                                                const conv = window.im.conversations?._findConv(peer.id);
                                                if (conv) {
                                                    if (conv._conversation) conv._conversation.can_write = { allowed: false, reason: 915 };
                                                    if (conv.peer) {
                                                        conv.peer.data.kicked = 1;
                                                        conv.peer.data.can_write = { allowed: false, reason: 915 };
                                                    }
                                                }

                                                if (window.im?.fastChats) {
                                                    const fc = window.im.fastChats.openedChats?.find(c => Number(c.peerId) === Number(peer.id));
                                                    if (fc) {
                                                        fc.canWrite = false;
                                                        fc.cantWriteReason = 915;
                                                        fc.cantWriteText = window.im.fastChats.getCantWriteText(fc);
                                                        window.im.fastChats.render();
                                                    }
                                                }

                                                window.im.openTabByName("messenger");
                                                window.im.messenger.update();
                                            }
                                            if (peer.members) {
                                                peer.members = null;
                                            }
                                            await peer.checkMembers();
                                            if (window.im.getTab("contact") && window.im.getTab("contact").render_class) {
                                                window.im.getTab("contact").render_class.update();
                                            }
                                        } catch (err) {
                                            fastError(String(err));
                                        }
                                    }, () => { }]
                                });
                            }}><span class="chats-close-icon"></span></a>
                                            `}
                                        </div>
                                    </div>
                                `;
                }) : (peer.members?.failed ? html`<div class="im-members-load-error">${tr("error")}</div>` : html`<div class="im-members-loading">${tr("loading")}</div>`)}
                        </div>
                    </div>
                ` : ""}
            </div>
        </div>
    </div>
    `;
};

export const PeerInfoView = ({ page, convo, togglePeerInfo }) => {
    const peer = convo.peer;

    return html`
        <div onClick=${(e) => { togglePeerInfo() }} class="messages--peers-header-peer-name">
            <img class="ava" src="${peer.getAvatar()}" />
            <span>${peer.getName()}</span>
        </div>
    `;
}

export const PeerInviteLinkSection = ({ peer }) => {
    if (!peer || peer.supposed_type !== 'chat' || peer.isILeft() || (typeof peer.can === 'function' && !peer.can("see_invite_link"))) return null;

    if (!peer._inviteLinkState) {
        peer._inviteLinkState = {
            link: null,
            isLoading: false
        };
    }

    const fetchInviteLink = async (reset = 0) => {
        peer._inviteLinkState.isLoading = true;
        if (window.im.getTab("contact")?.render_class) {
            window.im.getTab("contact").render_class.update();
        }

        try {
            const res = await window.OVKAPI.call("messages.getInviteLink", {
                peer_id: peer.id,
                reset: reset
            });
            peer._inviteLinkState.link = (res && res.link) ? res.link : (res && res.response ? res.response.link : null);
        } catch (e) {
            console.error("Failed to get invite link:", e);
        } finally {
            peer._inviteLinkState.isLoading = false;
            if (window.im.getTab("contact")?.render_class) {
                window.im.getTab("contact").render_class.update();
            }
        }
    };

    const copyInviteLink = () => {
        if (peer._inviteLinkState.link) {
            navigator.clipboard.writeText(peer._inviteLinkState.link).then(() => {
                fastErrortr("link_copied");
            }).catch(console.error);
        }
    };

    const canChangeInviteLink = typeof peer.can === 'function' ? peer.can("change_invite_link") : peer.isOwner?.();

    return html`
        <div class="peer-invite-section">
            <div class="chat-tab-2-header">
                <b>${tr("convo_invite_link")}</b>
            </div>
            <div class="peer-invite-body">
                ${peer._inviteLinkState.link ? html`
                    <div class="peer-invite-input-wrap">
                        <input type="text" readonly class="peer-invite-input" value="${peer._inviteLinkState.link}" onClick=${(e) => e.target.select()} />
                        <button class="button" onClick=${copyInviteLink}>${tr("copy")}</button>
                    </div>
                    ${canChangeInviteLink ? html`
                        <div class="peer-invite-reset">
                            <a onClick=${() => fetchInviteLink(1)}>${tr("reset_invite_link")}</a>
                        </div>
                    ` : ""}
                ` : html`
                    <button class="button" disabled=${peer._inviteLinkState.isLoading} onClick=${() => fetchInviteLink(0)}>
                        ${peer._inviteLinkState.isLoading ? tr("loading") : tr("get_invite_link")}
                    </button>
                `}
            </div>
        </div>
    `;
};

export const PeerAttachmentsSection = ({ peer }) => {
    if (!peer || !peer.id) return null;

    if (!peer._attState) {
        peer._attState = {
            type: 'photo',
            items: [],
            isLoading: false,
            hasLoaded: false
        };
    }

    const loadAttachments = async (type) => {
        peer._attState.type = type;
        peer._attState.isLoading = true;
        peer._attState.hasLoaded = true;
        if (window.im.getTab("contact")?.render_class) {
            window.im.getTab("contact").render_class.update();
        }

        try {
            const res = await window.OVKAPI.call('messages.getHistoryAttachments', {
                peer_id: peer.id,
                media_type: type,
                count: 30,
                extended: 1
            });
            peer._attState.items = (res && res.items) || [];
        } catch (e) {
            console.error("Failed to load attachments:", e);
            peer._attState.items = [];
        } finally {
            peer._attState.isLoading = false;
            if (window.im.getTab("contact")?.render_class) {
                window.im.getTab("contact").render_class.update();
            }
        }
    };

    if (!peer._attState.hasLoaded) {
        loadAttachments(peer._attState.type);
    }

    const currentType = peer._attState.type;
    const items = peer._attState.items || [];
    const isLoading = peer._attState.isLoading;
    const isGrid = currentType === 'photo' || currentType === 'video';

    return html`
        <div class="peer-attachments-section">
            <div class="chat-tab-2-header">
                <b>${tr("attachments")}</b>
                <div class="chat-header-actions">
                    <a class="peer-att-open-modal-btn" onClick=${(e) => {
            e.preventDefault();
            window.im.openTabByName("materials", true, { peer: peer, initialType: currentType });
        }}>${tr("open_all_materials")}</a>
                </div>
            </div>
            <div class="peer-att-tabs">
                <a class="peer-att-tab ${currentType === 'photo' ? 'active' : ''}" onClick=${() => loadAttachments('photo')}>${tr('photos')}</a>
                <a class="peer-att-tab ${currentType === 'video' ? 'active' : ''}" onClick=${() => loadAttachments('video')}>${tr('videos')}</a>
                <a class="peer-att-tab ${currentType === 'audio' ? 'active' : ''}" onClick=${() => loadAttachments('audio')}>${tr('audios')}</a>
                <a class="peer-att-tab ${currentType === 'doc' ? 'active' : ''}" onClick=${() => loadAttachments('doc')}>${tr('documents')}</a>
                <a class="peer-att-tab ${currentType === 'link' ? 'active' : ''}" onClick=${() => loadAttachments('link')}>${tr('links')}</a>
            </div>
            <div class="peer-att-content">
                ${isLoading ? html`
                    <!--div class="peer-att-loader"><img src="/assets/packages/static/openvk/img/loading_mini.gif" alt="..." /></div-->
                ` : (items.length === 0 ? html`
                    <div class="peer-att-empty">${tr('no_attachments')}</div>
                ` : (isGrid ? html`
                    <div class="peer-att-grid">
                        ${items.map(item => {
            const photo = item.attachment?.photo;
            const video = item.attachment?.video;
            if (photo) {
                const thumb = photo.sizes?.find(s => s.type === 'm' || s.type === 'x' || s.type === 's')?.url
                    || photo.sizes?.find(s => s.type === 'm' || s.type === 'x' || s.type === 's')?.src
                    || photo.sizes?.[0]?.url
                    || photo.sizes?.[0]?.src
                    || photo.photo_604
                    || photo.photo_130
                    || photo.photo_75
                    || photo.url
                    || photo.image_url
                    || '/assets/packages/static/openvk/img/camera_200.png';
                return html`
                                    <div class="peer-att-grid-item" onClick=${(e) => {
                        if (typeof PhotoViewer !== 'undefined') {
                            PhotoViewer.openById(e, `photo${photo.owner_id}_${photo.id}`);
                        }
                    }}>
                                        <img src="${thumb}" alt="" />
                                    </div>
                                `;
            }
            if (video) {
                const thumb = video.image?.[0]?.url || video.image?.[0]?.src || video.photo_320 || video.photo_130 || video.image_url || '/assets/packages/static/openvk/img/thumbnail_gone.jpg';
                const durStr = video.duration ? (Math.floor(video.duration / 60) + ':' + ('0' + (video.duration % 60)).slice(-2)) : '';
                return html`
                                    <div class="peer-att-grid-item video-item" onClick=${(e) => {
                        if (typeof VideoViewer !== 'undefined') {
                            VideoViewer.openById(`${video.owner_id}_${video.id}`, {}, e);
                        }
                    }}>
                                        <img src="${thumb}" alt="" onError=${(e) => { e.target.onerror = null; e.target.src = '/assets/packages/static/openvk/img/thumbnail_gone.jpg'; }} />
                                        ${durStr ? html`<span class="peer-att-video-dur">${durStr}</span>` : ''}
                                    </div>
                                `;
            }
            return null;
        })}
                    </div>
                ` : html`
                    <div class="peer-att-list">
                        ${items.map(item => {
            const audio = item.attachment?.audio;
            const doc = item.attachment?.doc;
            const link = item.attachment?.link;

            if (audio) {
                return html`
                                    <div class="peer-att-list-row audio-row">
                                        <div class="peer-att-icon audio-icon"></div>
                                        <div class="peer-att-list-meta">
                                            <span class="peer-att-author">${audio.artist || 'Неизвестный'}</span> — <span class="peer-att-title">${audio.title || 'Без названия'}</span>
                                        </div>
                                    </div>
                                `;
            }
            if (doc) {
                const sizeStr = doc.size ? (doc.size > 1048576 ? (doc.size / 1048576).toFixed(1) + ' МБ' : Math.round(doc.size / 1024) + ' КБ') : '';
                return html`
                                    <div class="peer-att-list-row doc-row">
                                        <div class="peer-att-icon doc-icon"></div>
                                        <div class="peer-att-list-meta">
                                            <a href="${doc.url}" target="_blank" class="peer-att-link-title">${doc.title || 'Документ'}</a>
                                            <span class="peer-att-sub">${sizeStr}</span>
                                        </div>
                                    </div>
                                `;
            }
            if (link) {
                return html`
                                    <div class="peer-att-list-row link-row">
                                        <div class="peer-att-icon link-icon"></div>
                                        <div class="peer-att-list-meta">
                                            <a href="${link.url}" target="_blank" class="peer-att-link-title">${link.title || link.url}</a>
                                            <span class="peer-att-sub">${link.description || link.url}</span>
                                        </div>
                                    </div>
                                `;
            }
            return null;
        })}
                    </div>
                `))}
            </div>
        </div>
    `;
};

export const ConversationsListError = ({ unreadMode, is_group }) => {
    let text = tr("zero_conversations_error");
    if (unreadMode) { text = tr("zero_unread_conversations_error"); }
    if (is_group) { text = tr("zero_conversations_error_club"); }

    return html`
        <div class="conversations_error_page">
            <span>${text}</span>
        </div>
    `
}

export const ErrorConversation = ({ }) => {
    return html`
        <div>
            <span>Select a convo</span>
        </div>
    `
}

export const TopicConversationChat = ({ chat_id }) => {
    return html`
    <div id="chat-topic">
        <span class="t1">${tr("topic_going_in_chat")}</span>
        <div class="chat-topic-preview">
            <img src="{$chat->getPhotoURL("miniscule")}" alt="chat" />
            <div>
                <b class="chat-topic-title">{$chat->getTitle()}</b>
                <span>сколько-то участников</span>
            </div>
        </div>
        <div class="t3">
            <input value="${tr("chat_join")}" class="button" type="button" onClick=${(event) => openChatTopic(event, chat_id)} />
        </div>
    </div>
    `;
}

export function openChatPermissionsModal(peer) {
    if (!peer || peer.supposed_type !== 'chat') return;

    const modalTitle = tr('chat_permissions_settings');
    const currentPerms = peer.getPermissions ? (peer.getPermissions() || {}) : (peer.data?.chat_settings?.permissions || {});

    const permsState = {
        invite: currentPerms.invite || 'all',
        change_info: currentPerms.change_info || 'admin',
        change_pin: currentPerms.change_pin || 'admin',
        use_mass_mentions: currentPerms.use_mass_mentions || 'all',
        see_invite_link: currentPerms.see_invite_link || 'admin',
        change_invite_link: currentPerms.change_invite_link || 'owner',
        change_admins: currentPerms.change_admins || 'owner',
    };

    const isOwner = peer.isOwner ? peer.isOwner() : false;
    const canChangeAdmins = isOwner || (peer.can && peer.can("change_admins"));

    const safeEsc = (s) => (typeof escapeHtml === 'function' ? escapeHtml(s) : String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'));

    const modal = new CMessageBox({
        title: modalTitle,
        body: `
            <div class="messagebox-content-header chat-permissions-desc">
                ${safeEsctr("chat_permissions_desc")}
            </div>
            <table class="flexible_table" cellspacing="7" cellpadding="0" border="0" width="100%" align="center">
                <tbody>
                    <tr>
                        <td width="55%" valign="top">
                            <span class="nobold">${safeEsctr("chat_perm_invite")}:</span>
                        </td>
                        <td>
                            <select id="_ovk_perm_invite" class="chat-perm-select">
                                <option value="all" ${permsState.invite === 'all' ? 'selected' : ''}>${safeEsctr("chat_perm_all")}</option>
                                <option value="admin" ${permsState.invite === 'admin' ? 'selected' : ''}>${safeEsctr("chat_perm_admin")}</option>
                                <option value="owner" ${permsState.invite === 'owner' ? 'selected' : ''}>${safeEsctr("chat_perm_owner")}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="55%" valign="top">
                            <span class="nobold">${safeEsctr("chat_perm_change_info")}:</span>
                        </td>
                        <td>
                            <select id="_ovk_perm_change_info" class="chat-perm-select">
                                <option value="all" ${permsState.change_info === 'all' ? 'selected' : ''}>${safeEsctr("chat_perm_all")}</option>
                                <option value="admin" ${permsState.change_info === 'admin' ? 'selected' : ''}>${safeEsctr("chat_perm_admin")}</option>
                                <option value="owner" ${permsState.change_info === 'owner' ? 'selected' : ''}>${safeEsctr("chat_perm_owner")}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="55%" valign="top">
                            <span class="nobold">${safeEsctr("chat_perm_change_pin")}:</span>
                        </td>
                        <td>
                            <select id="_ovk_perm_change_pin" class="chat-perm-select">
                                <option value="all" ${permsState.change_pin === 'all' ? 'selected' : ''}>${safeEsctr("chat_perm_all")}</option>
                                <option value="admin" ${permsState.change_pin === 'admin' ? 'selected' : ''}>${safeEsctr("chat_perm_admin")}</option>
                                <option value="owner" ${permsState.change_pin === 'owner' ? 'selected' : ''}>${safeEsctr("chat_perm_owner")}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="55%" valign="top">
                            <span class="nobold">${safeEsctr("chat_perm_use_mass_mentions")}:</span>
                        </td>
                        <td>
                            <select id="_ovk_perm_use_mass_mentions" class="chat-perm-select">
                                <option value="all" ${permsState.use_mass_mentions === 'all' ? 'selected' : ''}>${safeEsctr("chat_perm_all")}</option>
                                <option value="admin" ${permsState.use_mass_mentions === 'admin' ? 'selected' : ''}>${safeEsctr("chat_perm_admin")}</option>
                                <option value="owner" ${permsState.use_mass_mentions === 'owner' ? 'selected' : ''}>${safeEsctr("chat_perm_owner")}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="55%" valign="top">
                            <span class="nobold">${safeEsctr("chat_perm_see_invite_link")}:</span>
                        </td>
                        <td>
                            <select id="_ovk_perm_see_invite_link" class="chat-perm-select">
                                <option value="all" ${permsState.see_invite_link === 'all' ? 'selected' : ''}>${safeEsctr("chat_perm_all")}</option>
                                <option value="admin" ${permsState.see_invite_link === 'admin' ? 'selected' : ''}>${safeEsctr("chat_perm_admin")}</option>
                                <option value="owner" ${permsState.see_invite_link === 'owner' ? 'selected' : ''}>${safeEsctr("chat_perm_owner")}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="55%" valign="top">
                            <span class="nobold">${safeEsctr("chat_perm_change_invite_link")}:</span>
                        </td>
                        <td>
                            <select id="_ovk_perm_change_invite_link" class="chat-perm-select">
                                <option value="owner" ${permsState.change_invite_link === 'owner' ? 'selected' : ''}>${safeEsctr("chat_perm_owner")}</option>
                                <option value="admin" ${permsState.change_invite_link === 'admin' ? 'selected' : ''}>${safeEsctr("chat_perm_admin")}</option>
                                <option value="all" ${permsState.change_invite_link === 'all' ? 'selected' : ''}>${safeEsctr("chat_perm_all")}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="55%" valign="top">
                            <span class="nobold">${safeEsctr("chat_perm_change_admins")}:</span>
                        </td>
                        <td>
                            <select id="_ovk_perm_change_admins" class="chat-perm-select" ${!canChangeAdmins ? 'disabled' : ''}>
                                <option value="owner" ${permsState.change_admins === 'owner' ? 'selected' : ''}>${safeEsctr("chat_perm_owner")}</option>
                                <option value="admin" ${permsState.change_admins === 'admin' ? 'selected' : ''}>${safeEsctr("chat_perm_admin")}</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        `,
        buttons: [tr('save'), tr('cancel')],
        callbacks: [
            async () => {
                const node = modal.getNode();
                const container = node && node.nodes ? node.nodes[0] : document;
                const getVal = (id) => {
                    const el = container.querySelector('#' + id);
                    return el ? el.value : undefined;
                };

                const newPerms = {
                    invite: getVal('_ovk_perm_invite') || permsState.invite,
                    change_info: getVal('_ovk_perm_change_info') || permsState.change_info,
                    change_pin: getVal('_ovk_perm_change_pin') || permsState.change_pin,
                    use_mass_mentions: getVal('_ovk_perm_use_mass_mentions') || permsState.use_mass_mentions,
                    see_invite_link: getVal('_ovk_perm_see_invite_link') || permsState.see_invite_link,
                    change_invite_link: getVal('_ovk_perm_change_invite_link') || permsState.change_invite_link,
                    change_admins: getVal('_ovk_perm_change_admins') || permsState.change_admins,
                };

                try {
                    const res = await window.OVKAPI.call("messages.setChatPermissions", {
                        peer_id: peer.id,
                        ...newPerms
                    });

                    if (res) {
                        peer.data = peer.data || {};
                        peer.data.chat_settings = peer.data.chat_settings || {};
                        const resPerms = res.permissions || newPerms;
                        peer.data.permissions = resPerms;
                        peer.data.chat_settings.permissions = resPerms;
                        if (res.acl) {
                            peer.data.acl = res.acl;
                            peer.data.chat_settings.acl = res.acl;
                        }
                    }

                    if (peer.members) {
                        peer.members = null;
                    }
                    await peer.checkMembers();

                    if (window.im.getTab("contact")?.render_class) {
                        window.im.getTab("contact").render_class.update();
                    }
                    if (window.im.messenger) {
                        window.im.messenger.update();
                    }
                } catch (e) {
                    fastError(String(e));
                }
            },
            () => { }
        ]
    });

    const rootNode = modal.getNode();
    if (rootNode && rootNode.nodes && rootNode.nodes[0]) {
        rootNode.nodes[0].style.width = "480px";
    }

    return modal;
}

export function openChatMuteModal(peer) {
    if (!peer) return;

    const modalTitle = tr('chat_mute_title');
    const isMuted = peer.isMuted ? peer.isMuted() : false;
    const safeEsc = (s) => (typeof escapeHtml === 'function' ? escapeHtml(s) : String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'));

    const modal = new CMessageBox({
        title: modalTitle,
        body: `
            <table class="flexible_table" cellspacing="7" cellpadding="0" border="0" width="100%" align="center">
                <tbody>
                    <tr>
                        <td width="40%" valign="top">
                            <span class="nobold">${safeEsctr("chat_mute_notifications")}:</span>
                        </td>
                        <td>
                            <select id="_ovk_mute_duration" class="chat-perm-select">
                                ${isMuted ? `<option value="0">${safeEsctr("chat_unmute_notifications")}</option>` : ''}
                                <option value="3600">${safeEsctr("chat_mute_1_hour")}</option>
                                <option value="28800">${safeEsctr("chat_mute_8_hours")}</option>
                                <option value="-1">${safeEsctr("chat_mute_forever")}</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        `,
        buttons: [tr('save'), tr('cancel')],
        callbacks: [
            async () => {
                const node = modal.getNode();
                const container = node && node.nodes ? node.nodes[0] : document;
                const durEl = container.querySelector('#_ovk_mute_duration');
                const duration = durEl ? parseInt(durEl.value, 10) : 0;

                try {
                    await window.OVKAPI.call("account.setSilenceMode", {
                        peer_id: peer.id,
                        time: duration,
                        sound: duration === 0 ? 1 : 0
                    });

                    peer.data = peer.data || {};
                    peer.data.push_settings = peer.data.push_settings || {};
                    if (duration === 0) {
                        peer.data.push_settings.disabled_until = 0;
                        peer.data.push_settings.sound = 1;
                    } else if (duration === -1) {
                        peer.data.push_settings.disabled_until = -1;
                        peer.data.push_settings.sound = 0;
                    } else {
                        peer.data.push_settings.disabled_until = Math.floor(Date.now() / 1000) + duration;
                        peer.data.push_settings.sound = 0;
                    }

                    if (window.im.getTab("contact")?.render_class) {
                        window.im.getTab("contact").render_class.update();
                    }
                    if (window.im.messenger) {
                        window.im.messenger.update();
                    }
                } catch (e) {
                    fastError(String(e));
                }
            },
            () => { }
        ]
    });

    const rootNode = modal.getNode();
    if (rootNode && rootNode.nodes && rootNode.nodes[0]) {
        rootNode.nodes[0].style.width = "400px";
    }

    return modal;
}

if (typeof window !== 'undefined') {
    window.openChatPermissionsModal = openChatPermissionsModal;
    window.openChatMuteModal = openChatMuteModal;
}
