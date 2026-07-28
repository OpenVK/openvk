async function showUserDialog(userId) {
    const conv = await window.im.conversations._findConvFromApi(userId);

    const html = `
        <div class="messenger-layer" id="user-send-dialog">
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
                        <textarea min-height: 190px; id="_text" class="udlg-textarea expanded-textarea small-textarea" placeholder="${tr('enter_message')}"></textarea>
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

function updateChatTitle(e, chat) {
    if (!chat) {
        console.log("IM | Editing | чата нет");
        return;
    }

    const msg = new CMessageBox({
        title: tr("change_chat_title"),
        close_on_buttons: false,
        body: `
            <input value="${escapeHtml(chat.name)}" type="text" id="_new_chat_title">
        `,
        buttons: [tr("cancel"), tr("change")],
        callbacks: [() => {
            msg.close();
        }, async () => {
            const new_title = msg.getNode().find("#_new_chat_title").last().value;
            msg.close();
            await chat.updateTitle(new_title);
        }]
    })
}

function updateChatAvatar(e, chat) {
    if (!chat) {
        console.log("IM | Editing | чата нет");
        return;
    }

    OpenAvatarUpdateDialogue(null, chat, 1, 1)
}

function OpenChatAvatar(event, peer) {
    if (peer.supposed_type == "chat") {
        OpenMiniature(event, peer.avatar_max, peer.id, "skip", "chat", null, true, 0)
        return;
    }

    if (peer.photo_pid == null) {
        console.log("IM | Photo viewer | i think this user does not have avatar.");
        return;
    }

    OpenAvatar(event, peer.avatar_max, peer.id + '_profile', peer.data.photo_pid);
}

function createChatTopic(group_id) {
    const msg = new CMessageBox({
        title: tr("create_topic_as_chat"),
        close_on_buttons: false,
        body: `
        <div>
            <p>${tr("create_topic_as_chat_desc")}</p>
            <p>${tr("create_topic_as_chat_desc_2")}:</p>
            <div>
                <select id="chat_sel"></select>
            </div>
        </div>`,
        buttons: [tr("create"), tr("cancel")],
        callbacks: [async () => {
            const val = msg.getNode().find("#chat_sel").last().value;

            msg.close();

            CMessageBox.toggleLoader();

            let res = await window.OVKAPI.call("board.addChatTopic", {
                "group_id": group_id,
                "chat_id": val
            }, true);

            if (res.error_msg != null) {
                if (res.error_code == 14) {
                    fastError(tr("chat_topic_already_attached_error"));
                } else {
                    fastError(String(res.error_msg));
                }

                CMessageBox.toggleLoader();
                return;
            }

            window.router.route("/topic" + group_id + "_" + res);
            CMessageBox.toggleLoader();
        }, () => {
            msg.close();
        }]
    });

    if (window.im) {
        window.im.conversations.convs.forEach(item => {
            if (item.peer && item.peer.supposed_type == "chat") {
                msg.getNode().find("#chat_sel").append(`
                    <option value="${item.peer.id}">${escapeHtml(item.peer.full_name)}</option>
                `);
            }
        })
    }
}

async function openChatTopic(event, prettyId) {
    event.target.classList.add("lagged");

    const group_id = prettyId.split("_")[0];
    const topic_id = prettyId.split("_")[1];

    let res = null;
    try {
        res = await window.OVKAPI.call("messages.joinChatByTopic", {
            "group_id": group_id,
            "topic_id": topic_id
        });
    } catch (e) {
        fastError(e)
    }
}
