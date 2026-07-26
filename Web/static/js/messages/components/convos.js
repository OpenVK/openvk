import { html, render } from '../im.js';

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

export const WriteBar = ({ convo }) => {
    let cls = ["messenger-app-status"];
    const a = convo.getActivityMsg();

    if (a[1].length > 0) {
        cls.push("shown");
    }

    return html`
        <div class="${cls.join(' ')}">
            <div class="write-bar">
                ${a[0]}
            </div>
        </div>
    `;
}

export const InputArea = ({ editMsg, replyTo, onRemoveReply, onSend, onKeyPress, currentDraft, onInput, togglePeerInfo, clickOnReply }) => {
    const is_editing = editMsg != null;

    return html`
    <div class="messenger-app-end${(replyTo || editMsg) ? ' m-selected' : ''}">
      ${ replyTo && html`
        <div class="input-reply input-m">
          <span onclick=${() => { clickOnReply(replyTo) }} aria-label="link" class="input-type">${escapeHtml(tr("reply_to", replyTo.sender.full_name))}</span>
          <span class="input-close" onClick=${onRemoveReply}>×</span>
        </div>
      `}
       ${ editMsg && html`
        <div class="input-edit input-m">
          <span onclick=${() => { clickOnReply(editMsg) }} aria-label="link" class="input-type">edit of message</span>
          <span class="input-close" onClick=${(e) => { window.im.messenger.view.cancelEdit() }}>×</span>
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
              <button class="button" onClick=${onSend}>${ !is_editing ? tr('send') : tr('edit_action_lr')}</button>
              <${AttachmentMenu} />
            </div>
          </div>
          <img class="ava" src="${window.im?.corresponder?.avatar_any || ''}"
               alt="${window.im?.corresponder?.full_name || ''}" />
        </div>
      </div>
    </div>
  `;
};

export const ConversationItem = ({ conv }) => {
    const last_msg = conv.last_message;
    const has_activity = conv.hasActivity();
    const cls1 = ["crp-entry"];
    if (last_msg && (last_msg.data.from_id != conv.peer.id || conv.peer.is_saved_messages == true)) {
        cls1.push("crp-entry-replied-same");
    }

    const d = last_msg != null && has_activity == false;
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
            ${d && html`
            <div class="crp-entry--message---av">
                <img src="${last_msg.sender.avatar_any}" />
            </div>
            <div class="crp-entry--message---text" dangerouslySetInnerHTML=${{ __html: last_msg.conv_summary_with_attachments }} />`}
            ${has_activity == true && html`
                <div class="crp-entry--message---av"></div>
                <div class="crp-entry--message---text">
                    ${(conv.getActivityMsg()[0] || "").toLowerCase()}
                </div>
            `}
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
    const showSpecActions = activeTab != "friends";

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
        <div class="${showSpecActions == false ? 'hidden' : '' }" id="spec-actions">
            ${activeTab == "messenger" ? html`
                <a onclick=${() => { window.im.messenger.view.togglePeerInfo() }}>${tr('about_peer')}</a>
                <span class="tab-divider">|</span>
            ` : '' }
            <a onclick=${() => { window.im.selectTab("friends")} }>${tr('to_friendslist')}</a>
        </div>
        </div>
    `;
};

export const PeerWindow = ({ peer, togglePeerInfo }) => {
    const supposed_type = peer.supposed_type;
    const isOnline = peer.online == 1;
    const avatar = peer.avatar_big || peer.data.photo_50 || '';
    const has_avatar = true;

    return html`
    <div class="back-side"><a onClick=${() => togglePeerInfo()}>${tr('back').toLowerCase()}</a></div>
    <div class="peer-side">
        <div class="peer-info">
            <div class="peer-avatar">
                <img src=${avatar} alt=${tr('avatar')} />
                <a onClick=${(event) => { OpenAvatar(event, peer.avatar_max, peer.id + '_profile', peer.data.photo_pid) }} class="avatar-opener hoverable"></a>
            </div>
            <div class="peer-name">
                <div class="peer-name-1">
                    <a class="peer-link" href=${peer.page_url}>${escapeHtml(peer.full_name)}</a>

                    ${peer.supposed_type != 'chat' ? html`
                    <div class="peer-status">
                        <span dangerouslySetInnerHTML=${{ __html: peer.online_status_str}} />
                    </div>`: ''}
                </div>

                <div class="peer-actions-1">
                    <a onClick=${() => { window.im.setChatByPeerId(peer.id) }}>${tr('write_message')}</a>
                </div>
            </div>
        </div>
        <div>
            ${ peer.canUpdateTitle() ? html`
                <a onClick=${(e) => { updateChatTitle(e, peer) }}>Обновить название</a>
            ` : "" }
            ${ peer.canUpdateAvatar() ? html`
                <a onClick=${(e) => { updateChatAvatar(e, peer) }}>Обновить аватар беседы</a>
            ` : "" }
        </div>
        <div class="chat-actions-2">
            <a onClick=${(e) => { window.im.messenger.view.setSpecialMode("pinned") }}>Закреплённое</a>
            <a>Фото (100)</a>
            <a>Видео (100)</a>
            <a>Аудио (100)</a>
        </div>
        <div class="chat-members"></div>
        <div class="chat-media"></div>
    </div>
    `;
}

export const ErrorConversation = ({ }) => {
    return html`
        <div>
            <span>Select a convo</span>
        </div>
    `
}
