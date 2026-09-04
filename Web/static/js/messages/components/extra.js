import { html, render } from './render.js';
import { ChatGeneralForm } from './messages.js';
import { MessageBubble } from './message.js';
import { PeerAvatar, formatTime, formatDate, getAppLocale } from './common.js';
import { imLog } from '../logger.js';

export const FriendsPageTemplate = ({ friends, count, referrer, onFriendClick, onSubmit, isSelected, onLoadMore, onTitleChangeClick, onSearch }) => {
    const isChatCreation = referrer == "chat_creation";
    const isAdd = referrer == "add_new";

    imLog("Friends list:", friends);
    return html`
        <div class="messenger-app--tab-friends">
            <div>
                <div class="friends-list-top">
                    <div class="inf">
                        <input onChange=${(e) => { onSearch(e) }} placeholder="${tr('search_sinister_noun')}" class="search_input" type="text" />
                    </div>
                </div>
                <div class="friends-list ${isChatCreation || isAdd ? 'friends-list-m' : ''}">
                    ${friends.map((f) => html`
                    <div class="friends-list-item ${isSelected(f) ? 'friends-selected' : ''}" onClick=${(e) => { onFriendClick(e, f) }}>
                        <div class="inf2">
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
                        <hr />
                    </div>
                    `)}
                </div>
                ${friends.length < count ? html`
                    <div id="show_more" class="friends-load-more" onClick=${onLoadMore}>
                        ${tr('show_next')}
                    </div>
                ` : ''}
            </div>
            <div class="friends-list-side">
                ${isChatCreation && html`
                <div class="friends-list-side-item sticky">
                    <div>
                        <div class="chat_prev">
                            <div style="display: flex;flex-direction: column;justify-content: center;">
                                <input type="text" id="_name" onInput=${(e) => { onTitleChangeClick(e) }} />
                                <p id="_m_count">${tr("members_count", 1)}</p>
                            </div>
                        </div>
                        <div class="inf">
                            <p>${tr('create_chat_tip_1')}</p>
                        </div>
                    </div>
                    <div class="friends-list-b">
                        <input onClick=${(e) => { onSubmit(e) }} class="button" type="button" value="${tr('create_chat_f')}" />
                    </div>
                </div>
                `}
                ${isAdd && html`
                <div class="friends-list-side-item sticky">
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
                ${!(isChatCreation || isAdd) ? html`
                    <p><a href="/invite">${tr("messenger_friends_info_1")}</a></p>
                    <p><a href="/friends${window.openvk.current_id}?act=incoming">${tr("messenger_friends_info_2")}</a></p>
                ` : ""}
            </div>
        </div>
  `;
};

function formatSearchDate(timestamp) {
    if (!timestamp) return "";
    const date = new Date(typeof timestamp === "number" && timestamp < 10000000000 ? timestamp * 1000 : timestamp);
    if (isNaN(date.getTime())) return "";
    const today = new Date();
    const isToday = date.toDateString() === today.toDateString();
    
    if (isToday) {
        return date.toLocaleTimeString(getAppLocale ? getAppLocale() : 'ru-RU', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    }

    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    const isYesterday = date.toDateString() === yesterday.toDateString();
    const timeStr = date.toLocaleTimeString(getAppLocale ? getAppLocale() : 'ru-RU', {
        hour: '2-digit',
        minute: '2-digit'
    });

    if (isYesterday) {
        return (tr("yesterday") || "вчера") + " " + timeStr;
    }

    const dayStr = formatDate(date, { month: '2-digit', day: '2-digit' });
    return dayStr + " " + timeStr;
}

function highlightQuery(text, query) {
    if (!text || typeof text !== "string") return text || "";
    if (!query || typeof query !== "string" || !query.trim()) return text;

    const words = query.trim().split(/\s+/).filter(w => w.length > 0);
    if (words.length === 0) return text;

    const escaped = words.map(w => w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|');
    const regex = new RegExp(`(${escaped})`, 'gi');
    return text.replace(regex, '<b class="im-search-hl">$1</b>');
}

function getSearchMessageSnippet(msg, query) {
    let rawText = "";
    if (typeof msg.getText === 'function') {
        rawText = msg.getText(true) || "";
    } else if (msg.data?.text) {
        rawText = msg.data.text;
    } else if (msg.text) {
        rawText = msg.text;
    }

    if (!rawText && msg.data?.attachments?.length) {
        const att = msg.data.attachments[0];
        const type = att.type || (att.photo ? "photo" : (att.audio ? "audio" : (att.doc ? "doc" : "attach")));
        switch (type) {
            case "photo": rawText = `[${tr('attachment_photo') || 'Фотография'}]`; break;
            case "audio": rawText = `[${tr('attachment_audio') || 'Аудиозапись'}]`; break;
            case "video": rawText = `[${tr('attachment_video') || 'Видеозапись'}]`; break;
            case "doc": rawText = `[${tr('attachment_doc') || 'Документ'}]`; break;
            default: rawText = `[${tr('attachment') || 'Вложение'}]`; break;
        }
    }

    const div = document.createElement("div");
    div.textContent = rawText;
    const escaped = div.innerHTML;

    return highlightQuery(escaped, query);
}

export const SearchMessageItem = ({ msg, query }) => {
    if (!msg) return null;
    const peerId = Number(msg.peer_id || msg.data?.peer_id || 0);
    const fromId = Number(msg.from_id || msg.data?.from_id || (msg.sender?.id) || 0);
    const myId = window.openvk ? window.openvk.current_id : window.im?.state?.getId();

    const sender = msg.sender || (window.im?.cached_profiles && window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(fromId));
    const authorName = sender && typeof sender.getName === 'function' ? sender.getName() : (fromId ? `id${fromId}` : '...');
    const authorAva = sender && typeof sender.getAvatar === 'function' ? sender.getAvatar('mid', false) : '/assets/packages/static/openvk/img/camera_50.png';
    const authorUrl = sender && typeof sender.getPageUrl === 'function' ? sender.getPageUrl() : `/id${fromId}`;

    const isChat = peerId >= 2000000000 || Boolean(msg.data?.chat_id) || Boolean(msg.chat_id);
    let contextNode = null;

    if (isChat) {
        let chatTitle = msg.data?.title || msg.title || "";
        if (!chatTitle && window.im?.cached_profiles) {
            const cachedChat = window.im.cached_profiles._findCachedProfileById(peerId);
            if (cachedChat && typeof cachedChat.getName === 'function') {
                chatTitle = cachedChat.getName();
            }
        }
        if (!chatTitle) {
            const cId = peerId >= 2000000000 ? (peerId - 2000000000) : (msg.data?.chat_id || peerId);
            chatTitle = `${tr('chat') || 'Беседа'} ${cId}`;
        }
        const highlightedTitle = highlightQuery(chatTitle, query);
        contextNode = html`
            <span class="im-search-context">
                ${' '}в беседу «<a class="im-search-chat-link" dangerouslySetInnerHTML=${{ __html: highlightedTitle }}></a>»
            </span>
        `;
    } else if (fromId === myId && peerId && peerId !== myId) {
        let targetName = "";
        if (window.im?.cached_profiles) {
            const cachedPeer = window.im.cached_profiles._findCachedProfileById(peerId);
            if (cachedPeer && typeof cachedPeer.getName === 'function') {
                targetName = cachedPeer.getName();
            }
        }
        if (!targetName) targetName = `id${peerId}`;
        const highlightedTarget = highlightQuery(targetName, query);
        contextNode = html`
            <span class="im-search-context">
                ${' '}для <a class="im-search-chat-link" dangerouslySetInnerHTML=${{ __html: highlightedTarget }}></a>
            </span>
        `;
    }

    const isImportant = Boolean(msg.data?.important || (msg.data?.flags & 8));
    const timestamp = msg.date || msg.data?.date;
    const formattedDate = formatSearchDate(timestamp);
    const snippetHtml = getSearchMessageSnippet(msg, query);

    const onRowClick = () => {
        if (window.im?.messenger && typeof window.im.messenger.jumpToMessage === 'function') {
            window.im.messenger.jumpToMessage(msg.id, peerId);
        } else if (window.im?.messenger) {
            window.im.messenger.selectConversationByPeerId(peerId);
        }
    };

    return html`
        <div class="im-search-item" onClick=${onRowClick}>
            <div class="im-search-item-avatar">
                <a href="${authorUrl}" onClick=${(e) => e.stopPropagation()}>
                    <img src="${authorAva}" alt="" />
                </a>
            </div>
            <div class="im-search-item-body">
                <div class="im-search-item-header">
                    <div class="im-search-item-title">
                        <a class="im-search-author" href="${authorUrl}" onClick=${(e) => e.stopPropagation()}>
                            ${authorName}
                        </a>
                        ${contextNode}
                    </div>
                    <div class="im-search-item-meta">
                        ${isImportant ? html`<span class="im-search-star" title="${tr('important') || 'Важное'}">⭐</span>` : ""}
                        <span class="im-search-date">${formattedDate}</span>
                    </div>
                </div>
                <div class="im-search-item-text" dangerouslySetInnerHTML=${{ __html: snippetHtml }}></div>
            </div>
        </div>
    `;
};

export const SearchPageTemplate = ({ q, date, c, onSearch, onCancel }) => {
    const query = q || "";
    const count = c.total_count || 0;
    const items = c.items || [];
    const loaded_count = items.length;

    const handleKeyDown = (e) => {
        if (e.key === "Enter") {
            onSearch(e.target.value);
        }
    };

    const handleClear = (e) => {
        const input = e.target.closest('.im-search-input-box')?.querySelector('input');
        if (input) {
            input.value = "";
            input.focus();
        }
        onSearch("");
    };

    const handleSearchClick = (e) => {
        const input = e.target.closest('.im-search-toolbar')?.querySelector('.im-search-input');
        onSearch(input ? input.value : query);
    };

    const handleCalendarClick = () => {
        if (typeof window.openCalendarModal === 'function') {
            window.openCalendarModal({
                onSelectDate: (dateObj, meta) => {
                    const dayStr = String(meta.day).padStart(2, '0');
                    const monthStr = String(meta.month + 1).padStart(2, '0');
                    const ddmmyyyy = `${dayStr}${monthStr}${meta.year}`;
                    onSearch(query, ddmmyyyy);
                }
            });
        }
    };

    return html`
        <div id="search-page-im">
            <div class="im-search-toolbar">
                <div class="im-search-input-box">
                    <input 
                        type="text" 
                        class="search_input im-search-input" 
                        placeholder="${tr('search_messages_tab') || 'Поиск'}" 
                        value="${query}" 
                        onKeyDown=${handleKeyDown}
                    />
                    ${query ? html`<div class="im-search-clear" title="${tr('clear') || 'Очистить'}" onClick=${handleClear}>×</div>` : ""}
                </div>
                <input 
                    type="button" 
                    class="button im-search-btn" 
                    value="${tr('search_messages_tab') || 'Поиск'}" 
                    onClick=${handleSearchClick} 
                />
                <div 
                    class="im-search-calendar-btn ${date ? 'active' : ''}" 
                    title="${date ? ((tr('search_by_date') || 'Поиск по дате') + ': ' + date) : (tr('search_by_date') || 'Поиск по дате')}" 
                    onClick=${handleCalendarClick}>
                    <svg width="13" height="13" viewBox="0 0 16 16" fill="#708398">
                        <path d="M4.5 1a.75.75 0 0 0-.75.75V3h-1.5A1.25 1.25 0 0 0 1 4.25v9.5A1.25 1.25 0 0 0 2.25 15h11.5A1.25 1.25 0 0 0 15 13.75v-9.5A1.25 1.25 0 0 0 13.75 3h-1.5V1.75a.75.75 0 0 0-1.5 0V3h-4.5V1.75A.75.75 0 0 0 5.25 1h-.75zm9 3.5v1.5H2.5V4.5h11zm-11 3h11v6.25a.25.25 0 0 1-.25.25H2.25a.25.25 0 0 1-.25-.25V7.5z"/>
                    </svg>
                </div>
                <div class="im-search-cancel-btn">
                    <a onClick=${onCancel}>${tr('cancel') || 'Отмена'}</a>
                </div>
            </div>

            <div class="im-search-results">
                ${items.length === 0 ? html`
                    <div class="im-search-empty">
                        ${tr('im_search_not_found') || 'По запросу ничего не найдено.'}
                    </div>
                ` : items.map((msg) => html`
                    <${SearchMessageItem} msg=${msg} query=${query} />
                `)}
            </div>

            ${loaded_count < count && html`
                <div onClick=${() => c.moveOffset()} class="show_more crp-load-more">
                    ${tr('show_next') || 'Показать следующие сообщения'}
                </div>
            `}
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
