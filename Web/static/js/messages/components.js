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
    msg.is_error ? 'msg-error' : '',
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
        <div class="click-territory">
            <div class="checkmark"></div>
            ${msg.is_error && html`
                <div class="error-checkmark" onClick=${(e) => { msg.tryToResend() }} title="${msg.data.error_text}"></div>
            `}
            <div class="message-id">
                <span>${msg.id}</span>
            </div>
        </div>
        <div class="_avatar">
          <img class="ava" src=${msg.sender.avatar_any} alt=${msg.sender.full_name} />
        </div>
        <div class="_content">
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
      <a onClick=${() => window.im?.selectChat(conv)}>${conv.peer.conversations_name}</a>
      <span class="messages--peers-tab-close" onClick=${() => window.im?.closeChat(conv)}>×</span>
    </div>
  `;
};

export const PeerTabsView = ({ had_more_one_tab, tabs, currentChat }) => {
  if (tabs.length < 2 && !window.im.messenger.view.had_more_one_tab) { return html`` }

  return html`
    <div class="messages--peers-tabs">
      ${tabs.map((tab, idx) => html`
        <${PeerTab} conv=${tab} active=${idx === currentChat} />
      `)}
    </div>
  `;
};

export const ActionsBar = ({ selectedMessages, count, onDelete, onUnselect, onReply }) => {
    if (count === 0) return null;
    let canDeleteThemAll = true;

    selectedMessages.forEach(msg => {
        if (msg.can_delete() == false) {
            canDeleteThemAll = false;
        }
    })

    return html`
        <div class="messages--actions shown">
            <div>
                <div class="message-tab-counter message-tab"><a onClick=${onUnselect}>${tr("selected_messages", count)}</a></div>
            </div>
            <div>
                ${canDeleteThemAll == true && html`
                <div class="message-tab"><a onClick=${onDelete}>${tr("delete_message")}</a></div>`}
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

export const InputArea = ({ replyTo, onRemoveReply, onSend, onKeyPress, currentDraft, onInput, togglePeerInfo, clickOnReply }) => {
  return html`
    <div class="messenger-app-end${replyTo ? ' reply-selected' : ''}">
      ${replyTo && html`
        <div class="input-reply">
          <span onclick=${() => { clickOnReply(replyTo) }} aria-label="link" class="input-type">${escapeHtml(tr("reply_to", replyTo.sender.full_name))}</span>
          <span class="input-close" onClick=${onRemoveReply}>×</span>
        </div>
      `}
      <div class="post-buttons">
        <div class="model_content_textarea messenger-app--input has_emoji_picker expanded-textarea" id="write">
          <img class="ava" src=${window.im.current.avatar_any} alt=${window.im.current.full_name} />
          <div class="messenger-app--input---messagebox">
            <div class="textareas has_emoji_picker">
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

export const ConversationItem = ({ conv }) => {
  const last_msg = conv.last_message;
  const cls1 = ["crp-entry"];
  if (last_msg && last_msg.data.from_id != conv.peer.id && conv.peer.is_saved_messages == true) {
      cls1.push("crp-entry-replied-same");
  }

  return html`
    <div class="${cls1.join(' ')}" onClick=${() => window.im?.selectChat(conv)}>
      <div class="crp-entry--image">
        <img src=${conv.peer.conversation_avatar_any} loading="lazy" />
      </div>
      <div class="crp-entry--info">
        <a>${ovk_proc_strtr(conv.peer.conversations_full_name, 30)}</a><br/>
        ${last_msg && html`<span>${last_msg.conv_date}</span>`}
      </div>
      <div class="crp-entry--message">
        ${last_msg != null && html`
        <div class="crp-entry--message---av">
          <img src="${last_msg.sender.avatar_any}" />
        </div>
        <div class="crp-entry--message---text" dangerouslySetInnerHTML=${{ __html: last_msg.conv_summary_with_attachments }} />`}
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

export const TabBar = ({ tabs, activeTab, onTabSelect }) => {
  return html`
    <div id="tabs-wr" class="messenger-app--global-tabs tabs">
      <div class="inner-tabs">
        ${tabs.map((tab) => html`
          <a data-tab="${tab.id}"
             id="${tab.id === activeTab ? 'activetabs' : ''}"
             class="tab"
             onClick=${() => onTabSelect(tab.id)}>
            ${tab.label ? (typeof tab.label == 'string' ? tab.label : tab.label()) : ""}
          </a>
        `)}
      </div>
      <div class="${activeTab == 'friends' ? 'hidden' : '' }" id="spec-actions">
        <a onclick=${() => { window.im.selectTab("friends")} }>${tr('to_friendslist')}</a>
      </div>
    </div>
  `;
};

export const SearchPage = ({ query }) => {
  return html`
    <input type="query" default="${tr('search_messages')}" value="${query}" />
  `;
};

export const FriendsPage = ({ friends, count, referrer, onFriendClick, onCreateChat, isSelected, onLoadMore }) => {
    return html`
        ${referrer == "chat_creation" && html`
        <div class="friends-list-top">
            <div class="inf">
                <p>${tr('create_chat_tip_1')}</p>
                <p>${tr('create_chat_tip_2')}</p>
            </div>
        </div>
        `}
        <div class="friends-list-top">
            <div class="inf">
                <input placeholder="${tr('search_sinister_noun')}" class="search_input" type="text" />
            </div>
        </div>
        <div class="friends-list ${referrer == 'chat_creation' ? 'friends-list-m' : ''}">
            ${friends.map((f) => html`
            <div class="friends-list-item ${isSelected(f) ? 'friends-selected' : ''}" onClick=${(e) => { onFriendClick(e, f) }}>
                <div class="inf">
                    <img src="${f.avatar_any}" class="friends-list-ava" />

                    <div>
                        <a class="friends-list-name">${f.full_name}</a>
                        <span class="friends-list-online">${f.online_status_str}</span>
                    </div>
                </div>
                ${referrer == "chat_creation" && html`
                    <div><input type="checkbox" /></div>
                `}
            </div>
            `)}
        </div>
        ${friends.length < count ? html`
            <div id="show_more" class="friends-load-more" onClick=${onLoadMore}>
                ${tr('show_next')}
            </div>
        ` : ''}
        ${referrer == "chat_creation" && html`
            <div class="friends-list-b">
                <input onClick=${(e) => { onCreateChat(e) }} class="button" type="button" value="${tr('create_chat_f')}" />
            </div>
        `}
  `;
};

export const ContactPage = ({ peer }) => {
  if (!peer) return html`<div class="messenger-page-stub"><p>${tr('no_user_selected')}</p></div>`;

  const user = peer.data;
  const lastSeen = user.last_seen ? new Date(user.last_seen.time * 1000).toLocaleString() : '';
  const name = window.escapeHtml(user.first_name + ' ' + user.last_name);

  return html`
    <div class="messenger-page-stub">
      <div class="contact-info">
        <img src="${avatar}" class="contact-info-ava" />
        <div class="contact-info-name">${name}</div>
        <div class="contact-info-online ${isOnline ? 'online' : 'offline'}">
          ${isOnline ? tr('online') : (lastSeen ? tr('last_seen') + ' ' + lastSeen : tr('offline'))}
        </div>
        <div class="contact-info-actions">
          <button class="button" onClick=${() => window.im?.selectChat(window.im.messenger.view.getChatWith({ id: user.id }))}>
            ${tr('write_message')}
          </button>
        </div>
      </div>
    </div>
  `;
};


export const PeerWindow = ({ peer, togglePeerInfo }) => {
    const isOnline = peer.online == 1;
    const avatar = peer.avatar_big || peer.data.photo_50 || '';
    const has_avatar = true;

    return html`
    <div class="back-side"><a onClick=${() => togglePeerInfo()}>${tr('back')}</a></div>
    <div class="peer-side">
        <div class="peer-info">
            <div class="peer-avatar">
                <img src=${avatar} alt=${tr('avatar')} />
                <a onClick=${(event) => { OpenAvatar(event, peer.avatar_max, peer.id + '_profile', peer.data.photo_pid) }} class="avatar-opener hoverable"></a>
            </div>
            <a href=${peer.page_url}>${escapeHtml(peer.full_name)}</a>
        </div>
        <div class="peer-actions">
            <a onClick=${() => { window.im.setChatByPeerId(peer.id) }}>${tr('write_message')}</a>
        </div>
        <div class="chat-members"></div>
        <div class="chat-media"></div>
    </div>
    `;
}
