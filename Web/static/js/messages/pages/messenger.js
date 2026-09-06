//import { ChatMessage, ChatGeneralForm, Draft } from '../components/messages.js';
const { ChatMessage, ChatGeneralForm, Draft } = await es6import_Im(import.meta.url, "../components/messages.js");
//import { Conversation } from './conversations.js';
const { Conversation } = await es6import_Im(import.meta.url, "./conversations.js");
const { MessageListView } = await es6import_Im(import.meta.url, "../components/message.js");
//import { ErrorConversation, WriteBar, ActionsBar, PeerWindow, InputArea, PeerTabsView, TopicConversationChat, PinnedMessageBar } from "../components/common.js"
const { ErrorConversation, WriteBar, ActionsBar, PeerWindow, InputArea, PeerTabsView, TopicConversationChat, PeerInfoView } = await es6import_Im(import.meta.url, "../components/common.js");
//import { IMTab, IMPage } from './page.js';
const { IMTab, IMPage } = await es6import_Im(import.meta.url, "./page.js");
const { ScrollPosition, MessageChunk } = await es6import_Im(import.meta.url, "../components/partition.js");
const { openCalendarModal, CalendarComponent } = await es6import_Im(import.meta.url, "../components/calendar.js");
const { html, render } = await es6import_Im(import.meta.url, "../components/render.js");
const { imLog } = await es6import_Im(import.meta.url, "../logger.js");

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
        this._typingStarted = null;
        this._window = null;
    }

    getWindow() {
        return this._window || window.im?.getTab("messenger")?.render_class || null;
    }

    afterFirstRender() {
        const current_chat = this.getCurrentChat();
        if (current_chat != null && current_chat.draft) {
            imLog("Messenger | Scroll from tab");
            const win = this.getWindow();
            if (win) {
                current_chat.draft.loadScroll(win);
            }
        }
        setTimeout(() => {
            this.getWindow()?._checkAndFillUnderflow?.();
        }, 150);
    }

    get view() { return this.getWindow(); }

    update() {
        try {
            const win = this.getWindow();
            if (win && typeof win._triggerUpdate === 'function') {
                return win._triggerUpdate();
            }
        } catch (e) {
            console.error(e);
        }
    }

    async selectConversation(convo, scrollToEnd = false, newDraft = true, forwarded = null) {
        const oldId = Number(this.currentChatId);
        if (convo == null) {
            throw new Error();
        }

        const hadUnread = Boolean((convo.unread_count > 0) || !convo.isRead());

        if (this.getWindow()) {
            const win = this.getWindow();
            if (typeof win._clearUnreadSpacing === 'function') {
                win._clearUnreadSpacing();
            }
            if (win._readObserver) {
                win._readObserver.disconnect();
                win._readObserver = null;
            }
            if (win._readTimer) {
                clearTimeout(win._readTimer);
                win._readTimer = null;
            }
            win._pendingReadId = 0;
        }

        if (window.im.state.is_debug) {
            imLog("Selected conversation:", convo);
        }

        this.setChat(convo);
        const tab = await window.im.openTabByName("messenger");

        try {
            await tab.render();
        } catch (e) { console.error(e); }

        if (!convo.peer.isMessagesInited() || hadUnread) {
            try {
                await tab.render();

                const unreadCount = Number(convo.unread_count || convo._conversation?.unread_count || 0);
                const effectiveUnread = Math.max(unreadCount, hadUnread ? 1 : 0);
                const count = 25;
                const offset = hadUnread ? Math.max(0, effectiveUnread - (count - 5)) : 0;
                const isAtEnd = (offset === 0);

                if (hadUnread && offset > 0) {
                    convo.peer._chunks.chunks = [];
                    convo.peer._chunks._map = new Map();
                    convo.peer._chunks._messagesInited = false;
                    convo.peer._chunks._cachedMessages = undefined;
                    convo.peer._chunks.invalidateCache = true;

                    const unreadChunk = new MessageChunk([], true, count);
                    unreadChunk._direction = 'center';
                    await unreadChunk.fetch({
                        peer_id: convo.peer.id,
                        offset: offset,
                        count: count,
                        extended: 1
                    });

                    convo.peer._chunks.appendChunk(unreadChunk, true);
                    convo.peer._chunks._messagesInited = true;

                    const sp = new ScrollPosition(convo.peer);
                    sp.direction = "any";
                    sp.reachedNewestPosition = false;
                    sp.reachedOldestPosition = (unreadChunk.messages.length < count);
                    convo._scroll = sp;
                    sp._invalidateCache();
                } else if (!convo.peer.isMessagesInited() || hadUnread) {
                    if (hadUnread) {
                        convo.peer._chunks.chunks = [];
                        convo.peer._chunks._map = new Map();
                        convo.peer._chunks._messagesInited = false;
                        convo.peer._chunks._cachedMessages = undefined;
                        convo.peer._chunks.invalidateCache = true;
                        convo.getEndScrollPosition().recenter(null);
                    }
                    await convo.getEndScrollPosition().loadOlder();
                    convo._scroll = convo.getEndScrollPosition();
                }

                convo.getScrollPosition()?.result?.();
            } catch (e) { // может быть и broker failure.
                console.error("IM | selectConversation load error:", e);
            }

            if (scrollToEnd == true && !hadUnread) {
                this.getWindow()._scrollToEnd();
            }
        }

        if (convo.peer) {
            let firstUnread = null;
            const sp = convo.getScrollPosition();
            const dayChunks = sp?.getDayDividedMessages?.() || [];
            for (const chunk of dayChunks) {
                for (const msg of chunk.messages) {
                    if (msg && !msg.isMine() && !msg.isRead()) {
                        firstUnread = msg;
                        break;
                    }
                }
                if (firstUnread) break;
            }
            const firstUnreadId = firstUnread ? (firstUnread.id || firstUnread.conversation_message_id) : null;
            if (firstUnreadId) {
                convo.peer._firstUnreadMsgId = firstUnreadId;
                if (sp) sp.relyMessageId = Number(firstUnreadId);
            } else if (!hadUnread) {
                convo.peer._firstUnreadMsgId = null;
            }
        }

        try {
            await tab.render();
            tab.showTab();
        } catch (e) { console.error(e); }
        const newId = Number(this.currentChatId);

        if (oldId != newId) {
            try {
                this._clearAttachments();
                this.removeReply();
                this.cancelEdit();
                this.removeForwarded();

                let draft = this.getCurrentChat()?.draft;
                if (!draft && newDraft == true) {
                    draft = new Draft();
                }

                if (draft) {
                    draft.loadToPage(this.getWindow());
                }
                this.update();
                this.getWindow().updUrl();
            } catch (e) {
                console.error(e);
            }
        }

        this.setForwarded(forwarded);
        if (forwarded != null) {
            await tab.render();
        }

        if (window.im.state.isFastchat) {
            window.im.fastChats.update();
        }

        if (convo.peer?._firstUnreadMsgId) {
            this.scrollToUnread();
        } else if (scrollToEnd == true || !hadUnread) {
            this.getWindow()?._scrollToEnd?.();
        }

        const currentSp = convo?.getScrollPosition();
        if (!hadUnread && !convo.peer?._firstUnreadMsgId && convo && convo.peer && (!currentSp || currentSp.reachedNewestPosition)) {
            convo.peer.read();
        }

        setTimeout(() => {
            this.getWindow()?._checkAndFillUnderflow?.();
        }, 150);
    }

    async selectConversationByPeerId(id) {
        let convo = null;
        try {
            convo = await window.im.conversations._findConvFromApi(id);

            if (!convo) {
                console.error("can't find convo with id", id);
                throw new Error("Not found conversation with id ", id);
            }
        } catch (e) {
            fastError(String(e));
            return;
        }

        await this.selectConversation(convo);
    }

    setChat(convo, pushstate = true) {
        const oldId = Number(this.currentChatId);

        try {
            const cur = this.getCurrentChat();
            if (cur && typeof cur.setDraft === 'function') {
                const win = this.getWindow();
                if (win) {
                    cur.setDraft(Draft.fromPage(win));
                }
            }
            const win = this.getWindow();
            if (win) {
                win.is_loading = false;
                win._scrollTicking = false;
                win.currentVisibleDate = null;
            }
        } catch (e) {
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
        if (window.im.state.isFastchat) {
            window.im.fastChats.pinPeer(conv);
        }

        this.opened_tabs.push(conv);
    }

    selectChat(conv) {
        if (!conv) return;
        let id = this.opened_tabs.indexOf(conv);
        if (id === -1) {
            const convPeerId = Number(conv.peer?.id || conv.id);
            id = this.opened_tabs.findIndex(item => convPeerId && Number(item.peer?.id || item.id) === convPeerId);
            if (id !== -1) {
                this.opened_tabs[id] = conv;
            }
        }
        if (id === -1) {
            console.error("can't find convo in tab", conv);
            return;
        }

        this.currentChatId = id;
    }

    hasChat(conv) {
        if (!conv) return false;
        imLog("hasChat:", this.opened_tabs, conv);
        const convPeerId = Number(conv.peer?.id || conv.id);
        return this.opened_tabs.some(item => item === conv || (convPeerId && Number(item.peer?.id || item.id) === convPeerId));
    }

    getTabsCount() {
        return this.opened_tabs.length;
    }

    getChatWith(chat_general_form) {
        if (!chat_general_form) return null;
        const targetId = Number(chat_general_form.id || chat_general_form.peer?.id);
        let found = this.opened_tabs.find(item => Number(item.peer?.id || item.id) === targetId);
        if (found) return found;

        if (window.im?.conversations?.convs) {
            found = window.im.conversations.convs.find(item => Number(item.peer?.id || item.id) === targetId);
            if (found) return found;
        }

        return new Conversation({ 'peer': chat_general_form });
    }

    getCurrentChat() {
        if (this.currentChatId === null || this.currentChatId === undefined) return null;
        return this.opened_tabs[this.currentChatId] || null;
    }

    async closeChat(conv, page) {
        let idx = this.opened_tabs.indexOf(conv);
        if (idx === -1) {
            const convPeerId = Number(conv?.peer?.id || conv?.id);
            idx = this.opened_tabs.findIndex(item => convPeerId && Number(item.peer?.id || item.id) === convPeerId);
        }
        if (idx === -1) return;

        const currentConv = this.getCurrentChat();
        const wasCurrent = Boolean(currentConv && (
            currentConv === conv ||
            Number(currentConv.peer?.id || currentConv.id) === Number(conv?.peer?.id || conv?.id)
        ));

        this.opened_tabs.splice(idx, 1);

        imLog("closeChat:", idx, "remaining tabs:", this.opened_tabs.length);

        if (this.opened_tabs.length === 0) {
            this.currentChatId = null;
            if (typeof window.im !== 'undefined') {
                if (window.im.updateTabs) {
                    window.im.updateTabs();
                }
                await window.im.openTabByName("conversations");
            }
            return;
        }

        if (wasCurrent) {
            const nextIdx = Math.min(idx, this.opened_tabs.length - 1);
            const nextConv = this.opened_tabs[nextIdx];
            this.currentChatId = nextIdx;
            if (nextConv) {
                await this.selectConversation(nextConv);
            }
            if (typeof window.im !== 'undefined' && window.im.updateTabs) {
                window.im.updateTabs();
            }
        } else {
            this.currentChatId = this.opened_tabs.indexOf(currentConv);
            if (this.currentChatId === -1 || this.currentChatId >= this.opened_tabs.length) {
                this.currentChatId = 0;
            }
            if (page && typeof page.update === 'function') {
                page.update();
            } else {
                this.update();
            }
            if (typeof window.im !== 'undefined' && window.im.updateTabs) {
                window.im.updateTabs();
            }
        }
    }

    async setWriting() {
        const curConvo = window.im?.state?.getCurrentConvo();
        if (!curConvo) return;

        const params = {
            "type": "typing",
            "peer_id": curConvo.id,
        };
        const gid = window.im.state.getId();
        if (gid < 0) {
            params["group_id"] = Math.abs(gid);
        }

        imLog('IM | setWriting called');
        try {
            await window.OVKAPI.call("messages.setActivity", params);
        } catch (e) {
            console.error(e);
        }
    }

    async sendToCurrentCorresponder() {
        const view = this.getWindow();
        let text = view ? (view.getCurrentText() || '') : '';
        const reply_to = this.replyTo;
        let reply_param = null;
        let attachments_list = null;
        const corresponder = window.im?.state?.getCurrentConvo();
        if (!corresponder || !corresponder.peer) return;

        const attachments = collect_attachments(u('.messenger-app--input---messagebox'));
        if (attachments.length > 0) { attachments_list = attachments; }
        if (reply_to) { reply_param = reply_to; }

        const cleanText = text.replace(/[\s\u200b\ufeff\u00a0]/g, '');
        if (!cleanText) {
            text = '';
        }
        if (!text && !attachments_list && !reply_param && (!this.forwarded_msg || this.forwarded_msg.length === 0)) {
            return;
        }

        if (text.length <= Messenger.MESSAGE_CHUNK_LENGTH + 20) {
            const msg = new ChatMessage({
                'from_id': window.im.state.getOperator().id,
                'peer_id': corresponder.id,
                'date': Math.round((new Date()).getTime() / 1000),
                "is_sending": true,
            });
            if (attachments_list) msg.has_not_loaded_attachments = true;
            msg._guessSender();
            msg.setText(text);
            return await corresponder.peer.sendMessage(msg, reply_param, attachments_list, null, () => {
                view._scrollToEnd();
            }, this.forwarded_msg);
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

    async viewMedia(media_type = "pinned") { }
    async viewPinned(event, convo) {
        if (event && event.target && event.target.classList) {
            event.target.classList.add("lagged");
        }
        if (!convo.hasPinned()) {
            console.error("IM | Messenger | No pinned");
            return;
        }

        await this.view.goToMessage({
            peer_id: convo.id,
            id: convo.getPinnedMessageId()
        });
        if (event && event.target && event.target.classList) {
            event.target.classList.remove("lagged");
        }
    }

    async showPinnedModal(convo) {
        let pinMsg = convo ? convo.getPinnedMessageObject() : null;
        if (!pinMsg) return;

        const peer = convo.peer;
        const sender = pinMsg.sender || window.im?.cached_profiles?._findCachedProfileById(pinMsg.from_id);
        const senderName = sender ? sender.getName(false, true) : (tr("user") + " " + pinMsg.from_id);
        const senderAva = sender ? sender.getAvatar("mid") : "/assets/packages/static/openvk/img/camera_100.png";
        const senderUrl = sender ? sender.getPageUrl() : "javascript:void(0);";
        const formattedDate = pinMsg.getDate(2) + " " + pinMsg.getDate(0);
        const formattedText = pinMsg.getText(false);

        let attachmentsHtml = "";
        const atts = pinMsg.getAttachments();
        if (atts && atts.length > 0) {
            attachmentsHtml = `<div class="attachments" style="margin-top: 8px;">`;
            for (const att of atts) {
                if (att.photo) {
                    const src = att.photo.photo_604 || att.photo.photo_130 || att.photo.photo_75 || att.photo.link;
                    attachmentsHtml += `<div style="margin-top:4px;"><img src="${src}" style="max-width: 100%; border-radius: 2px;" /></div>`;
                } else if (att.video) {
                    attachmentsHtml += `<div style="margin-top:4px;"><b>${tr("chat_media_video")}:</b> ${escapeHtml(att.video.title || "")}</div>`;
                } else if (att.audio) {
                    attachmentsHtml += `<div style="margin-top:4px;"><b>${tr("chat_media_audio")}:</b> ${escapeHtml(att.audio.artist || "")} - ${escapeHtml(att.audio.title || "")}</div>`;
                } else if (att.doc) {
                    attachmentsHtml += `<div style="margin-top:4px;"><b>${tr("chat_media_doc")}:</b> <a href="${att.doc.url}" target="_blank">${escapeHtml(att.doc.title || "")}</a></div>`;
                }
            }
            attachmentsHtml += `</div>`;
        }

        const bodyHtml = `
            <div class="pinned-message-modal-content" style="padding: 10px 0;">
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <a href="${senderUrl}" style="flex-shrink: 0;">
                        <img src="${senderAva}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;" />
                    </a>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: baseline;">
                            <a href="${senderUrl}" style="font-weight: bold; color: var(--link); text-decoration: none;">${escapeHtml(senderName)}</a>
                            <span style="color: #999; font-size: 10px;">${formattedDate}</span>
                        </div>
                        <div class="normalText" style="margin-top: 5px; font-size: 12px; line-height: 1.4; word-break: break-word;">
                            ${formattedText}
                        </div>
                        ${attachmentsHtml}
                    </div>
                </div>
            </div>
        `;

        const buttons = [tr("chat_view_pinned_single")];
        const callbacks = [
            async () => {
                await this.view.goToMessage({
                    id: pinMsg.id || convo.getPinnedMessageId(),
                    peer_id: convo.id
                });
            }
        ];

        const canUnpin = (peer && typeof peer.can === 'function' && peer.can("pin")) || (pinMsg && typeof pinMsg.can === 'function' && pinMsg.can("pin"));
        if (canUnpin) {
            buttons.push(tr("unpin_message"));
            callbacks.push(async () => {
                await this.unpinMessage(convo);
            });
        }

        buttons.push(tr("close"));
        callbacks.push(Function.noop);

        new CMessageBox({
            title: tr("pinned_message"),
            body: bodyHtml,
            buttons: buttons,
            callbacks: callbacks,
            close_on_buttons: true,
            unique_name: "pinned_message_box"
        });
    }

    async unpinMessage(convo, confirm = true) {
        if (!convo) return;

        const doUnpin = async () => {
            try {
                const peerId = convo.peer ? convo.peer.id : convo.id;
                const params = { "peer_id": peerId };
                if (window.im.usage_type == "group") {
                    params["group_id"] = Math.abs(window.im.state.getOperator().id);
                }
                await window.OVKAPI.call("messages.unpin", params);
                if (typeof convo.setPinnedMessage === 'function') {
                    convo.setPinnedMessage(null);
                }
                if (convo._conversation) {
                    convo._conversation.current_pinned_message = null;
                    convo._conversation.pinned_message = null;
                    if (convo._conversation.chat_settings) {
                        convo._conversation.chat_settings.pinned_message = null;
                    }
                }
                if (convo.peer && convo.peer.data) {
                    convo.peer.data.pinned_message = null;
                    convo.peer.data.current_pinned_message = null;
                    if (convo.peer.data.chat_settings) {
                        convo.peer.data.chat_settings.pinned_message = null;
                    }
                }
                this.update();
            } catch (e) {
                fastError(String(e));
                console.error(e);
            }
        };

        if (confirm) {
            new CMessageBox({
                title: tr("confirm"),
                body: tr("unpin_button_click"),
                buttons: [tr("yes"), tr("no")],
                callbacks: [
                    async () => { await doUnpin(); },
                    Function.noop
                ],
                close_on_buttons: true
            });
        } else {
            await doUnpin();
        }
    }

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
                objs.push(chat.peer._chunks._findMessageById(item));
            }
        })

        return objs;
    }

    get selected_messages_count() {
        return this.selected_messages.length;
    }

    async showAttachment(event, msg, attachment) {
        event.preventDefault();

        const idinarray = msg.getAttachments().indexOf(attachment);
        const type = attachment.type;

        CMessageBox.toggleLoader(true);

        const queue_items = [];

        msg.getAttachments().forEach(att => {
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
            imLog("IM | Messenger | Opening photo | Not found ", attachment, " image in", photos);
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
    removeForward(render = true) {
        this.forwarded_msg = null;
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

    async _ensureAtEndBeforeSend(convo = null) {
        const currentConv = convo || window.im?.state?.getCurrentConvo();
        if (!currentConv) return;
        const win = this.getWindow();

        const chunks = currentConv.peer?._chunks;
        const scrollPos = currentConv.getScrollPosition();
        const endSp = currentConv.getEndScrollPosition();

        let isViewingHistory = false;

        if (currentConv._scroll != null && endSp != null && currentConv._scroll !== endSp) {
            isViewingHistory = true;
        }
        if (scrollPos && scrollPos.reachedNewestPosition === false) {
            isViewingHistory = true;
        }

        if (chunks) {
            const latestChunkMsg = chunks.getLatestMessage ? chunks.getLatestMessage() : null;
            const convLastMsg = currentConv.last_message || currentConv._last_message || currentConv._conversation?.last_message;

            const latestId = Number(latestChunkMsg?.id || latestChunkMsg?.data?.conversation_message_id || latestChunkMsg?.data?.id || 0);
            const convLastId = Number(convLastMsg?.id || convLastMsg?.data?.conversation_message_id || convLastMsg?.data?.id || 0);

            if (latestId > 0 && convLastId > 0 && latestId < convLastId) {
                isViewingHistory = true;
            }
        }

        if (isViewingHistory) {
            if (chunks) {
                chunks.chunks = [];
                chunks._map = new Map();
                chunks._messagesInited = false;
                chunks._cachedMessages = undefined;
                chunks.invalidateCache = true;
            }
            if (endSp) {
                endSp.recenter(null);
                await endSp.loadOlder();
            }
            currentConv._scroll = endSp;
            currentConv._scroll?._invalidateCache?.();
        } else {
            if (endSp) {
                const allChunks = endSp.getChronologicalChunks();
                if (allChunks.length > 0) {
                    endSp.windowEndIndex = allChunks.length - 1;
                    endSp.windowStartIndex = Math.max(0, endSp.windowEndIndex - (endSp.constructor.MAX_RENDERED_CHUNKS || 10) + 1);
                    endSp.reachedNewestPosition = true;
                    endSp._invalidateCache();
                }
            }
            currentConv._scroll = endSp;
        }

        if (win) {
            await win.update();
            win._scrollToEnd(false);
            win._clearUnreadSpacing();
            win.updateMountainButton();
        }
    }

    // onSendMessageButtonClick
    async onSendMessage() {
        const _tmp_atts = collect_attachments(u('.messenger-app--input---messagebox'));
        const win = this.getWindow();
        const rawText = win ? (win.getCurrentText() || '') : '';
        const cleanText = rawText.replace(/[\s\u200b\ufeff\u00a0]/g, '');

        if (!cleanText && _tmp_atts.length === 0 && !this.isForwarded() && this.replyTo == null) return false;
        if (rawText.length > 55000) {
            fastError("> 55000")
            return;
        }

        if (this.editMsg != null) {
            if (!cleanText && _tmp_atts.length === 0) return false;
            this.editMsg.edit(cleanText ? rawText : '', _tmp_atts);

            this.cancelEdit();
            return;
        }

        await this._ensureAtEndBeforeSend(window.im?.state?.getCurrentConvo());

        this.sendToCurrentCorresponder();

        this.currentDraft = "";
        if (win) {
            win.currentDraft = "";
            win.setCurrentText("");
        }
        if (window.im?.state?.getCurrentConvo()) {
            window.im.state.getCurrentConvo().clearDraft();
        }
        this._clearAttachments();
        this.removeReply();
        this.removeForwarded();
    }

    async sendSticker(stickerId, packId = null, stickerData = null) {
        const corresponder = window.im?.state?.getCurrentConvo();
        if (!corresponder || !corresponder.peer) return;

        await this._ensureAtEndBeforeSend(corresponder);

        const view = this.getWindow();
        const reply_to = this.replyTo;
        let reply_param = null;
        if (reply_to) { reply_param = reply_to; }

        if (!stickerData && typeof window.findStickerData === 'function') {
            stickerData = window.findStickerData(stickerId);
        }

        const sId = Number(stickerId);
        const pId = Number(packId || (stickerData ? stickerData.product_id : 0));
        const photo128 = stickerData?.photo_128 || (pId ? `/sticker/${pId}/${sId}_128.webp` : '');
        const photo256 = stickerData?.photo_256 || photo128;
        const photo512 = stickerData?.photo_512 || (pId ? `/sticker/${pId}/${sId}_512.webp` : photo128);

        const animUrl = stickerData?.animation_url || (stickerData?.animations && stickerData?.animations[0]?.url) || '';

        const stickerObj = {
            id: sId,
            sticker_id: sId,
            product_id: pId,
            photo_64: photo128,
            photo_128: photo128,
            photo_256: photo256,
            photo_352: photo512,
            photo_512: photo512,
            width: 512,
            height: 512,
            animation_url: animUrl || (stickerData?.is_animated && pId ? `/sticker/${pId}/${sId}_512.json` : ''),
            is_animated: Boolean(animUrl || stickerData?.is_animated),
            animations: stickerData?.animations || (animUrl ? [{ type: 'light', url: animUrl }] : []),
            images: stickerData?.images || [
                { url: photo128, width: 128, height: 128 },
                { url: photo256, width: 256, height: 256 },
                { url: photo512, width: 512, height: 512 }
            ]
        };

        const randomId = Math.floor(Math.random() * 2147483647);

        const msg = new ChatMessage({
            'from_id': window.im.state.getOperator().id,
            'peer_id': corresponder.id,
            'date': Math.round((new Date()).getTime() / 1000),
            'is_sending': true,
            'is_sticker': 1,
            'random_id': randomId,
            'attachments': [{
                type: 'sticker',
                sticker: stickerObj
            }]
        });
        if (typeof msg._guessSender === 'function') {
            msg._guessSender();
        }
        msg.setText("");

        this.removeReply();
        this.removeForwarded();

        return await corresponder.peer.sendMessage(msg, reply_param, ['sticker' + sId], null, () => {
            if (view && typeof view._scrollToEnd === 'function') {
                view._scrollToEnd();
            }
        });
    }

    cancelEdit(render = true) {
        window.im.messenger.editMsg = null;
        const win = this.getWindow();
        this._clearAttachments();

        imLog("prevDraft:", this.prevDraft);
        const restored = this.prevDraft ? this.prevDraft : "";
        if (win) {
            win.setCurrentText(restored);
            win.currentDraft = String(restored);
        }
        this.currentDraft = String(restored);
        this.prevDraft = null;

        if (render == true) {
            this.getWindow()?.update();
        }
        setTimeout(() => {
            this.getWindow()?.setInputSelectionToEnd?.();
        }, 50);
    }

    isEditing() {
        return this.editMsg != null;
    }

    async goToMessage(msg, presetConvo = null, open_tab = true) {
        try {
            if (open_tab) {
                const tab = await window.im.openTabByName("messenger");
                if (tab) await tab.render();
            }
        } catch (e) { console.error(e); }

        await this.view.goToMessage(msg, presetConvo);
    }

    setForwarded(msgs) { this.forwarded_msg = msgs; }
    removeForwarded() { this.forwarded_msg = null; }
    isForwarded() { return this.forwarded_msg != null && this.forwarded_msg.length > 0 }
    async onConversationsClick(convo, isForward = false, page = null) {
        this.forwarded_msg = null;
        if (isForward) {
            await this.selectConversation(convo, false, true, page.options.forward)
        } else {
            this.selectConversation(convo, false);
        }
    }

    showDaySwitcher(date = null) {
        openCalendarModal({
            initialDate: date,
            peerId: this.getCurrentChat()?.peer?.id
        });
    }

    scrollToUnread() {
        const win = this.getWindow();
        if (win && typeof win.scrollToUnread === 'function') {
            win.scrollToUnread();
        }
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
        this._scrollTicking = false;
        this._readObserver = null;
        this._readTimer = null;
        this._pendingReadId = 0;
        this._hasBoundReadVisibility = false;
    }
    getCurrentChat() {
        return window.im?.messenger?.getCurrentChat?.() || this.current_chat;
    }
    showHook() {
        try {
            const v = window.im?.messenger?.getCurrentChat();
            if (v && v.peer && v.peer._firstUnreadMsgId) {
                this.scrollToUnread();
            } else if (v && v.draft && (v.draft.scroll != null || v.draft.anchorMsgId != null || v.draft.isAtEnd)) {
                this._clearUnreadSpacing();
                v.draft.loadScroll(this);
            } else {
                this._clearUnreadSpacing();
                this._scrollToEnd();
            }
            this.updateMountainButton();
            this._setupReadObserver();
            this._setupResizeListener();
            setTimeout(() => this._checkAndFillUnderflow(), 150);
        } catch (e) { console.error(e); }
    }
    isDisablesScroll() { return true; }
    _triggerUpdate() {
        if (window.im?.conversations) {
            window.im.conversations.update();
        }
        return this.update();
    }
    updUrl() {
        const url = new URL(location.href);
        url.searchParams.delete("joinByTopic");
        const cur = window.im?.messenger?.getCurrentChat();
        if (cur && cur.peer && cur.peer.id) {
            url.searchParams.set("sel", String(cur.peer.id));
        } else {
            url.searchParams.delete("sel");
        }
        window.im.state._pushState(url.toString());
    }

    //async render(container, special_mode = null, messages = null) {
    async render(container, options = {}, messenger = null) {
        const orig_messenger = messenger || window.im.messenger;
        try {
            const currentText = this.getCurrentText();
            orig_messenger.currentDraft = currentText || "";
            this.currentDraft = currentText || "";
        } catch (e) {
            console.error(e);
        }
        this.getNode().addClass("page-other");

        let messages = null;
        let special_mode = "";
        const root = container;
        if (!root) {
            console.error("no root")
            return;
        };

        const currentConv = orig_messenger.getCurrentChat();
        if (!currentConv) {
            render(html`<${ErrorConversation} />`, root)
            return;
        }

        const peer = currentConv ? currentConv.peer : null;
        const display_peer = this.toggled_peer_obj ? this.toggled_peer_obj : peer;
        const sp = currentConv.getScrollPosition();
        if (!messages && peer.isMessagesInited()) {
            messages = sp.getDayDividedMessages();
        }

        imLog("Messenger rendered messages:", messages);
        const is_rendering_contact_window = (window.im.tab == "contact" && special_mode === null);
        const initialDate = this.currentVisibleDate || (
            (messages && messages.length > 0)
                ? (messages[messages.length - 1].readable_date || messages[messages.length - 1].date || "")
                : ""
        );

        render(html`
        <div id="chat-page">
            <div class="chat-window ${peer.id == window.im.state.getId() ? "saved-msgs" : ""}">
            <${PeerTabsView} hadTab=${true} tabs=${orig_messenger.opened_tabs} currentChat=${orig_messenger.currentChatId} page=${this} convo=${currentConv} />
            <${PeerInfoView} page=${this} convo=${currentConv} togglePeerInfo=${() => { this.togglePeerInfo() }} />
            <${ActionsBar}
                selectedMessages=${orig_messenger.selected_messages_objs}
                count=${orig_messenger.selected_messages_count}
                onDelete=${() => this.callDeletion()}
                onUnselect=${() => orig_messenger.unselectAll()}
                onReply=${() => this.onReplyButtonClick()}
                onForwardClick=${() => { this.onForwardClick() }}
                onViewers=${(msg) => { this.onViewersButtonClick(null, msg || orig_messenger.selected_messages_objs[0]) }}
            />
            <div class="messenger-app messenger-layer">
                ${initialDate ? html`
                    <div class="im_floating_date_wrap" onClick=${(e) => this.onFloatingDateClick(e)}>
                        <b id="im_floating_date_text">${initialDate}</b>
                    </div>
                ` : ""}
                <${MessageListView}
                convo=${currentConv}
                dayDividedChunks=${messages} 
                page=${this} />
                ${!options.removeInput ? html`<${InputArea}
                convo=${currentConv}
                editMsg=${orig_messenger.editMsg}
                replyTo=${orig_messenger.replyTo}
                onRemoveReply=${() => orig_messenger.removeReply()}
                onSend=${() => orig_messenger.onSendMessage()}
                onKeyPress=${(e) => this.onTextareaKeyPress(e)}
                currentDraft=${orig_messenger.currentDraft}
                onInput=${(e) => {
                    const val = e.target.value;
                    this.currentDraft = val;
                    if (orig_messenger) orig_messenger.currentDraft = val;
                }}
                togglePeerInfo=${(e) => { this.togglePeerInfo() }}
                clickOnReply=${(msg, e) => { this.clickOnReply(msg, e) }}
                forwarded_msg=${orig_messenger.forwarded_msg}
                onRemoveForward=${() => orig_messenger.removeForward()}
                />` : ""}
            </div>
            </div>
        </div>
        `, root);
        this._updPadding();
        this._setupReadObserver();
    }

    _updPadding() {
        // Obsolete with flex layout; no-op preserved for backwards compatibility
    }

    _getChronologicalMessages(currentConv) {
        if (!currentConv) return [];

        const allMsgs = [];
        const seen = new Set();

        const addMsg = (m) => {
            if (!m) return;
            const key = m.id != null ? m.id : (m.data?.random_id || m.data?.conversation_message_id);
            if (key != null && seen.has(key)) return;
            if (key != null) seen.add(key);
            allMsgs.push(m);
        };

        if (currentConv.peer && currentConv.peer._chunks) {
            const chunks = currentConv.peer._chunks.chunks || [];
            chunks.forEach(chunk => {
                if (chunk && chunk.messages) {
                    chunk.getMessages().forEach(addMsg);
                }
            });
        }

        if (typeof currentConv.getScrollPosition === 'function' && currentConv.getScrollPosition()) {
            const sp = currentConv.getScrollPosition();
            const days = sp._cachedDays || (typeof sp.getDayDividedMessages === 'function' ? sp.getDayDividedMessages() : []);
            (days || []).forEach(day => {
                if (day && day.messages) {
                    day.messages.forEach(addMsg);
                }
            });
        }

        if (currentConv.last_message) addMsg(currentConv.last_message);
        if (currentConv._last_message) addMsg(currentConv._last_message);

        const domNodes = this.container?.querySelectorAll('.messenger-app--messages---message[data-msg-id]');
        if (domNodes && domNodes.length > 0 && currentConv.peer?._chunks?._findMessageById) {
            domNodes.forEach(node => {
                const id = Number(node.getAttribute('data-msg-id'));
                if (id && !seen.has(id)) {
                    const m = currentConv.peer._chunks._findMessageById(id);
                    if (m) addMsg(m);
                }
            });
        }

        allMsgs.sort((a, b) => {
            const dateA = Number(a.data?.date || 0);
            const dateB = Number(b.data?.date || 0);
            if (dateA !== dateB) return dateA - dateB;
            const idA = Number(a.id || a.data?.conversation_message_id || 0);
            const idB = Number(b.id || b.data?.conversation_message_id || 0);
            return idA - idB;
        });

        return allMsgs;
    }

    onTextareaKeyPress(e) {
        const ta = e.target;
        const isCtrl = e.ctrlKey || e.metaKey;

        if (e.which === 38 || e.key === "ArrowUp") {
            const currentConv = window.im.messenger.getCurrentChat();

            // Ctrl + ArrowUp: циклический переход по ответам (реплаям) в истории
            if (isCtrl) {
                if (currentConv) {
                    const allMsgs = this._getChronologicalMessages(currentConv);
                    const replyable = allMsgs.filter(m => m && !m.isDeleted() && !m.isAction() && (typeof m.can !== 'function' || m.can("reply")));

                    if (replyable.length > 0) {
                        e.preventDefault();
                        let targetMsg = null;
                        const currentReply = window.im.messenger.replyTo;

                        if (!currentReply) {
                            targetMsg = replyable[replyable.length - 1];
                        } else {
                            const currentId = currentReply.id;
                            const curIdx = replyable.findIndex(m =>
                                (currentId != null && m.id != null && m.id === currentId) || m === currentReply
                            );

                            if (curIdx > 0) {
                                targetMsg = replyable[curIdx - 1];
                            } else if (curIdx === 0) {
                                targetMsg = replyable[0];
                            } else {
                                targetMsg = replyable[replyable.length - 1];
                            }
                        }

                        if (targetMsg) {
                            const val = this.getCurrentText();
                            if (val) {
                                window.im.messenger.currentDraft = val;
                                this.currentDraft = val;
                            }
                            window.im.messenger.unselectAll();
                            window.im.messenger.replyTo = targetMsg;
                            this.update();
                            setTimeout(() => {
                                const targetEl = this.container?.querySelector(`.messenger-app--messages---message[data-msg-id="${targetMsg.id}"]`);
                                if (targetEl) {
                                    targetEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                }
                                this.setInputSelectionToEnd();
                            }, 30);
                            return false;
                        }
                    }
                }
                return false;
            }

            // ArrowUp: редактирование последнего сообщения
            const isEmpty = this.isInputEmpty(ta);
            if (isEmpty && !window.im.messenger.isEditing()) {
                if (currentConv) {
                    const allMsgs = this._getChronologicalMessages(currentConv);

                    let lastMyMsg = null;
                    for (let i = allMsgs.length - 1; i >= 0; i--) {
                        const m = allMsgs[i];
                        if (m && !m.isDeleted() && !m.isAction() && m.isMine() && m.can("edit")) {
                            lastMyMsg = m;
                            break;
                        }
                    }

                    if (lastMyMsg) {
                        e.preventDefault();
                        this.onEditButtonClick(e, lastMyMsg);
                        setTimeout(() => {
                            this.setInputSelectionToEnd();
                        }, 50);
                        return false;
                    }
                }
            }
        }

        // Ctrl + ArrowDown: переход вперед по реплаям или снятие реплая
        if ((e.which === 40 || e.key === "ArrowDown") && isCtrl) {
            if (window.im.messenger.replyTo != null) {
                e.preventDefault();
                const currentConv = window.im.messenger.getCurrentChat();
                if (currentConv) {
                    const allMsgs = this._getChronologicalMessages(currentConv);
                    const replyable = allMsgs.filter(m => m && !m.isDeleted() && !m.isAction() && (typeof m.can !== 'function' || m.can("reply")));
                    const currentId = window.im.messenger.replyTo.id;
                    const curIdx = replyable.findIndex(m =>
                        (currentId != null && m.id != null && m.id === currentId) || m === window.im.messenger.replyTo
                    );

                    let nextReplyMsg = null;
                    if (curIdx >= 0 && curIdx < replyable.length - 1) {
                        nextReplyMsg = replyable[curIdx + 1];
                        window.im.messenger.replyTo = nextReplyMsg;
                        this.update();
                    } else {
                        window.im.messenger.removeReply();
                    }
                    setTimeout(() => {
                        if (nextReplyMsg) {
                            const targetEl = this.container?.querySelector(`.messenger-app--messages---message[data-msg-id="${nextReplyMsg.id}"]`);
                            if (targetEl) {
                                targetEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                            }
                        }
                        this.setInputSelectionToEnd();
                    }, 30);
                }
                return false;
            }
        }

        if (e.which === 27 || e.key === "Escape") {
            if (window.im.messenger.isEditing()) {
                e.preventDefault();
                window.im.messenger.cancelEdit();
                return false;
            }
            if (window.im.messenger.replyTo != null) {
                e.preventDefault();
                window.im.messenger.removeReply();
                return false;
            }
        }

        if (e.which !== 13) {
            const now = Date.now();
            const lastTyping = window.im.messenger._typingStarted;
            if (!lastTyping || now - lastTyping > 5000) {
                window.im.messenger._typingStarted = now;
                window.im.messenger.setWriting();
            }
        }

        if (e.which === 13 || e.key === "Enter") {
            window.im.messenger._typingStarted = null;
            if (!e.metaKey && !e.shiftKey) {
                e.preventDefault();
                ta.blur();
                window.im.messenger.onSendMessage();
                ta.focus();
                return false;
            }
        }
        this._updPadding();
        return true;
    }

    onMessageClick(msg, e) {
        if (msg.isDeleted()) {
            return;
        }

        if (msg.isError()) {
            const cmsg = new CMessageBox({
                title: tr("error"),
                body: tr("error_sending_msg") + ": <br>" + escapeHtml(msg.data.error_text),
                buttons: [tr("msg_resend"), tr("msg_error_send_to_dev"), tr("ok")],
                callbacks: [() => {
                    msg.tryToResend();
                }, async () => {
                    await window.im.messenger.selectConversationByPeerId(window.openvk.dev_id);
                    window.im.messenger.view.setCurrentText(msg.data.error_text);
                }, () => { }]
            });
            return;
        }

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

    async goToMessage(msg, presetConvo) {
        const id = Number(typeof msg === 'object' ? (msg.id || msg.data?.id) : msg);
        if (!id) return;

        const peerId = Number(
            (typeof msg === 'object' ? (msg.peer_id || msg.data?.peer_id) : null) ||
            presetConvo?.peer?.id ||
            presetConvo?.id ||
            window.im.messenger.currentChatId
        );

        let conv = presetConvo;
        if (!conv || (conv.peer && Number(conv.peer.id) !== peerId)) {
            try {
                conv = await window.im.conversations._findConvFromApi(peerId) || window.im.conversations._findConv(peerId);
            } catch (e) {
                console.error(e);
            }
        }
        if (!conv) return;

        if (Number(window.im.messenger.currentChatId) !== peerId) {
            window.im.messenger.setChat(conv);
            const tab = await window.im.openTabByName("messenger");
            if (tab) await tab.render();
        }

        const chunks = conv.peer._chunks;
        let targetMsg = (chunks && chunks._messagesInited) ? chunks._findMessageById(id) : null;
        let targetAnchorId = id;

        if (targetMsg) {
            // Сообщение уже есть в текущем диалоге — центрируем окно чанков вокруг него
            let sp = conv.getScrollPosition();
            if (!sp || !conv.hasScrollPosition()) {
                sp = new ScrollPosition(conv.peer);
                conv._scroll = sp;
            }
            sp.recenter(id);
            await this.update();
            this.scrollToMessage(id, peerId, conv);
            return;
        }

        // Сообщения нет в текущем диалоге — полный сброс всех чанков и загрузка через getHistory
        chunks.chunks = [];
        chunks._map = new Map();
        chunks._messagesInited = false;
        chunks._cachedMessages = undefined;
        chunks.invalidateCache = true;

        const centerChunk = new MessageChunk([], true, 41);
        centerChunk._direction = 'center';
        await centerChunk.fetch({
            peer_id: peerId,
            start_message_id: id,
            offset: -20,
            count: 41,
            extended: 1
        });

        // Проверяем, появилось ли желаемое сообщение после загрузки
        const foundInLoaded = centerChunk.messages && centerChunk.messages.find(m => Number(m.id) === id);
        if (foundInLoaded) {
            targetAnchorId = id;
        } else if (centerChunk.messages && centerChunk.messages.length > 0) {
            // Если желаемое сообщение не появилось — узнать какой ID под индексом 20 (или ближайший)
            const fallbackIdx = centerChunk.messages[20] ? 20 : Math.floor(centerChunk.messages.length / 2);
            const nearestMsg = centerChunk.messages[fallbackIdx] || centerChunk.messages[0];
            if (nearestMsg && nearestMsg.id) {
                targetAnchorId = Number(nearestMsg.id);
            }
        }

        chunks.appendChunk(centerChunk, true);
        chunks._messagesInited = true;

        const sp = new ScrollPosition(conv.peer);
        sp.relyMessageId = targetAnchorId;
        sp.direction = "any";
        conv._scroll = sp;
        sp._invalidateCache();

        await this.update();
        this.scrollToMessage(targetAnchorId, peerId, conv);
    }

    clickOnReply(msg, e) {
        this.goToMessage(msg);
    }

    onReplyButtonClick() {
        if (window.im.messenger.isForwarded()) { return; }

        const f = () => {
            const ids = window.im.messenger.selected_messages;
            const current_chat = window.im.messenger.getCurrentChat();
            const m = current_chat.peer._chunks._findMessageById(ids[0]);
            window.im.messenger.unselectAll();
            window.im.messenger.replyTo = m;

            this.update();
            setTimeout(() => {
                this.setInputSelectionToEnd();
            }, 50);
        };

        if (window.im.messenger.editMsg != null) {
            this._triggerCancelEditingDialog(() => { f() });
        } else {
            f();
        }
    }

    onForwardClick() {
        const forwards = [];
        window.im.messenger.selected_messages_objs.forEach(item => {
            forwards.push(item);
        })

        window.im.openTabByName("conversations", false, {
            "forward": forwards
        });
        //window.im.messenger.unselectAll();
    }

    onEditButtonClick(e, msg) {
        if (!msg || window.im.messenger.isForwarded()) { return; }
        if (typeof msg.can === 'function' && !msg.can("edit")) { return; }
        if (typeof msg.isSpecial === 'function' && msg.isSpecial("sticker")) { return; }

        window.im.messenger.editMsg = msg;
        const msgText = msg.getText ? msg.getText(true) : (msg.data?.text || "");
        if (msgText.length > 0) {
            window.im.messenger.prevDraft = this.getCurrentText();
            window.im.messenger.currentDraft = msgText;
            this.setCurrentText(msgText);
        }

        if (msg.getAttachments().length > 0) {
            unpack_attachments_into_node(u(this.container.querySelector("#write")), msg.getAttachments());
        }

        this.update();
        setTimeout(() => {
            this.setInputSelectionToEnd();
        }, 50);
    }

    async onViewersButtonClick(e, msg) {
        if (e && e.stopPropagation) e.stopPropagation();
        if (window.im.messenger.isForwarded()) { return; }

        const peerId = msg.data?.peer_id || msg.peer?.id;
        const cmid = msg.data?.conversation_message_id || msg.conversation_message_id || msg.data?.local_id || msg.local_id || msg.data?.id;

        const cmsg = new CMessageBox({
            title: tr("message_viewers_title") || "Просмотрели сообщение",
            body: `<div class="message-viewers-modal-loader" style="text-align:center; padding: 25px;"><div id="gif_loader"></div></div>`,
            buttons: [tr("close")],
            callbacks: [() => { }]
        });

        try {
            const res = await window.OVKAPI.call("messages.getMessageViewers", {
                peer_id: peerId,
                conversation_message_id: cmid,
                message_id: msg.data?.id || cmid,
                extended: 1
            });

            const profiles = res.profiles || [];
            const items = res.items || [];

            if (!profiles.length && !items.length) {
                cmsg.getNode().find(".message-viewers-modal-loader").parent().html(`
                    <div class="message-viewers-empty" style="text-align: center; padding: 20px; color: var(--text-2ary); font-size: 13px;">
                        ${tr("message_viewers_empty") || "Это сообщение ещё никто не прочитал"}
                    </div>
                `);
                return;
            }

            const profMap = new Map();
            profiles.forEach(p => profMap.set(p.id, p));

            const usersListHtml = items.map(it => {
                const prof = profMap.get(it.user_id) || {
                    id: it.user_id,
                    first_name: "id" + it.user_id,
                    last_name: "",
                    photo_50: "/assets/packages/static/openvk/img/camera_50.png"
                };
                const fullName = escapeHtml(`${prof.first_name || ""} ${prof.last_name || ""}`.trim());
                const ava = prof.photo_50 || prof.photo_100 || "/assets/packages/static/openvk/img/camera_50.png";
                const isOnline = prof.online == 1;

                return `
                    <div class="message-viewer-row" style="display: flex; align-items: center; padding: 6px 10px; border-bottom: 1px solid var(--bg-slightly-border);">
                        <a href="/id${prof.id}" target="_blank" style="position: relative; margin-right: 10px; display: inline-block;">
                            <img src="${ava}" style="width: 36px; height: 36px; object-fit: cover; display: block;" />
                        </a>
                        <div style="flex: 1; min-width: 0;">
                            <a href="/id${prof.id}" target="_blank" style="font-weight: bold; color: var(--text-primary); text-decoration: none; font-size: 12px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                ${fullName}
                            </a>
                            <span style="font-size: 11px; color: var(--text-2ary);">
                                ${isOnline ? (tr('online') || 'в сети') : (tr('offline') || '')}
                            </span>
                        </div>
                    </div>
                `;
            }).join("");

            const titleCount = (tr("message_viewers_count", items.length) || `Прочитали: ${items.length}`);
            cmsg.getNode().find(".message-viewers-modal-loader").parent().html(`
                <div class="message-viewers-list-wrap">
                    <div style="font-size: 11px; color: var(--text-2ary); padding: 4px 10px 8px; border-bottom: 1px solid var(--bg-slightly-border); font-weight: bold;">
                        ${titleCount}
                    </div>
                    <div class="message-viewers-list" style="max-height: 280px; overflow-y: auto;">
                        ${usersListHtml}
                    </div>
                </div>
            `);
        } catch (err) {
            cmsg.getNode().find(".message-viewers-modal-loader").parent().html(`
                <div style="text-align: center; padding: 20px; color: #d00;">
                    ${tr("error")}: ${escapeHtml(err?.message || "Failed to load viewers")}
                </div>
            `);
        }
    }

    async onRestoreMessageClick(msg, e) {
        if (e && e.stopPropagation) e.stopPropagation();
        if (!msg || !msg.id) return;
        if (typeof msg.can === 'function' ? !msg.can('restore') : !msg.isMine()) return;

        try {
            const peerId = msg.data?.peer_id || msg.peer?.id || window.im?.messenger?.getCurrentChat()?.peer?.id;
            const res = await window.OVKAPI.call("messages.restore", {
                message_id: msg.id,
                peer_id: peerId
            });

            if (res === 1 || res) {
                if (msg.data._orig_text !== undefined) {
                    msg.restore();
                } else {
                    try {
                        const fetched = await window.OVKAPI.call("messages.getById", {
                            message_ids: msg.id,
                            peer_id: peerId
                        });
                        if (fetched && fetched.items && fetched.items[0]) {
                            const fMsg = fetched.items[0];
                            msg.restore(fMsg.text, fMsg.attachments);
                        } else {
                            msg.restore();
                        }
                    } catch (e2) {
                        msg.restore();
                    }
                }

                const curChat = window.im?.messenger?.getCurrentChat ? window.im.messenger.getCurrentChat() : null;
                if (curChat?.peer?._chunks) {
                    curChat.peer._chunks._invalidateCache();
                }
                this.update();
            }
        } catch (err) {
            console.error("Failed to restore message:", err);
        }
    }

    onPinButtonClick(e, msg) {
        if (window.im.messenger.isForwarded()) { return; }
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
            }, () => { }]
        })
    }

    onTimeClick(event, msg) {
        if (window.im.messenger.isForwarded()) { return; }
        if (window.im.state.is_debug) {
            const cmsg = new CMessageBox({
                title: "...",
                body: `<textarea></textarea>`,
                buttons: [tr("close")],
                callbacks: [() => { }],
            });
            const p = Object.assign({}, msg.data);
            p.sender = null;
            cmsg.getNode().find("textarea").last().value = JSON.stringify(p, "", 4);
        }
    }

    onReportButtonClick(e, msg) {
        if (window.im.messenger.isForwarded()) { return; }
        let is_sending = false;
        const cmsg = new CMessageBox({
            title: tr("report_question"),
            close_on_buttons: false,
            body: `
            <p>${tr("going_to_report_message")}</p>
            <textarea id='uReportMsgInput' placeholder='${tr("reason")}'></textarea>
            `,
            buttons: [tr("confirm_m"), tr("close")],
            callbacks: [async () => {
                if (is_sending) {
                    return;
                }

                is_sending = true;
                const text = cmsg.getNode().find("#uReportMsgInput").last().value;

                const res = await window.OVKAPI.call("messages.report", {
                    "comment": text,
                    "peer_id": msg.data.peer_id,
                    "message_id": msg.data.id,
                    "group_id": window.im.state.getId() > 0 ? null : Math.abs(window.im.state.getId()),
                }, true);

                if (!res.error_msg) {
                    MessageBox(tr("action_successfully"), tr("will_be_watched"), ["OK"], [Function.noop]);

                    cmsg.close();
                } else {
                    fastError(res.error_msg);
                }
            }, () => {
                cmsg.close();
            }],
        });
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
            }, () => { }]
        })
    }

    toggleMessageSelection(msg, e) {
        if (msg.isDeleted()) { return; }
        if (window.im.messenger.isForwarded()) { return; }
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
            if (window.im.getSelectedTabId() == "contact") {
                window.im.openTabByName('messenger');
            } else {
                const _c = window.im.state.getCurrentConvo();
                await _c.peer.checkMembers();

                if (typeof window.im !== 'undefined' && window.im.selectTab) {
                    window.im.openTabByName('contact', false, {
                        peer: {
                            "peer": sender
                        }
                    });
                }
            }
        }

        window.im.messenger.is_switching = false;
    }

    onAuthorNameClick(msg, e) {
        e.preventDefault();
        e.stopPropagation();

        this.togglePeerInfo(msg.sender);
    }

    getMessagesContainer() {
        if (this._messagesContainer && document.body.contains(this._messagesContainer)) {
            return this._messagesContainer;
        }
        this._messagesContainer = this.container ? this.container.querySelector(".messenger-app--messages") : document.querySelector(".messenger-app--messages");
        return this._messagesContainer;
    }

    getScrollTop() {
        const el = this.getMessagesContainer();
        return el ? el.scrollTop : 0;
    }

    getScrollHeight() {
        const el = this.getMessagesContainer();
        return el ? el.scrollHeight : 0;
    }

    getFirstVisibleMessageElement() {
        const container = this.getMessagesContainer();
        if (!container) return null;
        const msgs = container.querySelectorAll('.messenger-app--messages---message[data-msg-id]');
        const len = msgs.length;
        if (len === 0) return null;

        const containerRect = container.getBoundingClientRect();
        const targetTop = containerRect.top + 20;

        let low = 0;
        let high = len - 1;
        let best = null;

        while (low <= high) {
            const mid = (low + high) >> 1;
            const rect = msgs[mid].getBoundingClientRect();
            if (rect.bottom > targetTop) {
                best = msgs[mid];
                high = mid - 1;
            } else {
                low = mid + 1;
            }
        }

        return best || msgs[len - 1];
    }

    async _performScrollPreservingUpdate(actionFn) {
        const container = this.getMessagesContainer();
        if (!container) {
            if (typeof actionFn === "function") await actionFn();
            return;
        }

        const anchorMsg = this.getFirstVisibleMessageElement();
        const anchorId = anchorMsg ? (anchorMsg.getAttribute('data-msg-id') || anchorMsg.dataset?.msgId) : null;
        const containerRectBefore = container.getBoundingClientRect();
        const anchorRectBefore = anchorMsg ? anchorMsg.getBoundingClientRect() : null;
        const anchorRelTopBefore = (anchorRectBefore && containerRectBefore)
            ? (anchorRectBefore.top - containerRectBefore.top)
            : null;
        const oldScrollTop = container.scrollTop;
        const oldScrollHeight = container.scrollHeight;

        if (typeof actionFn === "function") {
            await actionFn();
        }

        let currentAnchor = (anchorMsg && container.contains(anchorMsg)) ? anchorMsg : null;
        if (!currentAnchor && anchorId) {
            currentAnchor = container.querySelector(`.messenger-app--messages---message[data-msg-id="${anchorId}"]`);
        }

        if (currentAnchor && anchorRelTopBefore !== null) {
            const containerRectAfter = container.getBoundingClientRect();
            const anchorRectAfter = currentAnchor.getBoundingClientRect();
            const anchorRelTopAfter = anchorRectAfter.top - containerRectAfter.top;
            const diff = anchorRelTopAfter - anchorRelTopBefore;
            if (Math.abs(diff) > 0.5) {
                container.scrollTop = oldScrollTop + diff;
            }
        } else {
            const diff = container.scrollHeight - oldScrollHeight;
            if (Math.abs(diff) > 0.5) {
                container.scrollTop = oldScrollTop + diff;
            }
        }
    }

    onMessagesScroll(e = null) {
        if (this._scrollTicking) return;
        this._scrollTicking = true;
        window.requestAnimationFrame(() => {
            this._scrollTicking = false;
            this._handleMessagesScroll(e);
        });
    }

    onMessagesWheel(e) {
        if (!e || e.ctrlKey || e.metaKey || e.altKey) return;
        if (e.deltaY < 0) {
            const container = this.getMessagesContainer();
            if (!container || this.is_loading) return;

            const currentConvo = this.getCurrentChat();
            const scrollPos = currentConvo?.getScrollPosition();
            if (!scrollPos || scrollPos.reachedOldestPosition) return;

            const arrayEl = container.querySelector(".messenger-app--messages-array");
            const isUnderflowing = arrayEl && (arrayEl.offsetHeight < container.clientHeight);

            if (container.scrollTop <= 100 || isUnderflowing) {
                if (isUnderflowing) {
                    this._checkAndFillUnderflow();
                } else {
                    this.onMessagesScroll();
                }
            }
        }
    }

    onMessagesTouchStart(e) {
        if (e && e.touches && e.touches[0]) {
            this._touchStartY = e.touches[0].clientY;
        }
    }

    onMessagesTouchMove(e) {
        if (!e || !e.touches || !e.touches[0] || this._touchStartY == null) return;
        const currentY = e.touches[0].clientY;
        const deltaY = currentY - this._touchStartY;
        if (deltaY > 30) {
            const container = this.getMessagesContainer();
            if (!container || this.is_loading) return;

            const currentConvo = this.getCurrentChat();
            const scrollPos = currentConvo?.getScrollPosition();
            if (!scrollPos || scrollPos.reachedOldestPosition) return;

            const arrayEl = container.querySelector(".messenger-app--messages-array");
            const isUnderflowing = arrayEl && (arrayEl.offsetHeight < container.clientHeight);

            if (container.scrollTop <= 10 || isUnderflowing) {
                if (isUnderflowing) {
                    this._checkAndFillUnderflow();
                } else {
                    this.onMessagesScroll();
                }
            }
        }
    }

    _setupResizeListener() {
        if (typeof window !== "undefined" && !this._hasBoundResize) {
            this._hasBoundResize = true;
            window.addEventListener("resize", () => {
                if (this._resizeTimer) clearTimeout(this._resizeTimer);
                this._resizeTimer = setTimeout(() => {
                    this._checkAndFillUnderflow();
                }, 100);
            });
        }
    }

    async _checkAndFillUnderflow() {
        const container = this.getMessagesContainer();
        if (!container || this.is_loading) return;

        const currentConvo = this.getCurrentChat();
        const scrollPos = currentConvo?.getScrollPosition();
        if (!scrollPos || scrollPos.reachedOldestPosition) return;

        const currentPeerId = currentConvo?.peer?.id;
        const arrayEl = container.querySelector(".messenger-app--messages-array");
        if (!arrayEl) return;

        const arrayHeight = arrayEl.offsetHeight;
        const viewportH = container.clientHeight;

        if (viewportH > 0 && arrayHeight > 0 && arrayHeight < viewportH && !scrollPos.reachedOldestPosition) {
            this.is_loading = true;
            const topLoader = container.querySelector(".im_top_loader") || document.querySelector(".im_top_loader");
            if (topLoader) topLoader.style.display = "block";
            let hasError = false;
            let loadedAny = false;
            const prevMsgCount = container.querySelectorAll(".messenger-app--messages---message[data-msg-id]").length;

            try {
                await scrollPos.loadOlder();
                if (this.getCurrentChat()?.peer?.id !== currentPeerId) {
                    return;
                }
                if (topLoader) topLoader.style.display = "none";
                await this._performScrollPreservingUpdate(async () => {
                    await scrollPos.result();
                });

                if (this.getCurrentChat()?.peer?.id !== currentPeerId) {
                    return;
                }

                const newMsgCount = container.querySelectorAll(".messenger-app--messages---message[data-msg-id]").length;
                if (newMsgCount > prevMsgCount) {
                    loadedAny = true;
                }
            } catch (err) {
                hasError = true;
                console.error("IM | _checkAndFillUnderflow error:", err);
            } finally {
                this.is_loading = false;
                if (topLoader) topLoader.style.display = "none";
                this.updateMountainButton();

                if (!hasError && loadedAny && !scrollPos.reachedOldestPosition && this.getCurrentChat()?.peer?.id === currentPeerId) {
                    setTimeout(() => this._checkAndFillUnderflow(), 50);
                }
            }
        }
    }

    async _handleMessagesScroll(e = null) {
        if (this.is_loading) return;
        const currentConvo = this.getCurrentChat();
        if (!currentConvo) return;
        const scrollPos = currentConvo.getScrollPosition();
        if (!scrollPos) return;

        const container = this.getMessagesContainer();
        if (!container) return;

        const _scroll = container.scrollTop;
        const scrollHeight = container.scrollHeight;
        const viewportH = container.clientHeight;
        const scrollBottom = Math.max(0, scrollHeight - _scroll - viewportH);

        const visibleAnchor = this.getFirstVisibleMessageElement();
        if (visibleAnchor) {
            const dayContainer = visibleAnchor.closest('.messenger-app--messages-day');
            const dayDivider = dayContainer ? dayContainer.querySelector('.messenger-app--messages-day-time b') : null;
            const dayText = dayDivider ? dayDivider.textContent.trim() : '';
            if (dayText) {
                this.currentVisibleDate = dayText;
                const floatDateWrap = this.container?.querySelector('.im_floating_date_wrap') || document.querySelector('.im_floating_date_wrap');
                const floatDateEl = this.container?.querySelector('#im_floating_date_text') || document.querySelector('#im_floating_date_text');
                if (floatDateEl && floatDateEl.textContent !== dayText) {
                    floatDateEl.textContent = dayText;
                }

                if (floatDateWrap && dayDivider) {
                    const containerRect = container.getBoundingClientRect();
                    const dividerRect = dayDivider.getBoundingClientRect();
                    const isDividerAtTop = (dividerRect.top >= containerRect.top - 12 && dividerRect.bottom <= containerRect.top + 50);
                    if (isDividerAtTop) {
                        floatDateWrap.style.opacity = '0';
                        floatDateWrap.style.pointerEvents = 'none';
                    } else {
                        floatDateWrap.style.opacity = '1';
                        floatDateWrap.style.pointerEvents = 'auto';
                    }
                }
            }
        }

        const topThreshold = 200;
        const bottomThreshold = 200;

        if (_scroll < topThreshold && !scrollPos.reachedOldestPosition) {
            this.is_loading = true;
            const topLoader = container.querySelector(".im_top_loader") || document.querySelector(".im_top_loader");
            if (topLoader) topLoader.style.display = "block";
            const currentPeerId = currentConvo?.peer?.id;
            try {
                await scrollPos.loadOlder();
                if (this.getCurrentChat()?.peer?.id !== currentPeerId) return;
                if (topLoader) topLoader.style.display = "none";
                await this._performScrollPreservingUpdate(async () => {
                    await scrollPos.result();
                });
                if (this.getCurrentChat()?.peer?.id !== currentPeerId) return;
            } catch (err) {
                console.error("IM | loadOlder error:", err);
            } finally {
                this.is_loading = false;
                if (topLoader) topLoader.style.display = "none";
                this.onMessagesScroll();
            }
        } else if (scrollBottom < bottomThreshold && !scrollPos.reachedNewestPosition) {
            this.is_loading = true;
            const currentPeerId = currentConvo?.peer?.id;
            try {
                await scrollPos.loadNewer();
                if (this.getCurrentChat()?.peer?.id !== currentPeerId) return;
                await this._performScrollPreservingUpdate(async () => {
                    await scrollPos.result();
                });
                if (this.getCurrentChat()?.peer?.id !== currentPeerId) return;
                if (scrollPos.reachedNewestPosition) {
                    const currentChat = this.getCurrentChat();
                    if (currentChat) {
                        currentChat._scroll = currentChat.getEndScrollPosition();
                        if (currentChat.peer) {
                            currentChat.peer.read();
                        }
                    }
                }
            } catch (err) {
                console.error("IM | loadNewer error:", err);
            } finally {
                this.is_loading = false;
                this.onMessagesScroll();
            }
        }

        if (this.isAtEnd(60)) {
            const currentChat = this.getCurrentChat();
            if (currentChat && (currentChat.unread_count === 0 || currentChat.isRead())) {
                this._clearUnreadSpacing();
            }
        }

        this.updateMountainButton();
    }

    callDeletion() {
        const ids = window.im.messenger.selected_messages;
        if (!ids || ids.length === 0) return;

        const gid = window.im.state.getId();
        const current_chat = window.im.messenger.getCurrentChat();
        if (!current_chat) return;

        const currentUserId = window.openvk ? window.openvk.current_id : window.im.state.getId();
        const isChatAdmin = Boolean(
            (current_chat.peer && typeof current_chat.peer.isAdmin === 'function' && current_chat.peer.isAdmin()) ||
            (current_chat._conversation && current_chat._conversation.chat_settings && current_chat._conversation.chat_settings.admin_id === currentUserId) ||
            (current_chat._conversation && current_chat._conversation.chat_settings && current_chat._conversation.chat_settings.is_admin) ||
            (current_chat._conversation && current_chat._conversation.admin_id === currentUserId) ||
            (current_chat.peer && current_chat.peer.data && current_chat.peer.data.admin_id === currentUserId)
        );

        let canDeleteForAll = ids.length > 0;
        if (!isChatAdmin) {
            ids.forEach((item) => {
                const m = current_chat.peer._chunks._findMessageById(item);
                if (m) {
                    const fromId = m.data ? m.data.from_id : (m.from_id || 0);
                    if (fromId != currentUserId) {
                        canDeleteForAll = false;
                    }
                } else {
                    canDeleteForAll = false;
                }
            });
        }

        const performDelete = async (deleteForAll = false) => {
            let ids2 = [];
            ids.forEach((item) => {
                let m = current_chat.peer._chunks._findMessageById(item);
                ids2.push(item);
                if (m) {
                    m.setDeleted(true);
                }
            });

            const params = {
                "message_ids": ids2.join(","),
                "peer_id": current_chat.peer.id,
                "delete_for_all": deleteForAll ? 1 : 0,
            };
            if (gid < 0) {
                params["group_id"] = Math.abs(gid);
            }
            try {
                await window.OVKAPI.call("messages.delete", params);
            } catch (e) {
                console.error(e);
            }
            if (current_chat.peer && current_chat.peer._chunks) {
                current_chat.peer._chunks._invalidateCache();
            }
            if (typeof current_chat.getScrollPosition === 'function' && current_chat.getScrollPosition()) {
                current_chat.getScrollPosition()._invalidateCache();
            }
            this._triggerUpdate();
            window.im.messenger.unselectAll();
        };

        const buttons = [tr("delete_for_me")];
        const callbacks = [() => performDelete(false)];

        if (canDeleteForAll) {
            buttons.push(tr("delete_for_all"));
            callbacks.push(() => performDelete(true));
        }

        buttons.push(tr("cancel"));
        callbacks.push(() => { });

        new CMessageBox({
            title: tr("message_deletion", ids.length),
            body: tr("message_deletion_confirm"),
            buttons: buttons,
            callbacks: callbacks,
        });
    }

    isAtEnd(threshold = 120) {
        const el = this.getMessagesContainer();
        if (!el) return true;
        const scrollBottom = Math.max(0, el.scrollHeight - el.scrollTop - el.clientHeight);
        return scrollBottom <= threshold;
    }

    getScroll() { return this.getScrollTop(); }

    _scrollTo(scroll_progress) {
        const el = this.getMessagesContainer();
        if (!el) return;
        if (scroll_progress === "end") {
            scroll_progress = el.scrollHeight;
        }

        imLog("scrolling messages container to: ", scroll_progress);
        el.scrollTop = scroll_progress;
    }

    onFloatingDateClick(e) {
        if (e) e.stopPropagation();
        if (window.im?.messenger?.showDaySwitcher) {
            window.im.messenger.showDaySwitcher(this.currentVisibleDate || null);
        } else if (typeof window.DaySwitcher !== "undefined") {
            new window.DaySwitcher(this.currentVisibleDate || null);
        }
    }

    _clearUnreadSpacing() {
        const el = this.getMessagesContainer();
        if (!el) return;
        el.classList.remove("has-unread-divider");
        const arrayEl = el.querySelector(".messenger-app--messages-array");
        if (arrayEl) {
            if (arrayEl.style.marginTop) arrayEl.style.marginTop = "";
            if (arrayEl.style.paddingBottom) arrayEl.style.paddingBottom = "";
        }
    }

    _scrollToEnd(smooth = false) {
        imLog("IM | scrolling messages to the end");
        this._clearUnreadSpacing();
        const el = this.getMessagesContainer();
        if (el) {
            if (smooth && typeof el.scrollTo === 'function') {
                el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
            } else {
                el.scrollTop = el.scrollHeight;
            }
            requestAnimationFrame(() => {
                if (el) el.scrollTop = el.scrollHeight;
            });
        }
    }

    updateMountainButton() {
        const container = this.getMessagesContainer();
        if (!container) return;

        const currentConv = this.getCurrentChat();
        const scrollPos = currentConv?.getScrollPosition();
        const chunks = currentConv?.peer?._chunks;

        let isViewingHistory = false;

        if (currentConv) {
            if (currentConv._scroll != null && currentConv._scroll !== currentConv.getEndScrollPosition()) {
                isViewingHistory = true;
            }
            if (scrollPos && scrollPos.reachedNewestPosition === false) {
                isViewingHistory = true;
            }

            const latestChunkMsg = chunks?.getLatestMessage ? chunks.getLatestMessage() : null;
            const convLastMsg = currentConv.last_message || currentConv._last_message || currentConv._conversation?.last_message;

            const latestId = Number(latestChunkMsg?.id || latestChunkMsg?.data?.conversation_message_id || 0);
            const convLastId = Number(convLastMsg?.id || convLastMsg?.data?.conversation_message_id || 0);

            if (latestId > 0 && convLastId > 0 && latestId < convLastId) {
                isViewingHistory = true;
            }
        }

        const scrollBottom = Math.max(0, container.scrollHeight - container.scrollTop - container.clientHeight);

        const shouldShow = isViewingHistory || (scrollBottom > 2000);

        const endNode = this.getNode().find(".messenger-app-end");
        if (shouldShow) {
            endNode.addClass("m-mountain");
        } else {
            endNode.removeClass("m-mountain");
        }
    }

    scrollToUnread() {
        let attempts = 0;
        const maxAttempts = 35;

        const tryScroll = () => {
            attempts++;
            const el = document.getElementById("im_unread_divider");
            const container = this.getMessagesContainer();
            const arrayEl = container ? container.querySelector(".messenger-app--messages-array") : null;

            if (el && container) {
                container.classList.add("has-unread-divider");

                const topTargetMargin = 12;
                const currentRelTop = el.getBoundingClientRect().top - container.getBoundingClientRect().top;
                const scrollDelta = currentRelTop - topTargetMargin;
                container.scrollTop += scrollDelta;

                this.updateMountainButton();

                if (attempts < 5) {
                    requestAnimationFrame(() => {
                        const newRelTop = el.getBoundingClientRect().top - container.getBoundingClientRect().top;
                        const maxScroll = container.scrollHeight - container.clientHeight;
                        if (Math.abs(newRelTop - topTargetMargin) > 2 && container.scrollTop < maxScroll) {
                            tryScroll();
                        } else {
                            this._setupReadObserver();
                        }
                    });
                } else {
                    this._setupReadObserver();
                }
                return true;
            }

            if (attempts < maxAttempts) {
                setTimeout(tryScroll, 35);
            } else {
                this._scrollToEnd();
                this._setupReadObserver();
            }
            return false;
        };

        requestAnimationFrame(() => {
            tryScroll();
        });
    }

    _setupReadObserver() {
        if (this._readObserver) {
            this._readObserver.disconnect();
            this._readObserver = null;
        }

        const container = this.getMessagesContainer();
        if (!container || typeof IntersectionObserver === "undefined") return;

        const unreadElements = container.querySelectorAll(".messenger-app--messages---message.unread[data-msg-id]");
        if (!unreadElements || unreadElements.length === 0) return;

        this._readObserver = new IntersectionObserver((entries) => {
            if (typeof document !== "undefined" && (document.hidden || (typeof document.hasFocus === 'function' && !document.hasFocus()))) {
                return;
            }

            let maxVisibleUnreadId = 0;
            entries.forEach((entry) => {
                if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {
                    const id = Number(entry.target.getAttribute("data-msg-id"));
                    if (id && id > maxVisibleUnreadId) {
                        maxVisibleUnreadId = id;
                    }
                }
            });

            if (maxVisibleUnreadId > 0) {
                this._scheduleMarkAsRead(maxVisibleUnreadId);
            }
        }, {
            root: container,
            threshold: [0.5]
        });

        unreadElements.forEach(el => this._readObserver.observe(el));

        if (typeof window !== "undefined" && !this._hasBoundReadVisibility) {
            this._hasBoundReadVisibility = true;
            const onVisible = () => {
                if (!document.hidden) {
                    this._setupReadObserver();
                }
            };
            document.addEventListener("visibilitychange", onVisible);
            window.addEventListener("focus", onVisible);
        }
    }

    _scheduleMarkAsRead(maxId) {
        this._pendingReadId = Math.max(this._pendingReadId || 0, maxId);
        if (this._readTimer) {
            clearTimeout(this._readTimer);
        }
        this._readTimer = setTimeout(async () => {
            this._readTimer = null;
            const idToRead = this._pendingReadId;
            this._pendingReadId = 0;
            if (!idToRead) return;
            if (typeof document !== "undefined" && (document.hidden || (typeof document.hasFocus === 'function' && !document.hasFocus()))) {
                return;
            }

            const currentChat = this.getCurrentChat();
            if (currentChat && currentChat.peer && typeof currentChat.peer.read === "function") {
                await currentChat.peer.read(idToRead);
            }
        }, 500);
    }

    async scrollToEndOfChat(event, convo = null) {
        if (event && typeof event.preventDefault === "function") {
            event.preventDefault();
            event.stopPropagation();
        }
        const currentConv = convo || this.getCurrentChat();
        if (!currentConv) return;

        // По требованию: ВСЕГДА делаем запрос к серверу и загружаем самые последние сообщения
        if (currentConv.peer?._chunks) {
            currentConv.peer._chunks.chunks = [];
            currentConv.peer._chunks._map = new Map();
            currentConv.peer._chunks._messagesInited = false;
            currentConv.peer._chunks._cachedMessages = undefined;
            currentConv.peer._chunks.invalidateCache = true;
        }

        const endSp = currentConv.getEndScrollPosition();
        if (endSp) {
            endSp.recenter(null);
            await endSp.loadOlder();
        }
        currentConv._scroll = endSp;
        currentConv._scroll?._invalidateCache?.();

        await this.update();
        this._scrollToEnd(false);
        this._clearUnreadSpacing();

        this.updateMountainButton();

        if (currentConv.peer && typeof currentConv.peer.read === "function") {
            currentConv.peer.read();
        }

        setTimeout(() => this._checkAndFillUnderflow(), 100);
    }

    scrollToMessage(msgId, peerId = null, conv = null) {
        const targetId = Number(typeof msgId === 'object' ? (msgId.id || msgId.data?.id) : msgId);
        const pid = Number(
            peerId ||
            (typeof msgId === 'object' ? (msgId.peer_id || msgId.data?.peer_id) : null) ||
            window.im.messenger.currentChatId
        );

        let attempts = 0;
        const maxAttempts = 25;

        const tryScroll = () => {
            attempts++;
            let el = document.querySelector(`#msg${pid}-${targetId}`) || document.querySelector(`[data-msg-id="${targetId}"]`);

            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.classList.remove("animated");
                void el.offsetWidth;
                el.classList.add("animated");
                setTimeout(() => {
                    if (el && document.body.contains(el)) {
                        el.classList.remove("animated");
                    }
                }, 5000);
                this.updateMountainButton();
                imLog('IM | Scrolled to message anchor msg' + pid + '-' + targetId);
                return true;
            }

            if (attempts < maxAttempts) {
                setTimeout(tryScroll, 50);
            } else if (conv && typeof conv.getScrollPosition === 'function') {
                const visibleMsgs = (conv.getScrollPosition() ? conv.getScrollPosition().getMessages() : []) || [];
                if (visibleMsgs.length > 0) {
                    let closest = visibleMsgs[0];
                    let minDiff = Math.abs(Number(closest.id) - targetId);
                    for (const m of visibleMsgs) {
                        if (m && m.id) {
                            const diff = Math.abs(Number(m.id) - targetId);
                            if (diff < minDiff) {
                                minDiff = diff;
                                closest = m;
                            }
                        }
                    }
                    if (closest && closest.id) {
                        const fallbackEl = document.querySelector(`#msg${pid}-${closest.id}`) || document.querySelector(`[data-msg-id="${closest.id}"]`);
                        if (fallbackEl) {
                            fallbackEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            fallbackEl.classList.remove("animated");
                            void fallbackEl.offsetWidth;
                            fallbackEl.classList.add("animated");
                            setTimeout(() => {
                                if (fallbackEl && document.body.contains(fallbackEl)) {
                                    fallbackEl.classList.remove("animated");
                                }
                            }, 5000);
                        }
                    }
                }
            }
            return false;
        };

        requestAnimationFrame(() => {
            tryScroll();
        });
    }

    getCurrentText() {
        if (!this.container) return "";
        const el = this.container.querySelector(".messenger-app--input---messagebox .content-editable, .messenger-app--input---messagebox textarea");
        if (!el) return "";
        if (el.tagName && el.tagName.toLowerCase() === "textarea") {
            return el.value || "";
        }
        if (el._contentEditable && typeof el._contentEditable.getText === "function") {
            return el._contentEditable.getText();
        }
        if (typeof el.getText === "function") {
            return el.getText();
        }
        return el.innerText || el.textContent || "";
    }
    setCurrentText(text) {
        if (!this.container) return;
        const el = this.container.querySelector(".messenger-app--input---messagebox .content-editable, .messenger-app--input---messagebox textarea, #write .content-editable, #write textarea");
        if (!el) return;
        if (el.tagName && el.tagName.toLowerCase() === "textarea") {
            el.value = text;
        } else if (el._contentEditable && typeof el._contentEditable.setText === "function") {
            if (!text && typeof el._contentEditable.clear === "function") {
                el._contentEditable.clear();
            } else {
                el._contentEditable.setText(text);
            }
        } else if (typeof el.setText === "function") {
            el.setText(text);
        } else {
            el.innerText = text;
        }

        const writeEl = this.container.querySelector("#write");
        if (writeEl) {
            const ta = writeEl.querySelector("textarea");
            if (ta && ta !== el) ta.value = text;
        }

        if (!text) {
            this.currentDraft = "";
            if (window.im?.messenger) {
                window.im.messenger.currentDraft = "";
            }
            const curChat = this.getCurrentChat();
            if (curChat && typeof curChat.clearDraft === "function") {
                curChat.clearDraft();
            }
        }
    }
    isInputEmpty(el) {
        if (!el) el = this.container?.querySelector(".messenger-app--input---messagebox .content-editable, .messenger-app--input---messagebox textarea, #write .small-textarea");
        if (!el) return true;
        let text = "";
        if (el.tagName && el.tagName.toLowerCase() === "textarea") {
            text = el.value || "";
        } else if (el._contentEditable && typeof el._contentEditable.getText === "function") {
            text = el._contentEditable.getText();
        } else if (typeof el.getText === "function") {
            text = el.getText();
        } else if (el.value !== undefined) {
            text = el.value || "";
        } else {
            text = el.innerText || el.textContent || "";
        }
        return !text || text.trim().length === 0;
    }
    setInputSelectionToEnd(el) {
        if (!el) {
            el = this.container?.querySelector(".messenger-app--input---messagebox .content-editable, .messenger-app--input---messagebox textarea, #write .small-textarea");
        }
        if (!el) return;
        el.focus();
        if (el.tagName && el.tagName.toLowerCase() === "textarea") {
            const len = el.value ? el.value.length : 0;
            if (typeof el.setSelectionRange === "function") {
                el.setSelectionRange(len, len);
            }
        } else if (typeof el.setSelectionRange === "function") {
            el.setSelectionRange();
        } else if (window.getSelection && document.createRange) {
            try {
                const range = document.createRange();
                range.selectNodeContents(el);
                range.collapse(false);
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            } catch (err) { }
        }
    }
    getCurrentAttachments() { return [this.container.querySelector(".post-horizontal").innerHTML, this.container.querySelector(".post-vertical").innerHTML]; }
}

export class ContactPage extends IMPage {
    shouldCloseOnExit() { return true; }
    static getPageId() { return "contact"; }

    async render(container) {
        this.getNode().addClass("page-other");

        const currentCorresponder = window.im.state.getCurrentConvo();
        await currentCorresponder.peer.checkMembers();
        let peer = null;
        if (this.options.peer == null || this.options.peer.peer == null) {
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
