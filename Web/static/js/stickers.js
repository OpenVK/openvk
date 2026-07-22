function appendEmoji(e) {
    let emoji = e.currentTarget.dataset.emoji;
    if (!emoji) return;

    let textarea = u(e.target).closest('#write').find('.small-textarea').nodes[0];
    if (!textarea) {
        textarea = document.querySelector('.small-textarea');
    }

    if (textarea) {
        let start = textarea.selectionStart;
        let end = textarea.selectionEnd;
        textarea.value = textarea.value.substring(0, start) + emoji + textarea.value.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
        textarea.focus();
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    addSmile(emoji);
}

function sendSticker(textarea) {

}

const emojiTippy = tippy.delegate("body", {
    content: "",
    allowHTML: true,
    target: '.emoji_picker_entrypoint',
    interactive: true,
    interactiveDebounce: 0,
    trigger: 'click',
    placement: 'top',
    theme: 'emoji light vk',
    placement: 'bottom-end',
    zIndex: 1024,
    delay: 0,
    onShow: async function (that) {
        const ref = that.reference;
        if (window.emojiData == null) {
            await loadEmojiData();
        }

        console.log("Emoji | Displaying")

        const allow_stickers = ref.dataset.stickers === "1";

        that.setContent("");
        const body = renderEmojiGrid(allow_stickers);
        that.setContent(body.last());
    }
});

function renderEmojiGrid(with_stickers = false) {
    if (!window.emojiData) return `<div style="padding:30px;">${tr('loading')}...</div>`;

    const recent = getRecentSmiles();
    const val = u(`
    <div>
        <div class="emoji-picker">
            <div class="emoji-picker-group"></div>
        </div>
        <div class="emoji-picker-footer">
            <div class="sticker-tabs">
                <div class="s-tab s-tab-smileys"></div>
            </div>
            <div class="sticker-store" onclick="OpenStickersStore()"></div>
        </div>
    </div>`);

    if (recent.length > 0) {
        const localized_group = tr("emoji_group_recent");

        val.find(".emoji-picker-group").append(`
            <div class="group-title-item" data-group="recent">
                <div class="group-title"><b>${localized_group}</b></div>
                <div class="emoji-picker-group-items"></div>
            </div>
        `);
        const block = val.find(`.emoji-picker-group .group-title-item[data-group="recent"]`)

        recent.forEach((smile) => {
            block.find(".emoji-picker-group-items").append(`<span class="emoji-picker-item" onclick="appendEmoji(event)" data-emoji="${smile}">${smile}</span>`)
        });
    }

    window.emojiData.forEach(function (group) {
        const localized_group = tr("emoji_group_"+group.slug);
        let insert_slug = group.slug;

        if (insert_slug != "flags") {
            val.find(".emoji-picker-group").append(`
                <div class="group-title-item" data-group="${insert_slug}">
                    <div class="group-title"><b>${localized_group}</b></div>
                    <div class="emoji-picker-group-items"></div>
                </div>
            `);
        } else {
            insert_slug = "symbols";
        }

        const block = val.find(`.emoji-picker-group .group-title-item[data-group="${insert_slug}"]`)
        let i = 0;
        group.emojis.forEach(function (item) {
            if (parseFloat(item.emoji_version) > 15) { // они некорректно отобразятся
                return;
            }

            if (group.slug == "flags" && ([5, 6].includes(i) || i > 7)) {
                return;
            }

            block.find(".emoji-picker-group-items").append(`<span title="${escapeHtml(item.name)}" class="emoji-picker-item" onclick="appendEmoji(event)" data-emoji="${item.emoji}">${item.emoji}</span>`);
            i += 1;
        });
    });

    if (with_stickers == true) {
        window.openvk.stickers.items.forEach(pack => {
            val.find(".sticker-tabs").append(`<div class="s-tab"><img src="${pack.photo_256}"></div>`);

            console.log(pack)
            val.find(".emoji-picker-group").append(`
            <div clas="group-title-item">
                <div class="group-title"><b>${escapeHtml(pack.name)}</b></div>
                <div class="emoji-picker-group-items"></div>
            </div>
            `)
        });
    } else {
        val.find(".emoji-picker-footer").remove();
    }

    return val;
}

async function loadEmojiData() {
    if (window.emojiData != null && window.openvk.stickers != null) {
        return window.emojiData;
    };

    console.log("loading emoji data");

    try {
        window.openvk.stickers = await getStickerpacks();
    } catch(e) {
        window.openvk.stickers = { items: [] };
    }

    const d = await fetch('/assets/packages/static/openvk/js/node_modules/unicode-emoji-json/data-by-group.json');
    const j = await d.json();

    window.emojiData = j;
}

async function getStickerpacks() {
    return await window.OVKAPI.call('stickers.get', {});
}

async function getAllStickerpacks() {
    return await window.OVKAPI.call('stickers.getAll', {});
}

async function getStickersFromPack(packId) {
   return await window.OVKAPI.call('stickers.getFrom', { 'stickerpack_id': packId });
}

async function buyStickerpack(buyPackId) {
    try {
        return await window.OVKAPI.call('stickers.buy', { 'stickerpack_id': buyPackId });
    } catch(e) {
        fastError(tr('purchase_failed'));
        return;
    }
}

// Recent smiles

function getRecentSmiles() {
    const l = localStorage.getItem("recent_smiles") ?? "[]";

    return JSON.parse(l);
}

function getRecentStickers() {
    return JSON.parse(localStorage.getItem("recent_sticker") ?? "[]");
}

function addSmile(smile) {
    const s = getRecentSmiles();
    let g = s.filter((i) => { return i != smile });
    g.unshift(smile);

    g = g.slice(0, 100);

    localStorage.setItem("recent_smiles", JSON.stringify(g));

    return g;
}

function addSticker(sticker = {}) {
    const s = getRecentStickers();
    // let g = s.filter(i => { return i != smile });
    s.unshift(sticker);

    localStorage.setItem("recent_sticker", JSON.stringify(s));

    return s;
}

function clearRecentSmiles() {
    localStorage.setItem("recent_smiles", "[]");
    localStorage.setItem("recent_sticker", "[]");
}

let _stickersActiveTab = 'all';

function _renderStickersGrid(container, packs) {
    container.html('');
    if (!packs || packs.length === 0) {
        container.append('<div class="stickers-empty">' + tr('no_stickers') + '</div>');
        return;
    }
    packs.items.forEach(function (pack) {
        const price = pack.price ? String(pack.price) : tr('free');
        container.append(
            `<div class="stickers-pack" onclick="OpenStickerpack(${pack.id})">
                <img src="${(pack.photo_256 || pack.photo_128 || '')}">
                <div class="stickers-pack-name">${escapeHtml(pack.name || '')}</div>
                <div class="stickers-pack-price">${price}</div>
            </div>`
        );
    });
}

async function _switchStickersTab(tabId, tabsEl, gridEl) {
    _stickersActiveTab = tabId;

    tabsEl.find('#activetabs').attr("id", "");
    tabsEl.find('[data-sticker-tab="' + tabId + '"]').attr("id", "activetabs");

    //gridEl.html('<div class="stickers-loading">' + tr('loading') + '...</div>');

    let packs = [];
    switch (tabId) {
        case 'all':
            packs = await getAllStickerpacks();
            break;
        case 'own':
            packs = await getStickerpacks();
            break;
        case 'deleted':
            const allPacks = await getAllStickerpacks();
            const ownPacks = await getStickerpacks();
            const ownIds = {};
            ownPacks.forEach(function (p) { ownIds[p.id] = true; });
            packs = allPacks.filter(function (p) { return !ownIds[p.id]; });
            break;
    }

    _renderStickersGrid(gridEl, packs);
}

function OpenStickersStore() {
    const cmsg = new CMessageBox({
        title: tr("stickers_store"),
        custom_template: u(`
        <div class="ovk-photo-view-dimmer">
            <div class="ovk-photo-view">
                <div class="stickers-store">
                    <div>
                        <div class="mb_tabs"></div>
                    </div>
                    <div>
                        <div class="stickers-grid"></div>
                    </div>
                </div>
            </div>
        </div>
        `)
    });

    const container = cmsg.getNode();
    const tabsEl = container.find('.mb_tabs');
    const gridEl = container.find('.stickers-grid');

    const tabDefs = [
        { id: 'all',    label: tr('sticker_tab_all') },
        { id: 'own',    label: tr('sticker_tab_own') },
        { id: 'deleted', label: tr('sticker_tab_deleted') },
    ];

    tabDefs.forEach(function (tab) {
        tabsEl.append(
            `<div class="mb_tab" data-sticker-tab="${tab.id}">
                ${escapeHtml(tab.label)}
            </div>`
        );
    });

    tabsEl.on('click', '[data-sticker-tab]', function () {
        const tabId = this.dataset.stickerTab;
        if (tabId === _stickersActiveTab) {
            return;
        };

        _switchStickersTab(tabId, tabsEl, gridEl);
    });

    _switchStickersTab(_stickersActiveTab, tabsEl, gridEl);
}

function OpenStickerpack(pack_id) {

}
