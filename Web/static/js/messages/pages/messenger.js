import { ChatMessage, ChatGeneralForm, Draft } from '../components/messages.js';
import { Conversation } from './conversations.js';
import { MessageListView } from "../components/message.js"
import { ErrorConversation, WriteBar, ActionsBar, PeerWindow, InputArea, PeerTabsView } from "../components/common.js"
import { IMTab, IMPage } from './page.js';
import { html, render } from '../components/render.js';

export class Messenger {
    static MAX_SELECTED_MESSAGES = 100;
    static MESSAGE_CHUNK_LENGTH = 1000;
    static MESSAGE_SEND_INTERVAL = 5000;

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

    async selectConversation(convo, scroll_down = false) {
        const oldId = Number(this.currentChatId);
        if (convo == null) {
            throw new Error();
        }

        this.setChat(convo);
        const tab = await window.im.openTabByName("messenger");

        await tab.render();

        if (!convo.peer._isMessagesInited()) {
            const c = await convo.peer.getMessages();
            convo.peer._chunks._appendChunk(c);

            if (scroll_down == true) {
                this.getWindow()._scrollToEnd();
            }
        }

        await tab.render();
        const newId = Number(this.currentChatId);

        if (oldId != newId) {
            try {
                this._clearAttachments();
                this.removeReply();
                this.cancelEdit();

                let draft = this.getCurrentChat().draft;
                if (!draft) {
                    draft = new Draft();
                }

                draft.loadToPage(this.getWindow());
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

        if (pushstate) {
            const url = new URL(location.href);
            url.searchParams.set("sel", convo.peer.id);
            window.im.state._pushState(url.toString());
        }
    }

    addChat(conv) {
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

    async sendToCurrentCorresponder() {
        const view = this.view;
        const text = view.currentDraft;
        const reply_to = view.replyTo;
        let reply_param = null;
        let attachments_list = null;
        const corresponder = window.im.state.getCurrentConvo();

        const attachments = collect_attachments(u('.messenger-app--input---messagebox'));
        if (attachments.length > 0) {
            attachments_list = attachments;
        }

        if (reply_to) {
            reply_param = reply_to;
        }

        if (text.length <= Messenger.MESSAGE_CHUNK_LENGTH + 20) {
            const msg = new ChatMessage({
                'from_id': window.im.current.id,
                'peer_id': corresponder.id,
                'date': Math.round((new Date()).getTime() / 1000),
            });
            if (attachments_list) msg.has_not_loaded_attachments = true;
            msg._guessSender();
            msg.setText(text);
            return await corresponder.sendMessage(msg, reply_param, attachments_list);
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
                'from_id': window.im.current.id,
                'peer_id': corresponder.id,
                'date': Math.round((new Date()).getTime() / 1000),
            });
            if (isLast && attachments_list) msg.has_not_loaded_attachments = true;
            msg._guessSender();
            msg.setText(chunks[i]);

            corresponder.sendMessage(msg, isLast ? reply_param : null, isLast ? attachments_list : null, isLast ? null : Messenger.MESSAGE_SEND_INTERVAL);
        }
    }

    /* Selectness */

    selectMessage(msg) {
        this.selected_messages.push(msg.id);
        //this._render();
    }

    unselectAll() {
        this.selected_messages = [];
        //this._render();
    }

    unselectMessage(msg) {
        const idx = this.selected_messages.indexOf(msg.id);
        if (idx !== -1) this.selected_messages.splice(idx, 1);
        //this._render();
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

        console.log(ids, msg, attachment);

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
                viewer.afterOpen(idForItem(first));

                break;
            case "audio":
                AudioViewer.openById(e, null, attachment.audio);
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

    cancelEdit(render = true) {
        this.editMsg = null;
        this._clearAttachments();

        if (this.prevDraft != null) {
            this.currentDraft = String(this.prevDraft);
            this.prevDraft = null;
        }

        if (render == true) {
            this.getWindow().update();
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

        this.currentDraft = '';
        this.prevDraft = null;
        this.prevAtts_1 = null;
        this.prevAtts_2 = null;
        this.drafts = {};
        this.scrolls = {};

        this.opened_tabs = [];
        this.current_chat = null;
        this.selected_messages = [];

        this.toggled_peer_obj = null;

        this.replyTo = null;
        this.editMsg = null;
    }
    isDisablesScroll() { return window.im.state.is_compact_mode_enabled == false; }
    _triggerUpdate() {
        window.im.conversations.update();
        this.update();
    }

	//async render(container, special_mode = null, messages = null) {
	async render(container, options = {}) {
        const orig_messenger = window.im.messenger;

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
        console.log(special_mode, is_rendering_contact_window)
        render(html`
        <div id="chat-page">
            <div class="chat-window">
            <${PeerTabsView} hadTab=${true} tabs=${orig_messenger.opened_tabs} currentChat=${orig_messenger.currentChatId} page=${this} />
            <${ActionsBar}
                selectedMessages=${this.selected_messages_objs}
                count=${this.selected_messages.length}
                onDelete=${() => this.callDeletion()}
                onUnselect=${() => this.unselect()}
                onReply=${() => this.onReplyButtonClick()}
            />
            <div class="messenger-app">
                <${MessageListView}
                convo=${currentConv}
                messages=${messages} 
                page=${this} />
                <${InputArea}
                editMsg=${this.editMsg}
                replyTo=${this.replyTo}
                onRemoveReply=${() => this.removeReply()}
                onSend=${() => this.onSendMessage()}
                onKeyPress=${(e) => this.onTextareaKeyPress(e)}
                currentDraft=${this.currentDraft}
                onInput=${(e) => { this.currentDraft = e.target.value; }}
                togglePeerInfo=${(e) => { this.togglePeerInfo() }}
                clickOnReply=${(msg, e) => { this.clickOnReply(msg, e) }}
                />
            </div>
            </div>
        </div>
        `, root);
	}

    async _renderSpecialMode(container, special_mode) {
        console.log("IM | Rendering special mode " + special_mode);

        let messages = [];
        const currentConv = this.getCurrentChat();
        const peer = currentConv ? currentConv.peer : null;
        const display_peer = this.toggled_peer_obj ? this.toggled_peer_obj : peer;

        switch (special_mode) {
            default:
                break;
            case "pinned":
                messages = display_peer ? display_peer.divided_messages : [];
                break;
            case "photos":
                break;
        }
        console.log(display_peer, special_mode, messages)

        this._render(container, special_mode, messages);
    }

	onTextareaKeyPress(e) {
		const ta = e.target;

        if (e.which !== 13) {
			const now = Date.now();
			if (!this._typingStarted) this._typingStarted = now;
			if (now - this._typingStarted > 6000) { // 2s
				this.setWriting();
			}
		}

		if (e.which === 13) {
			this._typingStarted = 0;
			if (!e.metaKey && !e.shiftKey) {
				e.preventDefault();
				ta.blur();
				this.sendMessage();
				ta.focus();
				return false;
			}
		}
		return true;
	}

    async setWriting() {
        this._typingStarted = 0;

        const group_id = null;

        console.log('IM | setWriting called');

        await window.OVKAPI.call("messages.setActivity", {
            "type": "typing",
            "peer_id": window.im.state.getCurrentConvo().id,
            "group_id": group_id
        });
	}

	onMessageClick(msg, e) {
		if (e.buttons !== 1 && e.type == 'mousemove') return;
		if (this.replyTo != null) return;

		if (this.selected_messages_count == 0 && !e.target.closest(".click-territory")) {
			return;
		}

		const target = e.target;
		if (!target.matches('.text, .time span') || this.selected_messages.length > 0) {
			e.preventDefault();
			this.toggleMessageSelection(msg, e);
		}
	}

	clickOnReply(msg) {
        console.log(msg)
        this.scrollToMessage(msg, true);
	}

	onReplyButtonClick() {
		const ids = this.selected_messages;
		const current_chat = this.getCurrentChat();
		const m = current_chat.peer._findMessageById(ids[0]);
		this.unselect();
		this.replyTo = m;
		this._render();
	}

    onEditButtonClick(e, msg) {
        this.editMsg = msg;
        this.prevDraft = String(this.currentDraft);
        this.prevAtts_1 = this.appEl.querySelector(".post-horizontal").outerHTML;
        this.prevAtts_2 = this.appEl.querySelector(".post-vertical").outerHTML;
        this.currentDraft = "";

        if (msg.text.length > 0) {
            this.currentDraft = msg.text;
        }

        console.log(msg.attachments)
        if (msg.attachments.length > 0) {
            unpack_attachments_into_node(u(this.appEl.querySelector("#write")), msg.attachments);
        }

		this._render();
    }

    onPinButtonClick(e, msg) {
        const isPinned = msg.isPinned();
        const cmsg = new CMessageBox({
            title: "dfdsfdsf",
            body: isPinned == true ? "открепить?" : "закрепить собщение?",
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

    _triggerCancelEditingDialog(callback = null) {
        const cmsg = new CMessageBox({
            title: "",
            body: "вы хотите прервать редактирования все изменения потеряются(",
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
                    title: "удалииии",
                    body: "удалить сообщение которое даже не постаралось отправиться а так подло тебя подвело и ты мискликнул???",
                    buttons: [tr("yes"), tr("no")],
                    callbacks: [() => {
                        msg.setDeleted();
                        this._triggerUpdate();
                    }, () => { }]
                });
            }
        }

        if (this.editMsg != null) {
            this._triggerCancelEditingDialog();
            return;
        }

        if (!this.isMessageSelected(msg)) {
            this.selectMessage(msg);
        } else {
            this.unselectMessage(msg);
        }
    }

    async togglePeerInfo(sender = null) {
        if (this.is_switching == true) {
            return;
        }

        this.is_switching = true;

        console.log('toggle peer info ', window.im.tab)

        if (window.im.tab == 'contact') {
            window.im.selectTab('messenger');
            this.toggled_peer_obj = null;
        } else {
            this.toggled_peer_obj = sender;

            const _c = window.im.state.getCurrentConvo();
            if (_c.supposed_type == "chat" && !_c._hasLoadedMembers()) {
                await _c.m_load(0);
            }

           	if (typeof window.im !== 'undefined' && window.im.selectTab) {
                window.im.selectTab('contact');
            }
        }

        this.is_switching = false;
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
		const ids = this.selected_messages;

		const current_chat = this.getCurrentChat();
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
				await window.OVKAPI.call("messages.delete", {
					"message_ids": ids2.join(","),
					"peer_id": current_chat.peer.id
				})
				this._triggerUpdate();
				this.unselect();
			}, () => { }],
		});
	}

    // onSendMessageButtonClick
	async onSendMessage() {
		const _tmp_atts = collect_attachments(u('.messenger-app--input---messagebox'));

		if (this.currentDraft === '' && _tmp_atts.length == 0) return false;

        if (this.currentDraft.length > 55000) {
            fastError("> 55000")
            return;
        }

        if (this.editMsg != null) {
            this.editMsg.edit(this.currentDraft, _tmp_atts);

            this.cancelEdit();
            return;
        }

		this._scrollToEnd();

		window.im.messenger.sendToCurrentCorresponder().then(() => {
			this._scrollToEnd();
		});

		this._eraseDraftFor({ peer: window.im.current });
		this._eraseCurrentDraft();
		this.removeReply();
	}

    getScroll() { return document.documentElement.scrollTop; }
    _scrollTo(scroll_progress) {
        console.log("scrolling page to: ", scroll_progress);
		document.documentElement.scroll({ top: scroll_progress });
	}

    _scrollToEnd() {
        console.log("scrolled page to the end");

		this._scrollTo(document.documentElement.scrollHeight);
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
    getCurrentAttachments() { return [this.container.querySelector(".post-horizontal").innerHTML, this.container.querySelector(".post-vertical").innerHTML]; }

    _saveDraft(to_chat) {
        if (!to_chat) return;
        //this.drafts[to_chat.peer.id] = this.currentDraft;
        //this.scrolls[to_chat.peer.id] = document.documentElement.scrollTop;
        this._eraseCurrentDraft();

        //console.log(this.scrolls);
        //console.log('Saved draft for ', to_chat, ", scroll: ", this.scrolls[to_chat.peer.id]);
    }

    _eraseDraftFor(chat) {
        //this.drafts[chat.peer.id] = undefined;
        //u('.messenger-app--input---messagebox .post-horizontal').html('');
        //u('.messenger-app--input---messagebox .post-vertical').html('');
    }

    _eraseCurrentDraft() {
        //this.currentDraft = '';
        //u('.messenger-app--input---messagebox textarea').attr("style", "height: 50px;");
    }

    _loadDraft(for_chat) {
        //if (!for_chat) return;
        //const _draft = this.drafts[for_chat.peer.id];
        //if (_draft && _draft !== '') {
        //    this.currentDraft = _draft;
        //}
        //const _scroll = this.scrolls[for_chat.peer.id];
        //if (_scroll) {
        //	this._scrollTo(_scroll);
        //} else {
        //	this._scrollToEnd();
        //}

        //console.log("Loaded draft for ", for_chat, ", scroll: ", _scroll)
    }

    applyDraftFromConv() {
        
    }
}

export class ContactPage extends IMPage {
    shouldCloseOnExit() { return true; }
    static getPageId() { return "contact"; }

	async render(container) {
        const currentCorresponder = window.im.state.getCurrentConvo();
        let peer = null;
        if (this.options.peer == null) {
            peer = currentCorresponder;
        }

        render(html`<${PeerWindow} fromConvo=${currentCorresponder} convo=${peer} />`, container);
	}
}
