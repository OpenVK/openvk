import { WriteBar } from './common.js';
import { html, render } from './render.js';

function isSelected(msg) {
    const view = window.im?.messenger;

    return view ? view.isMessageSelected(msg) : false;
}

function hideHead(msg, index, chunk) {
    return index > 0 && chunk.messages[index - 1].doHideHead(msg);
}

export const MessageBubble = ({ msg, index, chunk, page, fromSearch }) => {
    const isSearchTpl = fromSearch == "1";
    const isDeleted = msg.isDeleted();
    const cls = [
        'messenger-app--messages---message',
        'messenger-layer',
        isSelected(msg) ? 'msg-selected' : '',
        hideHead(msg, index, chunk) ? 'same-author' : '',
        isDeleted ? 'msg-deleted' : '',
        msg.isError() ? 'msg-error' : '',
        msg.isSending() ? 'msg-sending' : '',
        msg.isEdited() ? 'msg-edited' : '',
        msg.isReply() ? 'msg-reply' : '',
        msg.isPinned() ? 'msg-pinned' : '',
        (isSearchTpl) ? 'msg-searched' : (msg.isError() ? "msg-error-hoverable" : 'msg-hoverable'),
        msg.isRead() ? 'msg-read' : 'unread',
    ].filter(Boolean).join(' ');

    const has_postfix = msg.isSpecial("gift");
    if (msg.isSpecial() && !isSearchTpl) {
        const act = typeof msg.data?.action === "string"
            ? msg.data.action
            : (msg.data?.action?.type || msg.data?.action_type || msg.action?.type || msg.action || "unknown");
        const typ = SystemMessages[act] ?? SystemMessages["unknown"];

        try {
            return typ(msg, page);
        } catch (e) {
            console.error("Failed to render special message", e, msg);
            return SystemMessages["unknown"](msg, page);
        }
    }

    const peerId = msg.peer_id || msg.data?.peer_id || (page?.convo?.peer?.id) || (page?.convo?.id) || (window.im?.messenger?.currentChatId) || 0;
    const msgAnchorId = `msg${peerId}-${msg.id}`;

    const isReply = !isDeleted && (msg.isReply() || Boolean(msg.data?.reply_message) || Boolean(msg.data?.reply_to));
    let replyAuthorName = "...";
    const rep = msg.data?.reply_message || (msg.data?.reply_to ? { id: msg.data.reply_to } : null);
    if (rep) {
        if (rep.sender && typeof rep.sender.getName === "function") {
            replyAuthorName = rep.sender.getName();
        } else if (rep.data?.sender && typeof rep.data.sender.getName === "function") {
            replyAuthorName = rep.data.sender.getName();
        } else {
            const fid = rep.from_id || rep.data?.from_id;
            if (fid) {
                const cached = window.im?.cached_profiles?._findCachedProfileByIdEvenIfNotCached(fid);
                replyAuthorName = cached && typeof cached.getName === "function" ? cached.getName() : "id" + fid;
            }
        }
    }

    return html`
    <div class="${cls}"
        id=${msgAnchorId}
        data-msg-id=${msg.id}
        onMouseDown=${(e) => {
            !isSearchTpl ? window.im?.messenger?.view.onMessageClick(msg, e) : null
        }}
        onClick=${(e) => {
            isSearchTpl ? window.im.messenger.goToMessage(msg) : null
        }}>
        <div class="messenger-app--messages---message--wrap">
            <div class="inlines click-territory">
                <div class="checkmark"></div>
            </div>
            ${!isDeleted && html`
            <div class="actions-2">
                <div onClick=${async (e) => {
                    e.stopPropagation();
                    const isImp = Boolean(msg.data?.important || (msg.data?.flags & 8));
                    try {
                        await window.OVKAPI.call("messages.markAsImportant", {
                            message_ids: msg.id,
                            important: isImp ? 0 : 1
                        });
                        if (!msg.data) msg.data = {};
                        msg.data.important = isImp ? 0 : 1;
                        if (isImp) {
                            msg.data.flags = (msg.data.flags || 0) & ~8;
                        } else {
                            msg.data.flags = (msg.data.flags || 0) | 8;
                        }
                        if (window.im?.messenger?.view) {
                            window.im.messenger.view.update();
                        }
                    } catch (err) {
                        console.error(err);
                    }
                }} class="star-icon ${(msg.data?.important || (msg.data?.flags & 8)) ? 'active' : ''}" title="${(msg.data?.important || (msg.data?.flags & 8)) ? (tr('unmark_important') || 'Снять отметку важного') : (tr('mark_important') || 'Пометить как важное')}"></div>
                ${msg.can("viewers") && html`
                    <div onClick=${(e) => { window.im.messenger.view.onViewersButtonClick(e, msg) }} class="viewers-icon" title="${tr('message_viewers') || 'Кто прочитал'}"></div>
                `}
                ${msg.can("edit") && html`
                    <div onClick=${(e) => { window.im.messenger.view.onEditButtonClick(e, msg) }} class="edit-icon"></div>
                `}
                ${msg.can("pin") && html`
                    <div onClick=${(e) => { window.im.messenger.view.onPinButtonClick(e, msg) }} class="pin-icon"></div>
                `}
                ${msg.can("report") && html`
                    <div onClick=${(e) => { window.im.messenger.view.onReportButtonClick(e, msg) }} class="report-icon"></div>
                `}
            </div>
            `}
            <div class="inlines _avatar">
                <img class="ava" src=${msg.sender?.getAvatar ? msg.sender.getAvatar() : "/assets/packages/static/openvk/img/camera_100.png"} alt=${msg.sender?.getName ? msg.sender.getName() : ""} />
            </div>
            <div class="inlines _content">
                ${isReply && html`
                    <div class="reply-msg" onClick=${(e) => { e.stopPropagation(); if (!isSearchTpl && window.im?.messenger) { window.im.messenger.goToMessage(msg.data?.reply_message || { id: msg.data?.reply_to, peer_id: peerId }); } }}>
                        <span>${typeof tr === "function" ? tr("reply_to_msg") : "В ответ на"}</span>
                        <a class="reply-author">${replyAuthorName}</a>
                        <span dangerouslySetInnerHTML=${{ __html: msg.data?.reply_message?.getText ? msg.data.reply_message.getText(false, true) : (msg.data?.reply_message?.data?.text || msg.data?.reply_message?.text || '...') }} />
                    </div>
                `}
                <a class="_sender" onClick=${(e) => { window.im?.messenger?.view?.onAuthorNameClick(msg, e) }}>
                    <strong>${msg.sender?.getName ? msg.sender.getName(false, true) : (msg.data?.from_id ? "id" + msg.data.from_id : "...")}</strong>
                </a>
                ${has_postfix ? html`
                <div class="msg-postfix">
                ${msg.isSpecial("gift") ? html`
                    ${tr("msg_sent_gift_" + (msg.sender?.getGender ? msg.sender.getGender() : "neutral")).toLowerCase()}:
                ` : ""}
                </div>` : ""}
                <div class="time" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>
                    ${msg.id != null && html`
                    <span>${isSearchTpl ? msg.getDate(2) + " " + msg.getDate(0) : msg.getDate(0)}</span>
                    `}
                </div>
                ${isDeleted ? html`
                    <p class="normalText text msg-deleted-content">
                        <span class="msg-deleted-label">${tr('message_is_deleted')}</span>
                        <a class="msg-restore-btn" onClick=${(e) => { window.im.messenger.view.onRestoreMessageClick(msg, e) }}>${tr('restore') || 'Восстановить'}</a>
                    </p>
                ` : html`
                    <p dangerouslySetInnerHTML=${{ __html: msg.getText(false) }} class="normalText text" />
                `}
                <p class="msg-modificators">
                    <p class="modificator-sending">${tr('send_action_progress')}</p>
                    <p class="modificator-edited">${tr('edit_action_past')}</p>
                    <p class="modificator-pinned">${tr('pinned_action_past')}</p>
                </p>
                ${!isDeleted && msg.getAttachments() && msg.getAttachments().length > 0 && html`
                    <div class="attachments">
                    ${msg.getAttachments().map((att) => html`<${Attachment} msg=${msg} att=${att} />`)}
                    </div>
                `}
                ${!isDeleted && msg.getFwdMessages() && msg.getFwdMessages().length > 0 && html`
                    <div class="fwd-messages-container">
                        <div class="fwd-messages-count">${tr("forwarded_messages_noun", msg.getFwdMessages().length)}</div>
                        ${msg.getFwdMessages().map((fwd) => {
                            const fwdSender = fwd.sender || (window.im?.cached_profiles && window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(fwd.from_id || fwd.data?.from_id));
                            const fwdName = fwdSender?.getName ? fwdSender.getName() : (fwd.from_id ? "id" + fwd.from_id : "...");
                            const fwdAva = fwdSender?.getAvatar ? fwdSender.getAvatar("mid", false) : "/assets/packages/static/openvk/img/camera_50.png";
                            return html`
                                <div class="fwd-message-block">
                                    <div class="fwd-message-head">
                                        <a href="/id${fwd.from_id || fwd.data?.from_id}" target="_blank" class="fwd-avatar-link">
                                            <img class="fwd-avatar" src=${fwdAva} alt=${fwdName} />
                                        </a>
                                        <div class="fwd-author-info">
                                            <a class="fwd-author-name" href="/id${fwd.from_id || fwd.data?.from_id}" target="_blank">
                                                <strong>${fwdName}</strong>
                                            </a>
                                            <span class="fwd-date">${typeof fwd.getDate === "function" ? fwd.getDate(0) : ""}</span>
                                        </div>
                                    </div>
                                    <div class="fwd-text" dangerouslySetInnerHTML=${{ __html: typeof fwd.getText === "function" ? fwd.getText(false) : (fwd.data?.text || fwd.text || "") }} />
                                    ${fwd.getAttachments && fwd.getAttachments().length > 0 && html`
                                        <div class="attachments">
                                            ${fwd.getAttachments().map((att) => html`<${Attachment} msg=${fwd} att=${att} />`)}
                                        </div>
                                    `}
                                </div>
                            `;
                        })}
                    </div>
                `}
                ${!isDeleted && msg.has_not_loaded_attachments == true && html`
                    <img src=${_loader_link} />
                `}
            </div>
        </div>
    </div>
  `;
};

export const SystemMessages = {
    "chat_create": (msg, page) => {
        const peerId = msg.peer_id || msg.data?.peer_id || (page?.convo?.peer?.id) || (page?.convo?.id) || (window.im?.messenger?.currentChatId) || 0;
        const msgAnchorId = `msg${peerId}-${msg.id}`;
        const sender = msg.sender;
        const senderName = sender?.getName ? sender.getName() : (msg.data?.from_id ? "id" + msg.data.from_id : "...");
        const gender = sender && typeof sender.getGender === "function" ? sender.getGender() : "neutral";
        const chat_title = (msg.data?.action?.text || msg.action?.text || "").trim();
        let text = "";
        if (chat_title && chat_title !== "undefined") {
            text = tr("event_chat_creation_" + gender, chat_title);
        } else {
            text = tr("event_chat_creation_no_title_" + gender) || tr("event_chat_creation_" + gender) || tr("event_chat_create_impersonal");
        }
        return html`
            <div class="messenger-special-message" id=${msgAnchorId} data-msg-id=${msg.id}>
                <div>
                    <a class="_sender" onClick=${(e) => { page?.onAuthorNameClick ? page.onAuthorNameClick(msg, e) : null }}>
                        <strong>${senderName} </strong>
                    </a>
                    <span class="text">${text.toLowerCase()}</span>
                    <span class="date-mini" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>${msg.getDate(0)}</span>
                </div>
            </div>
        `;
    },
    "chat_pin_message": (msg, page) => {
        const peerId = msg.peer_id || msg.data?.peer_id || (page?.convo?.peer?.id) || (page?.convo?.id) || (window.im?.messenger?.currentChatId) || 0;
        const msgAnchorId = `msg${peerId}-${msg.id}`;
        const sender = msg.sender;
        const senderName = sender?.getName ? sender.getName() : (msg.data?.from_id ? "id" + msg.data.from_id : "...");
        const gender = sender && typeof sender.getGender === "function" ? sender.getGender() : "neutral";
        const text = tr("event_chat_pin_message_" + gender) || tr("event_chat_pin_message_impersonal") || "закрепил сообщение";
        return html`
            <div class="messenger-special-message" id=${msgAnchorId} data-msg-id=${msg.id}>
                <div>
                    <a class="_sender" onClick=${(e) => { page?.onAuthorNameClick ? page.onAuthorNameClick(msg, e) : null }}>
                        <strong>${senderName} </strong>
                    </a>
                    <span class="text">${text.toLowerCase()}</span>
                    <span class="date-mini" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>${msg.getDate(0)}</span>
                </div>
            </div>
        `;
    },
    "chat_unpin_message": (msg, page) => {
        const peerId = msg.peer_id || msg.data?.peer_id || (page?.convo?.peer?.id) || (page?.convo?.id) || (window.im?.messenger?.currentChatId) || 0;
        const msgAnchorId = `msg${peerId}-${msg.id}`;
        const sender = msg.sender;
        const senderName = sender?.getName ? sender.getName() : (msg.data?.from_id ? "id" + msg.data.from_id : "...");
        const gender = sender && typeof sender.getGender === "function" ? sender.getGender() : "neutral";
        const text = tr("event_chat_unpin_message_" + gender) || tr("event_chat_unpin_message_impersonal") || "открепил сообщение";
        return html`
            <div class="messenger-special-message" id=${msgAnchorId} data-msg-id=${msg.id}>
                <div>
                    <a class="_sender" onClick=${(e) => { page?.onAuthorNameClick ? page.onAuthorNameClick(msg, e) : null }}>
                        <strong>${senderName} </strong>
                    </a>
                    <span class="text">${text.toLowerCase()}</span>
                    <span class="date-mini" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>${msg.getDate(0)}</span>
                </div>
            </div>
        `;
    },
    "chat_title_update": (msg, page) => {
        const peerId = msg.peer_id || msg.data?.peer_id || (page?.convo?.peer?.id) || (page?.convo?.id) || (window.im?.messenger?.currentChatId) || 0;
        const msgAnchorId = `msg${peerId}-${msg.id}`;
        const sender = msg.sender;
        const senderName = sender?.getName ? sender.getName() : (msg.data?.from_id ? "id" + msg.data.from_id : "...");
        const gender = sender && typeof sender.getGender === "function" ? sender.getGender() : "neutral";
        const title = (msg.data?.action?.text || msg.action?.text || "").trim();
        const text = tr("event_chat_title_update_" + gender, title) || `изменил название беседы на «${title}»`;
        return html`
            <div class="messenger-special-message" id=${msgAnchorId} data-msg-id=${msg.id}>
                <div>
                    <a class="_sender" onClick=${(e) => { page?.onAuthorNameClick ? page.onAuthorNameClick(msg, e) : null }}>
                        <strong>${senderName} </strong>
                    </a>
                    <span class="text">${text.toLowerCase()}</span>
                    <span class="date-mini" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>${msg.getDate(0)}</span>
                </div>
            </div>
        `;
    },
    "chat_photo_update": (msg, page) => {
        const peerId = msg.peer_id || msg.data?.peer_id || (page?.convo?.peer?.id) || (page?.convo?.id) || (window.im?.messenger?.currentChatId) || 0;
        const msgAnchorId = `msg${peerId}-${msg.id}`;
        const sender = msg.sender;
        const senderName = sender?.getName ? sender.getName() : (msg.data?.from_id ? "id" + msg.data.from_id : "...");
        const gender = sender && typeof sender.getGender === "function" ? sender.getGender() : "neutral";
        const text = tr("event_chat_photo_update_" + gender) || "обновил фотографию беседы";
        return html`
            <div class="messenger-special-message" id=${msgAnchorId} data-msg-id=${msg.id}>
                <div>
                    <a class="_sender" onClick=${(e) => { page?.onAuthorNameClick ? page.onAuthorNameClick(msg, e) : null }}>
                        <strong>${senderName} </strong>
                    </a>
                    <span class="text">${text.toLowerCase()}</span>
                    <span class="date-mini" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>${msg.getDate(0)}</span>
                </div>
            </div>
        `;
    },
    "chat_photo_remove": (msg, page) => {
        const peerId = msg.peer_id || msg.data?.peer_id || (page?.convo?.peer?.id) || (page?.convo?.id) || (window.im?.messenger?.currentChatId) || 0;
        const msgAnchorId = `msg${peerId}-${msg.id}`;
        const sender = msg.sender;
        const senderName = sender?.getName ? sender.getName() : (msg.data?.from_id ? "id" + msg.data.from_id : "...");
        const gender = sender && typeof sender.getGender === "function" ? sender.getGender() : "neutral";
        const text = tr("event_chat_photo_remove_" + gender) || "удалил фотографию беседы";
        return html`
            <div class="messenger-special-message" id=${msgAnchorId} data-msg-id=${msg.id}>
                <div>
                    <a class="_sender" onClick=${(e) => { page?.onAuthorNameClick ? page.onAuthorNameClick(msg, e) : null }}>
                        <strong>${senderName} </strong>
                    </a>
                    <span class="text">${text.toLowerCase()}</span>
                    <span class="date-mini" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>${msg.getDate(0)}</span>
                </div>
            </div>
        `;
    },
    "chat_invite_user": (msg, page) => {
        const peerId = msg.peer_id || msg.data?.peer_id || (page?.convo?.peer?.id) || (page?.convo?.id) || (window.im?.messenger?.currentChatId) || 0;
        const msgAnchorId = `msg${peerId}-${msg.id}`;
        const sender = msg.sender;
        const senderName = sender?.getName ? sender.getName() : (msg.data?.from_id ? "id" + msg.data.from_id : "...");
        const gender = sender && typeof sender.getGender === "function" ? sender.getGender() : "neutral";
        const mid = msg.data?.action?.member_id ?? msg.data?.action_mid;
        if (sender && mid == sender.id) {
            const text = tr("event_chat_invite_user_self_" + gender) || "вернулся в беседу";
            return html`
                <div class="messenger-special-message" id=${msgAnchorId} data-msg-id=${msg.id}>
                    <div>
                        <a class="_sender" onClick=${(e) => { page?.onAuthorNameClick ? page.onAuthorNameClick(msg, e) : null }}>
                            <strong>${senderName} </strong>
                        </a>
                        <span class="text">${text.toLowerCase()}</span>
                        <span class="date-mini" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>${msg.getDate(0)}</span>
                    </div>
                </div>
            `;
        }

        const targetProf = window.im?.cached_profiles?._findCachedProfileByIdEvenIfNotCached ? window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(mid) : window.im?.cached_profiles?._findCachedProfileById(mid);
        const targetName = targetProf?.getName ? targetProf.getName() : (mid ? `id${mid}` : "...");
        const verb = tr("event_chat_invite_user_verb_" + gender) || (gender === "female" ? "пригласила" : gender === "neutral" ? "пригласили" : "пригласил");

        return html`
            <div class="messenger-special-message" id=${msgAnchorId} data-msg-id=${msg.id}>
                <div>
                    <a class="_sender" onClick=${(e) => { page?.onAuthorNameClick ? page.onAuthorNameClick(msg, e) : null }}>
                        <strong>${senderName} </strong>
                    </a>
                    <span class="text">${verb} </span>
                    <a class="_sender" href="/id${mid}" target="_blank">
                        <strong>${targetName}</strong>
                    </a>
                    <span class="date-mini" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>${msg.getDate(0)}</span>
                </div>
            </div>
        `;
    },
    "chat_invite_user_by_link": (msg, page) => {
        const peerId = msg.peer_id || msg.data?.peer_id || (page?.convo?.peer?.id) || (page?.convo?.id) || (window.im?.messenger?.currentChatId) || 0;
        const msgAnchorId = `msg${peerId}-${msg.id}`;
        const sender = msg.sender;
        const senderName = sender?.getName ? sender.getName() : (msg.data?.from_id ? "id" + msg.data.from_id : "...");
        const gender = sender && typeof sender.getGender === "function" ? sender.getGender() : "neutral";
        const text = tr("event_chat_invite_user_by_link_" + gender) || "присоединился к беседе по ссылке";
        return html`
            <div class="messenger-special-message" id=${msgAnchorId} data-msg-id=${msg.id}>
                <div>
                    <a class="_sender" onClick=${(e) => { page?.onAuthorNameClick ? page.onAuthorNameClick(msg, e) : null }}>
                        <strong>${senderName} </strong>
                    </a>
                    <span class="text">${text.toLowerCase()}</span>
                    <span class="date-mini" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>${msg.getDate(0)}</span>
                </div>
            </div>
        `;
    },
    "chat_kick_user": (msg, page) => {
        const peerId = msg.peer_id || msg.data?.peer_id || (page?.convo?.peer?.id) || (page?.convo?.id) || (window.im?.messenger?.currentChatId) || 0;
        const msgAnchorId = `msg${peerId}-${msg.id}`;
        const sender = msg.sender;
        const senderName = sender?.getName ? sender.getName() : (msg.data?.from_id ? "id" + msg.data.from_id : "...");
        const gender = sender && typeof sender.getGender === "function" ? sender.getGender() : "neutral";
        const mid = msg.data?.action?.member_id ?? msg.data?.action_mid;
        if (sender && mid == sender.id) {
            const text = tr("event_chat_kick_user_self_" + gender) || "покинул беседу";
            return html`
                <div class="messenger-special-message" id=${msgAnchorId} data-msg-id=${msg.id}>
                    <div>
                        <a class="_sender" onClick=${(e) => { page?.onAuthorNameClick ? page.onAuthorNameClick(msg, e) : null }}>
                            <strong>${senderName} </strong>
                        </a>
                        <span class="text">${text.toLowerCase()}</span>
                        <span class="date-mini" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>${msg.getDate(0)}</span>
                    </div>
                </div>
            `;
        }

        const targetProf = window.im?.cached_profiles?._findCachedProfileByIdEvenIfNotCached ? window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(mid) : window.im?.cached_profiles?._findCachedProfileById(mid);
        const targetName = targetProf?.getName ? targetProf.getName() : (mid ? `id${mid}` : "...");
        const verb = tr("event_chat_kick_user_verb_" + gender) || (gender === "female" ? "исключила" : gender === "neutral" ? "исключили" : "исключил");

        return html`
            <div class="messenger-special-message" id=${msgAnchorId} data-msg-id=${msg.id}>
                <div>
                    <a class="_sender" onClick=${(e) => { page?.onAuthorNameClick ? page.onAuthorNameClick(msg, e) : null }}>
                        <strong>${senderName} </strong>
                    </a>
                    <span class="text">${verb} </span>
                    <a class="_sender" href="/id${mid}" target="_blank">
                        <strong>${targetName}</strong>
                    </a>
                    <span class="date-mini" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>${msg.getDate(0)}</span>
                </div>
            </div>
        `;
    },
    "rating_up": (msg, page) => {
        const peerId = msg.peer_id || msg.data?.peer_id || (page?.convo?.peer?.id) || (page?.convo?.id) || (window.im?.messenger?.currentChatId) || 0;
        const msgAnchorId = `msg${peerId}-${msg.id}`;
        const sender = msg.sender;
        const senderName = sender?.getName ? sender.getName() : (msg.data?.from_id ? "id" + msg.data.from_id : "...");
        const gender = sender && typeof sender.getGender === "function" ? sender.getGender() : "neutral";
        return html`
            <div class="messenger-special-message centred" id=${msgAnchorId} data-msg-id=${msg.id}>
                <div>
                    <b>${tr("event_chat_user_up_your_rating_" + gender, senderName, msg.data?.action?.member_id)}</b>
                    <span class="date-mini" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>${msg.getDate(0)}</span>
                    <p>«${escapeHtml(msg.data?.action?.text || "")}»</p>
                </div>
            </div>
        `;
    },
    "coins_transfer": (msg, page) => {
        const peerId = msg.peer_id || msg.data?.peer_id || (page?.convo?.peer?.id) || (page?.convo?.id) || (window.im?.messenger?.currentChatId) || 0;
        const msgAnchorId = `msg${peerId}-${msg.id}`;
        const sender = msg.sender;
        const senderName = sender?.getName ? sender.getName() : (msg.data?.from_id ? "id" + msg.data.from_id : "...");
        const gender = sender && typeof sender.getGender === "function" ? sender.getGender() : "neutral";
        return html`
            <div class="messenger-special-message centred" id=${msgAnchorId} data-msg-id=${msg.id}>
                <div>
                    <b>${tr("event_chat_user_added_voices_" + gender, senderName, msg.data?.action?.member_id)}</b>
                    <span class="date-mini" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>${msg.getDate(0)}</span>
                    <p>«${escapeHtml(msg.data?.action?.text || "")}»</p>
                </div>
            </div>
        `;
    },
    "unknown": (msg, page) => {
        const peerId = msg.peer_id || msg.data?.peer_id || (page?.convo?.peer?.id) || (page?.convo?.id) || (window.im?.messenger?.currentChatId) || 0;
        const msgAnchorId = `msg${peerId}-${msg.id}`;
        return html`
            <div class="messenger-special-message" id=${msgAnchorId} data-msg-id=${msg.id}>
                <div class="messenger-app--messages---message--wrap">
                    <div class="_content">
                        <span class="text">${msg.getText()}</span>
                    </div>
                </div>
            </div>
        `;
    }
};

const Attachment = ({ msg, att }) => {
    switch (att.type) {
        case 'photo':
            return html`
            <a onclick=${(e) => { window.im.messenger.showAttachment(e, msg, att) }} class="msg-attach-j msg-attach-j-photo" href=${att.photo.link}>
                <img src=${att.photo.photo_604 ?? att.photo.photo_130} alt="..." />
            </a>`;
        case 'video':
            return html`
                <div class="msg-attach-j msg-attach-j-video">
                    <a onclick=${(e) => { window.im.messenger.showAttachment(e, msg, att) }} class="compact_video" href=${'/video' + att.video.owner_id + '_' + att.video.id}>
                        <div class='play-button'><div class='play-button-ico'></div></div>
                        <img src=${att.video.image[0].url} alt="..." />
                        ${att.video.length ? `<span class="length">${fmtTime(att.video.length)}</span>` : ""}
                    </a>
                </div>`;
        case 'doc':
            const ids = att.doc.owner_id + '_' + att.doc.id;
            return html`
                <div class="msg-attach-w msg-attach-w-doc">
                    <a data-id="${ids + (att.doc.access_key ? "_" + att.doc.access_key : "")}" href=${'/doc' + ids + (att.doc.access_key ? "?key=" + att.doc.access_key : "")} class="attachment_note attachment_doc">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8 10"><polygon points="0 0 0 10 8 10 8 4 4 4 4 0 0 0"></polygon><polygon points="5 0 5 3 8 3 5 0"></polygon></svg>
                        <div class="docOpener attachment_note_content">
                            <span class="attachment_note_name">
                                <span>
                                ${att.doc.title}
                                </span>
                            </span>
                        </div>
                    </a>
                </div>`;
        case 'audio':
            return html`
                <div onclick=${(e) => { window.im.messenger.showAttachment(e, msg, att) }} class="msg-attach-w msg-attach-w-audio">
                    <div class="_icon"></div>
                    <span class="_artist">${att.audio.artist}</span>
                    <span>—</span>
                    <span class="_title">${att.audio.title}</span>
                </div>`;
        case 'wall':
            const isNoText = att.wall.text == null || att.wall.text.length == 0;
            const date = new Date(att.wall.date * 1000).toLocaleDateString(navigator.language, { hour: '2-digit', minute: '2-digit' });
            const previewText = att.wall.text ? (typeof ovk_proc_strtr === 'function' ? ovk_proc_strtr(att.wall.text, 25) : att.wall.text.slice(0, 25)) : '';
            const previewEscaped = typeof escapeHtml === 'function' ? escapeHtml(previewText) : previewText;
            return html`
                <div class="msg-attach-w msg-attach-w-post">
                    <a onclick="${(e) => { typeof PostViewer !== 'undefined' && PostViewer.openById(e, typeof idForItem === 'function' ? idForItem(att.wall) : att.wall.id) }}">
                        <div class="_icon"></div>
                        <span>
                            <b>${tr("post")}</b> ${isNoText ? tr("post_attachment_text", date).toLowerCase() : previewEscaped}
                        </span>
                    </a>
                </div>
            `;
        case "gift":
            return html`
                <div class="msg-attach-w msg-attach-w-gift">
                    <img src="${att.gift.gift.thumb_256}" />
                </div>
            `;
        case "link":
        case "share":
            return null;
        default:
            return html`<div class="msg-attach-w msg-attach-w-unknown">${tr("version_incompatibility")}</div>`;
    }
};

export const DayDivider = ({ date, day, idate }) => {
    const displayDate = date || day || idate || "";

    const onDateClick = (e) => {
        if (e) e.stopPropagation();
        if (window.im?.messenger?.showDaySwitcher) {
            window.im.messenger.showDaySwitcher(idate || date);
        }
    };

    return html`
    <div class="messenger-app--messages-day-time">
        <b onClick=${onDateClick} title="${tr('jump_to_date') || 'Выбрать дату в календаре'}">${displayDate}</b>
    </div>
  `;
};

export const DayChunkView = ({ chunk, page }) => {
    const chunkDate = chunk.date || chunk.readable_date || chunk.day || chunk.idate;

    return html`
    <div class="messenger-app--messages-day">
        <${DayDivider} day=${chunk.day} date=${chunkDate} idate=${chunk.idate} />
        ${chunk.messages.map((msg, idx) => html`
            <${MessageBubble} key=${msg.id || msg.conversation_message_id || idx} msg=${msg} index=${idx} chunk=${chunk} page=${page} />
        `)}
    </div>
    `;
};

export const MessageListView = ({ dayDividedChunks, convo, page }) => {
    const isMessagesInited = convo?.peer?.isMessagesInited ? convo.peer.isMessagesInited() : true;
    const isLoading = !isMessagesInited;
    const hasMessages = dayDividedChunks && dayDividedChunks.length > 0;

    return html`
    <div class="messenger-app--messages">
      <div class="messenger-app--messages-array">
         <div class="im_top_loader" style="display: none;"><img src="/assets/packages/static/openvk/img/loading_mini.gif" alt="..." /></div>
         ${isLoading ? html`
             <div id="gif_loader"></div>
         ` : html`
            ${!hasMessages ? html`
                <div class="messenger-app--no-messages">
                    <p>${tr('no_messages_in_chat') || "Здесь пока нет сообщений."}</p>
                </div>
            ` : html`
                ${dayDividedChunks.map((chunk, cidx) => html`<${DayChunkView} key=${chunk.readable_date || chunk.idate || cidx} chunk=${chunk} page=${page} />`)}
            `}
            <div>
                <${WriteBar} convo=${convo} />
            </div>
         `}
      </div>
    </div>
  `;
};
