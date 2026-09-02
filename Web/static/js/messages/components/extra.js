import { html, render } from './render.js';
import { ChatGeneralForm } from './messages.js';
import { MessageBubble } from './message.js';
import { PeerAvatar } from './common.js';

export const FriendsPageTemplate = ({ friends, count, referrer, onFriendClick, onSubmit, isSelected, onLoadMore, onTitleChangeClick, onSearch }) => {
    const isChatCreation = referrer == "chat_creation";
    const isAdd = referrer == "add_new";

    console.log(friends)
    return html`
        <div class="messenger-app--tab-friends">
            <div>
                <div class="friends-list ${isChatCreation || isAdd ? 'friends-list-m' : ''}">
                    ${friends.map((f) => html`
                    <div class="friends-list-item ${isSelected(f) ? 'friends-selected' : ''}" onClick=${(e) => { onFriendClick(e, f) }}>
                        <div class="inf">
                            <img src="${f.getAvatar()}" class="friends-list-ava" />

                            <div>
                                <a class="friends-list-name">${f.getName()}</a>
                                <span class="friends-list-online">${f.getOnlineStatusString()}</span>
                            </div>
                        </div>
                        ${isChatCreation || isAdd ? html`
                            <div><input type="checkbox" checked=${isSelected(f)} style="pointer-events: none;" /></div>
                        ` : ""}
                    </div>
                    `)}
                </div>
                ${friends.length < count ? html`
                    <div id="show_more" class="friends-load-more" onClick=${onLoadMore}>
                        ${tr('show_next')}
                    </div>
                ` : ''}
            </div>
            <div>
                ${isChatCreation && html`
                <div class="friends-list-top">
                    <div style="display: grid;grid-template-columns: 2fr 1fr;">
                        <div class="chat_prev">
                            <div class="avtr">
                                <img src="${ChatGeneralForm.CHAT_NO_AVATAR}" />
                            </div>
                            <div style="display: flex;flex-direction: column;justify-content: center;">
                                <b id="_name" onClick=${(e) => { onTitleChangeClick(e) }}>...</b>
                                <p id="_m_count">${tr("members_count", 1)}</p>
                            </div>
                        </div>
                        <div class="inf">
                            <p>${tr('create_chat_tip_1')}</p>
                            <p>${tr('create_chat_tip_2')}</p>
                        </div>
                    </div>
                    <div class="friends-list-b">
                        <input onClick=${(e) => { onSubmit(e) }} class="button" type="button" value="${tr('create_chat_f')}" />
                    </div>
                </div>
                `}
                ${isAdd && html`
                <div class="friends-list-top">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                        <div class="inf">
                            <p style="margin: 0; color: var(--text-2ary);">${tr('add_chat_members_tip_1')}</p>
                        </div>
                        <div class="friends-list-b">
                            <input onClick=${(e) => { onSubmit(e) }} class="button" type="button" value="${tr('chat_add_members')}" />
                        </div>
                    </div>
                </div>
                `}
                <div class="friends-list-top">
                    <div class="inf">
                        <input onChange=${(e) => { onSearch(e) }} placeholder="${tr('search_sinister_noun')}" class="search_input" type="text" />
                    </div>
                </div>
            </div>
        </div>
  `;
};

export const SearchPageTemplate = ({ q, c, onSearch }) => {
    const query = q;
    const count = c.total_count;
    const items = c.items;
    const loaded_count = items.length;

    return html`
        <div id="search-page-im">
            <div class="search-up">
                <input class="search_input" onChange=${(e) => { onSearch(e, true) }} type="text" default="${tr('search_messages')}" value="${query}" />
            </div>
            <div class="search-summary">
                <b>${tr("messages_search_count", count)}</b>
            </div>
            <div>
                ${items.map((msg) => {
                    return html`<${MessageBubble} msg=${msg} fromSearch=1 />`
                })}
            </div>
            ${loaded_count < count && html`
            <div onClick=${(e) => { window.im.search.moveOffset() }} class="show_more crp-load-more">
                ${tr('show_next')}
            </div>`}
        </div>
  `;
};

export const FastChatsBar = ({ pinnedItems, convos }) => {
    return html`
        <div>
            <div class="fastchat_items">
                ${pinnedItems.map((item) => {
                    const peer = item.peer;
                    return html`
                    <div title="${peer.getName()}" onClick=${(e) => { window.im.fastChats.selectConversation(e, item) }} class="fastchat_item ${!item.isRead() ? "unread" : ""}">
                        <div class="fastchat_unread">+${item.unread_count}</div>
                        <div class="fastchat_close"></div>
                        <${PeerAvatar} peer=${peer} orig_ava=${false} />
                    </div>`
                })}
            </div>
            <div onClick=${() => {window.im.fastChats.onEntryPointClick()}} class="fastchat_entrypoint">
                <span>${convos.total_convs}</span>
            </div>
        </div>
    `;
}

export const FastChatsWindow = () => {
    return html`
        <div id="fastchat_item">
            <b></b>
        </div>
    `;
}
