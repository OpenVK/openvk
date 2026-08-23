import { html, render } from './render.js';

export const PeerTab = ({ conv, active, page }) => {
    return html`
        <div class="messages--peers-tab${active ? ' selected' : ''} ${ !conv.is_read ? 'unread' : ''}">
            <a onClick=${() => window.im?.messenger.selectConversation(conv)}>${conv.peer.conversations_name}</a>
            <span class="messages--peers-tab-counter">+${conv.unread_count}</span>
            <span class="messages--peers-tab-close" onClick=${() => window.im?.messenger.closeChat(conv, page)}>
                <div class="cross ${active ? "white" : ""}"></div>
            </span>
        </div>
    `;
};

export const PeerTabsView = ({ had_more_one_tab, tabs, currentChat, page }) => {
    //if (tabs.length < 2 && had_more_one_tab) { return html`` }

    console.log(currentChat)
    return html`
        <div class="messages--peers-tabs">
            ${tabs.map((tab, idx) => html`
                <${PeerTab} conv=${tab} active=${idx === currentChat} page=${page} />
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
                <span class="input-close" onClick=${onRemoveReply}><div class="cross"></div></span>
            </div>
        `}
        ${ editMsg && html`
            <div class="input-edit input-m">
                <span onclick=${() => { clickOnReply(editMsg) }} aria-label="link" class="input-type">${tr("edit_of_message")}</span>
                <span class="input-close" onClick=${(e) => { window.im.messenger.cancelEdit() }}><div class="cross"></div></span>
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
                <img class="ava ava2" src="${replyTo ? replyTo.sender.avatar_any : corresponder.peer.avatar_any || ''}"
                    alt="${replyTo ? replyTo.sender.full_name : corresponder.peer.full_name || ''}" />
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
    if (!conv.is_read) {
        cls1.push("unread");
    }

    const d = last_msg != null && has_activity == false;
    return html`
        <div class="${cls1.join(' ')}" onClick=${() => window.im?.messenger.selectConversation(conv, true)}>
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
            <div class="unread-msgs-count">${conv.unread_count}+</div>
        </div>
        </div>
    `;
};

export const ConversationListView = ({ conversations, hasMore, onLoadMore, onCreateChat, onSearch }) => {
    const is_group = window.im.state.is_group;

    return html`
        <div id="conversations-top-buttons">
            <div id="conversations-search-bar">
                <input class="search_input" type="text" placeholder="${tr('search_messages')}" onChange=${onSearch} />
            </div>
            ${ !is_group ? html`<input type="button" class="button" value="${tr('create_chat')}" onClick=${onCreateChat} />` : "" }
        </div>
        <div class="crp-list">
            ${conversations.length > 0 ? conversations.map((conv) => html`<${ConversationItem} conv=${conv} />`) : html`<${ConversationsListError} is_group=${is_group} />`}
            ${hasMore && html`
            <div onClick=${onLoadMore} id="show_more" class="crp-load-more">
                ${tr('show_next')}
            </div>
            `}
        </div>
    `;
};

export const TabBar = ({ tabs, activeTab, onTabSelect }) => {
    let activeTabName = "";

    try {
        activeTabName = activeTab.getPageId();
    } catch(e) {
        console.error(e);
    }

    const showContactButton = activeTabName == "messenger";
    const showFriendsButton = !window.im.state.is_group && activeTabName != "friends";
    const showSettingsButton = !window.im.state.is_group && activeTabName == "conversations";
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
                    <a class="button" onClick=${() => { window.im.messenger.selectConversation(peer) }}>${tr('write_message')}</a>
                </div>
            </div>
        </div>
        <div class="chat-btns">
            <div class="chat-tab-1 chat-actions-common">
                ${ peer.supposed_type == "chat" && (peer.can("update_title") || peer.can("update_avatar")) ? html`
                <b>${ tr("chat_actions") }</b>
                <div class="chat-tab-column">
                    ${ peer.can("update_title") ? html`
                        <a onClick=${(e) => { updateChatTitle(e, peer) }}>${tr("change_chat_title")}</a>
                    ` : "" }
                    ${ peer.can("update_avatar") ? html`
                        <a onClick=${(e) => { updateChatAvatar(e, peer) }}>${tr("change_chat_avatar")}</a>
                    ` : "" }
                </div>       
                ` : ""}

                <b>${ tr("actions") }</b>
                <div class="chat-tab-column">
                    ${ peer.can("view_invite_links") && html`<a>${tr("convo_invite_links")}</a>` }
                    <a>${tr("convo_search_messages")}</a>
                    ${ window.im.state.is_debug ? html`
                        <a onClick=${(e) => { peer.showAsJson() }}>JSON</a>
                    ` : ""}
                    ${is_from_chat === true && html`
                    <div class="chat-actions-usr chat-actions-common">
                        <a><b>${tr("convo_action_kick")}</b></a>
                    </div>
                    `}
                    ${ (peer.supposed_type == "club" && peer.is_club_messages_blocked) ? html`
                        <div class="chat-actions-usr chat-actions-common" onClick="${(e) => {peer.toggleClubMessages(e, true)}}">
                            <a><b>${tr("group_allow_messages")}</b></a>
                        </div>
                    ` : ""}
                    ${ (peer.supposed_type == "club" && !peer.is_club_messages_blocked) ? html`
                        <div class="chat-actions-usr chat-actions-common" onClick="${(e) => {peer.toggleClubMessages(e, false)}}">
                            <a><b>${tr("group_deny_messages")}</b></a>
                        </div>
                    ` : ""}
                </div>
                <b> ${tr("chat_media")} </b>
                <div class="chat-tab-column chat-actions-2 chat-actions-common chat-actions-media">
                    <a onClick=${(e) => { window.im.messenger.viewMedia("pinned") }}><b>${tr("chat_pinned")}</b></a>
                    <a onClick=${(e) => { window.im.messenger.viewMedia("photos") }}><b>${tr("chat_media_photo")}</b> <span>100</span></a>
                    <a onClick=${(e) => { window.im.messenger.viewMedia("videos") }}><b>${tr("chat_media_video")}</b> <span>100</span></a>
                    <a onClick=${(e) => { window.im.messenger.viewMedia("audios") }}><b>${tr("chat_media_audio")}</b> <span>100</span></a>
                    <a onClick=${(e) => { window.im.messenger.viewMedia("documents") }}><b>${tr("chat_media_doc")}</b> <span>100</span></a>
                </div>
            </div>
            ${ peer.supposed_type == "chat" ? html`
                <div class="chat-tab-2">
                    <div>
                        <div>
                            <b>${tr("participants")}</b> ()
                        </div>
                        <div>
                            ${ peer.can("leave_chat") ? html`
                                <a>${tr("leave_chat")}</a>
                            ` : "" }
                            ${ peer.can("invite_new") ? html`
                                <a onClick=${(e) => { window.im.openTabByName("friends", true, {
                                    "referrer": "add_new",
                                    "convo_id": peer.id
                                }) }}>${tr("chat_add_members_ext")}</a>
                            ` : "" }
                        </div>
                    </div>
                    <div class="chat-members"></div>
                </div>
            ` : ""}
        </div>
    </div>
    </div>
    `;
}

export const ConversationsListError = ({ is_group }) => {
    return html`
        <div class="conversations_error_page">
            <span>${is_group ? tr("zero_conversations_error_club") : tr("zero_conversations_error")}</span>
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
    <div id="chat-topic" style="margin-top: 140px;">
        <span class="t1">${tr("topic_going_in_chat")}</span>
        <div class="chat-topic-preview">
            <img src="{$chat->getPhotoURL("miniscule")}" alt="chat" />
            <div>
                <b style="display: block;">{$chat->getTitle()}</b>
                <span>сколько-то участников</span>
            </div>
        </div>
        <div class="t3">
            <input value="${tr("chat_join")}" class="button" type="button" onClick=${(event) => openChatTopic(event, chat_id)} />
        </div>
    </div>
    `;
}
