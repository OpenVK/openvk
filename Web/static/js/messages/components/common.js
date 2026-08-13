import { html, render } from './render.js';

export const PeerTab = ({ conv, active }) => {
    console.log(conv)
    return html`
        <div class="messages--peers-tab${active ? ' selected' : ''}">
            <a onClick=${() => window.im?.messenger.selectConversation(conv)}>${conv.peer.conversations_name}</a>
            <span class="messages--peers-tab-close" onClick=${() => window.im?.closeChat(conv)}>×</span>
        </div>
    `;
};

export const PeerTabsView = ({ had_more_one_tab, tabs, currentChat }) => {
    //if (tabs.length < 2 && had_more_one_tab) { return html`` }

    console.log(currentChat)
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
    const current_user = window.im.state.getOperator();
    const corresponder = window.im.state.getCurrentConvo();

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
                <img class="ava" src=${current_user.avatar_any} alt=${current_user.full_name} />
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
                <img class="ava" src="${corresponder.peer.avatar_any || ''}"
                    alt="${corresponder.peer.full_name || ''}" />
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
        <div class="${cls1.join(' ')}" onClick=${() => window.im?.messenger.selectConversation(conv)}>
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
        ${conversations.length > 0 ? conversations.map((conv) => html`<${ConversationItem} conv=${conv} />`) : html`<${ConversationsListError} />`}
        ${hasMore && html`
        <div onClick=${onLoadMore} id="show_more" class="crp-load-more">
            ${tr('show_next')}
        </div>
        `}
    </div>
  `;
};

export const TabBar = ({ tabs, activeTab, onTabSelect }) => {
    console.log(activeTab.getPageId())
    const showContactButton = activeTab.getPageId() == "messenger";
    const showFriendsButton = activeTab.getPageId() != "friends";
    const showSettingsButton = activeTab.getPageId() == "conversations";
    const showSpecActions = showSettingsButton || showContactButton || showFriendsButton;

    return html`
        <div class="messenger-app--global-tabs tabs">
            <div class="inner-tabs">
                ${tabs.map((tab) => html`
                <a data-tab="${tab.getId()}"
                    id="${tab.isActive() ? 'activetabs' : ''}"
                    class="tab"
                    onClick=${() => onTabSelect(tab)}>
                    ${tab.getName()}
                </a>
                `)}
            </div>
            <div class="${showSpecActions == false ? 'hidden' : '' }" id="spec-actions">
                ${showContactButton ? html`
                    <a onclick=${() => { window.im.openTabByName("contact") }}>${tr('about_peer')}</a>
                    <span class="tab-divider">|</span>
                ` : '' }
                ${showSettingsButton ? html`
                    <a onclick=${() => { window.im.openTabByName("settings") }}>${tr("messenger_tab_settings")}</a>
                    <span class="tab-divider">|</span> 
                ` : ""}
                ${showFriendsButton ? html`<a onclick=${() => { window.im.openTabByName("friends")} }>${tr('to_friendslist')}</a>` : ""}
            </div>
        </div>
    `;
};

export const PeerWindow = ({ fromConvo, convo, togglePeerInfo }) => {
    const peer = convo.peer;
    const supposed_type = peer.supposed_type;
    const isOnline = peer.online == 1;
    const avatar = peer.avatar_big || peer.data.photo_50 || '';
    const has_avatar = true;
    const is_from_chat = fromConvo.supposed_type == "chat" && peer.supposed_type != "chat";

    return html`
    <div class="peer-window">
    <div class="back-side"><a onClick=${() => { window.im.openTabByName("messenger") }}>${tr('back')}</a></div>
    <div class="peer-side">
        <div class="peer-info">
            <div class="peer-avatar sliding-thing-wrapper">
                <img src=${avatar} alt=${tr('avatar')} />
                <a onClick=${(event) => { OpenChatAvatar(event, peer) }} class="avatar-opener sliding-thing">
                    <div class="lupa"></div>
                </a>
            </div>
            <div class="peer-name">
                <div class="peer-name-1">
                    <a class="peer-link" href=${peer.page_url}>${escapeHtml(peer.full_name)}</a>

                    <div class="peer-status">
                        <span dangerouslySetInnerHTML=${{ __html: peer.online_status_str }} />
                    </div>
                </div>

                <div class="peer-actions-1">
                    <a class="button" onClick=${() => { window.im.setChatByPeerId(peer.id) }}>${tr('write_message')}</a>
                </div>
            </div>
        </div>
        <div class="chat-actions-common">
            ${ peer.canUpdateTitle() ? html`
                <a onClick=${(e) => { updateChatTitle(e, peer) }}>Обновить название</a>
            ` : "" }
            ${ peer.canUpdateAvatar() ? html`
                <a onClick=${(e) => { updateChatAvatar(e, peer) }}>Обновить аватар беседы</a>
            ` : "" }
            ${ peer.canLeaveChat() ? html`
                <a>Выйти</a>
            ` : "" }
            <a>Настройки</a>
            <a onClick=${(e) => { peer.showAsJson() }}>ПОКАЖИ JSON</a>
            <a>Поиск по сообщениям</a>
            ${ peer.canViewInviteLinks() && html`<a>Пригласительные ссылки</a>` }
        </div>
        ${is_from_chat === true && html`
        <div class="chat-actions-usr chat-actions-common">
            <a><b>Кикнуть</b></a>
        </div>
        `}
        <div class="chat-actions-2 chat-actions-common">
            <a onClick=${(e) => { window.im.messenger.view.setSpecialMode("pinned") }}><b>Закреплённое</b></a>
            <a onClick=${(e) => { window.im.messenger.view.setSpecialMode("photos") }}><b>Фото</b> (100)</a>
            <a onClick=${(e) => { window.im.messenger.view.setSpecialMode("videos") }}><b>Видео</b> (100)</a>
            <a onClick=${(e) => { window.im.messenger.view.setSpecialMode("audios") }}><b>Аудио</b> (100)</a>
            <a onClick=${(e) => { window.im.messenger.view.setSpecialMode("documents") }}><b>Документы</b> (100)</a>
        </div>
        <div class="chat-members"></div>
        <div class="chat-media"></div>
    </div>
    </div>
    `;
}

export const ConversationsListError = ({ }) => {
    return html`
        <div class="conversations_error_page">
            <span>${tr("zero_conversations_error")}</span>
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
