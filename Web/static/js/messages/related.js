async function showUserDialog(userId) {
    const conv = await window.im.conversations._findConvFromApi(userId);

    const html = `
        <div id="user-send-dialog">
            <div class="user-send-left">
                <img class="udlg-avatar" src="${conv.peer.avatar_any}" alt="" />
                <a class="udlg-goto" style="text-align: right;">${tr('go_to_dialog').toLowerCase()} &rarr;</a>
            </div>
            <div class="udlg-send-right">
                <div>
                    <div class="udlg-info">
                        <div class="udlg-name">${conv.peer.full_name}</div>
                        <div class="udlg-online nobold">${conv.peer.online_status_str}</div>
                    </div>
                </div>

                <div id="write" class="has_emoji_picker model_content_textarea">
                    <div class="textareas">
                        <textarea id="_text" class="udlg-textarea expanded-textarea small-textarea" placeholder="${tr('enter_message')}"></textarea>
                        <div class="emoji_picker_entrypoint"></div>
                    </div>

                    <div class="post-horizontal"></div>
                    <div class="post-vertical"></div>
                    <div class="udlg-actions">
                        <div class="attachment-icons">
                            <div id="__photoAttachment"></div>
                            <div id="__videoAttachment"></div>
                            <div id="__audioAttachment"></div>
                            <div id="__documentAttachment"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;

    const msg = new CMessageBox({
        title: tr("send_message"),
        body: html,
        buttons: [tr('close'), tr("send")],
        close_on_buttons: false,
        callbacks: [() => { msg.close(); }, async () => {
            const btn = msg.getNode().find(".ovk-diag-action button").last();
            btn.classList.add("lagged");
            const targetUserId = parseInt(conv.peer.id);
            if (!targetUserId) {
                btn.classList.remove("lagged");
                return;
            }

            const text = msg.getNode().find("#_text").last().value;
            const atts = collect_attachments(msg.getNode().find("#write"));
            if (!text && atts.length == 0) {
                btn.classList.remove("lagged");
                return;
            };

            try {
                await window.OVKAPI.call('messages.send', {
                    peer_id: targetUserId,
                    message: text,
                    attachment: atts.join(","),
                });
                msg.close();
                NewNotification(tr("message_sent_excl"), "");
            } catch (err) {
                fastError(tr('error_sending_message'));
                btn.classList.remove("lagged");
            }
        }],
    });
    msg.getNode().attr("style", "z-index: 200;")
    msg.getNode().find(".ovk-diag-body").attr("style", "height: 300px;")
}

u(document).on("click", "#_message_send", async (e) => {
    e.preventDefault();

    await showUserDialog(Number(e.target.dataset.eid));
})

async function updateChatTitle(chat_id) {
    const chat = await window.im.conversations._findConvFromApi(chat_id);

    if (!chat) {
        console.log("IM | Editing | чата нет");
        return;
    }

    const msg = new CMessageBox({
        title: tr("change_chat_title"),
        close_on_buttons: false,
        body: `
            <input value="${escapeHtml(chat.peer.name)}" type="text" id="_new_chat_title">
        `,
        buttons: [tr("cancel"), tr("change")],
        callbacks: [() => {
            msg.close();
        }, () => {
            const new_title = msg.getNode().find("#_new_chat_title").last().value;
            console.log(new_title)
            msg.close();
        }]
    })
}

async function updateChatAvatar(chat_id) {
    const chat = await window.im.conversations._findConvFromApi(chat_id);

    if (!chat) {
        console.log("IM | Editing | чата нет");
        return;
    }

    const msg = new CMessageBox({
        title: tr("update_chat_avatar"),
        close_on_buttons: false,
        body: `
            <input type="text">
        `,
        buttons: [tr("cancel"), tr("change")],
        callbacks: [() => {
            msg.close();
        }, () => {
            msg.close();
        }]
    })
}
