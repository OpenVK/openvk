import { html, render } from '../im.js';
import { WriteBar } from './convos.js';

function isSelected(msg) {
  const view = window.im?.messenger?.view;

  return view ? view.isMessageSelected(msg) : false;
}

function hideHead(msg, index, chunk) {
  return index > 0 && chunk.messages[index - 1].doHideHead(msg);
}

export const MessageBubble = ({ msg, index, chunk }) => {
  const cls = [
    'messenger-app--messages---message',
    isSelected(msg) ? 'msg-selected' : '',
    hideHead(msg, index, chunk) ? 'same-author' : '',
    msg.data.deleted ? 'msg-deleted' : '',
    msg.is_error ? 'msg-error' : '',
    msg.is_got_edited ? 'msg-edited' : '',
  ].filter(Boolean).join(' ');

  if (msg.is_action) {
    const act = msg.data.action.type;
    const typ = SystemMessages[act] ?? SystemMessages["unknown"];

    return typ(msg);
  }

  return html`
    <div class="${cls}"
      data-msg-id=${msg.id}
      onMouseDown=${(e) => window.im?.messenger?.view?.onMessageClick(msg, e)}>
      <div class="messenger-app--messages---message--wrap">
        <div class="inlines click-territory">
            <div class="checkmark"></div>
            ${msg.is_error && html`
                <div class="error-checkmark" onClick=${(e) => { msg.tryToResend() }} title="${msg.data.error_text}"></div>
            `}
            <div class="message-id">
                <span>${msg.id}</span>
            </div>
        </div>
        <div class="actions-2">
            ${msg.canEdit() && html`
                <div onClick=${(e) => { window.im.messenger.view.onEditButtonClick(e, msg) }} class="edit-icon"></div>
            `}
            ${msg.canPin() && html`
                <div onClick=${(e) => { window.im.messenger.view.onPinButtonClick(e, msg) }} class="pin-icon"></div>
            `}
        </div>
        <div class="inlines _avatar">
          <img class="ava" src=${msg.sender.avatar_any} alt=${msg.sender.full_name} />
        </div>
        <div class="inlines _content">
          <a class="_sender" onClick=${(e) => { window.im?.messenger?.view?.onAuthorNameClick(msg, e) }}>
            <strong>${msg.sender.name}</strong>
          </a>
          ${msg.is_reply == true && html`
              <div class="reply-msg" onClick="${() => { window.im.messenger.view.scrollToMessage(msg.data.reply_message.id, true) }}">
                  <a class="reply-author">${msg.has_sender ? msg.sender.full_name : "..."}</a>
                  <span dangerouslySetInnerHTML=${{ __html: msg.data.reply_message.conv_summary }} />
              </div>
          `}
          <p dangerouslySetInnerHTML=${{ __html: msg.text }} class="text" />
          <p class="msg-edit-mark">(${tr('edit_action_past').toLowerCase()})</p>
          ${msg.attachments && msg.attachments.length > 0 && html`
            <div class="attachments">
              ${msg.attachments.map((att) => html`<${Attachment} msg=${msg} att=${att} />`)}
            </div>
          `}
          ${msg.has_not_loaded_attachments == true && html`
              <img src=${_loader_link} />
          `}
        </div>
      </div>
      <div class="time">
        ${msg.id != null && html`
          <span>${msg.readable_date}</span>
        `}
      </div>
    </div>
  `;
};

export const SystemMessages = {
    "chat_create": (msg) => {
        const sender = msg.sender;
        const chat_title = msg.data.action.text;
        return html`
            <div class="messenger-special-message">
                <div>
                    <a class="_sender" onClick=${(e) => { window.im?.messenger?.view?.onAuthorNameClick(msg, e) }}>
                        <strong>${sender.full_name} </strong>
                    </a>
                    <span class="text">${tr("event_chat_creation_" + sender.gender, chat_title).toLowerCase()}</span>
                    <span class="date-mini">${msg.readable_date}</span>
                </div>
            </div>
        `;
    },
    "unknown": (msg) => {
        return html`
            <div class="messenger-special-message">
                <div class="messenger-app--messages---message--wrap">
                    <div class="_content">
                        <span class="text">${msg.text}</span>
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
          <a onclick=${(e) => {window.im.messenger.view.showPhoto(e, msg, att)}} class="msg-attach-j msg-attach-j-photo" href=${att.photo.link}>
            <img src=${att.photo.photo_604 ?? att.photo.photo_130} alt="..." />
          </a>`;
    case 'video':
      return html`
        <div class="msg-attach-j msg-attach-j-video">
          <a onclick=${(e) => {window.im.messenger.view.showVideo(e, msg, att)}} class="compact_video" href=${'/video' + att.video.owner_id + '_' + att.video.id}>
            <div class='play-button'><div class='play-button-ico'></div></div>
            <img src=${att.video.image[0].url} alt="..." />
            ${att.video.length ? `<span class="length">${fmtTime(att.video.length)}</span>` : ""}
          </a>
        </div>`;
	case 'doc':
      const ids = att.doc.owner_id + '_' + att.doc.id;
      return html`
        <div class="msg-attach-w msg-attach-w-doc">
            <a href=${'/doc' + ids + (att.doc.access_key ? "?key="+att.doc.access_key : "")} class="attachment_note attachment_doc">
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
        <div onclick=${(e) => { window.im.messenger.view.showAudio(e, msg, att) }} class="msg-attach-w msg-attach-w-audio">
          <span class="_icon"></span>
          <span class="_artist">${att.audio.artist}</span>
          <span>—</span>
          <span class="_title">${att.audio.title}</span>
        </div>`;
    case 'post':
		return html`
			<div class="msg-attach-w msg-attach-w-post">
				<a href="/wall${att.post.owner_id}_${att.post.id}" target="_blank">${tr("post")}</a>
			</div>
        `;
    default:
      return html`<div class="msg-attach-w msg-attach-w-unknown">${tr("version_incompatibility")}</div>`;
  }
};

export const DayDivider = ({ date }) => {
  return html`
    <div class="messenger-app--messages-day-time">
      <b>${date}</b>
    </div>
  `;
};

export const DayChunkView = ({ chunk }) => {
  return html`
    <div class="messenger-app--messages-day">
      <${DayDivider} date=${chunk.readable_date} />
      ${chunk.messages.map((msg, idx) => html`
        <${MessageBubble} msg=${msg} index=${idx} chunk=${chunk} />
      `)}
    </div>
  `;
};

export const MessageListView = ({ messages, convo }) => {
    return html`
    <div id="messenger-app--down-button" style="display:none" onClick=${() => window.im.messenger.view._scrollToEnd()}>DOWN</div>
    <div class="messenger-app--messages">
      <div class="messenger-app--messages-array">
         ${messages.map((chunk) => html`<${DayChunkView} chunk=${chunk} />`)}

         <div>
            <${WriteBar} convo=${convo} />
         </div>
      </div>
    </div>
  `;
};
