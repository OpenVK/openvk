import { html, render } from './render.js';
import { ChatGeneralForm } from './messages.js';

export const PeerAvatar = ({ peer, className = "", loading = "lazy", saved_messages_ava = true }) => {
    if (!peer) {
        return html`<img class="${className}" src="/assets/packages/static/openvk/img/im/chat_meaningless.jpg" loading="${loading}" />`;
    }

    if (peer.id === window.im.state.getId()) {
        if (!saved_messages_ava) {
            return html`<div style="display:block;width:52px;height:52px;"></div>`;
        }

        return html`<img class="${className}" src=${ChatGeneralForm.SAVED_MESSAGES_AVATAR} loading="${loading}" />`;
    }

    if (peer.supposed_type === 'chat' && !peer.has_custom_avatar) {
        const avatars = peer.getMosaicAvatars() || [];
        const cell0 = avatars[0] || null;
        const cell1 = avatars[1] || null;
        const cell2 = avatars[2] || null;
        const cell3 = avatars[3] || null;

        if (avatars.length == 1 || (!cell0 && !cell1 && !cell2 && !cell3)) {
            return html`<img class="${className}" src="/assets/packages/static/openvk/img/im/chat_meaningless.jpg" loading="${loading}" />`;
        }

        if (avatars.length == 2) {
            // "object-position: left;" для парных аватарочек ^_^
            return html`
            <div class="chat_table_avatar chat_table_avatar_double ${className}">
                ${cell0 ? html`<img style="object-position: left;" class="chat_table_avatar_cell" src="${cell0}" loading="${loading}" />` : ''}
                ${cell1 ? html`<img style="object-position: right;" class="chat_table_avatar_cell" src="${cell1}" loading="${loading}" />` : ''}
            </div>`;
        }

        if (avatars.length == 3) {
            return html`
            <div class="chat_table_avatar chat_table_avatar_third ${className}">
                ${cell0 ? html`<img style="width: 50%;" class="chat_table_avatar_cell" src="${cell0}" loading="${loading}" />` : ''}
                <div style="width: 50%;display:inline-block;">
                ${cell1 ? html`<img style="height: 50%;" class="chat_table_avatar_cell" src="${cell1}" loading="${loading}" />` : ''}
                ${cell2 ? html`<img style="height: 50%;" class="chat_table_avatar_cell" src="${cell1}" loading="${loading}" />` : ''}
                </div>
            </div>`;
        }

        return html`
            <div class="chat_table_avatar chat_table_avatar_more3 ${className}">
                ${cell0 ? html`<img style="height:50%;width: 50%;" class="chat_table_avatar_cell" src="${cell0}" loading="${loading}" />` : ''}
                ${cell1 ? html`<img style="height:50%;width: 50%;" class="chat_table_avatar_cell" src="${cell1}" loading="${loading}" />` : ''}
                ${cell2 ? html`<img style="height:50%;width: 50%;" class="chat_table_avatar_cell" src="${cell2}" loading="${loading}" />` : ''}
                ${cell3 ? html`<img style="height:50%;width: 50%;" class="chat_table_avatar_cell" src="${cell3}" loading="${loading}" />` : ''}
            </div>;
        `;
    }

    const src = peer.getAvatar("mid", true);
    return html`<img class="${className}" src=${src} loading="${loading}" />`;
};

export const PeerTab = ({ conv, active, page }) => {
    return html`
        <div class="messages--peers-tab${active ? ' selected' : ''} ${ !conv.isRead() ? 'unread' : ''}">
            <a onClick=${() => window.im?.messenger.selectConversation(conv)}>${conv.peer.getName(true, true)}</a>
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
        if (msg.can("delete") == false) {
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

export const InputArea = ({ editMsg, replyTo, onRemoveReply, onSend, onKeyPress, currentDraft, onInput, togglePeerInfo, clickOnReply, convo }) => {
    const is_editing = editMsg != null;
    const current_user = window.im.state.getOperator();
    const corresponder = window.im.state.getCurrentConvo();
    const cls = [
        "messenger-app-end",
        (replyTo || editMsg) ? 'm-selected' : '',
        convo.hasScrollPosition() && (!editMsg && !replyTo) ? "m-mountain m-mountain-fatal" : "",
    ]

    return html`
    <div class="${cls.join(" ")}">
        ${ replyTo && html`
            <div class="input-reply input-m">
                <span onclick=${() => { clickOnReply(replyTo) }} aria-label="link" class="input-type">${tr("reply_to", replyTo.sender.getName())}</span>
                <span class="input-close" onClick=${onRemoveReply}><div class="cross"></div></span>
            </div>
        `}
        ${ editMsg && html`
            <div class="input-edit input-m">
                <span onclick=${() => { clickOnReply(editMsg) }} aria-label="link" class="input-type">${tr("edit_of_message")}</span>
                <span class="input-close" onClick=${(e) => { window.im.messenger.cancelEdit() }}><div class="cross"></div></span>
            </div>
        `}
        <div class="messenger-mountain" onClick=${(e) => {window.im.messenger.view.scrollToEndOfChat(e, convo)}}>
            ${tr("viewing_old_messages")}
        </div>
        <div class="post-buttons">
            <div class="model_content_textarea messenger-app--input has_emoji_picker expanded-textarea" id="write">
                <img class="ava" src=${current_user.getAvatar("mid", false)} alt=${current_user.getName()} />
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
                <${PeerAvatar}
                    peer=${replyTo ? replyTo.sender : corresponder.peer}
                    className="ava ava2"
                    loading="eager"
                    saved_messages_ava=${false} />
            </div>
        </div>
    </div>
  `;
};

export const ConversationItem = ({ conv }) => {
    const last_msg = conv.last_message;
    const peer = conv.peer;
    const has_activity = conv.hasActivity();
    const cls1 = ["crp-entry"];
    if (last_msg && (last_msg.data.from_id == peer.id || peer.isSavedMessages() == true)) {
        cls1.push("crp-entry-replied-same");
    }
    if (!conv.isRead()) {
        cls1.push("unread");
    }

    // здесь появился соблазн добавить && peer.data.members_count > 3 чтобы число участников показывалось только если в беседе много людей
    // с одной стороны по названию или аватарке и так понятно, что это беседа, но название и аватарка могут быть изменены
    const d = last_msg != null && has_activity == false;
    return html`
        <div class="${cls1.join(' ')}" onClick=${() => window.im?.messenger.selectConversation(conv, true)}>
        <div class="crp-entry--image">
            <${PeerAvatar} peer=${peer} />
        </div>
        <div class="crp-entry--info">
            <a>${ovk_proc_strtr(peer.getName(true), 30)}</a>
            ${peer.supposed_type == "chat" && peer.data.members_count ? html`<span>${tr("members_count", peer.data.members_count)}</span>` : ""}
            ${last_msg && html`<span>${last_msg.getDate(2)}</span>`}
        </div>
        <div class="crp-entry--message">
            ${d && html`
            <div class="crp-entry--message---av">
                <img src="${last_msg.sender.getAvatar("mid", false)}" />
            </div>
            <div class="crp-entry--message---text" dangerouslySetInnerHTML=${{ __html: last_msg.getText(false, true, true) }} />`}
            ${has_activity == true && html`
                <div class="crp-entry--message---av"></div>
                <div class="crp-entry--message---text">
                    ${(conv.getActivityMsg()[0] || "").toLowerCase()}
                </div>
            `}
            <div class="unread-msgs-count">+${conv.unread_count}</div>
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
    const showSettingsButton = window.im.state.is_compact_mode_enabled || !window.im.state.is_group && activeTabName == "conversations";
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
    const avatar = peer.getAvatar("big");
    const has_avatar = true;
    const is_from_chat = fromConvo.supposed_type == "chat" && peer.supposed_type != "chat";
    const is_club_related = peer.supposed_type == "club" || window.im.state.getOperator().supposed_type == "club";

    return html`
    <div class="peer-window">
    <div class="back-side"><a onClick=${() => { window.im.openTabByName("messenger") }}>${tr('back')}</a></div>
    <div class="peer-side">
        <div class="peer-info">
            <div class="peer-avatar sliding-thing-wrapper">
                <${PeerAvatar} saved_messages_ava=${false} peer=${peer} />
                <a onClick=${(event) => { OpenChatAvatar(event, peer) }} class="avatar-opener sliding-thing">
                    <div class="lupa"></div>
                </a>
            </div>
            <div class="peer-name">
                <div class="peer-name-1">
                    <a class="peer-link" href=${peer.getPageUrl()}>${peer.getName()}</a>

                    <div class="peer-status">
                        <span dangerouslySetInnerHTML=${{ __html: peer.getOnlineStatusString() }} />
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
                    <a onClick=${(e) => {
                        window.im.openTabByName("search", true, {
                            "q": "",
                            "peer_id": peer.id
                        })
                    }}>${tr("convo_search_messages")}</a>
                    ${ window.im.state.is_debug ? html`
                        <a onClick=${(e) => {  fastError(`<textarea>${JSON.stringify(peer.data, null, 4)}</textarea>`); }}>JSON</a>
                    ` : ""}
                    ${is_from_chat === true && html`
                    <div class="chat-actions-usr chat-actions-common">
                        <a><b>${tr("convo_action_kick")}</b></a>
                    </div>
                    `}
                    ${ (is_club_related && peer.isClubMessagesBlocked()) ? html`
                        <div class="chat-actions-usr chat-actions-common" onClick="${async (e) => {await peer.toggleClubMessagesBlockness(e, "enable")}}">
                            <a>${tr("group_allow_messages")}</a>
                        </div>
                    ` : ""}
                    ${ (is_club_related && !peer.isClubMessagesBlocked()) ? html`
                        <div class="chat-actions-usr chat-actions-common" onClick="${async (e) => {await peer.toggleClubMessagesBlockness(e, "disable")}}">
                            <a>${tr("group_deny_messages")}</a>
                        </div>
                    ` : ""}
                </div>
                <b> ${tr("chat_media")} </b>
                <div class="chat-tab-column chat-actions-2 chat-actions-common chat-actions-media">
                    <a onClick=${(e) => { window.im.messenger.viewMedia("pinned") }}>${tr("chat_pinned")}</a>
                    <a style="display:none;" onClick=${(e) => { window.im.messenger.viewMedia("photos") }}>${tr("chat_media_photo")} <span>100</span></a>
                    <a style="display:none;" onClick=${(e) => { window.im.messenger.viewMedia("videos") }}>${tr("chat_media_video")} <span>100</span></a>
                    <a style="display:none;" onClick=${(e) => { window.im.messenger.viewMedia("audios") }}>${tr("chat_media_audio")} <span>100</span></a>
                    <a style="display:none;" onClick=${(e) => { window.im.messenger.viewMedia("documents") }}>${tr("chat_media_doc")} <span>100</span></a>
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
