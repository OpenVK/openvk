/**
 * OpenVK Gifts & Sticker Pack Gifting Module
 */

(function(window, document) {
    'use strict';

    let friendsCache = null;

    /**
     * Loads current user's friends list for autocomplete.
     */
    async function loadFriends() {
        if (friendsCache) return friendsCache;
        try {
            if (window.OVKAPI && typeof window.OVKAPI.call === 'function') {
                const res = await window.OVKAPI.call('friends.get', {
                    fields: 'first_name,last_name,photo_50,photo',
                    order: 'hints'
                });
                const items = Array.isArray(res) ? res : (res && res.items ? res.items : []);
                if (items && items.length) {
                    friendsCache = items;
                    return friendsCache;
                }
            }
        } catch (e) {}
        friendsCache = [];
        return friendsCache;
    }

    /**
     * Helper to safely escape HTML.
     */
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Opens the gift sending modal.
     * Reusable across sticker packs, regular gifts, and profiles.
     */
    async function openGiftDialog(options = {}) {
        // Handle shorthand openGiftDialog(packInfo)
        if (options && (options.stickers || options.slug || (options.id && !options.type && !options.giftId))) {
            options = { type: 'stickerpack', pack: options };
        }

        const currentUserId = (window.openvk && window.openvk.current_id) ? parseInt(window.openvk.current_id, 10) : 0;
        if (!currentUserId || currentUserId <= 0) {
            window.location.href = '/login?return_to=' + encodeURIComponent(window.location.pathname + window.location.search);
            return;
        }

        let type = options.type || (options.pack || options.packId ? 'stickerpack' : 'gift');
        let pack = options.pack || null;
        let gift = options.gift || null;
        let giftId = options.giftId || (gift ? (gift.id || gift.gift_id) : 0);

        if (type === 'stickerpack' && !pack && options.packId) {
            if (window.API && window.API.Stickers && typeof window.API.Stickers.getPackInfo === 'function') {
                try {
                    pack = await window.API.Stickers.getPackInfo(options.packId);
                } catch (e) {}
            }
        }

        let name = '';
        let price = 0;
        let imgUrl = '';

        if (type === 'stickerpack' && pack) {
            name = pack.name || '';
            price = parseInt(pack.price, 10) || 0;
            imgUrl = pack.gift_img_url || ('/images/gift/' + pack.id + '/256.png');
        } else {
            name = options.name || (gift ? (gift.name || gift.internal_name) : (window.tr ? tr('gift') : 'Подарок'));
            price = options.price !== undefined
                ? parseInt(options.price, 10)
                : (gift ? (parseInt(gift.price, 10) || 0) : 0);
            imgUrl = options.image || (gift ? (gift.image || gift.photo || gift.thumb_256) : '') || (giftId ? ('/images/gift/' + giftId + '/256.png') : '');
        }

        if (type === 'stickerpack' && price <= 0) {
            MessageBox(
                window.tr ? tr('error') : 'Ошибка',
                window.tr ? tr('stickers_gift_free_prohibited') : 'Бесплатные стикерпаки нельзя дарить',
                [window.tr ? tr('ok') : 'OK'],
                [Function.noop]
            );
            return;
        }

        const priceFormatted = price > 0 ? (window.tr ? tr('coins', price) : (price + ' голосов')) : (window.tr ? tr('stickers_free') : 'Бесплатно');

        // User balance
        let userCoins = (window.openvk && typeof window.openvk.coins !== 'undefined') ? parseInt(window.openvk.coins, 10) : null;
        let balanceHtml = '';
        if (userCoins !== null && !isNaN(userCoins)) {
            const bCoins = window.tr ? tr('coins', userCoins) : (userCoins + ' голосов');
            const bText = window.tr ? tr('gift_user_balance', bCoins) : ('У Вас ' + bCoins);
            balanceHtml = `<div id="stickers_gift_balance_val" style="font-size: 11px; color: #777; margin-top: 2px;">${escapeHtml(bText)}</div>`;
        }

        // Selected recipients list
        let selectedUsers = [];
        if (options.recipients && Array.isArray(options.recipients)) {
            selectedUsers = options.recipients.slice();
        } else if (options.recipient) {
            if (typeof options.recipient === 'object') {
                selectedUsers = [options.recipient];
            } else if (typeof options.recipient === 'number') {
                selectedUsers = [{ id: options.recipient, name: 'id' + options.recipient }];
            }
        } else if (options.recipientId) {
            selectedUsers = [{
                id: options.recipientId,
                name: options.recipientName || ('id' + options.recipientId),
                first_name: options.recipientFirstName || options.recipientName || ''
            }];
        } else if (options.user) {
            selectedUsers = [options.user];
        }

        const title = (type === 'stickerpack' && name)
            ? ((window.tr ? tr('stickers_gift_title') : 'Подарить стикерпак') + ' «' + escapeHtml(name) + '»')
            : (window.tr ? tr('send_gift') : 'Отправить подарок');

        const bodyHtml = `
            <div class="stickers_gift_dialog_body">
                <div class="stickers_gift_top_preview">
                    <img src="${escapeHtml(imgUrl)}" class="stickers_gift_preview_image" alt="" />
                    <div style="font-size: 12px; color: #555;">
                        ${window.tr ? tr('price') : 'Цена'}: <b style="color: #222;" id="stickers_gift_price_value">${priceFormatted}</b>
                    </div>
                    ${balanceHtml}
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">${window.tr ? tr('stickers_gift_recipient_label') : 'Получатель'}:</label>
                    <div class="stickers_gift_selector_box is-empty" id="stickers_gift_selector_box">
                        <div class="stickers_gift_tokens_list" id="stickers_gift_tokens_list"></div>
                        <input type="text" id="stickers_gift_user_input" class="stickers_gift_input" placeholder="${window.tr ? tr('stickers_gift_recipient_placeholder') : 'Введите имя друга или ID...'}" autocomplete="off" />
                        <span class="stickers_gift_selector_arrow"></span>
                        <div id="stickers_gift_user_suggestions" class="stickers_gift_suggestions" style="display: none;"></div>
                    </div>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">${window.tr ? tr('stickers_gift_message_label') : 'Сообщение к подарку'}:</label>
                    <textarea id="stickers_gift_comment" style="width: 100%; box-sizing: border-box; resize: vertical; height: 60px;" placeholder="${window.tr ? tr('stickers_gift_message_placeholder') : 'Прикрепить сообщение...'}"></textarea>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="cursor: pointer; user-select: none;">
                        <input type="checkbox" id="stickers_gift_anonymous" /> ${window.tr ? tr('stickers_gift_anonymous') : 'Отправить анонимно'}
                    </label>
                </div>
            </div>
        `;

        let giftMsg = null;

        giftMsg = new CMessageBox({
            title: title,
            body: bodyHtml,
            buttons: [window.tr ? tr('send_gift') : 'Отправить подарок', window.tr ? tr('cancel') : 'Отмена'],
            close_on_buttons: false,
            callbacks: [
                async () => {
                    // If no tokens selected yet, check if text was typed in input
                    if (selectedUsers.length === 0) {
                        const rawVal = userInput?.value?.trim() || '';
                        const idMatch = rawVal.match(/^(?:https?:\/\/[^\/]+\/)?(?:id)?(\d+)$/i);
                        if (idMatch) {
                            const uId = parseInt(idMatch[1], 10);
                            if (uId > 0 && uId !== currentUserId) {
                                selectedUsers.push({ id: uId, name: 'id' + uId });
                            }
                        }
                    }

                    if (selectedUsers.length === 0) {
                        MessageBox(
                            window.tr ? tr('error') : 'Ошибка',
                            window.tr ? tr('stickers_gift_select_recipient_err') : 'Пожалуйста, выберите получателя подарка',
                            [window.tr ? tr('ok') : 'OK'],
                            [Function.noop]
                        );
                        return;
                    }

                    const totalCost = price * selectedUsers.length;
                    if (price > 0 && userCoins !== null && userCoins < totalCost) {
                        MessageBox(
                            window.tr ? tr('error') : 'Ошибка',
                            window.tr ? tr('stickers_not_enough_coins') : 'Недостаточно голосов',
                            [window.tr ? tr('ok') : 'OK'],
                            [Function.noop]
                        );
                        return;
                    }

                    const comment = gNode.find('#stickers_gift_comment').nodes[0]?.value || '';
                    const anonymous = Boolean(gNode.find('#stickers_gift_anonymous').nodes[0]?.checked);

                    const sendBtn = gNode.find('.ovk-diag-action button').nodes[0];
                    if (sendBtn) {
                        sendBtn.disabled = true;
                        sendBtn.textContent = window.tr ? tr('loading') : 'Загрузка...';
                    }

                    const errors = [];
                    let successCount = 0;

                    for (const user of selectedUsers) {
                        try {
                            if (type === 'stickerpack') {
                                if (!window.API || !window.API.Stickers || typeof window.API.Stickers.giftPack !== 'function') {
                                    throw new Error('API.Stickers is not available');
                                }
                                await window.API.Stickers.giftPack(pack.id, user.id, comment, anonymous);
                            } else {
                                if (!window.OVKAPI || typeof window.OVKAPI.call !== 'function') {
                                    throw new Error('OVKAPI is not available');
                                }
                                const res = await window.OVKAPI.call('gifts.send', {
                                    user_ids: user.id,
                                    gift_id: giftId,
                                    message: comment,
                                    privacy: anonymous ? 1 : 0
                                });
                                if (res && res.error) throw new Error(res.error);
                                if (res && res.response && res.response.error) throw new Error(res.response.error);
                            }
                            successCount++;
                        } catch (err) {
                            const uName = user.first_name ? ((user.first_name + ' ' + (user.last_name || '')).trim()) : (user.name || ('id' + user.id));
                            errors.push(uName + ': ' + ((err && err.message) ? err.message : (window.tr ? tr('error') : 'Ошибка')));
                        }
                    }

                    // Update local balance
                    if (price > 0 && successCount > 0 && window.openvk && typeof window.openvk.coins === 'number') {
                        window.openvk.coins = Math.max(0, window.openvk.coins - (price * successCount));
                    }

                    giftMsg.close();

                    if (typeof options.onSuccess === 'function' && successCount > 0) {
                        options.onSuccess({ successCount: successCount, total: selectedUsers.length });
                    }

                    if (errors.length > 0 && successCount === 0) {
                        MessageBox(
                            window.tr ? tr('error') : 'Ошибка',
                            errors.join('<br>'),
                            [window.tr ? tr('ok') : 'OK'],
                            [Function.noop]
                        );
                    } else if (errors.length > 0) {
                        MessageBox(
                            window.tr ? tr('warning') : 'Внимание',
                            `Отправлено ${successCount} из ${selectedUsers.length} подарков.<br><br>` + errors.join('<br>'),
                            [window.tr ? tr('ok') : 'OK'],
                            [Function.noop]
                        );
                    } else {
                        const successMsg = type === 'stickerpack'
                            ? (window.tr ? tr('stickers_gift_success') : 'Стикерпак успешно отправлен в подарок!')
                            : (window.tr ? tr('gift_sent') : 'Подарок отправлен');
                        MessageBox(
                            window.tr ? tr('success') : 'Успех',
                            successMsg,
                            [window.tr ? tr('ok') : 'OK'],
                            [Function.noop]
                        );
                    }
                },
                () => {
                    giftMsg.close();
                }
            ]
        });

        const gNode = giftMsg.getNode();
        const diagBody = gNode.find('.ovk-diag-body').nodes[0];
        if (diagBody) {
            diagBody.style.overflow = 'visible';
            diagBody.style.maxHeight = 'none';
        }
        const diag = gNode.find('.ovk-diag').nodes[0];
        if (diag) {
            diag.style.overflow = 'visible';
        }

        // Close button in header
        const gHead = gNode.find('.ovk-diag-head');
        if (gHead.nodes[0] && !gHead.find('.stickers_modal_close_cross').nodes.length) {
            gHead.append(u('<a href="javascript:void(0)" class="stickers_modal_close_cross"></a>'));
            gHead.find('.stickers_modal_close_cross').on('click', (e) => {
                e.preventDefault();
                giftMsg.close();
            });
        }

        const selectorBox = gNode.find('#stickers_gift_selector_box').nodes[0];
        const tokensList = gNode.find('#stickers_gift_tokens_list').nodes[0];
        const userInput = gNode.find('#stickers_gift_user_input').nodes[0];
        const userSugg = gNode.find('#stickers_gift_user_suggestions').nodes[0];
        const priceEl = gNode.find('#stickers_gift_price_value').nodes[0];

        function updatePrice() {
            if (!priceEl) return;
            const count = Math.max(1, selectedUsers.length);
            const total = price * count;
            if (price === 0) {
                priceEl.textContent = window.tr ? tr('stickers_free') : 'Бесплатно';
            } else if (selectedUsers.length > 1) {
                priceEl.textContent = `${window.tr ? tr('coins', total) : (total + ' голосов')} (${price} × ${selectedUsers.length})`;
            } else {
                priceEl.textContent = window.tr ? tr('coins', price) : (price + ' голосов');
            }
        }

        function renderTokens() {
            if (!tokensList) return;
            tokensList.innerHTML = selectedUsers.map(u => {
                const displayName = escapeHtml(u.first_name ? ((u.first_name + ' ' + (u.last_name || '')).trim()) : (u.name || ('id' + u.id)));
                return `
                    <div class="stickers_gift_token" data-user-id="${u.id}">
                        <span>${displayName}</span>
                        <a href="javascript:void(0)" class="stickers_gift_token_remove" data-remove-id="${u.id}" title="×">×</a>
                    </div>
                `;
            }).join('');

            tokensList.querySelectorAll('.stickers_gift_token_remove').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const uid = parseInt(btn.dataset.removeId, 10);
                    removeUser(uid);
                });
            });

            if (selectedUsers.length === 0) {
                selectorBox.classList.add('is-empty');
                userInput.placeholder = window.tr ? tr('stickers_gift_recipient_placeholder') : 'Введите имя друга или ID...';
            } else {
                selectorBox.classList.remove('is-empty');
                userInput.placeholder = window.tr ? tr('gift_add_recipient') : 'Добавить +';
            }

            updatePrice();
        }

        function addUser(user) {
            if (!user || !user.id) return;
            if (selectedUsers.some(u => u.id === user.id)) return;
            if (user.id === currentUserId) {
                MessageBox(
                    window.tr ? tr('error') : 'Ошибка',
                    window.tr ? tr('stickers_gift_self_error') : 'Вы не можете отправить подарок самому себе',
                    [window.tr ? tr('ok') : 'OK'],
                    [Function.noop]
                );
                return;
            }
            selectedUsers.push(user);
            renderTokens();
            userInput.value = '';
            renderSuggestions('');
            userInput.focus();
        }

        function removeUser(userId) {
            selectedUsers = selectedUsers.filter(u => u.id !== userId);
            renderTokens();
            renderSuggestions(userInput.value);
            userInput.focus();
        }

        if (selectorBox) {
            selectorBox.addEventListener('click', (e) => {
                if (!e.target.closest('.stickers_gift_token_remove')) {
                    userInput.focus();
                    renderSuggestions(userInput.value);
                }
            });
        }

        function attachSuggestionClicks() {
            userSugg.querySelectorAll('.stickers_gift_suggestion_item').forEach(item => {
                item.addEventListener('click', () => {
                    const uid = parseInt(item.dataset.userId, 10);
                    const name = item.dataset.userName || '';
                    const firstName = item.dataset.userFirstName || name;
                    addUser({ id: uid, first_name: firstName, name: name });
                });
            });
        }

        async function renderSuggestions(query = '') {
            const q = query.trim().toLowerCase();
            const friends = await loadFriends();

            let filtered = friends.filter(f => !selectedUsers.some(u => u.id === f.id));
            if (q) {
                filtered = filtered.filter(f => {
                    const fn = (f.first_name || '').toLowerCase();
                    const ln = (f.last_name || '').toLowerCase();
                    const full = `${fn} ${ln}`;
                    return fn.includes(q) || ln.includes(q) || full.includes(q) || String(f.id).includes(q);
                });
            }

            if (filtered.length === 0) {
                const idMatch = q.match(/^(?:https?:\/\/[^\/]+\/)?(?:id)?(\d+)$/i);
                if (idMatch) {
                    const uId = parseInt(idMatch[1], 10);
                    if (uId > 0 && !selectedUsers.some(u => u.id === uId)) {
                        userSugg.innerHTML = `
                            <div class="stickers_gift_suggestion_item" data-user-id="${uId}" data-user-name="id${uId}">
                                <div class="stickers_gift_suggestion_name">id${uId} (${window.tr ? tr('select') : 'Выбрать'})</div>
                            </div>
                        `;
                        userSugg.style.display = 'block';
                        attachSuggestionClicks();
                        return;
                    }
                }
                userSugg.style.display = 'none';
                return;
            }

            userSugg.innerHTML = filtered.slice(0, 10).map(f => {
                const photo = f.photo_50 || f.photo || '/assets/packages/static/openvk/img/camera_50.png';
                const name = escapeHtml(`${f.first_name || ''} ${f.last_name || ''}`.trim() || `id${f.id}`);
                const firstName = escapeHtml(f.first_name || name);
                return `
                    <div class="stickers_gift_suggestion_item" data-user-id="${f.id}" data-user-name="${name}" data-user-first-name="${firstName}">
                        <img src="${escapeHtml(photo)}" class="stickers_gift_suggestion_avatar" alt="" />
                        <div class="stickers_gift_suggestion_name">${name}</div>
                    </div>
                `;
            }).join('');
            userSugg.style.display = 'block';
            attachSuggestionClicks();
        }

        const onDocClick = (e) => {
            if (selectorBox && !selectorBox.contains(e.target)) {
                userSugg.style.display = 'none';
            }
        };
        document.addEventListener('click', onDocClick);

        const origClose = giftMsg.close.bind(giftMsg);
        giftMsg.close = function() {
            document.removeEventListener('click', onDocClick);
            origClose();
            if (typeof options.onClose === 'function') {
                options.onClose();
            }
        };

        if (userInput) {
            userInput.addEventListener('focus', () => renderSuggestions(userInput.value));
            userInput.addEventListener('input', () => renderSuggestions(userInput.value));
            userInput.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !userInput.value && selectedUsers.length > 0) {
                    const last = selectedUsers[selectedUsers.length - 1];
                    removeUser(last.id);
                }
            });
        }

        // Render initial tokens
        renderTokens();

        return giftMsg;
    }

    /**
     * Backward-compatible wrapper for sticker pack gifting.
     */
    function openGiftStickerPackDialog(packInfo, recipient) {
        return openGiftDialog({
            type: 'stickerpack',
            pack: packInfo,
            recipient: recipient
        });
    }

    // Export globally
    window.openGiftDialog = openGiftDialog;
    window.openGiftStickerPackDialog = openGiftStickerPackDialog;

    // Delegate click handler for gift cards on /gifts or anywhere with data-gift attribute
    function handleGiftClick(e) {
        const giftLink = e.target.closest ? e.target.closest('.gift_sel[data-gift]') : null;
        if (giftLink && !e.ctrlKey && !e.metaKey && !e.shiftKey && (e.button === 0 || typeof e.button === 'undefined')) {
            if (giftLink.classList.contains('disabled')) {
                e.preventDefault();
                return;
            }
            e.preventDefault();
            if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
            if (typeof e.stopPropagation === 'function') e.stopPropagation();

            const giftId = parseInt(giftLink.dataset.gift, 10);
            const price = parseInt(giftLink.dataset.giftPrice, 10) || 0;
            const img = giftLink.dataset.giftImg || giftLink.querySelector('img')?.src || ('/images/gift/' + giftId + '/256.png');
            const rId = parseInt(giftLink.dataset.recipientId, 10);
            const rName = giftLink.dataset.recipientName;
            const rFirstName = giftLink.dataset.recipientFirstName;

            openGiftDialog({
                type: 'gift',
                giftId: giftId,
                price: price,
                image: img,
                recipient: rId ? { id: rId, name: rName, first_name: rFirstName } : null
            });
            return false;
        }

        const giftPackTrigger = e.target.closest ? e.target.closest('[data-gift-pack]') : null;
        if (giftPackTrigger && !e.ctrlKey && !e.metaKey && !e.shiftKey && (e.button === 0 || typeof e.button === 'undefined')) {
            e.preventDefault();
            if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
            if (typeof e.stopPropagation === 'function') e.stopPropagation();

            const packId = parseInt(giftPackTrigger.dataset.giftPack, 10);
            if (packId) {
                openGiftDialog({
                    type: 'stickerpack',
                    packId: packId
                });
            }
            return false;
        }
    }

    if (window.$ && typeof window.$.fn === 'object') {
        $(document).on('click', '.gift_sel[data-gift], [data-gift-pack]', function(e) {
            handleGiftClick(e);
        });
    }
    document.addEventListener('click', handleGiftClick, true);


})(window, document);
