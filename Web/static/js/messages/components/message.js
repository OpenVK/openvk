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
    const cls = [
        'messenger-app--messages---message',
        'messenger-layer',
        isSelected(msg) ? 'msg-selected' : '',
        hideHead(msg, index, chunk) ? 'same-author' : '',
        msg.isDeleted(0) ? 'msg-deleted' : '',
        msg.isError() ? 'msg-error' : '',
        msg.isEdited() ? 'msg-edited' : '',
        msg.isReply() ? 'msg-reply' : '',
        msg.isPinned() ? 'msg-pinned' : '',
        (isSearchTpl) ? 'msg-searched' : (msg.isError() ? "msg-error-hoverable" : 'msg-hoverable'),
        msg.isRead() ? 'msg-read' : 'unread',
    ].filter(Boolean).join(' ');

    const has_postfix = msg.isSpecial("gift");
    if (msg.isSpecial() && !isSearchTpl) {
        const act = msg.data.action.type;
        const typ = SystemMessages[act] ?? SystemMessages["unknown"];

        return typ(msg, page);
    }

    return html`
    <div class="${cls}"
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
            <div class="actions-2">
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
            <div class="inlines _avatar">
                <img class="ava" src=${msg.sender.getAvatar()} alt=${msg.sender.getName()} />
            </div>
            <div class="inlines _content">
                ${msg.isReply() == true && html`
                    <div class="reply-msg" onClick="${() => { !isSearchTpl ? window.im.messenger.view.scrollToMessage(msg.data.reply_message.id, true) : null }}">
                        <span>${tr("reply_to_msg")}</span>
                        <a class="reply-author">${msg.hasSender() ? msg.sender.getName() : "..."}</a>
                        <span dangerouslySetInnerHTML=${{ __html: msg.data.reply_message.getText(false, true) }} />
                    </div>
                `}
                <a class="_sender" onClick=${(e) => { window.im?.messenger?.view?.onAuthorNameClick(msg, e) }}>
                    <strong>${msg.sender.getName(false, true)}</strong>
                </a>
                ${has_postfix ? html`
                <div class="msg-postfix">
                ${msg.isSpecial("gift") ? html`
                    ${tr("msg_sent_gift_" + msg.sender.getGender()).toLowerCase()}:
                ` : ""}
                </div>` : ""}
                <div class="time" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>
                    ${msg.id != null && html`
                    <span>${isSearchTpl ? msg.getDate(2) + " " + msg.getDate(0) : msg.getDate(0)}</span>
                    `}
                </div>
                <p dangerouslySetInnerHTML=${{ __html: msg.getText(false) }} class="normalText text" />
                <p class="msg-modificators">
                    <p class="modificator-edited">(${tr('edit_action_past').toLowerCase()})</p>
                    <p class="modificator-pinned">(${tr('pinned_action_past').toLowerCase()})</p>
                </p>
                ${msg.getAttachments() && msg.getAttachments().length > 0 && html`
                    <div class="attachments">
                    ${msg.getAttachments().map((att) => html`<${Attachment} msg=${msg} att=${att} />`)}
                    </div>
                `}
                ${msg.has_not_loaded_attachments == true && html`
                    <img src=${_loader_link} />
                `}
            </div>
        </div>
    </div>
  `;
};

export const SystemMessages = {
    "chat_create": (msg, page) => {
        const sender = msg.sender;
        const chat_title = (msg.data?.action?.text || msg.action?.text || "").trim();
        let text = "";
        if (chat_title && chat_title !== "undefined") {
            text = tr("event_chat_creation_" + sender.getGender(), chat_title);
        } else {
            text = tr("event_chat_creation_no_title_" + sender.getGender());
            if (text === "event_chat_creation_no_title_" + sender.getGender()) {
                text = tr("event_chat_creation_" + sender.getGender());
            }
        }
        return html`
            <div class="messenger-special-message">
                <div>
                    <a class="_sender" onClick=${(e) => { page.onAuthorNameClick(msg, e) }}>
                        <strong>${sender.getName()} </strong>
                    </a>
                    <span class="text">${text.toLowerCase()}</span>
                    <span class="date-mini" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>${msg.getDate(0)}</span>
                </div>
            </div>
        `;
    },
    "rating_up": (msg, page) => {
        const sender = msg.sender;
        return html`
            <div class="messenger-special-message centred">
                <div>
                    <b>${tr("event_chat_user_up_your_rating_" + sender.getGender(), sender.getName(), msg.data.action.member_id)}</b>
                    <span class="date-mini" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>${msg.getDate(0)}</span>
                    <p>«${escapeHtml(msg.data.action.text)}»</p>
                </div>
            </div>
        `;
    },
    "coins_transfer": (msg, page) => {
        const sender = msg.sender;
        return html`
            <div class="messenger-special-message centred">
                <div>
                    <b>${tr("event_chat_user_added_voices_" + sender.getGender(), sender.getName(), msg.data.action.member_id)}</b>
                    <span class="date-mini" onClick=${(e) => { window.im.messenger.view.onTimeClick(e, msg) }}>${msg.getDate(0)}</span>
                    <p>«${escapeHtml(msg.data.action.text)}»</p>
                </div>
            </div>
        `;
    },
    "unknown": (msg, page) => {
        return html`
            <div class="messenger-special-message">
                <div class="messenger-app--messages---message--wrap">
                    <div class="_content">
                        <span class="text">${msg.getText()}</span>
                    </div>
                </div>
            </div>
        `;
    }
}

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
        default:
            return html`<div class="msg-attach-w msg-attach-w-unknown">${tr("version_incompatibility")}</div>`;
    }
};

export const DayDivider = ({ date, day, idate }) => {
    return html`
    <div class="messenger-app--messages-day-time">
        <b onClick=${(e) => { window.im.messenger.showDaySwitcher(idate) }}>${date}</b>
    </div>
  `;
};

export const DayChunkView = ({ chunk, page }) => {
    return html`
    <div class="messenger-app--messages-day">
        <${DayDivider} day=${chunk.day} date=${chunk.readable_date} idate=${chunk.idate} />
        ${chunk.messages.map((msg, idx) => html`
            <${MessageBubble} key=${msg.id || msg.conversation_message_id || idx} msg=${msg} index=${idx} chunk=${chunk} page=${page} />
        `)}
    </div>
    `;
};

export const MessageListView = ({ dayDividedChunks, convo, page }) => {
    const isMessagesInited = convo?.peer?.isMessagesInited ? convo.peer.isMessagesInited() : true;
    const isLoading = !isMessagesInited;

    return html`
    <div class="messenger-app--messages">
      <div class="messenger-app--messages-array">
         ${isLoading ? html`
             <div id="gif_loader"></div>
         ` : html`
            ${dayDividedChunks.map((chunk, cidx) => html`<${DayChunkView} key=${chunk.readable_date || chunk.idate || cidx} chunk=${chunk} page=${page} />`)}
            <div>
                <${WriteBar} convo=${convo} />
            </div>
         `}
      </div>
    </div>
  `;
};
