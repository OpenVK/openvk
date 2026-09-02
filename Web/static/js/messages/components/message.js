import { WriteBar } from './common.js';
import { html, render } from './render.js';

function isSelected(msg) {
    const view = window.im?.messenger;

    return view ? view.isMessageSelected(msg) : false;
}

function hideHead(msg, index, chunk) {
    return index > 0 && chunk.messages[index - 1].doHideHead(msg);
}

function formatReplyDate(date) {
    if (!date || !(date instanceof Date) || isNaN(date.getTime())) return "";
    const timeStr = date.toLocaleTimeString(navigator.language || 'default', { hour: '2-digit', minute: '2-digit' });
    const atStr = typeof tr === "function" && tr("time_at_sp") && !tr("time_at_sp").startsWith("@") ? tr("time_at_sp") : " в ";

    const day = date.getDate();
    const month = date.getMonth() + 1;
    const monthTr = typeof tr === "function" ? tr("month_gen_" + month) : "";
    if (monthTr && !monthTr.startsWith("@")) {
        const monthStr = monthTr.toLowerCase();
        const year = date.getFullYear();
        return `${day} ${monthStr} ${year}${atStr}${timeStr}`;
    }

    const dateStr = date.toLocaleDateString(navigator.language || 'default', { day: 'numeric', month: 'long', year: 'numeric' });
    return `${dateStr}${atStr}${timeStr}`;
}

export const MessageBubble = ({ msg, index, chunk, page, fromSearch }) => {
    const isSearchTpl = fromSearch == "1";
    const isDeleted = msg.isDeleted();
    const isReplyingTo = Boolean(window.im?.messenger?.replyTo && (Number(window.im.messenger.replyTo.id) === Number(msg.id) || window.im.messenger.replyTo === msg));
    const isEditingThis = Boolean(window.im?.messenger?.editMsg && (Number(window.im.messenger.editMsg.id) === Number(msg.id) || window.im.messenger.editMsg === msg));
    const cls = [
        'messenger-app--messages---message',
        'messenger-layer',
        isSelected(msg) ? 'msg-selected' : '',
        isReplyingTo ? 'msg-replying-to' : '',
        isEditingThis ? 'msg-editing-this' : '',
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
    let replySender = null;
    let replyAuthorName = "...";
    let replyAuthorAvatar = "/assets/packages/static/openvk/img/camera_50.png";
    let replyFromId = null;
    let replyFormattedDate = "";
    let replyText = "";

    let replyAttachments = [];
    const rep = msg.data?.reply_message || (msg.data?.reply_to ? { id: msg.data.reply_to } : null);
    if (rep) {
        replyFromId = rep.from_id || rep.data?.from_id;
        if (rep.sender && typeof rep.sender.getName === "function") {
            replySender = rep.sender;
        } else if (rep.data?.sender && typeof rep.data.sender.getName === "function") {
            replySender = rep.data.sender;
        } else if (replyFromId) {
            replySender = window.im?.cached_profiles?._findCachedProfileByIdEvenIfNotCached(replyFromId);
        }

        if (replySender) {
            replyAuthorName = typeof replySender.getName === "function" ? replySender.getName() : (replyFromId ? "id" + replyFromId : "...");
            replyAuthorAvatar = typeof replySender.getAvatar === "function" ? replySender.getAvatar("mid", false) : "/assets/packages/static/openvk/img/camera_50.png";
        } else if (replyFromId) {
            replyAuthorName = "id" + replyFromId;
        }

        let replyDateObj = null;
        if (typeof rep.getSentTime === "function") {
            replyDateObj = rep.getSentTime();
        } else if (rep.data?.date) {
            replyDateObj = new Date(rep.data.date * 1000);
        } else if (rep.date) {
            replyDateObj = new Date(rep.date * 1000);
        }
        if (replyDateObj) {
            replyFormattedDate = formatReplyDate(replyDateObj);
        }

        if (typeof rep.getText === "function") {
            replyText = rep.getText(false);
        } else if (rep.data?.text) {
            replyText = escapeHtml(rep.data.text);
        } else if (rep.text) {
            replyText = escapeHtml(rep.text);
        }

        if (typeof rep.getAttachments === "function") {
            replyAttachments = (rep.getAttachments() || []).filter(a => a && a.type !== 'link' && a.type !== 'share');
        } else if (Array.isArray(rep.data?.attachments)) {
            replyAttachments = (rep.data.attachments || []).filter(a => a && a.type !== 'link' && a.type !== 'share');
        } else if (Array.isArray(rep.attachments)) {
            replyAttachments = (rep.attachments || []).filter(a => a && a.type !== 'link' && a.type !== 'share');
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
                ${isReply && html`
                    <div class="reply-msg-container" onClick=${(e) => {
                e.stopPropagation();
                if (!isSearchTpl && window.im?.messenger) {
                    window.im.messenger.goToMessage(rep || { id: msg.data?.reply_to, peer_id: peerId });
                }
            }}>
                        <div class="reply-msg-header">
                            ${typeof tr === "function" && tr("reply_to_message_user", replyAuthorName) && !tr("reply_to_message_user", replyAuthorName).startsWith("@")
                ? tr("reply_to_message_user", replyAuthorName)
                : ("в ответ на сообщение " + replyAuthorName)}
                        </div>
                        <div class="reply-msg-head">
                            <a href=${replyFromId ? `/id${replyFromId}` : "javascript:void(0)"} target="_blank" class="reply-avatar-link" onClick=${(e) => e.stopPropagation()}>
                                <img class="reply-avatar" src=${replyAuthorAvatar} alt=${replyAuthorName} />
                            </a>
                            <a class="reply-author-name" href=${replyFromId ? `/id${replyFromId}` : "javascript:void(0)"} target="_blank" onClick=${(e) => e.stopPropagation()}>
                                <strong>${replyAuthorName}</strong>
                            </a>
                            ${replyFormattedDate ? html`<span class="reply-date">${replyFormattedDate}</span>` : ""}
                        </div>
                        ${replyText ? html`
                            <div class="reply-text" dangerouslySetInnerHTML=${{ __html: replyText }} />
                        ` : (replyAttachments.length > 0 ? null : html`
                            <div class="reply-text reply-no-text">${typeof tr === "function" && tr("message_no_text") ? "(" + tr("message_no_text").toLowerCase() + ")" : "..."}</div>
                        `)}
                        ${replyAttachments.length > 0 && html`
                            <div class="reply-compact-attachments">
                                ${replyAttachments.map((att) => html`<${CompactReplyAttachment} rep=${rep} att=${att} />`)}
                            </div>
                        `}
                    </div>
                `}
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

const CompactReplyAttachment = ({ rep, att }) => {
    if (!att || !att.type) return null;
    const type = att.type;

    switch (type) {
        case 'photo': {
            const photoSrc = att.photo?.photo_75 || att.photo?.photo_130 || att.photo?.sizes?.[0]?.url || att.photo?.link || att.photo?.photo_604 || '';
            const label = typeof tr === 'function' && tr('photo') && !tr('photo').startsWith('@') ? tr('photo') : 'Фотография';
            return html`
                <div class="reply-compact-attach reply-compact-photo" title=${label} onClick=${(e) => {
                    e.stopPropagation();
                    if (window.im?.messenger?.showAttachment && rep) {
                        window.im.messenger.showAttachment(e, rep, att);
                    }
                }}>
                    ${photoSrc ? html`<img class="reply-compact-thumb" src=${photoSrc} alt="photo" />` : html`<span class="reply-attach-icon">📷</span>`}
                    <span class="reply-attach-label">${label}</span>
                </div>
            `;
        }
        case 'video': {
            const videoThumb = att.video?.image?.[0]?.url || att.video?.photo_130 || att.video?.photo_320 || '';
            const title = att.video?.title || (typeof tr === 'function' && tr('video') && !tr('video').startsWith('@') ? tr('video') : 'Видеозапись');
            return html`
                <div class="reply-compact-attach reply-compact-video" title=${title} onClick=${(e) => {
                    e.stopPropagation();
                    if (window.im?.messenger?.showAttachment && rep) {
                        window.im.messenger.showAttachment(e, rep, att);
                    }
                }}>
                    ${videoThumb ? html`<img class="reply-compact-thumb" src=${videoThumb} alt="video" />` : html`<span class="reply-attach-icon">🎬</span>`}
                    <span class="reply-attach-label">${title}</span>
                </div>
            `;
        }
        case 'audio': {
            const artist = att.audio?.artist || '';
            const title = att.audio?.title || (typeof tr === 'function' && tr('audio') && !tr('audio').startsWith('@') ? tr('audio') : 'Аудиозапись');
            const fullTitle = artist ? `${artist} — ${title}` : title;
            return html`
                <div class="reply-compact-attach reply-compact-audio" title=${fullTitle} onClick=${(e) => {
                    e.stopPropagation();
                    if (window.im?.messenger?.showAttachment && rep) {
                        window.im.messenger.showAttachment(e, rep, att);
                    }
                }}>
                    <span class="reply-attach-icon">🎵</span>
                    <span class="reply-attach-label">${fullTitle}</span>
                </div>
            `;
        }
        case 'doc': {
            const docTitle = att.doc?.title || (typeof tr === 'function' && tr('document') && !tr('document').startsWith('@') ? tr('document') : 'Документ');
            const ids = att.doc ? (att.doc.owner_id + '_' + att.doc.id + (att.doc.access_key ? "?key=" + att.doc.access_key : "")) : "";
            return html`
                <a class="reply-compact-attach reply-compact-doc" title=${docTitle} href=${ids ? '/doc' + ids : 'javascript:void(0)'} onClick=${(e) => e.stopPropagation()}>
                    <span class="reply-attach-icon">📄</span>
                    <span class="reply-attach-label">${docTitle}</span>
                </a>
            `;
        }
        case 'wall': {
            const wallTitle = typeof tr === 'function' && tr('post') && !tr('post').startsWith('@') ? tr('post') : 'Запись на стене';
            return html`
                <div class="reply-compact-attach reply-compact-wall" title=${wallTitle} onClick=${(e) => {
                    e.stopPropagation();
                    if (typeof PostViewer !== 'undefined' && att.wall) {
                        PostViewer.openById(e, typeof idForItem === 'function' ? idForItem(att.wall) : att.wall.id);
                    }
                }}>
                    <span class="reply-attach-icon">📝</span>
                    <span class="reply-attach-label">${wallTitle}</span>
                </div>
            `;
        }
        case 'gift': {
            const giftThumb = att.gift?.thumb_48 || att.gift?.thumb_96 || att.gift?.gift?.thumb_48 || att.gift?.gift?.thumb_96 || att.gift?.gift?.thumb_256 || '';
            const giftTitle = typeof tr === 'function' && tr('gift') && !tr('gift').startsWith('@') ? tr('gift') : 'Подарок';
            return html`
                <div class="reply-compact-attach reply-compact-gift" title=${giftTitle}>
                    ${giftThumb ? html`<img class="reply-compact-thumb" src=${giftThumb} alt="gift" />` : html`<span class="reply-attach-icon">🎁</span>`}
                    <span class="reply-attach-label">${giftTitle}</span>
                </div>
            `;
        }
        case 'sticker': {
            const stickerThumb = att.sticker?.images?.[0]?.url || att.sticker?.photo_64 || att.sticker?.photo_128 || '';
            return html`
                <div class="reply-compact-attach reply-compact-sticker" title="Стикер">
                    ${stickerThumb ? html`<img class="reply-compact-thumb" src=${stickerThumb} alt="sticker" />` : ''}
                    <span class="reply-attach-label">Стикер</span>
                </div>
            `;
        }
        case 'poll': {
            const pollQ = att.poll?.question || (typeof tr === 'function' && tr('poll') && !tr('poll').startsWith('@') ? tr('poll') : 'Опрос');
            return html`
                <div class="reply-compact-attach reply-compact-poll" title=${pollQ}>
                    <span class="reply-attach-icon">📊</span>
                    <span class="reply-attach-label">${pollQ}</span>
                </div>
            `;
        }
        default: {
            if (type === 'link' || type === 'share') return null;
            return html`
                <div class="reply-compact-attach reply-compact-generic">
                    <span class="reply-attach-label">(${type})</span>
                </div>
            `;
        }
    }
};

const WallPostAttachment = ({ wall }) => {
    if (!wall) return null;
    const wallId = wall.id;
    const ownerId = wall.to_id || wall.owner_id || 0;
    const fromId = wall.from_id || ownerId;
    const dateObj = wall.date ? new Date(wall.date * 1000) : null;
    const formattedDate = dateObj ? dateObj.toLocaleDateString(navigator.language, {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    }) : '';

    const authorProfile = window.im?.cached_profiles && window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(fromId || ownerId);
    const authorName = wall.author_name || (authorProfile?.getName ? authorProfile.getName() : (ownerId < 0 ? 'Группа' : 'Пользователь'));
    const defaultAva = ownerId < 0 ? '/assets/packages/static/openvk/img/community_50.png' : '/assets/packages/static/openvk/img/camera_50.png';
    const authorAvatar = wall.author_avatar || (authorProfile?.getAvatar ? authorProfile.getAvatar('mid', false) : defaultAva);
    const authorLink = ownerId < 0 ? `/club${Math.abs(ownerId)}` : `/id${ownerId}`;
    const fullPostId = typeof idForItem === 'function' ? idForItem(wall) : `${ownerId}_${wallId}`;

    const text = wall.text || '';
    const textFormatted = text ? (typeof ovk_proc_strtr === 'function' ? ovk_proc_strtr(text, 280) : text.slice(0, 280)) : '';

    let layoutTiles = [];
    let layoutExtras = [];
    let layoutWidth = '100%';
    let layoutHeight = 'unset';

    if (wall.layout && Array.isArray(wall.layout.tiles) && wall.layout.tiles.length > 0) {
        layoutTiles = wall.layout.tiles;
        layoutExtras = Array.isArray(wall.layout.extras) ? wall.layout.extras : [];
        layoutWidth = wall.layout.width || '100%';
        layoutHeight = wall.layout.height || 'unset';

        const totalW = parseFloat(layoutWidth) || 380;
        const rows = [];
        let currentRow = [];
        let currentWidthSum = 0;

        layoutTiles.forEach(tile => {
            const w = parseFloat(tile.width) || 0;
            const isFullWidth = w >= (totalW - 5);
            if (isFullWidth) {
                if (currentRow.length > 0) {
                    rows.push(currentRow);
                    currentRow = [];
                    currentWidthSum = 0;
                }
                rows.push([tile]);
            } else {
                if (currentWidthSum + w > totalW + 5) {
                    if (currentRow.length > 0) {
                        rows.push(currentRow);
                        currentRow = [];
                        currentWidthSum = 0;
                    }
                }
                currentRow.push(tile);
                currentWidthSum += w;
            }
        });
        if (currentRow.length > 0) {
            rows.push(currentRow);
        }

        rows.forEach(row => {
            const rowWidthSum = row.reduce((sum, t) => sum + (parseFloat(t.width) || 0), 0) || 1;
            row.forEach(t => {
                const w = parseFloat(t.width) || 0;
                t.percentWidth = ((w / rowWidthSum) * 100).toFixed(4);
            });
        });
    } else {
        const rawAtts = Array.isArray(wall.attachments) ? wall.attachments : [];
        const visualItems = [];
        rawAtts.forEach(att => {
            if (!att) return;
            if (att.type === 'photo' && att.photo) {
                visualItems.push({ type: 'photo', photo: att.photo });
            } else if (att.type === 'video' && att.video) {
                visualItems.push({ type: 'video', video: att.video });
            } else {
                layoutExtras.push(att);
            }
        });

        if (visualItems.length === 1) {
            layoutTiles = [{
                width: '100%',
                percentWidth: '100',
                height: 'auto',
                float: 'none',
                ...visualItems[0]
            }];
        } else if (visualItems.length === 2) {
            layoutTiles = visualItems.map(item => ({
                width: '50%',
                percentWidth: '50',
                height: '150px',
                float: 'left',
                ...item
            }));
            layoutHeight = '150px';
        } else if (visualItems.length >= 3) {
            layoutTiles = visualItems.slice(0, 4).map((item, idx) => ({
                width: '50%',
                percentWidth: '50',
                height: visualItems.length === 3 && idx === 0 ? '180px' : '90px',
                float: 'left',
                ...item
            }));
            layoutHeight = '180px';
        }
    }

    const onCardClick = (e) => {
        if (e.target.closest('.audioEmbed, a, .wall-card-avatar-link, .musicIcon, .selectableTrack, .playerButton, .playIcon')) {
            return;
        }
        if (typeof PostViewer !== 'undefined') {
            PostViewer.openById(e, fullPostId);
        } else {
            window.open(`/wall${fullPostId}`, '_blank');
        }
    };

    return html`
        <div class="msg-attach-wall-card" onClick=${onCardClick}>
            <div class="wall-card-head">
                <a href=${authorLink} target="_blank" class="wall-card-avatar-link" onClick=${(e) => e.stopPropagation()}>
                    <img class="wall-card-avatar" src=${authorAvatar} alt=${authorName} />
                </a>
                <div class="wall-card-author-info">
                    <a href=${authorLink} target="_blank" class="wall-card-author-name" onClick=${(e) => e.stopPropagation()}>
                        ${authorName}
                    </a>
                    ${formattedDate ? html`<span class="wall-card-date">${formattedDate}</span>` : ''}
                </div>
            </div>

            ${textFormatted ? html`
                <div class="wall-card-text">${textFormatted}</div>
            ` : (!layoutTiles.length && !layoutExtras.length ? html`
                <div class="wall-card-text wall-card-empty">${typeof tr === 'function' ? tr('post') : 'Запись на стене'}</div>
            ` : '')}

            ${layoutTiles.length > 0 && html`
                <div class="attachments attachments_b" style="width: 100%; height: ${layoutHeight};">
                    ${layoutTiles.map(tile => {
                        const styleStr = `float: ${tile.float || 'left'}; width: ${tile.percentWidth || 100}%; height: ${tile.height || 'auto'};`;
                        if (tile.type === 'photo') {
                            const photo = tile.photo;
                            const photoSrc = photo.photo_604 || photo.photo_320 || photo.photo_130 || photo.photo_75 || photo.sizes?.[0]?.url || photo.link || '';
                            const photoLink = photo.link || `/photo${photo.owner_id}_${photo.id}`;
                            return html`
                                <div class="attachment" style=${styleStr}>
                                    <a class="wall-media-tile" href=${photoLink} onClick=${(e) => {
                                        e.stopPropagation();
                                        if (typeof OpenMiniature === 'function') {
                                            OpenMiniature(e, photo.photo_604 || photo.link, fullPostId, photo.id, 'post');
                                        }
                                    }}>
                                        <img class="media media_makima" src=${photoSrc} alt=${photo.text || 'photo'} loading="lazy" />
                                    </a>
                                </div>
                            `;
                        } else if (tile.type === 'video') {
                            const rawVideo = tile.video?.video || tile.video || {};
                            const videoId = rawVideo.id;
                            const videoOwnerId = rawVideo.owner_id;
                            const videoTitle = rawVideo.title || rawVideo.name || 'Видеозапись';
                            const videoDuration = rawVideo.duration || rawVideo.length || 0;
                            const videoThumb = rawVideo.thumbnail || rawVideo.photo_320 || rawVideo.photo_130 || rawVideo.image?.[0]?.url || rawVideo.image || '/assets/packages/static/openvk/video/rendering.apng';
                            const durationFormatted = videoDuration ? (typeof fmtTime === 'function' ? fmtTime(videoDuration) : `${Math.floor(videoDuration / 60)}:${(videoDuration % 60 < 10 ? '0' : '') + (videoDuration % 60)}`) : '';
                            return html`
                                <div class="attachment" style=${styleStr}>
                                    <a class="compact_video" href="/video${videoOwnerId}_${videoId}" onClick=${(e) => {
                                        e.stopPropagation();
                                        if (typeof VideoViewer !== 'undefined') {
                                            VideoViewer.openById(videoOwnerId + '_' + videoId, {}, e);
                                        }
                                    }}>
                                        <div class="play-button"><div class="play-button-ico"></div></div>
                                        ${durationFormatted ? html`<div class="video-length">${durationFormatted}</div>` : ''}
                                        <img class="media media_makima" src=${videoThumb} alt=${videoTitle} loading="lazy" />
                                    </a>
                                </div>
                            `;
                        }
                        return null;
                    })}
                </div>
            `}

            ${layoutExtras.length > 0 && html`
                <div class="attachments attachments_m">
                    ${layoutExtras.map(extra => {
                        if (extra.type === 'audio') {
                            return html`<${Attachment} msg=${null} att=${extra} />`;
                        } else if (extra.type === 'doc') {
                            const doc = extra.doc;
                            const docId = doc.owner_id + '_' + doc.id + (doc.access_key ? '?key=' + doc.access_key : '');
                            return html`
                                <div class="attachment_note attachment_doc" onClick=${(e) => e.stopPropagation()}>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8 10"><polygon points="0 0 0 10 8 10 8 4 4 4 4 0 0 0"></polygon><polygon points="5 0 5 3 8 3 5 0"></polygon></svg>
                                    <div class="attachment_note_content">
                                        <span class="attachment_note_text">${typeof tr === 'function' ? tr('document') : 'Документ'}</span>
                                        <span class="attachment_note_name"><a href="/doc${docId}" target="_blank">${doc.title}</a></span>
                                    </div>
                                </div>
                            `;
                        } else {
                            return html`<${CompactReplyAttachment} rep=${null} att=${extra} />`;
                        }
                    })}
                </div>
            `}
        </div>
    `;
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
        case 'audio': {
            const audio = att.audio;
            if (!audio) return null;
            const audioId = audio.global_id || audio.id || audio.aid || 0;
            const artist = audio.artist || audio.performer || '';
            const title = audio.title || audio.name || '';
            const duration = audio.duration || audio.length || 0;
            const durationFormatted = typeof fmtTime === 'function' ? fmtTime(duration) : `${Math.floor(duration / 60)}:${(duration % 60 < 10 ? '0' : '') + (duration % 60)}`;
            const isPlaying = window.player && window.player.current_track_id == audioId && !window.player.audioPlayer?.paused;
            let keysObj = {};
            try {
                if (audio.keys && typeof audio.keys === 'object') {
                    keysObj = audio.keys;
                } else if (typeof audio.keys === 'string' && audio.keys.trim().startsWith('{')) {
                    keysObj = JSON.parse(audio.keys);
                }
            } catch (e) {
                keysObj = {};
            }
            const keysStr = JSON.stringify(keysObj);
            const playUrl = audio.manifest || audio.url || '';
            const downloadUrl = audio.url || '';
            const trackName = `${artist} — ${title}`;

            return html`
                <div id="audioEmbed-${audioId}"
                     data-realid="${audioId}"
                     data-name="${trackName}"
                     data-genre="${audio.genre_str || audio.genre || 'Other'}"
                     class="audioEmbed ctx_place msg-attach-audio-player"
                     data-length="${duration}"
                     data-keys=${keysStr}
                     data-url="${playUrl}"
                     data-owner-id="${audio.owner_id || 0}">
                    <audio class="audio"></audio>

                    <div class="audioEntry">
                        <div class="audioEntryWrapper" draggable="false">
                            <div class="playerButton">
                                <div class="playIcon ${isPlaying ? 'paused' : ''}"></div>
                            </div>

                            <div class="status">
                                <div class="mediaInfo noOverflow" title="${trackName}">
                                    <div class="info">
                                        <strong class="performer">
                                            <a draggable="false" href="/search?section=audios&order=listens&only_performers=on&q=${encodeURIComponent(artist)}" onClick=${(e) => e.stopPropagation()}>${artist}</a>
                                        </strong>
                                        <span class="tire">—</span>
                                        <span draggable="false" class="title">${title}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mini_timer">
                                <span class="nobold hideOnHover" data-unformatted="${duration}">${durationFormatted}</span>
                                <div class="buttons">
                                    <div class="add-icon musicIcon hovermeicon" data-id="${audioId}" title="${typeof tr === 'function' ? tr('add') : 'Добавить'}" onClick=${(e) => { e.stopPropagation(); if (typeof __showAudioAddDialog === 'function') __showAudioAddDialog(Number(audioId)); }}></div>
                                    ${downloadUrl ? html`<a class="download-icon musicIcon" href="${downloadUrl}" download="${artist} - ${title}.mp3" title="${typeof tr === 'function' ? tr('download') : 'Скачать'}" onClick=${(e) => e.stopPropagation()}></a>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="subTracks ${isPlaying ? 'shown' : ''}" draggable="false">
                            <div class="lengthTrackWrapper">
                                <div class="track lengthTrack">
                                    <div class="selectableTrack">
                                        <div class="selectableTrackLoadProgress">
                                            <div class="load_bar"></div>
                                        </div>
                                        <div class="selectableTrackSlider">
                                            <div class="slider"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="volumeTrackWrapper">
                                <div class="track volumeTrack">
                                    <div class="selectableTrack">
                                        <div class="selectableTrackSlider">
                                            <div class="slider"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        case 'wall':
            return html`<${WallPostAttachment} wall=${att.wall} />`;
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
