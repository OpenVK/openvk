import { html, render } from './render.js';

export const FriendsPageTemplate = ({ friends, count, referrer, onFriendClick, onCreateChat, isSelected, onLoadMore }) => {
    return html`
        <div class="messenger-app--tab-friends">
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
        </div>
  `;
};

export const SearchPageTemplate = ({ q, c }) => {
    const query = q;
    const count = c.total_count;
    const items = c.items;
    const loaded_count = items.length;

    return html`
        <div id="search-page-im">
            <div class="search-up">
                <input class="search_input" onChange=${(e) => { window.im.conversations._onMessagesSearch(e) }} type="text" default="${tr('search_messages')}" value="${query}" />
            </div>
            <div class="search-summary">
                <b>${tr("messages_search_count", count)}</b>
            </div>
            <div>
                ${items.map((msg) => {
                    return html`<${MessageBubble} msg=${msg} />`
                })}
            </div>
            ${loaded_count < count && html`
            <div onClick=${(e) => { window.im.search.moveOffset() }} class="show_more crp-load-more">
                ${tr('show_next')}
            </div>`}
        </div>
  `;
};
