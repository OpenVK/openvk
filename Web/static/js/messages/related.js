async function showUserDialog(event, userId) {
    event.preventDefault();

    const conv = await window.im_variants.getCurrentUser().conversations._findConvFromApi(userId);

    const html = `
        <div class="messenger-layer" id="user-send-dialog">
            <div class="user-send-centre">
                <div class="user-send-left">
                    <img class="udlg-avatar" src="${conv.peer.getAvatar()}" alt="" />
                    <div class="udlg-online nobold"><a class="udlg-goto">${tr('go_to_dialog')}</a></div>
                </div>
                <div class="udlg-send-right">
                    <div>
                        <div class="udlg-info">
                            <a class="udlg-name" href="${conv.peer.getPageUrl()}">${conv.peer.getName()}</a>
                        </div>
                        <div class="udlg-online nobold">${conv.peer.getOnlineStatusString()}</div>
                    </div>

                    <div id="write" class="has_emoji_picker model_content_textarea">
                        <div class="textareas">
                            <textarea min-height: 190px; id="_text" class="udlg-textarea expanded-textarea small-textarea" placeholder="${tr('enter_message')}"></textarea>
                            <div class="emoji_picker_entrypoint" data-stickers="0"></div>
                        </div>

                        <div class="post-horizontal"></div>
                        <div class="post-vertical"></div>
                        <div class="udlg-actions" style="margin-top: 8px;">
                            <div class="attachment-icons" style="display: flex;justify-content: end;">
                                <div id="__photoAttachment">
                                    <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/application-x-egon.png" />
                                </div>
                                <div id="__videoAttachment">
                                    <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/application-vnd.rn-realmedia.png" />
                                </div>
                                <div id="__audioAttachment">
                                    <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/audio-ac3.png" />
                                </div>
                                <div id="__documentAttachment">
                                    <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/application-octet-stream.png" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer-actions">
                <div></div>
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
    ContentEditable.enhance(msg.getNode().find("#_text").last());
    msg.getNode().attr("style", "z-index: 200;");
    msg.getNode().find(".ovk-diag").attr("style", "width: 500px;");
    msg.getNode().find(".ovk-diag-body").attr("style", "min-height: 200px;");
    msg.getNode().find(".ovk-diag-head #_close").on("click", (e) => {
        msg.close();
    });
    msg.getNode().find(".udlg-goto").on("click", (e) => {
        window.router.route({
            url: "/im?sel=" + conv.id
        });
    });
    msg.getNode().find("#_send_msg").on("click", async (e) => {
        const btn = e.target;
        toggleUnclickability(btn, true);
        const targetUserId = parseInt(conv.peer.id);
        if (!targetUserId) {
            toggleUnclickability(btn, false);
            return;
        }

        const rawText = msg.getNode().find("#_text").last() ? (msg.getNode().find("#_text").last().value || '') : '';
        const cleanText = rawText.replace(/[\s\u200b\ufeff\u00a0]/g, '');
        const atts = collect_attachments(msg.getNode().find("#write"));
        if (!cleanText && atts.length == 0) {
            toggleUnclickability(btn, false);
            return;
        };

        try {
            await window.OVKAPI.call('messages.send', {
                peer_id: targetUserId,
                message: cleanText ? rawText : '',
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
        imLog("Editing | chat not found");
        return;
    }

    const currentTitle = chat.name || chat.data?.title || chat.data?.name || "";
    const msg = new CMessageBox({
        title: tr("change_chat_title"),
        close_on_buttons: false,
        body: `
            <div class="chat-title-edit-box">
                <input value="${escapeHtml(currentTitle)}" type="text" id="_new_chat_title" class="chat-title-edit-input">
            </div>
        `,
        buttons: [tr("cancel"), tr("change")],
        callbacks: [() => {
            msg.close();
        }, async () => {
            const inputEl = msg.getNode().find("#_new_chat_title").last();
            const new_title = inputEl ? inputEl.value.trim() : "";
            if (!new_title) return;
            msg.close();
            await chat.updateTitle(new_title);
        }]
    });

    setTimeout(() => {
        const input = msg.getNode().find("#_new_chat_title").last();
        if (input) {
            input.focus();
            input.select();
        }
    }, 50);
}

function updateChatAvatar(e, chat) {
    if (!chat) {
        imLog("Editing | chat not found");
        return;
    }

    if (typeof OpenAvatarUpdateDialogue === 'function') {
        OpenAvatarUpdateDialogue(null, chat, 1, 1);
    } else {
        const input = document.createElement("input");
        input.type = "file";
        input.accept = "image/*";
        input.onchange = async (ev) => {
            if (ev.target.files && ev.target.files[0]) {
                await chat.updateAvatar(ev.target.files[0]);
            }
        };
        input.click();
    }
}

function OpenChatAvatar(event, peer) {
    imLog("OpenChatAvatar peer:", peer);
    if (!peer) return;
    if (peer.supposed_type == "chat") {
        if (typeof OpenMiniature === 'function') {
            OpenMiniature(event, peer.getAvatar("max"), peer.id, "skip", "chat", null, true, 0);
        }
        return;
    }

    if (peer.data?.photo_pid == null) {
        imLog("Photo viewer | user does not have avatar");
        return;
    }

    if (typeof OpenAvatar === 'function') {
        OpenAvatar(event, peer.getAvatar("max"), peer.id + '_profile', peer.data.photo_pid);
    }
}

window.updateChatTitle = updateChatTitle;
window.updateChatAvatar = updateChatAvatar;
window.OpenChatAvatar = OpenChatAvatar;

function createChatTopic(group_id) {
    const params = {"group_id": group_id,};
    async function send() {
        CMessageBox.toggleLoader();

        let res = await window.OVKAPI.call("board.addChatTopic", params, true);

        if (res.error && res.error.error_msg != null) {
            if (res.error.error_code == 14) {
                fastError(tr("chat_topic_already_attached_error"));
            } else {
                fastError(String(res.error.error_msg));
            }

            CMessageBox.toggleLoader();
            return;
        }

        window.router.route("/im?join=" + group_id + "_" + res + "&act=topic");
        CMessageBox.toggleLoader();
    }

    const msg1 = new CMessageBox({
        title: tr("create_topic_as_chat"),
        body: ``,
        buttons: [tr("create_topic_as_chat_v_1"), tr("create_topic_as_chat_v_2"), tr("close")],
        callbacks: [
        () => {
            const msg2 = new CMessageBox({
                title: tr("create_topic_as_chat"),
                close_on_buttons: false,
                body: `
                <div>
                    <p>${tr("create_topic_as_chat_desc")}</p>
                    <p>${tr("create_topic_as_chat_desc_3")}:</p>
                    <div>
                        <input id="name" type="text">
                    </div>
                </div>`,
                buttons: [tr("create"), tr("cancel")],
                callbacks: [() => {
                    const title = msg2.getNode().find("#name").last().value;
                    if (!title || title.length == 0) { return; }
                    params["title"] = title;
                    send();
                    msg2.close();
                }, () => {
                    msg2.close();
                }]
            });
        },
        () => {
            const msg2 = new CMessageBox({
                title: tr("create_topic_as_chat"),
                close_on_buttons: false,
                body: `
                <div>
                    <p>${tr("create_topic_as_chat_desc")}</p>
                    <p>${tr("create_topic_as_chat_desc_2")}:</p>
                    <div>
                        <select id="chat_id"></select>
                    </div>
                </div>`,
                buttons: [tr("create"), tr("cancel")],
                callbacks: [() => {
                    const chat_id = msg2.getNode().find("#chat_id").last().value;
                    if (!chat_id || chat_id == 0) { return; }
                    params["chat_id"] = chat_id;
                    params["title"] = "-";

                    send();
                    msg2.close();
                }, () => {
                    msg2.close();
                }]
            });
            window.im.conversations.convs.forEach(item => {
                if (item.peer && item.peer.can("add_to_topic")) {
                    msg2.getNode().find("#chat_id").append(`
                        <option value="${item.id}">${escapeHtml(item.peer.getName())}</option>    
                    `);
                }
            });
        },
        () => {}],
    });
    msg1.getNode().find(".ovk-diag-body").attr("style", "display:none;");
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
        msg.getNode().find("#_switch_list").append(`
        <div data-id="${item.id}" class="entity_vertical_list_item scroll_node">
            <div class="first_column">
                <a href="${item.getPageUrl()}" class="avatar"><img src="${item.getAvatar()}"></a>
                <div class="info">
                    <b class="noOverflow">
                        <a href="${item.getPageUrl()}">${escapeHtml(item.getName())}</a>
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
        msg.close();

        await window.im_class.insertIn(container, eid > 0 ? null : eid);
    })
}

async function openChatTopic(event, prettyId) {
    window.router.route({
        "url": "/im?join=" + prettyId + "&act=topic"
    });
    /*toggleUnclickability(event.target, true);

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

    toggleUnclickability(event.target, false);*/
}

class DaySwitcher {
    constructor(start_date = null, peer_id = null) {
        if (typeof window.openCalendarModal === "function") {
            return window.openCalendarModal({ initialDate: start_date, peerId: peer_id });
        }
    }
}

window.DaySwitcher = DaySwitcher;
