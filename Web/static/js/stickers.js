// Track last focused text input or textarea across the page
let lastFocusedInput = null;
document.addEventListener('focusin', (e) => {
    if (e.target && (e.target.tagName === 'TEXTAREA' || (e.target.tagName === 'INPUT' && e.target.type === 'text'))) {
        lastFocusedInput = e.target;
    }
});

function appendEmoji(e) {
    let emoji = null;
    if (e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.emoji) {
        emoji = e.currentTarget.dataset.emoji;
    } else if (e.target) {
        const el = e.target.closest('[data-emoji]');
        if (el) emoji = el.dataset.emoji;
    }
    if (!emoji) return;

    // 1. Check if the trigger element was inside a specific form or chat box
    let textarea = null;
    if (window._currentEmojiTrigger) {
        const box = window._currentEmojiTrigger.closest('.emoji-input-wrap, .existing_sticker_card, .draft_sticker_card, #write, .fc_chat_box, .fc_input_bar, .reply_form, form');
        if (box) {
            textarea = box.querySelector('.content-editable, textarea, input[type="text"]');
        }
    }

    // 2. Check ContentEditable.lastFocused
    if (!textarea && window.ContentEditable && window.ContentEditable.lastFocused) {
        textarea = window.ContentEditable.lastFocused.el;
    }

    // 3. Check last focused textarea/input
    if (!textarea && lastFocusedInput && document.contains(lastFocusedInput)) {
        textarea = lastFocusedInput;
    }

    // 4. Fallbacks
    if (!textarea) {
        textarea = document.querySelector('#write .content-editable')
            || document.querySelector('#write .small-textarea')
            || document.querySelector('.content-editable')
            || document.querySelector('.small-textarea')
            || document.querySelector('.fc_textarea');
    }

    if (textarea) {
        if (typeof textarea.insertEmoji === 'function') {
            textarea.insertEmoji(emoji);
        } else if (textarea._contentEditable) {
            textarea._contentEditable.insertEmoji(emoji);
        } else {
            const start = typeof textarea.selectionStart !== 'undefined' ? textarea.selectionStart : textarea.value.length;
            const end = typeof textarea.selectionEnd !== 'undefined' ? textarea.selectionEnd : textarea.value.length;
            const val = textarea.value;
            textarea.value = val.substring(0, start) + emoji + val.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
            textarea.focus();
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            textarea.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    addSmile(emoji);
    updateRecentSmilesInPicker();
    if (typeof window.updateRecentSmilesBar === 'function') {
        window.updateRecentSmilesBar();
    }
}

function sendSticker(textarea) {
    // Stub
}

let _emojiDataPromise = null;
function preloadEmojiData() {
    if (window.emojiData) {
        return Promise.resolve(window.emojiData);
    }
    if (!_emojiDataPromise) {
        _emojiDataPromise = fetch('/assets/packages/static/openvk/js/emoji-data.json')
            .then(res => res.json())
            .then(data => {
                window.emojiData = data;
                return data;
            }).catch(e => {
                console.error("Failed to load emoji data:", e);
                return [];
            });
    }
    return _emojiDataPromise;
}

// Pre-load emoji data immediately when script is executed
preloadEmojiData();

async function loadEmojiData() {
    if (window.emojiData != null) {
        return window.emojiData;
    }
    return await preloadEmojiData();
}

let _cachedEmojiWrapper = null;
let _myStickerPacks = null;
let _myStickerPacksPromise = null;

async function preloadStickerPacks(force = false) {
    if (_myStickerPacks !== null && !force) {
        return _myStickerPacks;
    }
    if (!_myStickerPacksPromise || force) {
        _myStickerPacksPromise = getStickerpacks().then(res => {
            _myStickerPacks = (res && res.items) ? res.items : [];
            window.myStickerPacks = _myStickerPacks;
            return _myStickerPacks;
        }).catch(err => {
            console.error("Failed to load user sticker packs:", err);
            _myStickerPacks = [];
            return [];
        });
    }
    return _myStickerPacksPromise;
}
preloadStickerPacks();

async function loadMyStickerPacks(force = false) {
    if (_myStickerPacks !== null && !force) {
        return _myStickerPacks;
    }
    return await preloadStickerPacks(force);
}

function isStickersAllowed(el) {
    if (!el) return false;
    if (el.dataset && el.dataset.stickers === "0") return false;
    if (el.closest('.no-stickers, .existing_sticker_card, .draft_sticker_card, .stickers_modal_grid_wrap')) return false;

    if (el.closest('.fc_chat_box, .fc_input_bar, .messenger-app, #im_page, .udlg-dialog, .udlg-box') || window.location.pathname.startsWith('/im')) {
        return true;
    }

    if (el.closest('form[action*="/makePost"], #write .post-opts, .wall-post-form')) {
        return false;
    }

    if (el.closest('.reply_form, .comment_form, .comments_form, .comments, .comment-form, #comments, [id^="comment"]') ||
        el.closest('form[action*="/comment"], form[action*="/reply"], form[action*="act=comment"]')) {
        return true;
    }

    const writeBox = el.closest('#write');
    if (writeBox) {
        const form = writeBox.querySelector('form');
        const action = form ? (form.getAttribute('action') || '') : '';
        if (action.includes('/makePost')) return false;
        if (!writeBox.querySelector('.post-opts')) return true;
    }

    return false;
}

const emojiTippy = tippy.delegate("body", {
    content: "",
    allowHTML: true,
    target: '.emoji_picker_entrypoint, .fc_emoji_btn',
    interactive: true,
    interactiveDebounce: 0,
    trigger: 'click',
    placement: 'top-end',
    theme: 'emoji light vk',
    zIndex: 100005,
    delay: 0,
    onShow: async function (that) {
        window._currentEmojiTrigger = that.reference;

        if (window.emojiData == null) {
            that.setContent(`<div class="emoji-picker-wrap"><div class="emoji-picker-loading">${tr('loading') || 'Загрузка'}...</div></div>`);
            await loadEmojiData();
        }

        const allow_stickers = isStickersAllowed(that.reference);
        const bodyEl = renderEmojiGrid(allow_stickers);
        that.setContent(bodyEl);
    }
});

function renderEmojiGrid(with_stickers = false) {
    if (!window.emojiData) {
        const wrap = document.createElement('div');
        wrap.className = 'emoji-picker-wrap';
        wrap.innerHTML = `<div class="emoji-picker-loading">${tr('loading') || 'Загрузка'}...</div>`;
        return wrap;
    }

    if (_cachedEmojiWrapper) {
        updateRecentSmilesInPicker();
        if (with_stickers) {
            _cachedEmojiWrapper.classList.remove('stickers-disabled');
            _cachedEmojiWrapper.classList.add('stickers-enabled');
            updateStickerPacksInPicker(_cachedEmojiWrapper);
        } else {
            _cachedEmojiWrapper.classList.remove('stickers-enabled');
            _cachedEmojiWrapper.classList.add('stickers-disabled');
            const scroll = _cachedEmojiWrapper.querySelector('.emoji-picker-scroll');
            if (scroll) scroll.scrollTop = 0;
        }
        return _cachedEmojiWrapper;
    }

    const wrapper = document.createElement('div');
    wrapper.className = `emoji-picker-wrap ${with_stickers ? 'stickers-enabled' : 'stickers-disabled'}`;

    const recent = getRecentSmiles();
    let groupsHtml = '';

    // Recent group
    if (recent.length > 0) {
        const localizedRecent = tr("emoji_group_recent") || "Недавние";
        const recentItems = recent.map(smile => {
            const hex = encode_emoji(smile);
            return `<span class="emoji-picker-item emoji emoji_${hex}" data-emoji="${smile}" title="${smile}">${smile}</span>`;
        }).join('');

        groupsHtml += `
            <div class="emoji-picker-group" data-group="recent">
                <div class="group-title"><b>${localizedRecent}</b></div>
                <div class="emoji-picker-group-items">${recentItems}</div>
            </div>
        `;
    }

    // Standard groups
    window.emojiData.forEach(group => {
        const localizedGroup = tr("emoji_group_" + group.slug) || group.slug;
        const itemsHtml = group.emojis.map(item => {
            return `<span class="emoji-picker-item emoji emoji_${item.hex}" data-emoji="${item.emoji}" title="${escapeHtml(item.name || '')}">${item.emoji}</span>`;
        }).join('');

        groupsHtml += `
            <div class="emoji-picker-group" data-group="${group.slug}">
                <div class="group-title"><b>${localizedGroup}</b></div>
                <div class="emoji-picker-group-items">${itemsHtml}</div>
            </div>
        `;
    });

    // Category buttons for the footer
    let catButtonsHtml = '';
    if (recent.length > 0) {
        catButtonsHtml += `
            <div class="emoji-cat-btn" data-target="recent" title="${tr('emoji_group_recent')}">
                <span class="emoji-cat-btn-recent"></span>
            </div>
        `;
    }

    window.emojiData.forEach((group, idx) => {
        const localizedGroup = tr("emoji_group_" + group.slug) || group.slug;
        const isActive = !recent.length && idx === 0;
        catButtonsHtml += `
            <div class="emoji-cat-btn ${isActive ? 'active' : ''}" data-target="${group.slug}" title="${localizedGroup}">
                <span class="emoji emoji_${group.icon_hex}"></span>
            </div>
        `;
    });

    wrapper.innerHTML = `
        <div class="emoji-picker-scroll">
            <div class="emoji-picker-groups">${groupsHtml}</div>
            <div class="sticker-picker-groups"></div>
        </div>
        <div class="emoji-picker-footer">
            <div class="emoji-footer-left">
                <div class="emoji-main-tab active" data-target="emojis" title="${tr('emoji_group_smileys_emotion') || 'Смайлы'}">
                    <span class="emoji-icon"></span>
                </div>
                <div class="emoji-category-nav">
                    ${catButtonsHtml}
                </div>
                <div class="emoji-scroll-arrow" title="${tr('next') || 'Далее'}">
                    <svg viewBox="0 0 8 12" width="5" height="9">
                        <path d="M1.5 1l5 5-5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="emoji-footer-divider"></div>
            <div class="emoji-footer-right">
                <div class="sticker-tabs"></div>
                <a href="/stickers" class="sticker-store-btn" title="${tr('stickers_store') || 'Магазин стикеров'}"></a>
            </div>
        </div>
    `;

    // Event delegation: emoji clicks and sticker clicks
    wrapper.addEventListener('click', (e) => {
        const item = e.target.closest('.emoji-picker-item');
        if (item) {
            appendEmoji(e);
            return;
        }

        const stickerItem = e.target.closest('.sticker-picker-item');
        if (stickerItem) {
            if (stickerItem._longPressTriggered) {
                delete stickerItem._longPressTriggered;
                return;
            }
            const stickerId = parseInt(stickerItem.dataset.stickerId);
            const packId = parseInt(stickerItem.dataset.packId);
            const photo128 = stickerItem.querySelector('img')?.src;
            const photo512 = stickerItem.dataset.url512;
            sendOrAttachSticker(stickerId, packId, { photo_128: photo128, photo_512: photo512 });
            return;
        }
    });

    // Press-and-hold preview on sticker items
    initStickerPickerHoldPreview(wrapper);

    const scrollContainer = wrapper.querySelector('.emoji-picker-scroll');
    const categoryNav = wrapper.querySelector('.emoji-category-nav');
    const arrowBtn = wrapper.querySelector('.emoji-scroll-arrow');
    const mainTab = wrapper.querySelector('.emoji-main-tab');
    const footerLeft = wrapper.querySelector('.emoji-footer-left');
    const stickerTabs = wrapper.querySelector('.sticker-tabs');

    // Main smileys tab click handler
    if (mainTab) {
        mainTab.addEventListener('click', () => {
            if (scrollContainer) scrollContainer.scrollTo({ top: 0, behavior: 'smooth' });
            if (categoryNav) categoryNav.scrollTo({ left: 0, behavior: 'smooth' });
            wrapper.querySelectorAll('.s-tab').forEach(b => b.classList.remove('active'));
            mainTab.classList.add('active');
        });
    }

    // Category click handler
    wrapper.addEventListener('click', (e) => {
        const catBtn = e.target.closest('.emoji-cat-btn');
        if (catBtn) {
            const targetSlug = catBtn.dataset.target;
            const targetGroupEl = scrollContainer.querySelector(`.emoji-picker-group[data-group="${targetSlug}"]`);
            if (targetGroupEl) {
                const targetTop = targetGroupEl.offsetTop - scrollContainer.offsetTop;
                scrollContainer.scrollTo({ top: targetTop, behavior: 'smooth' });
            }
            wrapper.querySelectorAll('.emoji-cat-btn').forEach(b => b.classList.remove('active'));
            catBtn.classList.add('active');
            wrapper.querySelectorAll('.s-tab').forEach(b => b.classList.remove('active'));
            if (mainTab) mainTab.classList.add('active');
            return;
        }

        const sTab = e.target.closest('.s-tab');
        if (sTab) {
            const packId = sTab.dataset.packId;
            const targetPackEl = scrollContainer.querySelector(`.sticker-picker-pack[data-pack-id="${packId}"]`);
            if (targetPackEl) {
                const targetTop = targetPackEl.offsetTop - scrollContainer.offsetTop;
                scrollContainer.scrollTo({ top: targetTop, behavior: 'smooth' });
            }
            wrapper.querySelectorAll('.s-tab').forEach(b => b.classList.remove('active'));
            sTab.classList.add('active');
            if (mainTab) mainTab.classList.remove('active');
            wrapper.querySelectorAll('.emoji-cat-btn').forEach(b => b.classList.remove('active'));
            return;
        }
    });

    // Arrow click handler: horizontal scroll for category tabs
    if (arrowBtn && categoryNav) {
        arrowBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const step = 65;
            if (categoryNav.scrollLeft + categoryNav.clientWidth >= categoryNav.scrollWidth - 8) {
                categoryNav.scrollTo({ left: 0, behavior: 'smooth' });
            } else {
                categoryNav.scrollBy({ left: step, behavior: 'smooth' });
            }
        });
    }

    // Horizontal wheel scroll handler for category tabs
    if (footerLeft && categoryNav) {
        footerLeft.addEventListener('wheel', (e) => {
            if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                e.preventDefault();
                let move = e.deltaY;
                if (e.deltaMode === 1) move *= 28;
                else if (e.deltaMode === 2) move *= 100;
                categoryNav.scrollBy({ left: move, behavior: 'auto' });
            }
        }, { passive: false });
    }

    // Scroll listener to update active tab (emojis vs sticker packs)
    let isScrollingTimeout = null;
    scrollContainer.addEventListener('scroll', () => {
        if (isScrollingTimeout) return;
        isScrollingTimeout = setTimeout(() => {
            isScrollingTimeout = null;
            const scrollPos = scrollContainer.scrollTop + 20;

            const packs = scrollContainer.querySelectorAll('.sticker-picker-pack');
            let activePackId = null;
            for (const p of packs) {
                if (p.offsetTop - scrollContainer.offsetTop <= scrollPos) {
                    activePackId = p.dataset.packId;
                } else {
                    break;
                }
            }

            if (activePackId) {
                if (mainTab) mainTab.classList.remove('active');
                wrapper.querySelectorAll('.emoji-cat-btn').forEach(b => b.classList.remove('active'));
                const sTabs = wrapper.querySelectorAll('.s-tab');
                sTabs.forEach(tab => {
                    const isActive = tab.dataset.packId === activePackId;
                    tab.classList.toggle('active', isActive);
                    if (isActive && stickerTabs) {
                        const tabLeft = tab.offsetLeft;
                        const tabRight = tabLeft + tab.offsetWidth;
                        const navScrollLeft = stickerTabs.scrollLeft;
                        const navWidth = stickerTabs.clientWidth;
                        if (tabLeft < navScrollLeft) {
                            stickerTabs.scrollTo({ left: tabLeft, behavior: 'smooth' });
                        } else if (tabRight > navScrollLeft + navWidth) {
                            stickerTabs.scrollTo({ left: tabRight - navWidth, behavior: 'smooth' });
                        }
                    }
                });
            } else {
                if (mainTab) mainTab.classList.add('active');
                wrapper.querySelectorAll('.s-tab').forEach(b => b.classList.remove('active'));

                const groups = scrollContainer.querySelectorAll('.emoji-picker-group');
                let activeGroupSlug = null;
                for (const grp of groups) {
                    if (grp.offsetTop - scrollContainer.offsetTop <= scrollPos) {
                        activeGroupSlug = grp.dataset.group;
                    } else {
                        break;
                    }
                }
                if (activeGroupSlug) {
                    const btns = wrapper.querySelectorAll('.emoji-cat-btn');
                    btns.forEach(b => {
                        const isActive = b.dataset.target === activeGroupSlug;
                        b.classList.toggle('active', isActive);
                        if (isActive && categoryNav) {
                            const btnLeft = b.offsetLeft;
                            const btnRight = btnLeft + b.offsetWidth;
                            const navScrollLeft = categoryNav.scrollLeft;
                            const navWidth = categoryNav.clientWidth;
                            if (btnLeft < navScrollLeft) {
                                categoryNav.scrollTo({ left: btnLeft, behavior: 'smooth' });
                            } else if (btnRight > navScrollLeft + navWidth) {
                                categoryNav.scrollTo({ left: btnRight - navWidth, behavior: 'smooth' });
                            }
                        }
                    });
                }
            }
        }, 50);
    }, { passive: true });

    _cachedEmojiWrapper = wrapper;

    if (with_stickers) {
        updateStickerPacksInPicker(wrapper);
    }

    return wrapper;
}

async function updateStickerPacksInPicker(wrapper) {
    if (!wrapper) return;
    const stickerGroupsEl = wrapper.querySelector('.sticker-picker-groups');
    const stickerTabsEl = wrapper.querySelector('.sticker-tabs');
    if (!stickerGroupsEl || !stickerTabsEl) return;

    const packs = await loadMyStickerPacks();
    const recentStickers = getRecentStickers();

    // 1. Build tabs HTML
    let tabsHtml = '';
    if (recentStickers.length > 0) {
        tabsHtml += `
            <div class="s-tab s-tab-recent" data-pack-id="recent" title="${tr('stickers_recent') || 'Недавние'}">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
        `;
    }

    packs.forEach(pack => {
        const iconUrl = pack.photo_128 || (pack.stickers && pack.stickers[0] ? pack.stickers[0].photo_128 : '');
        tabsHtml += `
            <div class="s-tab" data-pack-id="${pack.id}" title="${escapeHtml(pack.name || '')}">
                <img src="${iconUrl}" loading="lazy" alt="" />
            </div>
        `;
    });
    stickerTabsEl.innerHTML = tabsHtml;

    // 2. Build sticker groups HTML
    let packsHtml = '';

    // Recent stickers pack
    if (recentStickers.length > 0) {
        const recentItemsHtml = recentStickers.map(stk => `
            <div class="sticker-picker-item" data-sticker-id="${stk.id}" data-pack-id="${stk.pack_id || ''}" data-url512="${stk.photo_512 || ''}" title="">
                <img src="${stk.photo_128}" loading="lazy" alt="sticker" />
            </div>
        `).join('');

        packsHtml += `
            <div class="sticker-picker-pack" data-pack-id="recent">
                <div class="group-title"><b>${tr('stickers_recent') || 'Недавние'}</b></div>
                <div class="sticker-picker-grid">${recentItemsHtml}</div>
            </div>
        `;
    }

    // Installed packs
    if (packs.length > 0) {
        packs.forEach(pack => {
            const stickers = pack.stickers || [];
            const stickersHtml = stickers.map(stk => {
                const p128 = stk.photo_128 || `/sticker/${pack.id}/${stk.id}_128.webp`;
                const p512 = stk.photo_512 || `/sticker/${pack.id}/${stk.id}_512.webp`;
                return `
                    <div class="sticker-picker-item" data-sticker-id="${stk.id}" data-pack-id="${pack.id}" data-url512="${p512}" title="${escapeHtml(stk.emoji || '')}">
                        <img src="${p128}" loading="lazy" alt="sticker" />
                    </div>
                `;
            }).join('');

            packsHtml += `
                <div class="sticker-picker-pack" data-pack-id="${pack.id}">
                    <div class="group-title"><b>${escapeHtml(pack.name)}</b></div>
                    <div class="sticker-picker-grid">${stickersHtml}</div>
                </div>
            `;
        });
    } else {
        packsHtml += `
            <div class="sticker-picker-pack sticker-picker-empty-pack" data-pack-id="empty">
                <div class="sticker-picker-empty">
                    <div class="sticker-empty-text">${tr('stickers_empty_prompt') || 'У вас пока нет стикеров'}</div>
                    <a href="/stickers" class="button sticker-empty-btn">${tr('stickers_goto_store') || 'Магазин стикеров'}</a>
                </div>
            </div>
        `;
    }

    stickerGroupsEl.innerHTML = packsHtml;
}

function initStickerPickerHoldPreview(wrapper) {
    let holdTimer = null;
    let previewEl = null;
    let activeItem = null;

    function createPreview(item) {
        if (!item) return;
        const url512 = item.dataset.url512 || item.querySelector('img')?.src;
        if (!url512) return;

        removePreview();
        activeItem = item;
        item._longPressTriggered = true;
        wrapper.classList.add('is-sticker-holding');

        previewEl = document.createElement('div');
        previewEl.className = 'stickers_hold_preview_wrap';
        previewEl.innerHTML = `<img src="${url512}" alt="preview" />`;
        document.body.appendChild(previewEl);
    }

    function removePreview() {
        if (previewEl) {
            previewEl.remove();
            previewEl = null;
        }
        wrapper.classList.remove('is-sticker-holding');
        activeItem = null;
    }

    function cancelTimer() {
        if (holdTimer) {
            clearTimeout(holdTimer);
            holdTimer = null;
        }
    }

    wrapper.addEventListener('mousedown', (e) => {
        const item = e.target.closest('.sticker-picker-item');
        if (!item || e.button !== 0) return;

        cancelTimer();
        holdTimer = setTimeout(() => {
            createPreview(item);
        }, 220);
    });

    wrapper.addEventListener('mousemove', (e) => {
        if (!previewEl) return;
        const item = document.elementFromPoint(e.clientX, e.clientY)?.closest('.sticker-picker-item');
        if (item && item !== activeItem) {
            createPreview(item);
        }
    });

    document.addEventListener('mouseup', () => {
        cancelTimer();
        if (previewEl) {
            removePreview();
        }
    });

    // Touch events
    wrapper.addEventListener('touchstart', (e) => {
        const item = e.target.closest('.sticker-picker-item');
        if (!item) return;

        cancelTimer();
        holdTimer = setTimeout(() => {
            createPreview(item);
        }, 220);
    }, { passive: true });

    wrapper.addEventListener('touchmove', (e) => {
        if (!previewEl) return;
        const touch = e.touches[0];
        if (!touch) return;
        const item = document.elementFromPoint(touch.clientX, touch.clientY)?.closest('.sticker-picker-item');
        if (item && item !== activeItem) {
            createPreview(item);
        }
    }, { passive: true });

    document.addEventListener('touchend', () => {
        cancelTimer();
        if (previewEl) {
            removePreview();
        }
    });

    document.addEventListener('touchcancel', () => {
        cancelTimer();
        if (previewEl) {
            removePreview();
        }
    });
}

function sendOrAttachSticker(stickerId, packId, stickerData = {}) {
    if (!stickerId) return;

    const trigger = window._currentEmojiTrigger;
    if (trigger && !isStickersAllowed(trigger)) return;

    // 1. Save to recent stickers
    addSticker({
        id: stickerId,
        pack_id: packId,
        photo_128: stickerData.photo_128 || `/sticker/${packId}/${stickerId}_128.webp`,
        photo_512: stickerData.photo_512 || `/sticker/${packId}/${stickerId}_512.webp`,
    });

    // 2. Hide tippy picker
    if (trigger && trigger._tippy) {
        trigger._tippy.hide();
    }

    // 3. Fastchats (floating chat boxes) - check first if triggered from fastchat
    if (trigger && trigger.closest('.fc_chat_box')) {
        const fcBox = trigger.closest('.fc_chat_box');
        const peerId = fcBox.id.replace('fc_chat_', '');
        if (peerId && window.im?.fastChats) {
            window.im.fastChats.sendSticker(peerId, stickerId, packId, stickerData);
            return;
        }
    }

    // 4. Full messenger chat window
    if (window.im && window.im.messenger && window.im.state && window.im.state.getCurrentConvo()) {
        window.im.messenger.sendSticker(stickerId, packId, stickerData);
        return;
    }

    // 5. Comments only (attach media)
    if (trigger) {
        const box = trigger.closest('.reply_form, form, #write');
        if (box && isStickersAllowed(trigger)) {
            const hContainer = box.querySelector('.post-horizontal') || document.querySelector('.post-horizontal');
            if (hContainer) {
                const postButtons = hContainer.closest('.post-buttons');
                if (postButtons) {
                    postButtons.style.display = 'block';
                }
                // Only allow one sticker attached in comments
                hContainer.querySelectorAll('.sticker-attached-item').forEach(el => el.remove());

                const imgUrl = stickerData.photo_128 || `/sticker/${packId}/${stickerId}_128.webp`;
                const attachLink = document.createElement('a');
                attachLink.dataset.type = 'sticker';
                attachLink.dataset.id = stickerId;
                attachLink.className = 'sticker-attached-item';
                attachLink.innerHTML = `
                    <img src="${imgUrl}" alt="sticker" />
                    <span class="sticker-attached-remove">&times;</span>
                `;
                attachLink.querySelector('.sticker-attached-remove').addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    attachLink.remove();
                });
                hContainer.appendChild(attachLink);
                return;
            }
        }
    }
}

function updateRecentSmilesInPicker() {
    if (!_cachedEmojiWrapper) return;
    const recent = getRecentSmiles();
    let recentGroup = _cachedEmojiWrapper.querySelector('.emoji-picker-group[data-group="recent"]');
    const scrollContainer = _cachedEmojiWrapper.querySelector('.emoji-picker-groups');
    const categoryNav = _cachedEmojiWrapper.querySelector('.emoji-category-nav');

    if (recent.length === 0) {
        if (recentGroup) recentGroup.remove();
        const recentBtn = categoryNav.querySelector('.emoji-cat-btn[data-target="recent"]');
        if (recentBtn) recentBtn.remove();
        return;
    }

    const recentItemsHtml = recent.map(smile => {
        const hex = encode_emoji(smile);
        return `<span class="emoji-picker-item emoji emoji_${hex}" data-emoji="${smile}" title="${smile}">${smile}</span>`;
    }).join('');

    if (!recentGroup && scrollContainer) {
        recentGroup = document.createElement('div');
        recentGroup.className = 'emoji-picker-group';
        recentGroup.dataset.group = 'recent';
        recentGroup.innerHTML = `
            <div class="group-title"><b>${tr("emoji_group_recent") || "Недавние"}</b></div>
            <div class="emoji-picker-group-items">${recentItemsHtml}</div>
        `;
        scrollContainer.prepend(recentGroup);

        // Also add recent icon to footer category nav if not present
        if (categoryNav && !categoryNav.querySelector('.emoji-cat-btn[data-target="recent"]')) {
            const btn = document.createElement('div');
            btn.className = 'emoji-cat-btn';
            btn.dataset.target = 'recent';
            btn.title = tr('emoji_group_recent') || 'Недавние';
            btn.innerHTML = `
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            `;
            categoryNav.prepend(btn);
        }
    } else if (recentGroup) {
        const itemsContainer = recentGroup.querySelector('.emoji-picker-group-items');
        if (itemsContainer) itemsContainer.innerHTML = recentItemsHtml;
    }
}

async function getStickerpacks() {
    try {
        return await window.OVKAPI.call('stickers.get', {});
    } catch (e) {
        return { count: 0, items: [] };
    }
}

async function getAllStickerpacks() {
    try {
        return await window.OVKAPI.call('stickers.getAll', {});
    } catch (e) {
        return { count: 0, items: [] };
    }
}

async function getStickersFromPack(packId) {
    try {
        return await window.OVKAPI.call('stickers.getFrom', { 'stickerpack_id': packId });
    } catch (e) {
        return { count: 0, items: [] };
    }
}

async function buyStickerpack(buyPackId) {
    try {
        const res = await window.OVKAPI.call('stickers.buy', { 'stickerpack_id': buyPackId });
        await loadMyStickerPacks(true);
        if (_cachedEmojiWrapper) {
            updateStickerPacksInPicker(_cachedEmojiWrapper);
        }
        return res;
    } catch (e) {
        fastError(tr('purchase_failed'));
        return;
    }
}

// Recent smiles storage
function getRecentSmiles() {
    const l = localStorage.getItem("recent_smiles") || "[]";
    try {
        return JSON.parse(l);
    } catch (e) {
        return [];
    }
}

function getRecentStickers() {
    try {
        return JSON.parse(localStorage.getItem("recent_sticker") || "[]");
    } catch (e) {
        return [];
    }
}

function findStickerData(stickerId) {
    const sId = Number(stickerId);
    if (!sId) return null;

    window._stickersCache = window._stickersCache || new Map();
    if (window._stickersCache.has(sId)) {
        return window._stickersCache.get(sId);
    }

    if (window.myStickerPacks && Array.isArray(window.myStickerPacks)) {
        for (const p of window.myStickerPacks) {
            if (p && p.stickers && Array.isArray(p.stickers)) {
                const s = p.stickers.find(stk => Number(stk.id) === sId);
                if (s) {
                    const p128 = s.photo_128 || `/sticker/${p.id}/${sId}_128.webp`;
                    const p256 = s.photo_256 || p128;
                    const p512 = s.photo_512 || `/sticker/${p.id}/${sId}_512.webp`;
                    const data = {
                        id: sId,
                        sticker_id: sId,
                        product_id: p.id,
                        photo_128: p128,
                        photo_256: p256,
                        photo_512: p512,
                        images: s.images || [
                            { url: p128, width: 128, height: 128 },
                            { url: p256, width: 256, height: 256 },
                            { url: p512, width: 512, height: 512 }
                        ]
                    };
                    window._stickersCache.set(sId, data);
                    return data;
                }
            }
        }
    }

    const recent = getRecentStickers();
    const r = recent.find(stk => Number(stk.id) === sId);
    if (r) {
        const pId = Number(r.pack_id || 0);
        const p128 = r.photo_128 || (pId ? `/sticker/${pId}/${sId}_128.webp` : '');
        const p256 = r.photo_256 || p128;
        const p512 = r.photo_512 || (pId ? `/sticker/${pId}/${sId}_512.webp` : p128);
        const data = {
            id: sId,
            sticker_id: sId,
            product_id: pId,
            photo_128: p128,
            photo_256: p256,
            photo_512: p512,
            images: [
                { url: p128, width: 128, height: 128 },
                { url: p256, width: 256, height: 256 },
                { url: p512, width: 512, height: 512 }
            ]
        };
        window._stickersCache.set(sId, data);
        return data;
    }

    return null;
}
window.findStickerData = findStickerData;

function addSmile(smile) {
    const s = getRecentSmiles();
    let g = s.filter((i) => { return i != smile });
    g.unshift(smile);
    g = g.slice(0, 100);
    localStorage.setItem("recent_smiles", JSON.stringify(g));
    return g;
}

function addSticker(sticker = {}) {
    if (!sticker.id) return [];
    const s = getRecentStickers();
    let g = s.filter((i) => { return Number(i.id) !== Number(sticker.id); });
    g.unshift(sticker);
    g = g.slice(0, 32);
    localStorage.setItem("recent_sticker", JSON.stringify(g));
    if (_cachedEmojiWrapper) {
        updateStickerPacksInPicker(_cachedEmojiWrapper);
    }
    return g;
}

function clearRecentSmiles() {
    localStorage.setItem("recent_smiles", "[]");
    localStorage.setItem("recent_sticker", "[]");
}

function OpenStickersStore() {
    window.location.href = '/stickers';
}

function OpenStickerpack(pack_id) {
    if (typeof openStickerPackModal === 'function' && window.location.pathname === '/stickers') {
        openStickerPackModal(pack_id);
    } else {
        window.location.href = '/stickers/' + pack_id;
    }
}

function confirmUninstallPack(form) {
    MessageBox(tr('warning'), tr('stickers_uninstall_confirm'), [tr('yes'), tr('cancel')], [
        () => {
            form.submit();
        },
        Function.noop
    ]);
}

function confirmDeletePack(form) {
    MessageBox(tr('warning'), tr('stickers_delete_pack_confirm'), [tr('yes'), tr('cancel')], [
        () => {
            let hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'action';
            hidden.value = 'delete';
            form.appendChild(hidden);
            form.submit();
        },
        Function.noop
    ]);
}

async function withdrawStickers(id, currentBalance) {
    if (!currentBalance || currentBalance <= 0) {
        MessageBox(tr('stickers_withdrawal'), tr('stickers_withdrawal_empty'), [tr('ok') || "OK"], [Function.noop]);
        return;
    }

    let taxPercent = typeof window.stickersWithdrawTax !== 'undefined' ? window.stickersWithdrawTax : 0;

    let bodyHtml = `
    <div class="stickers_withdraw_modal">
        <div class="stickers_withdraw_available">
            ${tr('stickers_withdraw_modal_available')}: <b class="stickers_withdraw_available_val">${tr('coins', currentBalance)}</b>
        </div>
        <div class="stickers_withdraw_field">
            <label for="diag_withdraw_input" class="stickers_withdraw_label">
                ${tr('stickers_withdraw_modal_amount_label')}
            </label>
            <div class="stickers_withdraw_input_row">
                <input type="number" id="diag_withdraw_input" class="stickers_withdraw_input" min="1" max="${currentBalance}" step="1" value="${currentBalance}" />
                <a href="javascript:void(0)" id="diag_withdraw_all" class="stickers_withdraw_all_btn">
                    ${tr('stickers_withdraw_all')}
                </a>
            </div>
        </div>
        <div class="stickers_withdraw_calc_box">
            <div class="stickers_withdraw_tax_row" id="diag_tax_row">
                ${tr('stickers_withdraw_tax')}: <span id="diag_tax_val" class="stickers_withdraw_tax_val">0%</span>
            </div>
            <div class="stickers_withdraw_receive_row">
                ${tr('stickers_withdraw_receive')} <span id="diag_receive_val" class="stickers_withdraw_receive_val">0</span>
            </div>
        </div>
        <div id="diag_withdraw_err" class="stickers_withdraw_err"></div>
    </div>
    `;

    let msg = new CMessageBox({
        title: tr('stickers_withdraw_modal_title'),
        body: bodyHtml,
        buttons: [tr('stickers_withdraw_execute'), tr('cancel')],
        callbacks: [
            async () => {
                const node = msg.getNode();
                const input = node.find('#diag_withdraw_input').nodes[0];
                const errBox = node.find('#diag_withdraw_err').nodes[0];

                let amount = parseFloat(input ? input.value : '0');
                if (isNaN(amount) || amount <= 0 || amount > currentBalance) {
                    if (errBox) {
                        errBox.textContent = tr('stickers_withdrawal_bad_amount') + ' (1 - ' + currentBalance + ')';
                        errBox.style.display = 'block';
                    }
                    return;
                }

                msg.close();

                try {
                    let res = await API.Stickers.withdrawFunds(id, amount);
                    let received = res.received || amount;
                    MessageBox(
                        tr('stickers_withdraw_modal_title'),
                        tr('stickers_withdrawal_success', tr('coins', Math.round(received))),
                        [tr('ok') || "OK"],
                        [() => location.reload()]
                    );
                } catch (e) {
                    MessageBox(tr('error'), (e && e.message) ? e.message : tr('error'), [tr('ok') || "OK"], [Function.noop]);
                }
            },
            () => {
                msg.close();
            }
        ],
        close_on_buttons: false,
        unique_name: 'stickers_withdraw_' + id
    });

    const node = msg.getNode();
    const input = node.find('#diag_withdraw_input').nodes[0];
    const allBtn = node.find('#diag_withdraw_all').nodes[0];
    const taxVal = node.find('#diag_tax_val').nodes[0];
    const receiveVal = node.find('#diag_receive_val').nodes[0];
    const errBox = node.find('#diag_withdraw_err').nodes[0];

    function updateCalc() {
        if (!input) return;
        let amount = parseFloat(input.value);
        if (isNaN(amount) || amount <= 0 || amount > currentBalance) {
            if (errBox) {
                errBox.textContent = tr('stickers_withdrawal_bad_amount') + ' (1 - ' + currentBalance + ')';
                errBox.style.display = 'block';
            }
            if (receiveVal) receiveVal.textContent = tr('coins', 0);
            return;
        }

        if (errBox) errBox.style.display = 'none';

        let tax = (amount * taxPercent) / 100;
        let received = Math.max(0, amount - tax);

        if (taxVal) {
            if (taxPercent > 0) {
                taxVal.textContent = taxPercent + '% (' + (tax % 1 === 0 ? tax : tax.toFixed(2)) + ')';
            } else {
                taxVal.textContent = '0% (' + tr('stickers_withdraw_no_tax') + ')';
            }
        }

        if (receiveVal) {
            let roundedReceived = Math.round(received);
            receiveVal.textContent = tr('coins', roundedReceived);
        }
    }

    if (input) {
        input.addEventListener('input', updateCalc);
        input.addEventListener('change', updateCalc);
        setTimeout(() => {
            input.focus();
            input.select();
        }, 60);
    }

    if (allBtn) {
        allBtn.addEventListener('click', (ev) => {
            ev.preventDefault();
            if (input) {
                input.value = currentBalance;
                updateCalc();
            }
        });
    }

    updateCalc();
}

window._activeStickerModal = null;
window._activeStickerModalSlug = null;

async function openStickerPackModal(slugOrId, event) {
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
    }
    if (!slugOrId) return;

    if (window._activeStickerModal && window._activeStickerModalSlug === String(slugOrId)) {
        return;
    }

    if (window._activeStickerModal) {
        window._activeStickerModal.close();
    }

    const uniqueName = 'stickers_pack_view_' + slugOrId;
    let isClosed = false;

    const urlObj = new URL(location.href);
    const existingPackParam = urlObj.searchParams.get('pack');
    if (existingPackParam !== String(slugOrId)) {
        urlObj.searchParams.set('pack', slugOrId);
        history.pushState({ stickerModal: slugOrId }, '', urlObj.toString());
    }

    const cleanupUrl = () => {
        isClosed = true;
        window._activeStickerModal = null;
        window._activeStickerModalSlug = null;
        const u = new URL(location.href);
        if (u.searchParams.get('pack') === String(slugOrId)) {
            u.searchParams.delete('pack');
            history.replaceState(null, '', u.toString());
        }
    };

    const initialBody = `
        <div class="stickers_pack_modal">
            <div class="stickers_modal_loading">
                <div class="emoji-picker-loading">${tr('loading') || 'Загрузка'}...</div>
            </div>
        </div>
    `;

    const msg = new CMessageBox({
        title: tr('stickers_pack_info_title') || 'Информация о наборе',
        body: initialBody,
        buttons: [tr('close') || 'Закрыть'],
        callbacks: [() => msg.close()],
        unique_name: uniqueName
    });

    if (!msg || !msg.getNode) {
        return;
    }

    window._activeStickerModal = msg;
    window._activeStickerModalSlug = String(slugOrId);

    const originalClose = msg.close.bind(msg);
    msg.close = function () {
        cleanupUrl();
        originalClose();
    };

    const node = msg.getNode();
    node.addClass('stickers_pack_modal_cont');

    const head = node.find('.ovk-diag-head');
    if (head.nodes[0] && !head.find('.stickers_modal_close_cross').nodes.length) {
        head.append(u('<a href="javascript:void(0)" class="stickers_modal_close_cross">&times;</a>'));
        head.find('.stickers_modal_close_cross').on('click', (e) => {
            e.preventDefault();
            msg.close();
        });
    }

    try {
        const info = await API.Stickers.getPackInfo(slugOrId);
        if (isClosed) return;

        if (head.nodes[0]) {
            const titleSpan = escapeHtml(info.name);
            const closeBtn = '<a href="javascript:void(0)" class="stickers_modal_close_cross">&times;</a>';
            head.nodes[0].innerHTML = titleSpan + closeBtn;
            head.find('.stickers_modal_close_cross').on('click', (e) => {
                e.preventDefault();
                msg.close();
            });
        }

        const coverHtml = info.cover_url
            ? `<img src="${escapeHtml(info.cover_url)}" alt="${escapeHtml(info.name)}" />`
            : `<div class="stickers_modal_cover_placeholder"></div>`;

        let authorHtml = '';
        if (info.author || info.author_url) {
            const authorName = escapeHtml(info.author || info.author_url);
            if (info.author_url) {
                authorHtml = `<a href="${escapeHtml(info.author_url)}" target="_blank" rel="noopener noreferrer">${authorName}</a>`;
            } else {
                authorHtml = authorName;
            }
        }

        let actionBtnHtml = '';
        if (info.isPurchased) {
            actionBtnHtml = `<button type="button" class="button" disabled id="stickers_modal_action_btn">${tr('stickers_purchased_label') || 'Приобретён'}</button>`;
        } else if (info.isBought || info.isOwner || info.price === 0) {
            actionBtnHtml = `<button type="button" class="button" id="stickers_modal_action_btn">${tr('add') || 'Добавить'}</button>`;
        } else {
            const priceText = tr('stickers_buy_pack', tr('coins', info.price)) || `Купить за ${info.price} голосов`;
            actionBtnHtml = `<button type="button" class="button" id="stickers_modal_action_btn">${priceText}</button>`;
        }

        const copyBtnHtml = `
            <a href="javascript:void(0)" class="stickers_modal_copy_link" id="stickers_modal_copy_btn">${tr('stickers_copy_link') || 'Скопировать ссылку'}</a>
        `;

        const count = info.count != null ? info.count : (info.stickers ? info.stickers.length : 0);
        const countText = tr('stickers_count', count);

        let stickersGridHtml = '';
        if (info.stickers && info.stickers.length > 0) {
            stickersGridHtml = info.stickers.map(s => `
                <div class="stickers_modal_item" data-url512="${escapeHtml(s.url512 || s.url)}" title="${escapeHtml(s.emoji || '')}">
                    <img src="${escapeHtml(s.url)}" alt="${escapeHtml(s.emoji || '')}" loading="lazy" draggable="false" />
                </div>
            `).join('');
        }

        const bodyHtml = `
            <div class="stickers_pack_modal">
                <div class="stickers_modal_header">
                    <div class="stickers_modal_cover_wrap">
                        ${coverHtml}
                    </div>
                    <div class="stickers_modal_info">
                        <div class="stickers_modal_title">${escapeHtml(info.name)}</div>
                        ${authorHtml ? `<div class="stickers_modal_author">${authorHtml}</div>` : ''}
                        ${info.description ? `<div class="stickers_modal_desc">${escapeHtml(info.description)}</div>` : ''}
                        <div class="stickers_modal_actions">
                            ${actionBtnHtml}
                        </div>
                    </div>
                </div>
                <div class="stickers_modal_subbar">
                    <div class="stickers_modal_count">${countText}</div>
                    ${copyBtnHtml}
                </div>
                <div class="stickers_modal_grid_wrap">
                    <div class="stickers_modal_grid">
                        ${stickersGridHtml}
                    </div>
                </div>
            </div>
        `;

        const bodyContainer = node.find('.ovk-diag-body');
        if (bodyContainer.nodes[0]) {
            bodyContainer.nodes[0].innerHTML = bodyHtml;
        }

        const copyBtn = node.find('#stickers_modal_copy_btn').nodes[0];
        if (copyBtn) {
            copyBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                const directUrl = window.location.origin + '/stickers/' + info.slug;
                await copyToClipboard(directUrl);
                const origText = copyBtn.textContent;
                copyBtn.textContent = tr('stickers_link_copied') || 'Ссылка скопирована!';
                setTimeout(() => {
                    copyBtn.textContent = origText;
                }, 2000);
            });
        }

        const actionBtn = node.find('#stickers_modal_action_btn').nodes[0];
        if (actionBtn && !actionBtn.disabled) {
            actionBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                if (!info.isAuthorized) {
                    location.href = '/login';
                    return;
                }
                actionBtn.disabled = true;

                try {
                    const res = await API.Stickers.buyPack(info.id);
                    actionBtn.disabled = true;
                    actionBtn.textContent = tr('stickers_purchased_label') || 'Приобретён';
                    info.isPurchased = true;

                    updateShopPackCard(info.slug);
                } catch (err) {
                    actionBtn.disabled = false;
                    MessageBox(tr('error'), (err && err.message) ? err.message : tr('error'), [tr('ok') || 'OK'], [Function.noop]);
                }
            });
        }

        let previewWrap = document.getElementById('stickers_hold_preview');
        if (!previewWrap) {
            previewWrap = document.createElement('div');
            previewWrap.id = 'stickers_hold_preview';
            previewWrap.className = 'stickers_hold_preview_wrap';
            previewWrap.style.display = 'none';
            previewWrap.innerHTML = '<img id="stickers_hold_preview_img" alt="" draggable="false" />';
            document.body.appendChild(previewWrap);
        }
        const previewImg = document.getElementById('stickers_hold_preview_img');
        let isHolding = false;

        function showPreview(url512) {
            if (!url512 || !previewWrap || !previewImg) return;
            previewImg.src = url512;
            previewWrap.style.display = 'flex';
            node.addClass('is-sticker-holding');
            isHolding = true;
        }

        function hidePreview() {
            if (!isHolding) return;
            isHolding = false;
            if (previewWrap) previewWrap.style.display = 'none';
            if (previewImg) previewImg.src = '';
            node.removeClass('is-sticker-holding');
        }

        const gridEl = node.find('.stickers_modal_grid').nodes[0];
        if (gridEl) {
            gridEl.addEventListener('mousedown', (e) => {
                if (e.button !== 0) return;
                const item = e.target.closest('.stickers_modal_item');
                if (item && item.dataset.url512) {
                    e.preventDefault();
                    showPreview(item.dataset.url512);
                }
            });

            const onMouseMove = (e) => {
                if (!isHolding) return;
                const el = document.elementFromPoint(e.clientX, e.clientY);
                const item = el ? el.closest('.stickers_modal_item') : null;
                if (item && item.dataset.url512 && previewImg.src !== item.dataset.url512) {
                    previewImg.src = item.dataset.url512;
                }
            };

            const onMouseUp = () => {
                if (isHolding) hidePreview();
            };

            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);

            let touchTimer = null;
            gridEl.addEventListener('touchstart', (e) => {
                const touch = e.touches[0];
                const el = document.elementFromPoint(touch.clientX, touch.clientY);
                const item = el ? el.closest('.stickers_modal_item') : null;
                if (item && item.dataset.url512) {
                    touchTimer = setTimeout(() => {
                        showPreview(item.dataset.url512);
                    }, 150);
                }
            }, { passive: true });

            const onTouchMove = (e) => {
                if (isHolding) {
                    const touch = e.touches[0];
                    const el = document.elementFromPoint(touch.clientX, touch.clientY);
                    const item = el ? el.closest('.stickers_modal_item') : null;
                    if (item && item.dataset.url512 && previewImg.src !== item.dataset.url512) {
                        previewImg.src = item.dataset.url512;
                    }
                } else if (touchTimer) {
                    clearTimeout(touchTimer);
                }
            };

            const onTouchEnd = () => {
                if (touchTimer) clearTimeout(touchTimer);
                if (isHolding) hidePreview();
            };

            window.addEventListener('touchmove', onTouchMove, { passive: true });
            window.addEventListener('touchend', onTouchEnd);
            window.addEventListener('touchcancel', onTouchEnd);

            const prevClose = msg.close.bind(msg);
            msg.close = function () {
                hidePreview();
                window.removeEventListener('mousemove', onMouseMove);
                window.removeEventListener('mouseup', onMouseUp);
                window.removeEventListener('touchmove', onTouchMove);
                window.removeEventListener('touchend', onTouchEnd);
                window.removeEventListener('touchcancel', onTouchEnd);
                prevClose();
            };
        }

    } catch (err) {
        if (isClosed) return;
        msg.close();
        MessageBox(tr('error'), (err && err.message) ? err.message : tr('error'), [tr('ok') || 'OK'], [Function.noop]);
    }
}

function updateShopPackCard(slug) {
    const cards = document.querySelectorAll('.stickers_pack_card');
    cards.forEach(card => {
        const link = card.querySelector(`a[href*="/stickers/${slug}"]`);
        if (link) {
            const footer = card.querySelector('.stickers_pack_footer');
            if (footer) {
                footer.innerHTML = `
                    <span class="stickers_pack_status_purchased">
                        ${tr('stickers_purchased_label') || 'Приобретён'}
                    </span>
                `;
            }
        }
    });
}

window.openStickerPackModal = openStickerPackModal;

window.addEventListener('popstate', (e) => {
    const u = new URL(location.href);
    const pack = u.searchParams.get('pack');
    if (!pack && window._activeStickerModal) {
        window._activeStickerModal.close();
    } else if (pack && (!window._activeStickerModal || window._activeStickerModalSlug !== pack)) {
        openStickerPackModal(pack);
    }
});

