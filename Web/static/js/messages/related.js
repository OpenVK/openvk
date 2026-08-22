async function showUserDialog(event, userId) {
    event.preventDefault();

    const conv = await window.im_variants.getCurrentUser().conversations._findConvFromApi(userId);

    const html = `
        <div class="messenger-layer" id="user-send-dialog">
            <div class="user-send-centre">
                <div class="user-send-left">
                    <img class="udlg-avatar" src="${conv.peer.avatar_any}" alt="" />
                    <div class="udlg-online nobold">${conv.peer.online_status_str}</div>
                </div>
                <div class="udlg-send-right">
                    <div>
                        <div class="udlg-info">
                            <div class="udlg-name">${conv.peer.full_name}</div>
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
            </div>
            <div style="display: flex;justify-content: space-between;">
                <a class="udlg-goto">${tr('go_to_dialog').toLowerCase()} &rarr;</a>

                <div>
                    <input type="button" class="button" id="_close" value="${tr("close")}">
                    <input type="button" class="button" id="_send_msg" value="${tr("send")}">
                </div>
            </div>
        </div>`;

    const msg = new CMessageBox({
        title: "send_message",
        body: "",
        custom_template: msgboxModernTemplate(tr("send_message"), html),
        close_on_buttons: false,
    });
    msg.getNode().attr("style", "z-index: 200;");
    msg.getNode().find(".ovk-diag").attr("style", "width: 500px;");
    msg.getNode().find(".ovk-diag-body").attr("style", "min-height: 300px;");
    msg.getNode().find(".ovk-diag-head #_close").on("click", (e) => {
        msg.close();
    });
    msg.getNode().find("#_send_msg").on("click", async (e) => {
        const btn = e.target;
        toggleUnclickability(btn, true);
        const targetUserId = parseInt(conv.peer.id);
        if (!targetUserId) {
            toggleUnclickability(btn, false);
            return;
        }

        const text = msg.getNode().find("#_text").last().value;
        const atts = collect_attachments(msg.getNode().find("#write"));
        if (!text && atts.length == 0) {
            toggleUnclickability(btn, false);
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
            toggleUnclickability(btn, false);
        }
    });
    msg.getNode().find("#_close").on("click", (e) => {
        msg.close();
    })
}

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
    console.log(peer)
    if (peer.supposed_type == "chat") {
        OpenMiniature(event, peer.avatar_max, peer.id, "skip", "chat", null, true, 0)
        return;
    }

    if (peer.data.photo_pid == null) {
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
            <div>
                <input id="name" type="text">
            </div>
        </div>`,
        buttons: [tr("create"), tr("cancel")],
        callbacks: [async () => {
            const title = msg.getNode().find("#name").last().value;
            if (!title || title.length == 0) { return; }
            msg.close();

            CMessageBox.toggleLoader();

            let res = await window.OVKAPI.call("board.addChatTopic", {
                "group_id": group_id,
                "title": title,
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
}

async function imSwitchCurrent() {
    CMessageBox.toggleLoader(true);

    const container = document.querySelector('.page_content');
    const c = window.im_variants.getCurrentUser().state.getOperator();
    const groups = await loadEditableGroups();

    CMessageBox.toggleLoader(false);

    const msg = new CMessageBox({
        custom_template: msgboxModernTemplate("...", `
        <div id="_switch_list" class="entity_vertical_list mini m_mini scroll_container"></div>
        `),
        title: "-",
        body: "-"
    });

    function makeItem(item) {
        console.log(item)

        msg.getNode().find("#_switch_list").append(`
        <div data-id="${item.id}" class="entity_vertical_list_item scroll_node">
            <div class="first_column">
                <a href="${item.page_url}" class="avatar"><img src="${item.avatar_any}"></a>
                <div class="info">
                    <b class="noOverflow">
                        <a href="${item.page_url}">${escapeHtml(item.full_name)}</a>
                    </b>
                </div>
            </div>
        </div>`);
    }

    msg.getNode().find(".ovk-diag").attr("style", "width: 300px;");

    makeItem(c);
    groups.items.forEach(item => {
        makeItem(window.im._toCGF(item));
    });
    msg.getNode().find("#_close").on("click", (e) => { msg.close(); })
    msg.getNode().find("#_switch_list").on("click", ".entity_vertical_list_item", async (e) => {
        e.preventDefault();

        const eid = Number(e.target.closest(".entity_vertical_list_item").dataset.id);
        const new_im = window.im_variants.getForX(eid);
        msg.close();

        await window.im_class.insertIn(container, eid > 0 ? null : eid);
        console.log(new_im, eid)
    })
}

async function openChatTopic(event, prettyId) {
    toggleUnclickability(event.target, true);

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

    toggleUnclickability(event.target, false);
}
