import { ChatMessage, ChatGeneralForm, Draft } from '../components/messages.js';
import { Conversation } from './conversations.js';
import { MessageListView } from "../components/message.js"
import { ErrorConversation, WriteBar, ActionsBar, PeerWindow, InputArea, PeerTabsView, TopicConversationChat } from "../components/common.js"
import { IMTab, IMPage } from './page.js';
import { html, render } from '../components/render.js';

export class Messenger {
    static MAX_SELECTED_MESSAGES = 100;
    static MESSAGE_CHUNK_LENGTH = 1000;
    static MESSAGE_SEND_INTERVAL = 1000;

    constructor() {
        this.is_showing_profile = false;
        this.is_loading = false;
        this.had_more_one_tab = false;
        this.is_switching = false;

        this.currentDraft = '';
        this.prevDraft = null;
        this.prevAtts_1 = null;
        this.prevAtts_2 = null;
        this.drafts = {};
        this.scrolls = {};

        this.opened_tabs = [];
        this.currentChatId = null;
        this.selected_messages = [];

        this.toggled_peer_obj = null;

        this.replyTo = null;
        this.editMsg = null;
    }

    getWindow() {
        return window.im.getTab("messenger").render_class;
    }

    afterFirstRender() {
        const current_chat = this.getCurrentChat();
        if (current_chat != null && current_chat.draft) {
            console.log("IM | Scroll from tab");
            current_chat.draft.loadScroll(window.im.getTab("messenger").render_class);
        }
    }

    get view() { return this.getWindow(); }

    update() {
        try {
            return this.getWindow()._triggerUpdate();
        } catch(e) {
            console.error(e);
        }
    }

    async selectConversation(convo, scrollToEnd = false, newDraft = true) {
        const oldId = Number(this.currentChatId);
        if (convo == null) {
            throw new Error();
        }

        if (window.im.state.is_debug) {
            console.log(convo);
        }

        this.setChat(convo);
        const tab = await window.im.openTabByName("messenger");

        try {
            await tab.render();
        } catch(e) { console.error(e); }

        if (!convo.peer._isMessagesInited()) {
            try {
                const c = await convo.peer.getMessages();
                convo.peer._chunks._appendChunk(c);
            } catch(e) { // может быть и broker failure. Хз зачем.
                console.error(e);
            }

            if (scrollToEnd == true) {
                this.getWindow()._scrollToEnd();
            }
        }

        try {
            await tab.render();
            tab.showTab();
        } catch(e) { console.error(e); }
        const newId = Number(this.currentChatId);

        if (oldId != newId) {
            try {
                this._clearAttachments();
                this.removeReply();
                this.cancelEdit();

                let draft = this.getCurrentChat().draft;
                if (!draft && newDraft == true) {
                    draft = new Draft();
                }

                if (draft) {
                    draft.loadToPage(this.getWindow());
                }
                this.update();
                this.getWindow().updUrl();
            } catch(e) {
                console.error(e);
            }
        }
    }

    async selectConversationByPeerId(id) {
        let convo = null;
        try {
            convo = await window.im.conversations._findConvFromApi(id);

            if (!convo) {
            console.error("can't find convo with id", id);
                throw new Error("Not found conversation with id ", id);
            }
        } catch(e) {
            fastError(String(e));
            return;
        }

        await this.selectConversation(convo);
    }

    setChat(convo, pushstate = true) {
        const oldId = Number(this.currentChatId);

        try {
            this.getCurrentChat().setDraft(Draft.fromPage(this.getWindow()));
        } catch(e) {
            console.error(e);
        }

        if (!this.hasChat(convo)) {
            this.addChat(convo);
        }

        this.unselectAll();
        this.selectChat(convo);

        const newId = Number(this.currentChatId);

        if (this.opened_tabs.length > 1) {
            this.had_more_one_tab = true;
        }
    }

    addChat(conv) {
        // console.trace()
        this.opened_tabs.push(conv);
    }

    selectChat(conv) {
        const id = this.opened_tabs.indexOf(conv);
        if (id === -1) {
            console.error("can't find convo in tab", conv)
            return;
        }

        this.currentChatId = id;
    }

    hasChat(conv) {
        console.log(this.opened_tabs, conv)
        return this.opened_tabs.indexOf(conv) !== -1;
    }

    getTabsCount() {
        return this.opened_tabs.length;
    }

    getChatWith(chat_general_form) {
        let is = null;
        window.im.conversations.convs.forEach((item) => {
            if (item.peer.id == chat_general_form.id) {
                is = item;
                return;
            }
        });

        if (!is) {
            return new Conversation({ 'peer': chat_general_form });
        }

        return is;
    }

    getCurrentChat() {
        if (this.currentChatId === null || this.currentChatId === undefined) return null;
        return this.opened_tabs[this.currentChatId] || null;
    }

    closeChat(conv, page) {
        const idx = this.opened_tabs.indexOf(conv);
        if (idx !== -1) { this.opened_tabs.splice(idx, 1) };

        if (typeof window.im !== 'undefined' && window.im.updateTabs) {
            window.im.updateTabs();
        }

        try {
            if (this.opened_tabs[idx - 1] != null) {
                this.selectConversation(this.opened_tabs[idx - 1]);
            } else if(this.opened_tabs[idx + 1] != null) {
                this.selectConversation(this.opened_tabs[idx + 1]);
            } else {
                window.im.openTabByName("conversations");
            }
        } catch(e) {
            console.error(e);
        }

        if (page) {
            page.update();
        }
    }

    async setWriting() {
        this._typingStarted = 0;

        const params = {
            "type": "typing",
            "peer_id": window.im.state.getCurrentConvo().id,
        };
        const gid = window.im.state.getId();
        if (gid < 0) {
            params["group_id"] = Math.abs(gid);
        }

        console.log('IM | setWriting called');

        await window.OVKAPI.call("messages.setActivity", params);
    }

    async sendToCurrentCorresponder() {
        const view = this.getWindow();
        const text = view.getCurrentText();
        const reply_to = this.replyTo;
        let reply_param = null;
        let attachments_list = null;
        const corresponder = window.im.state.getCurrentConvo();

        const attachments = collect_attachments(u('.messenger-app--input---messagebox'));
        if (attachments.length > 0) { attachments_list = attachments; }
        if (reply_to) { reply_param = reply_to; }

        if (text.length <= Messenger.MESSAGE_CHUNK_LENGTH + 20) {
            const msg = new ChatMessage({
                'from_id': window.im.state.getOperator().id,
                'peer_id': corresponder.id,
                'date': Math.round((new Date()).getTime() / 1000),
            });
            if (attachments_list) msg.has_not_loaded_attachments = true;
            msg._guessSender();
            msg.setText(text);
            return await corresponder.peer.sendMessage(msg, reply_param, attachments_list, null, () => {
                view._scrollToEnd();
            });
        }

        // ── Split long message into chunks ──
        const chunks = [];
        for (let i = 0; i < text.length; i += Messenger.MESSAGE_CHUNK_LENGTH) {
            chunks.push(text.slice(i, i + Messenger.MESSAGE_CHUNK_LENGTH));
        }

        const total = chunks.length;
        for (let i = 0; i < total; i++) {
            const isLast = i === total - 1;
            const msg = new ChatMessage({
                'from_id': window.im.state.getOperator(),
                'peer_id': corresponder.id,
                'date': Math.round((new Date()).getTime() / 1000),
            });
            if (isLast && attachments_list) msg.has_not_loaded_attachments = true;
            msg._guessSender();
            msg.setText(chunks[i]);

            corresponder.peer.sendMessage(msg, isLast ? reply_param : null, isLast ? attachments_list : null, isLast ? null : Messenger.MESSAGE_SEND_INTERVAL, () => {
                view._scrollToEnd();
            });
        }
    }

    async viewMedia(media_type = "pinned") {}

    /* Selectness */

    selectMessage(msg) {
        this.selected_messages.push(msg.id);
        this.update();
    }

    unselectAll() {
        this.selected_messages = [];
        this.update();
    }

    unselectMessage(msg) {
        const idx = this.selected_messages.indexOf(msg.id);
        if (idx !== -1) this.selected_messages.splice(idx, 1);
        this.update();
    }

    isMessageSelected(msg) {
        return this.selected_messages.indexOf(msg.id) !== -1;
    }

    get selected_messages_objs() {
        let objs = [];
        const chat = this.getCurrentChat();
        this.selected_messages.forEach(item => {
            if (chat != null) {
                objs.push(chat.peer._findMessageById(item));
            }
        })

        return objs;
    }

    get selected_messages_count() {
        return this.selected_messages.length;
    }

    async showAttachment(event, msg, attachment) {
        event.preventDefault();

        const idinarray = msg.attachments.indexOf(attachment);
        const type = attachment.type;

        CMessageBox.toggleLoader(true);

        const queue_items = [];

        msg.attachments.forEach(att => {
            if (att[type]) {
                queue_items.push(att[type]);
            }
        });
        let viewer = null;
        const ids = idForItem(attachment[type]);
        const first = queue_items.find(function (p) {
            return idForItem(p) === ids;
        });

        if (!first) {
            console.log("IM | Messenger | Opening photo | Not found ", attachment, " image in", photos)
            return;
        };

        switch (type) {
            case "photo":
                if (typeof PhotoViewer === 'undefined') return;

                viewer = new PhotoViewer();
                viewer.context.not_load_comments = true;
                await viewer.loadAlbumContext({
                    count: queue_items.length,
                    items: queue_items
                });
                viewer.open();
                viewer.setMode("tg");
                viewer.afterOpen(idForItem(first));

                break;
            case "video":
                viewer = new VideoViewer();
                viewer.context.not_load_comments = true;
                await viewer.loadCustomContext({
                    count: queue_items.length,
                    items: queue_items
                });
                viewer.open();
                // JUST ACCEPT IT
                //viewer.afterOpen(idForItem(first));
                viewer.afterOpen(first.id);

                break;
            case "audio":
                AudioViewer.openById(event, null, attachment.audio);
                break;
            default:
                console.error("I can't open it: ", attachment, msg, e);
        }

        CMessageBox.toggleLoader(false);
    }

    removeReply(render = true) {
        this.replyTo = null;

        if (render == true) {
            this.getWindow().update();
        }
    }

    _clearAttachments() {
        try {
            this.getWindow().container.querySelector(".post-horizontal").innerHTML = "";
            this.getWindow().container.querySelector(".post-vertical").innerHTML = "";
        } catch (e) {
            console.error(e);
        }
    }

    // onSendMessageButtonClick
    async onSendMessage() {
        const _tmp_atts = collect_attachments(u('.messenger-app--input---messagebox'));
        const win = this.getWindow();

        if (win.getCurrentText() === '' && _tmp_atts.length == 0) return false;
        if (win.getCurrentText().length > 55000) {
            fastError("> 55000")
            return;
        }

        if (this.editMsg != null) {
            this.editMsg.edit(win.getCurrentText(), _tmp_atts);

            this.cancelEdit();
            return;
        }

        win._scrollToEnd();

        this.sendToCurrentCorresponder();

        window.im.state.getCurrentConvo().clearDraft();
        this._clearAttachments();
        this.removeReply();
        win.setCurrentText("");
    }

    cancelEdit(render = true) {
        window.im.messenger.editMsg = null;
        const win = this.getWindow();
        this._clearAttachments();

        if (this.prevDraft != null) {
            win.setCurrentText(this.prevDraft);
            this.currentDraft = String(this.prevDraft);
            this.prevDraft = null;
        }

        if (render == true) {
            this.getWindow().update();
        }
    }

    isEditing() {
        return this.editMsg != null;
    }

    async goToMessage(msg) {
        console.log(msg)
    }
}

export class MessengerPage extends IMPage {
    static getPageId() { return "messenger"; }
    isVisibleWhenHidden() { return true; }
    shouldCloseOnExit() { return window.im.messenger.opened_tabs.length == 0; }

    constructor() {
        super();
        this.MAX_SELECTED_MESSAGES = 100;

        this.appEl = null;
        this.messagesListBlock = null;

        this.is_showing_profile = false;
        this.is_loading = false;
        this.had_more_one_tab = false;
        this.is_switching = false;

        this.opened_tabs = [];
        this.current_chat = null;
        this.selected_messages = [];

        this.toggled_peer_obj = null;

        this.replyTo = null;
        this.editMsg = null;
    }
    showHook() {
        try {
            const v = window.im.messenger.getCurrentChat();
            if (v.peer && v.peer.draft) {
                v.peer.draft.loadScroll(window.im.getTab("messenger").render_class);
            } else {
                this._scrollToEnd();
            }
        } catch(e) {console.error(e);}
    }
    isDisablesScroll() { return true; }
    _triggerUpdate() {
        window.im.conversations.update();
        this.update();
    }
    updUrl() {
        const url = new URL(location.href);
        url.searchParams.delete("joinByTopic");
        url.searchParams.set("sel", String(window.im.messenger.getCurrentChat().peer.id));
        window.im.state._pushState(url.toString());
    }

    //async render(container, special_mode = null, messages = null) {
    async render(container, options = {}) {
        const orig_messenger = window.im.messenger;
        this.getNode().addClass("page-other");

        let messages = null;
        let special_mode = "";
        const root = container;
        if (!root) {
            console.error("no root")
            return;
        };

        const currentConv = window.im.messenger.getCurrentChat();
        if (!currentConv) {
            render(html`<${ErrorConversation} />`, root)
            return;
        }

        const peer = currentConv ? currentConv.peer : null;
        const display_peer = this.toggled_peer_obj ? this.toggled_peer_obj : peer;

        if (!messages) {
            messages = peer ? peer.divided_messages : [];
        }

        const is_rendering_contact_window = (window.im.tab == "contact" && special_mode === null);
        render(html`
        <div id="chat-page">
            <div class="chat-window ${peer.id == window.openvk.current_id ? "saved-msgs" : ""}">
            <${PeerTabsView} hadTab=${true} tabs=${orig_messenger.opened_tabs} currentChat=${orig_messenger.currentChatId} page=${this} />
            <${ActionsBar}
                selectedMessages=${window.im.messenger.selected_messages_objs}
                count=${window.im.messenger.selected_messages_count}
                onDelete=${() => this.callDeletion()}
                onUnselect=${() => window.im.messenger.unselectAll()}
                onReply=${() => this.onReplyButtonClick()}
            />
            <div class="messenger-app messenger-layer">
                <${MessageListView}
                convo=${currentConv}
                messages=${messages} 
                page=${this} />
                <${InputArea}
                editMsg=${window.im.messenger.editMsg}
                replyTo=${window.im.messenger.replyTo}
                onRemoveReply=${() => window.im.messenger.removeReply()}
                onSend=${() => window.im.messenger.onSendMessage()}
                onKeyPress=${(e) => this.onTextareaKeyPress(e)}
                currentDraft=${window.im.messenger.currentDraft}
                onInput=${(e) => { this.currentDraft = e.target.value; }}
                togglePeerInfo=${(e) => { this.togglePeerInfo() }}
                clickOnReply=${(msg, e) => { this.clickOnReply(msg, e) }}
                />
            </div>
            </div>
        </div>
        `, root);
    }

    onTextareaKeyPress(e) {
        const ta = e.target;

        if (e.which !== 13) {
            const now = Date.now();
            if (!this._typingStarted) this._typingStarted = now;
            if (now - this._typingStarted > 6000) { // 2s
                window.im.messenger.setWriting();
            }
        }

        if (e.which === 13) {
            this._typingStarted = 0;
            if (!e.metaKey && !e.shiftKey) {
                e.preventDefault();
                ta.blur();
                window.im.messenger.onSendMessage();
                ta.focus();
                return false;
            }
        }
        return true;
    }

    onMessageClick(msg, e) {
        if (e.buttons !== 1 && e.type == 'mousemove') return;
        if (window.im.messenger.replyTo != null) return;

        if (window.im.messenger.selected_messages_count == 0 && !e.target.closest(".click-territory")) {
            return;
        }

        const target = e.target;
        if (!target.matches('.text, .time span') || window.im.messenger.selected_messages.length > 0) {
            e.preventDefault();
            this.toggleMessageSelection(msg, e);
        }
    }

    clickOnReply(msg) {
        this.scrollToMessage(msg, true);
    }

    onReplyButtonClick() {
        const ids = window.im.messenger.selected_messages;
        const current_chat = window.im.messenger.getCurrentChat();
        const m = current_chat.peer._findMessageById(ids[0]);
        window.im.messenger.unselectAll();
        window.im.messenger.replyTo = m;

        this.update();
    }

    onEditButtonClick(e, msg) {
        window.im.messenger.editMsg = msg;
        if (msg.text.length > 0) {
            window.im.messenger.prevDraft = String(window.im.messenger.currentDraft || "");
            window.im.messenger.currentDraft = msg.text;
        }

        if (msg.attachments.length > 0) {
            unpack_attachments_into_node(u(this.container.querySelector("#write")), msg.attachments);
        }

        this.update();
    }

    onPinButtonClick(e, msg) {
        const isPinned = msg.isPinned();
        const cmsg = new CMessageBox({
            title: tr("confirm"),
            body: isPinned == true ? tr("unpin_button_click") : tr("pin_button_click"),
            buttons: [tr("yes"), tr("no")],
            callbacks: [async () => {
                if (isPinned == true) {
                    await msg.togglePin(false);
                } else {
                    await msg.togglePin(true);
                }

                this._triggerUpdate();
            }, () => {}]
        })
    }

    onDebugButtonClick(e, msg) {
        const cmsg = new CMessageBox({
            title: "...",
            body: `<textarea></textarea>`,
            buttons: [tr("close")],
            callbacks: [() => {}],
        });
        const p = Object.assign({}, msg.data);
        p.sender = null;
        cmsg.getNode().find("textarea").last().value = JSON.stringify(p, "", 4);
    }

    _triggerCancelEditingDialog(callback = null) {
        const cmsg = new CMessageBox({
            title: tr("confirm"),
            body: tr("cancel_edit_confirmation"),
            buttons: [tr("yes"), tr("no")],
            callbacks: [() => {
                this.cancelEdit();

                if (callback) {
                    callback();
                }
            }, () => {}]
        })
    }

    toggleMessageSelection(msg, e) {
        if (msg.id == null) {
            if (e.target.closest(".error-checkmark") == null) {
                const c = new CMessageBox({
                    title: tr("confirm"),
                    body: tr("cancel_sending_confirmation"),
                    buttons: [tr("yes"), tr("no")],
                    callbacks: [() => {
                        msg.setDeleted();
                        this._triggerUpdate();
                    }, () => { }]
                });
            }
        }

        if (window.im.messenger.isEditing()) {
            this._triggerCancelEditingDialog();
            return;
        }

        if (!window.im.messenger.isMessageSelected(msg)) {
            window.im.messenger.selectMessage(msg);
        } else {
            window.im.messenger.unselectMessage(msg);
        }
        this.update();
    }

    async togglePeerInfo(sender = null) {
        if (window.im.messenger.is_switching == true) {
            return;
        }

        window.im.messenger.is_switching = true;

        if (false) {
            window.im.selectTab('messenger');
            window.im.messenger.toggled_peer_obj = null;
        } else {
            const _c = window.im.state.getCurrentConvo();
            if (_c.peer.supposed_type == "chat" && !_c.peer._hasLoadedMembers()) {
                await _c.peer._setMembers();
            }

            if (typeof window.im !== 'undefined' && window.im.selectTab) {
                window.im.openTabByName('contact', false, {
                    peer: {
                        "peer": sender
                    }
                });
            }
        }

        window.im.messenger.is_switching = false;
    }

    onAuthorNameClick(msg, e) {
        e.preventDefault();
        e.stopPropagation();

        this.togglePeerInfo(msg.sender);
    }

    onScrollDownButtonClick() {
        // "Return to the newest" — reset the active chunk to the actual
        // (newest) chunk, then scroll to the bottom.
        const corresponder = window.im.state.getCurrentConvo();
        if (corresponder && typeof corresponder.scrollToNewest === "function") {
            corresponder.scrollToNewest();
        } else {
            this._scrollToEnd();
        }
    }

    async onMessagesScroll(e = null) {
        if (this.is_loading) return;
        this.is_loading = true;

        const _scroll = document.documentElement.scrollTop;

        if (_scroll < 21) {
            console.log("IM | Loading older chunk from API");
            // ── Scrolled near the top → load older messages (scroll UP) ──
            // await window.im.state.getCurrentConvo()._messagesLoad_UpFromLastChunk();
        } else {
            const scrollBottom = document.documentElement.scrollHeight - _scroll - document.documentElement.clientHeight;

            if (scrollBottom < 10) {
                // ── Scrolled near the bottom → load newer messages (scroll DOWN) ──
                if (window.im.state.getCurrentConvo()._chunks_HasMoreNewerChunkRelativelyToCurrentChat()) {
                    // There's already a newer chunk available without fetching
                    console.log('IM | Switching to a newer chunk');
                    // await window.im.state.getCurrentConvo()._messagesLoad_DownFromCurrentChunk();
                } else {
                    // No newer chunk loaded yet — fetch from API
                    console.log('IM | Loading newer chunk from API');
                    //  await window.im.state.getCurrentConvo()._messagesLoad_DownFromCurrentChunk();
                }
            }

            if (scrollBottom > 600) {
                this.getNode().addClass("overscrolled");
            } else {
                this.getNode().removeClass("overscrolled");
            }
        }

        this.is_loading = false;
    }

    callDeletion() {
        const ids = window.im.messenger.selected_messages;
        const gid = window.im.state.getId()
        const current_chat = window.im.messenger.getCurrentChat();
        const box = new CMessageBox({
            title: tr("message_deletion", ids.length),
            body: tr("message_deletion_confirm"),
            buttons: [tr('yes'), tr('no')],
            callbacks: [async () => {
                let ids2 = [];
                ids.forEach((item) => {
                    let m = current_chat.peer._findMessageById(item);
                    ids2.push(item);
                    m.setDeleted(true);
                });

                const params = {
                    "message_ids": ids2.join(","),
                    "peer_id": current_chat.peer.id
                };
                if (gid < 0) {
                    params["group_id"] = Math.abs(gid);
                }
                await window.OVKAPI.call("messages.delete", params)
                this._triggerUpdate();
                window.im.messenger.unselectAll();
            }, () => { }],
        });
    }

    isAtEnd() { return false; }
    getScroll() { return document.documentElement.scrollTop; }
    _scrollTo(scroll_progress) {
        if (scroll_progress == "end") {
            if (window.im.state.isFastchat) {
                scroll_progress = document.querySelector("#fastchats_related #fastchats_chat #wrap").scrollHeight;
            } else {
                scroll_progress = document.documentElement.scrollHeight;
            }
        }

        console.log("scrolling page to: ", scroll_progress);
        if (window.im.state.isFastchat) {
            document.querySelector("#fastchats_related #fastchats_chat #wrap").scroll({ top: scroll_progress });
        } else {
            document.documentElement.scroll({ top: scroll_progress });
        }
    }

    _scrollToEnd() {
        console.log("scrolled page to the end");

        this._scrollTo("end");
    }

    scrollToMessage(msg, load_chunk_where_it_can_be = false) {
        const msgId = typeof msg === 'object' ? msg.id : msg;

        const el = this.messagesListBlock
            ? this.messagesListBlock.querySelector(`[data-msg-id="${msgId}"]`)
            : document.querySelector(`[data-msg-id="${msgId}"]`);

        if (el) {
            scrollTo({
                top: el.offsetTop - 200,
            });

            el.classList.add("animated");

            setTimeout(() => {
                el.classList.remove("animated");
            }, 5000);

            console.log('IM | Scrolled to message #' + msgId);

            return;
        }

        if (load_chunk_where_it_can_be) {
            const chat = this.getCurrentChat();
            if (chat && chat.peer) {
                chat.peer.loadChunkByMessageId(msgId).then(() => {
                    const el2 = this.messagesListBlock
                        ? this.messagesListBlock.querySelector(`[data-msg-id="${msgId}"]`)
                        : document.querySelector(`[data-msg-id="${msgId}"]`);
                    if (el2) {
                        scrollTo({ top: el2.offsetTop - 200 });
                        el2.classList.add("animated");
                        setTimeout(() => el2.classList.remove("animated"), 5000);
                        console.log('IM | Scrolled to message #' + msgId + ' after loading chunk');
                    }
                });
            }
        } else {
            console.warn('IM | scrollToMessage: message #' + msgId + ' not found in DOM');
        }
    }

    getCurrentText() { return this.container.querySelector(".messenger-app--input---messagebox textarea").value; }
    setCurrentText(text) { console.log("setCurrentText"); this.container.querySelector(".messenger-app--input---messagebox textarea").value = text; }
    getCurrentAttachments() { return [this.container.querySelector(".post-horizontal").innerHTML, this.container.querySelector(".post-vertical").innerHTML]; }
}

export class ContactPage extends IMPage {
    shouldCloseOnExit() { return true; }
    static getPageId() { return "contact"; }

    async render(container) {
        this.getNode().addClass("page-other");

        const currentCorresponder = window.im.state.getCurrentConvo();
        let peer = null;
        if (this.options.peer == null) {
            peer = currentCorresponder;
        } else {
            peer = this.options.peer;
        }

        render(html`<${PeerWindow} fromConvo=${currentCorresponder} convo=${peer} />`, container);
    }
}

export class ChatTopicPreviewPage extends IMPage {
    shouldCloseOnExit() { return true; }
    static getPageId() { return "chat_preview_topic"; }

    async render(container) {
        const chat = this.options.topic;

        render(html`<${TopicConversationChat} chat_id=${chat} />`, container);
    }
}
