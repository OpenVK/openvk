import { h, render as preactRender } from '../node_modules/preact/dist/preact.mjs';
import htm from '../node_modules/htm/dist/htm.mjs';

const tr = window.tr;

const html = htm.bind(h);

export { html, preactRender as render };

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
  ].filter(Boolean).join(' ');

  if (msg.is_action) {
    const act = msg.data.action.type;
    const typ = SystemMessages[act] ?? SystemMessages["unknown"];

    return typ(msg);
  }

  return html`
    <div class="${cls}"
      onMouseDown=${(e) => window.im?.messenger?.view?.onMessageClick(msg, e)}>
      <div class="messenger-app--messages---message--wrap">
        <div class="click-territory">
            <div class="checkmark"></div>
            <div class="message-id">
                <span>${msg.id}</span>
            </div>
        </div>
        <div class="_avatar">
          <img class="ava" src=${msg.sender.avatar_any} alt=${msg.sender.full_name} />
        </div>
        <div class="_content">
          <a class="_sender" onClick=${(e) => { window.im?.messenger?.view?.onAuthorNameClick(msg, e) }}>
            <strong>${msg.sender.full_name}</strong>
          </a>
          <span dangerouslySetInnerHTML=${{ __html: msg.text }} class="text" />
          ${msg.attachments.length > 0 && html`
            <div class="attachments">
              ${msg.attachments.map((att) => html`<${Attachment} att=${att} />`)}
            </div>
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
      return html`
        <div class="messenger-special-message">
            <div>
                <a class="_sender" href=${msg.sender.page_url || msg.sender.chat_url}>
                    <strong>${msg.sender.full_name} </strong>
                </a>
                <span class="text">${msg.text}</span>
                <span class="date-mini">${msg.readable_date}</span>
            </div>
        </div>
    `;
  },
  "unknown": (msg) => {
      return html`
        <div class="${msg}">
            <div class="messenger-app--messages---message--wrap">
                <div class="_content">
                <span class="text">${msg.text}</span>
                </div>
            </div>
        </div>
    `;
  }
}

const Attachment = ({ att }) => {
  switch (att.type) {
    case 'photo':
      return html`
        <div class="msg-attach-j msg-attach-j-photo">
          <a href=${att.photo.link}>
            <img src=${att.photo.photo_130} alt="..." />
          </a>
        </div>`;
    case 'video':
      return html`
        <div class="msg-attach-j msg-attach-j-video">
          <a href=${'/video' + att.video.owner_id + '_' + att.video.id}>
            <span>${att.video.title}</span>
          </a>
        </div>`;
    case 'doc':
      return html`
        <div class="msg-attach-j msg-attach-j-doc">
          <a href=${'/doc' + att.doc.owner_id + '_' + att.doc.id}>
            <span>${att.doc.title}</span>
          </a>
        </div>`;
    case 'audio':
      return html`
        <div class="msg-attach-j msg-attach-j-audio">
          <a>${att.audio.artist}</a>
          —
          <span>${att.audio.title}</span>
        </div>`;
    default:
      return null;
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

export const MessageListView = ({ messages }) => {
  return html`
    <div class="messenger-app--messages">
      <div class="messenger-app--messages-array">
        ${messages.map((chunk) => html`<${DayChunkView} chunk=${chunk} />`)}
      </div>
    </div>
  `;
};

export const PeerTab = ({ conv, active }) => {
  return html`
    <div class="messages--peers-tab${active ? ' selected' : ''}">
      <a onClick=${() => window.im?.selectChat(conv)}>${conv.peer.name}</a>
      <span class="messages--peers-tab-close" onClick=${() => window.im?.closeChat(conv)}>x</span>
    </div>
  `;
};

export const PeerTabsView = ({ tabs, currentChat }) => {
  if (tabs.length < 2) { return }

  return html`
    <div class="messages--peers-tabs">
      ${tabs.map((tab, idx) => html`
        <${PeerTab} conv=${tab} active=${idx === currentChat} />
      `)}
    </div>
  `;
};

export const ActionsBar = ({ count, onDelete, onUnselect, onReply }) => {
  if (count === 0) return null;
  return html`
    <div class="messages--actions shown">
      <div>
        <div class="message-tab"><a onClick=${onUnselect}>${tr("selected_messages", count)}</a></div>
      </div>
      <div>
        <div class="message-tab"><a onClick=${onDelete}>${tr("delete_message")}</a></div>
        ${count === 1 && html`
            <div class="message-tab"><a onClick=${onReply}>${tr("reply_to_message")}</a></div>
        `}
      </div>
    </div>
  `;
};

export const AttachmentMenu = () => {
  return html`
    <div>
      <a class="menu_toggler">${tr('attach')}</a>
      <div id="wallAttachmentMenu" class="up_direction hidden">
        <a class="header menu_toggler">${tr('attach')}</a>
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
  `;
};

export const InputArea = ({ replyTo, onRemoveReply, onSend, onKeyPress, currentDraft, onInput, togglePeerInfo, clickOnReply }) => {
  return html`
    <div class="messenger-app-end${replyTo ? ' reply-selected' : ''}">
      ${replyTo && html`
        <div class="input-reply">
          <span onclick=${clickOnReply(replyTo)} aria-label="link" class="input-type">${replyTo.text}</span>
          <span class="input-close" onClick=${onRemoveReply}>close</span>
        </div>
      `}
      <div class="post-buttons">
        <div class="model_content_textarea messenger-app--input has_emoji_picker expanded-textarea" id="write">
          <img class="ava" src=${window.im.current.avatar_any} alt=${window.im.current.full_name} />
          <div class="messenger-app--input---messagebox">
            <div style="position:relative;">
                <textarea
                class="small-textarea"
                placeholder=${tr('enter_message')}
                value=${currentDraft}
                onInput=${onInput}
                onKeyDown=${onKeyPress}></textarea>
                <div class="emoji_picker_entrypoint"></div>
            </div>
            <div class="post-horizontal"></div>
            <div class="post-vertical"></div>
            <div class="input--messagebox-buttons">
              <button class="button" onClick=${onSend}>${tr('send')}</button>
              <${AttachmentMenu} />
            </div>
          </div>
          <img class="ava peer-toggler" onclick="${togglePeerInfo}" src="${window.im?.corresponder?.avatar_any || ''}"
               alt="${window.im?.corresponder?.full_name || ''}" />
        </div>
      </div>
    </div>
  `;
};

export const ChatView = ({ peer }) => {
  if (!peer) return null;
  const view = window.im?.messenger?.view;
  if (!view) return null;

  return html`
    <div class="chat-window">
      <${PeerTabsView} tabs=${view.opened_tabs} currentChat=${view.current_chat} />
      <${ActionsBar}
        count=${view.selected_messages.length}
        onDelete=${() => view.callDeletion()}
        onUnselect=${() => view.unselect()}
        onReply=${() => view.onReplyButtonClick()}
      />
      <div class="messenger-app">
        <div id="messenger-app--down-button" style="display:none"
             onClick=${() => view._scrollToEnd()}>DOWN</div>
        <${MessageListView} messages=${peer.divided_messages} />
      </div>
    </div>
  `;
};

export const ConversationItem = ({ conv }) => {
  const last_msg = conv.last_message;
  const cls1 = ["crp-entry"];
  if (last_msg && last_msg.data.from_id != conv.peer.id) {
    cls1.push("crp-entry-replied-same");
  }

  return html`
    <div class="${cls1.join(' ')}" onClick=${() => window.im?.selectChat(conv)}>
      <div class="crp-entry--image">
        <img src=${conv.peer.avatar_any} loading="lazy" />
      </div>
      <div class="crp-entry--info">
        <a href=${conv.peer.chat_url}>${ovk_proc_strtr(conv.peer.full_name, 30)}</a><br/>
        ${last_msg && html`<span>${last_msg.conv_date}</span>`}
      </div>
      <div class="crp-entry--message">
        ${last_msg != null && html`
        <div class="crp-entry--message---av">
          <img src="${last_msg.sender.avatar_any}" />
        </div>
        <div class="crp-entry--message---text">
        ${last_msg.conv_summary}
        </div>`
        }
      </div>
    </div>
  `;
};

export const ConversationListView = ({ conversations, hasMore, onLoadMore, onCreateChat, onSearch }) => {
  return html`
    <div id="conversations-top-buttons">
        <div id="conversations-search-bar">
            <input class="search_input" type="text" placeholder="${tr('search_messages')}" onChange=${onSearch} />
        </div>
        <input type="button" class="button" value="${tr('create_chat')}" onClick=${onCreateChat} />
    </div>
    <div class="crp-list">
      ${conversations.map((conv) => html`<${ConversationItem} conv=${conv} />`)}
      ${hasMore && html`
        <div onClick=${onLoadMore} id="show_more" class="crp-load-more">
          ${tr('show_next')}
        </div>
      `}
    </div>
  `;
};
