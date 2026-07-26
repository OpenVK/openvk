import { html, render } from '../im.js';

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
