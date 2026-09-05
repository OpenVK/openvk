import { html } from './render.js';
import { formatTime } from './common.js';
import { LottieSticker } from './message.js';

/**
 * Universal drag handler for floating fastchat windows
 */
function handleHeaderMouseDown(e, initialPosition, onFocus, onMove, onToggle) {
    if (e.button !== 0) return;
    if (e.target.closest('.fc_head_close')) return;

    e.preventDefault();
    if (onFocus) onFocus();

    const startX = e.clientX;
    const startY = e.clientY;
    const windowEl = e.currentTarget.closest('.fc_chat_box, .fc_friends_window, .fc_tab_minimized');
    if (!windowEl) return;

    const rect = windowEl.getBoundingClientRect();
    const curX = initialPosition && typeof initialPosition.x === 'number' ? initialPosition.x : rect.left;
    const curY = initialPosition && typeof initialPosition.y === 'number' ? initialPosition.y : rect.top;

    let hasDragged = false;

    const onMouseMove = (moveEvent) => {
        const dx = moveEvent.clientX - startX;
        const dy = moveEvent.clientY - startY;

        if (!hasDragged && (Math.abs(dx) > 3 || Math.abs(dy) > 3)) {
            hasDragged = true;
            document.body.classList.add("fc_dragging");
        }

        if (hasDragged) {
            let newX = curX + dx;
            let newY = curY + dy;

            // Viewport boundary clamping
            const maxW = window.innerWidth - (windowEl.offsetWidth || 230);
            const maxH = window.innerHeight - (windowEl.offsetHeight || 28);
            newX = Math.max(5, Math.min(maxW - 5, newX));
            newY = Math.max(5, Math.min(maxH, newY));

            if (onMove) {
                onMove({ x: newX, y: newY });
            }
        }
    };

    const onMouseUp = () => {
        document.removeEventListener("mousemove", onMouseMove);
        document.removeEventListener("mouseup", onMouseUp);
        document.body.classList.remove("fc_dragging");

        if (!hasDragged && onToggle) {
            onToggle();
        }
    };

    document.addEventListener("mousemove", onMouseMove);
    document.addEventListener("mouseup", onMouseUp);
}

/**
 * FastChatOnlineWindow - Floating window with friends list (VK 2012-2016 style)
 */
export const FastChatOnlineWindow = ({
    isOpened,
    friends,
    allFriends,
    showAll,
    searchQuery,
    position,
    zIndex,
    isFocused,
    onSearch,
    onFriendClick,
    onToggle,
    onClose,
    onFocus,
    onMove,
    onToggleShowAll
}) => {
    const onlineCount = friends ? friends.length : 0;
    const totalCount = allFriends ? allFriends.length : onlineCount;

    let baseList = [];
    if (searchQuery) {
        baseList = allFriends && allFriends.length > 0 ? allFriends : (friends || []);
    } else if (showAll) {
        baseList = allFriends && allFriends.length > 0 ? allFriends : (friends || []);
    } else {
        baseList = friends || [];
    }

    const filteredFriends = baseList.filter(f => {
        if (!searchQuery) return true;
        const name = (f.first_name + " " + f.last_name).toLowerCase();
        return name.includes(searchQuery.toLowerCase());
    });

    const styleStr = position
        ? `position: fixed; left: ${position.x}px; top: ${position.y}px; z-index: ${zIndex || 10000}; margin: 0;`
        : `position: relative; z-index: ${zIndex || 10000};`;

    if (!isOpened) {
        // Always pinned to bottom-right as a fixed tab when closed
        return html`
            <div
                class="fc_online_tab_pinned ${isFocused ? 'fc_focused' : ''}"
                onClick=${onToggle}
            >
                <span class="fc_online_tab_label">${tr('friends_online_count', onlineCount)}</span>
            </div>
        `;
    }

    // When opened without dragging, anchor fixed bottom-right with same offset as #fastchats_container
    const openedStyle = position
        ? `position: fixed; left: ${position.x}px; top: ${position.y}px; z-index: ${zIndex || 10000}; margin: 0;`
        : `position: fixed; right: 20px; bottom: 30px; z-index: ${zIndex || 10000}; margin: 0;`;

    let hasRenderedOfflineDivider = false;

    return html`
        <div
            class="fc_friends_window ${isFocused ? 'fc_focused' : ''}"
            style=${openedStyle}
            onClick=${onFocus}
        >
            <div
                class="fc_head"
                onMouseDown=${(e) => handleHeaderMouseDown(e, position, onFocus, onMove, onToggle)}
            >
                <div class="fc_head_title">${tr('friends_online_count', onlineCount)}</div>
                <div class="fc_head_close" onClick=${(e) => { e.stopPropagation(); onClose(); }}></div>
            </div>
            <div class="fc_search_wrap">
                <input
                    type="text"
                    class="fc_search_input"
                    placeholder="${tr('start_typing_name') || 'Начните вводить имя...'}"
                    value=${searchQuery}
                    onInput=${(e) => onSearch(e.target.value)}
                    onClick=${(e) => e.stopPropagation()}
                />
            </div>
            <div class="fc_friends_list">
                ${filteredFriends.length > 0 ? filteredFriends.map(f => {
        const fullName = f.first_name + " " + f.last_name;
        const isOnline = f.online === 1 || f.online === true;
        let statusText = isOnline ? "online" : "";
        if (!isOnline && f.last_seen && f.last_seen.time) {
            const minsAgo = Math.floor((Date.now() / 1000 - f.last_seen.time) / 60);
            if (minsAgo < 60) {
                statusText = tr('was_n_mins_ago', minsAgo) || `был(а) ${minsAgo} мин. назад`;
            }
        }

        const showDivider = !searchQuery && showAll && !isOnline && !hasRenderedOfflineDivider && onlineCount > 0;
        if (showDivider) {
            hasRenderedOfflineDivider = true;
        }

        return html`
                        ${showDivider && html`
                            <div class="fc_divider_row">${tr('offline_divider') || 'Не в сети'}</div>
                        `}
                        <div class="fc_friend_row" onClick=${() => onFriendClick(f)}>
                            <img src="${f.photo_50 || '/assets/packages/static/openvk/img/camera_50.png'}" class="fc_avatar" />
                            <div class="fc_info">
                                <span class="fc_name">${fullName}</span>
                                <span class="fc_status ${isOnline ? 'online' : ''}">${statusText}</span>
                            </div>
                            <div class="fc_action_btn">+1</div>
                        </div>
                    `;
    }) : html`
                    <div class="fc_friends_hint">
                        ${tr('fastchat_search_hint') || 'Введите имя и выберите пользователя, чтобы начать диалог.'}
                    </div>
                `}
            </div>

            ${!searchQuery && allFriends && allFriends.length > 0 && html`
                <div class="fc_toggle_offline_btn" onClick=${onToggleShowAll}>
                    ${showAll
                ? (tr('show_online_only', onlineCount) || `Показать только онлайн (${onlineCount})`)
                : (tr('show_all_friends', totalCount) || `Показать всех друзей (${totalCount})`)
            }
                </div>
            `}
        </div>
    `;
};

/**
 * FastChatBox - Active floating conversation box
 */
export const FastChatBox = ({
    chat,
    currentUserId,
    currentUserAvatar,
    onToggle,
    onClose,
    onLoadOlder,
    onTextChange,
    onSend,
    onKeyDown,
    onFocus,
    onMove
}) => {
    const styleStr = chat.position
        ? `position: fixed; left: ${chat.position.x}px; top: ${chat.position.y}px; z-index: ${chat.zIndex || 10000}; margin: 0;`
        : `position: relative; z-index: ${chat.zIndex || 10000};`;

    if (chat.isMinimized) {
        return html`
            <div
                class="fc_tab_minimized ${chat.isFocused ? 'fc_focused' : ''}"
                style=${styleStr}
                onMouseDown=${(e) => handleHeaderMouseDown(e, chat.position, () => onFocus(chat.peerId), (pos) => onMove(chat.peerId, pos), () => onToggle(chat.peerId))}
            >
                <div class="fc_min_title">${chat.title}</div>
                ${chat.unreadCount > 0 && html`<span class="fc_unread_badge">+${chat.unreadCount}</span>`}
                <div class="fc_head_close" onClick=${(e) => { e.stopPropagation(); onClose(chat.peerId); }}></div>
            </div>
        `;
    }

    const messages = chat.messages || [];

    return html`
        <div
            class="fc_chat_box ${chat.isFocused ? 'fc_focused' : ''}"
            data-peer-id="${chat.peerId}"
            style=${styleStr}
            onClick=${() => onFocus(chat.peerId)}
        >
            <div
                class="fc_head"
                onMouseDown=${(e) => handleHeaderMouseDown(e, chat.position, () => onFocus(chat.peerId), (pos) => onMove(chat.peerId, pos), () => onToggle(chat.peerId))}
            >
                <div class="fc_head_title">${chat.title}</div>
                <div class="fc_head_close" onClick=${(e) => { e.stopPropagation(); onClose(chat.peerId); }}></div>
            </div>

            ${chat.hasMore && html`
                <div class="fc_load_more" onClick=${() => onLoadOlder(chat.peerId)}>
                    ${tr('show_previous_messages') || 'Показать предыдущие сообщения'}
                </div>
            `}

            <div class="fc_messages_list" id="fc_messages_${chat.peerId}">
                ${chat.isLoading && messages.length === 0 && html`
                    <div class="fc_loading_state">
                        <img src="/assets/packages/static/openvk/img/loading_mini.gif" alt="..." />
                    </div>
                `}

                ${!chat.isLoading && messages.length === 0 && html`
                    <div class="fc_empty_history">
                        ${tr('no_messages_in_dialog') || 'Здесь пока нет сообщений.'}
                    </div>
                `}

                ${messages.map(msg => {
        const isOut = msg.from_id === currentUserId || msg.out === 1;
        const authorName = isOut ? (tr('you') || 'Вы') : (msg.author_name || chat.title);
        const authorAva = isOut ? currentUserAvatar : (msg.author_photo || chat.photo);
        const timeStr = msg.time_str || (msg.date ? formatTime(msg.date, false) : '');
        const isTargetUnread = chat.firstUnreadMsgId && (Number(msg.id) === Number(chat.firstUnreadMsgId));

        return html`
                        ${isTargetUnread ? html`
                            <div class="fc_unread_divider" id="fc_unread_${chat.peerId}">
                                <span class="fc_unread_divider_text">${tr('unread_messages')}</span>
                            </div>
                        ` : null}
                        <div class="fc_msg_row" data-msg-id="${msg.id}">
                            <img src="${authorAva || '/assets/packages/static/openvk/img/camera_50.png'}" class="fc_msg_avatar" />
                            <div class="fc_msg_body">
                                <div class="fc_msg_header">
                                    <span class="fc_msg_author">${authorName}</span>
                                    <span class="fc_msg_time">${timeStr}</span>
                                </div>
                                <div class="fc_msg_text">
                                    ${msg.text || (msg.body || '')}
                                    ${msg.attachments && Array.isArray(msg.attachments) && msg.attachments.map(att => {
                                        if (att && att.type === 'sticker') {
                                            let stk = att.sticker || {};
                                            let sId = stk.sticker_id || stk.id;
                                            if ((!stk.photo_128 && !stk.photo_256 && (!stk.images || !stk.images.length)) && typeof window.findStickerData === 'function' && sId) {
                                                const found = window.findStickerData(sId);
                                                if (found) stk = { ...found, ...stk };
                                            }

                                            let animUrl = stk.animation_url || (stk.animations && stk.animations[0]?.url) || '';
                                            if (!animUrl && stk.photo_128 && stk.photo_128.endsWith('.json')) {
                                                animUrl = stk.photo_128;
                                            }
                                            if (!animUrl && stk.product_id && sId && stk.is_animated) {
                                                animUrl = `/sticker/${stk.product_id}/${sId}_512.json`;
                                            }

                                            if (animUrl) {
                                                if (window.location.protocol === 'https:' && animUrl.startsWith('http://')) {
                                                    animUrl = animUrl.replace(/^http:\/\//i, 'https://');
                                                }
                                                return html`<div class="fc_msg_sticker"><${LottieSticker} url=${animUrl} stickerId=${sId} width=${110} height=${110} /></div>`;
                                            }

                                            let img = stk.photo_128 || stk.photo_256 || (stk.images && stk.images[0]?.url) || (stk.product_id && sId ? `/sticker/${stk.product_id}/${sId}_128.webp` : '');
                                            if (img && window.location.protocol === 'https:' && img.startsWith('http://')) {
                                                img = img.replace(/^http:\/\//i, 'https://');
                                            }
                                            return html`<div class="fc_msg_sticker"><img src="${img}" alt="sticker" loading="lazy" /></div>`;
                                        }
                                        return null;
                                    })}
                                </div>
                            </div>
                        </div>
                    `;
    })}
            </div>

            <div class="fc_input_bar">
                <img src="${currentUserAvatar || '/assets/packages/static/openvk/img/camera_50.png'}" class="fc_my_avatar" />
                <textarea
                    class="fc_textarea"
                    placeholder="${tr('enter_your_message') || 'Введите Ваше сообщение...'}"
                    value=${chat.text || ''}
                    onInput=${(e) => onTextChange(chat.peerId, e.target.value)}
                    onKeyDown=${(e) => onKeyDown(e, chat.peerId)}
                    onFocus=${() => onFocus(chat.peerId)}
                ></textarea>
                <div class="fc_emoji_btn emoji_picker_entrypoint" title="${tr('smiles')}"></div>
            </div>
        </div>
    `;
};

/**
 * FastChatsRoot - Root container rendered into #fastchats_container
 */
export const FastChatsRoot = ({
    onlineWindow,
    openedChats,
    currentUserId,
    currentUserAvatar,
    onOnlineSearch,
    onOnlineFriendClick,
    onOnlineToggle,
    onOnlineClose,
    onOnlineFocus,
    onOnlineMove,
    onOnlineToggleShowAll,
    onChatToggle,
    onChatClose,
    onChatLoadOlder,
    onChatTextChange,
    onChatSend,
    onChatKeyDown,
    onChatFocus,
    onChatMove
}) => {
    return html`
        ${onlineWindow && html`
            <${FastChatOnlineWindow}
                key="fc_online_window"
                isOpened=${onlineWindow.isOpened}
                friends=${onlineWindow.friends}
                allFriends=${onlineWindow.allFriends}
                showAll=${onlineWindow.showAll}
                searchQuery=${onlineWindow.searchQuery}
                position=${onlineWindow.position}
                zIndex=${onlineWindow.zIndex}
                isFocused=${onlineWindow.isFocused}
                onSearch=${onOnlineSearch}
                onFriendClick=${onOnlineFriendClick}
                onToggle=${onOnlineToggle}
                onClose=${onOnlineClose}
                onFocus=${onOnlineFocus}
                onMove=${onOnlineMove}
                onToggleShowAll=${onOnlineToggleShowAll}
            />
        `}

        ${openedChats && openedChats.map(chat => html`
            <${FastChatBox}
                key=${chat.peerId}
                chat=${chat}
                currentUserId=${currentUserId}
                currentUserAvatar=${currentUserAvatar}
                onToggle=${onChatToggle}
                onClose=${onChatClose}
                onLoadOlder=${onChatLoadOlder}
                onTextChange=${onChatTextChange}
                onSend=${onChatSend}
                onKeyDown=${onChatKeyDown}
                onFocus=${onChatFocus}
                onMove=${onChatMove}
            />
        `)}
    `;
};
