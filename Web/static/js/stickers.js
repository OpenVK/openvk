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

    const val = u(`
    <div>
        <div class="emoji-picker">
            <div class="emoji-picker-group"></div>
        </div>
        <div class="emoji-picker-footer">
            <div class="sticker-tabs">
                <div class="s-tab s-tab-smileys"></div>
            </div>
            <div class="sticker-store"></div>
        </div>
    </div>`);

    window.emojiData.forEach(function (group) {
        const localized_group = tr("emoji_group_"+group.slug);
        let insert_slug = group.slug;

        if (insert_slug != "flags") {
            val.find(".emoji-picker-group").append(`
                <div class="group-title-item" data-group="${insert_slug}">
                    <div class="group-title">${localized_group}</div>
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
                <div class="group-title">${escapeHtml(pack.name)}</div>
                <div class="emoji-picker-group-items"></div>
            </div>
            `)
        });
    } else {
        val.find(".emoji-picker-footer").remove();
    }

    return val;
}

async function _updStickersInfo() {
    try {
        window.openvk.stickers = await getStickerpacks();
    } catch(e) {
        window.openvk.stickers = { items: [] };
    }
}

async function loadEmojiData() {
    if (window.emojiData != null && window.openvk.stickers != null) {
        return Promise.resolve(window.emojiData)
    };

    await _updStickersInfo();

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
    const l = localStorage.getItem("recent_smiles") ?? "";

    return l.split("");
}

function getRecentStickers() {
    const l = localStorage.getItem("recent_sticker") ?? "[]";

    return JSON.parse(l);
}

function addSmile(smile) {
    const s = getRecentSmiles();
    let g = s.filter(i => { return i != smile });
    g.unshift(smile);

    localStorage.setItem("recent_smiles", g.join(""));

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
    localStorage.setItem("recent_smiles", "");
    localStorage.setItem("recent_sticker", "");
}
