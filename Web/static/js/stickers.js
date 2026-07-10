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

(function() {
let emojiTippy = tippy.delegate("body", {
    content: "",
    allowHTML: true,
    target: '.emoji_picker_entrypoint',
    interactive: true,
    interactiveDebounce: 0,
    trigger: 'click',
    placement: 'top',
    theme: 'emoji light vk',
    zIndex: 1024,
    onShow: async function(that) {
        if (!window.emojiData) {
          await loadEmojiData();
          that.setContent(renderEmojiGrid());
        } else {
          that.setContent(renderEmojiGrid());
        }
    }
});

// clicking on tabs, document.querySelector(".emoji-picker").scrollTo(0, 10)

function renderEmojiGrid() {
    if (!window.emojiData) return '<div style="padding:30px;">' + tr('loading') + '...</div>';

    var html = '<div class="emoji-picker">';
    window.emojiData.forEach(function (group) {
        if (group.slug == "flags") { // от греха подальше
          return;
        }

        const localized_group = group.slug;
        html += '<div class="emoji-picker-group">';
        html += '<div class="group-title">' + tr("emoji_group_"+localized_group) + '</div>';
        html += '<div class="emoji-picker-group-items">';
        group.emojis.forEach(function (item) {
            if (parseFloat(item.emoji_version) > 15) { // они некорректно отрендерятся
              return;
            }
            html += '<span title="' + escapeHtml(item.name) +'" class="emoji-picker-item" onclick="appendEmoji(event)" data-emoji="' + item.emoji + '">' + item.emoji + '</span>';
        });
        html += '</div></div>';
    });
    html += `</div>
    <div class="emoji-picker-footer">
      <div class="sticker-tabs">
        <div class="s-tab s-tab-smileys"></div>`

    window.openvk.stickers.items.forEach(pack => {
        html += `<div class="s-tab"><img src="${pack.photo_256}"></div>`
    });

    html += `
      </div>
      <div class="sticker-store"></div>
    </div>`;

    return html;
}

async function _updStickersInfo() {
    try {
        window.openvk.stickers = await window.OVKAPI.call("stickers.get", {});
    } catch(e) {
        window.openvk.stickers = { items: [] };
    }
}

async function loadEmojiData() {
  if (window.emojiData) return Promise.resolve(window.emojiData);
  await _updStickersInfo();

  return fetch('/assets/packages/static/openvk/js/node_modules/unicode-emoji-json/data-by-group.json')
      .then(function(r) { return r.json(); })
      .then(function(data) {
          window.emojiData = data;
          return data;
      });
  }

    u(document).on('click', '.sticker-store', async function(e) {
        var allPacks, myPacks;

        try {
            allPacks = await window.OVKAPI.call('stickers.getAll', {});
        } catch(e) {
            allPacks = { items: [] };
        }

        try {
            myPacks = await window.OVKAPI.call('stickers.get', {});
        } catch(e) {
            myPacks = { items: [] };
        }

        var myAllPacks = myPacks.items || [];
        var myPurchasedIds = myAllPacks.map(function(p) { return p.id; });
        var myAddedPacks = myAllPacks.filter(function(p) { return !p.price || p.price == 0; });

        function renderTabHeader(activeTab) {
            return '<div class="sticker-store-tabs" style="display:flex;gap:0;border-bottom:1px solid #ddd;margin-bottom:12px">' +
                '<div class="tab' + (activeTab === 'all' ? ' selected' : '') + '" data-tab="all">' + tr('all') + '</div>' +
                '<div class="tab' + (activeTab === 'myadded' ? ' selected' : '') + '" data-tab="myadded">' + tr('my') + '</div>' +
                '<div class="tab' + (activeTab === 'myall' ? ' selected' : '') + '" data-tab="myall">' + tr('all_purchased') + '</div>' +
                '</div>';
        }

        // нужно переделать!!!!!
        function renderPackList(packs) {
            if (!packs || packs.length === 0) {
                return '<div style="padding:30px;text-align:center;color:#999">' + tr('no_packs') + '</div>';
            }

            var html = '<div style="display:flex;flex-wrap:wrap;gap:10px">';
            packs.forEach(function(pack) {
                var thumb = pack.photo_256 || pack.photo_128 || '';
                html += '<div class="sticker-pack-card" data-pack-id="' + pack.id + '" style="width:140px;padding:10px;border:1px solid #e5e5e5;border-radius:8px;cursor:pointer;text-align:center;background:#fafafa">';
                if (thumb) {
                    html += '<img src="' + thumb + '" style="width:80px;height:80px;object-fit:contain;display:block;margin:0 auto 6px" />';
                }
                html += '<div style="font-size:12px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escapeHtml(pack.name) + '</div>';
                if (pack.price > 0) {
                    html += '<div style="font-size:11px;color:#e67e22;margin-top:4px">' + pack.price + ' coins</div>';
                } else {
                    html += '<div style="font-size:11px;color:#27ae60;margin-top:4px">' + tr('free') + '</div>';
                }
                html += '</div>';
            });
            html += '</div>';
            return html;
        }

        function buildStoreBody(activeTab) {
            var packs;
            switch (activeTab) {
                case 'myadded':
                    packs = myAddedPacks;
                    break;
                case 'myall':
                    packs = myAllPacks;
                    break;
                default:
                    packs = allPacks.items || [];
                    break;
            }
            return renderTabHeader(activeTab) + renderPackList(packs);
        }

        var currentTab = 'all';
        var storeMsg = new CMessageBox({
            title: tr('sticker_store'),
            body: buildStoreBody(currentTab),
            buttons: [tr('close')],
            callbacks: [Function.noop]
        });

        var storeNode = storeMsg.getNode();
        u(storeNode).on('click', '.sticker-store-tabs .tab', function(ev) {
            var tab = ev.currentTarget.dataset.tab;
            if (!tab) return;
            currentTab = tab;
            storeMsg.getNode().find('.ovk-diag-body').html(buildStoreBody(currentTab));
        });

        u(storeNode).on('click', '.sticker-pack-card', async function(ev) {
            var packId = parseInt(ev.currentTarget.dataset.packId);
            if (!packId) return;

            var allPacksArray = allPacks.items || [];
            var allPacks2 = myAllPacks;
            var pack = allPacksArray.find(function(p) { return p.id === packId; }) || allPacks2.find(function(p) { return p.id === packId; });
            if (!pack) return;

            var packDetail;
            try {
                packDetail = await window.OVKAPI.call('stickers.getFrom', { 'stickerpack_id': packId });
            } catch(e) {
                return;
            }

            var isPurchased = packDetail.purchased == 1;
            var isFree = !packDetail.price || packDetail.price == 0;

            var detailHtml = '<div style="display:flex;gap:16px;align-items:flex-start;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid #eee">';
            if (packDetail.photo_256) {
                detailHtml += '<img src="' + packDetail.photo_256 + '" style="width:80px;height:80px;object-fit:contain;flex-shrink:0" />';
            }
            detailHtml += '<div style="flex:1">';
            detailHtml += '<div style="font-size:16px;font-weight:700">' + escapeHtml(packDetail.name) + '</div>';
            if (packDetail.description) {
                detailHtml += '<div style="font-size:12px;color:#666;margin-top:4px">' + escapeHtml(packDetail.description) + '</div>';
            }
            if (pack.slug) {
                detailHtml += '<div style="font-size:11px;color:#999;margin-top:4px">@' + escapeHtml(pack.slug) + '</div>';
            }
            if (!isPurchased && packDetail.price > 0) {
                detailHtml += '<div style="font-size:13px;color:#e67e22;margin-top:6px;font-weight:600">' + packDetail.price + ' coins</div>';
            }
            detailHtml += '</div>';
            detailHtml += '<div>';
            if (isPurchased) {
                detailHtml += '<div class="button" style="background:#e8e8e8;color:#555;cursor:default">' + tr('purchased') + '</div>';
            } else if (isFree) {
                detailHtml += '<div class="button sticker-buy-btn" data-pack-id="' + packId + '">' + tr('add') + '</div>';
            } else {
                detailHtml += '<div class="button sticker-buy-btn" data-pack-id="' + packId + '">' + tr('buy_for', packDetail.price) + '</div>';
            }
            detailHtml += '</div></div>';

            if (packDetail.stickers && packDetail.stickers.length > 0) {
                detailHtml += '<div style="display:flex;flex-wrap:wrap;gap:6px">';
                packDetail.stickers.forEach(function(st) {
                    var src = st.photo_128 || '';
                    detailHtml += '<img src="' + src + '" style="width:64px;height:64px;object-fit:contain;border:1px solid #eee;border-radius:6px;padding:4px" title="' + escapeHtml(st.emoji || '') + '" />';
                });
                detailHtml += '</div>';
            }

            var detailMsg = new CMessageBox({
                title: packDetail.name,
                body: detailHtml,
                buttons: [tr('close')],
                callbacks: [Function.noop]
            });

            u(detailMsg.getNode()).on('click', '.sticker-buy-btn', async function(buyEv) {
                var buyPackId = parseInt(buyEv.currentTarget.dataset.packId);
                if (!buyPackId) return;

                try {
                    await window.OVKAPI.call('stickers.buy', { 'stickerpack_id': buyPackId });
                } catch(e) {
                    fastError(tr('purchase_failed'));
                    return;
                }

                detailMsg.close();
                storeMsg.close();
            });
        });
    });
})();
