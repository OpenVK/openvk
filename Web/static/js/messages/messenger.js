import { ChatMessage, ChatGeneralForm } from './messages.js';
import { Conversation } from './conversations.js';
import { render, html, PeerTabsView, WriteBar, ActionsBar, MessageListView, PeerWindow, InputArea } from './components.js';

const u = window.u;
const collect_attachments = window.collect_attachments;

export class Messenger {
  async init() {
    this.insert_type = 'page';
    this.view = new MessengerViewModel();
  }

    hasAppeared(container) {
        return container.querySelector('.messenger-app') != null;
    }

    appear(container = null) {
        container.classList.remove('hidden');
        if (this.hasAppeared(container)) {
        this.view._render(container);
        this.view._loadDraft(this.view.getCurrentChat());
            return;
        }

        this.view._render(container);
        this.view.messagesListBlock = container.querySelector(".messenger-app--messages");
        this.view.messagesList  = container.querySelector(".messenger-app--messages-array");
        this.view.appEl = container;
    }

  hide(container) {
    container.classList.add('hidden');
  }

  async sendToCurrentCorresponder() {
    const view = this.view;
    const text = view.currentDraft;
    const reply_to = view.replyTo;
    let reply_param = null;
    let attachments_list = null;
    const corresponder = window.im.corresponder;

    const msg = new ChatMessage({
      'from_id': window.im.current.id,
      'peer_id': corresponder.id,
      'date': Math.round((new Date()).getTime() / 1000),

    });

    if (reply_to) {
      reply_param = reply_to;
    }

    const attachments = collect_attachments(u('.messenger-app--input---messagebox'));
    if (attachments.length > 0) {
        attachments_list = attachments;
        msg.has_not_loaded_attachments = true;
    }

    msg._guessSender();
    msg.setText(text);

    return await corresponder.sendMessage(msg, reply_param, attachments_list);
  }
}

export class MessengerViewModel {
	constructor() {
        this.MAX_SELECTED_MESSAGES = 100;

		this.appEl = null;
        this.messagesListBlock = null;

        this.is_showing_profile = false;
        this.is_loading = false;
        this.had_more_one_tab = false;

		this.currentDraft = '';
        this.prevDraft = null;
        this.prevAtts_1 = null;
        this.prevAtts_2 = null; // Между вкладками прикрепления теряются(
		this.drafts = {};
		this.scrolls = {};

		this.opened_tabs = [];
        this.current_chat = null;
		this.selected_messages = [];

		this.messagesTrigger = 0;

        this.toggled_peer_obj = null;

		this.replyTo = null;
        this.editMsg = null;
	}

    _triggerUpdate() {
        window.im.conversations.view._update();
        this._render();
    }

    _triggerUpdateSlightly() {
        this._render();
    }

	_render(container) {
		const root = container || this.appEl;
		if (!root) return;

		const currentConv = this.getCurrentChat();
        const peer = currentConv ? currentConv.peer : null;
        const display_peer = this.toggled_peer_obj ? this.toggled_peer_obj : peer;

		render(html`
      <div id="chat-page" class="${window.im.tab == "contact" ? 'peer-shown' : ''}">
        <div class="chat-window">
          <${PeerTabsView} hadTab=${this.had_more_one_tab} tabs=${this.opened_tabs} currentChat=${this.current_chat} />
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
            messages=${peer ? peer.divided_messages : []} />
            <${InputArea}
              editMsg=${this.editMsg}
              replyTo=${this.replyTo}
              onRemoveReply=${() => this.removeReply()}
              onSend=${() => this.sendMessage()}
              onKeyPress=${(e) => this.onTextareaKeyPress(e)}
              currentDraft=${this.currentDraft}
              onInput=${(e) => { this.currentDraft = e.target.value; }}
              togglePeerInfo=${(e) => { this.togglePeerInfo() }}
              clickOnReply=${(msg, e) => { this.clickOnReply(msg, e) }}
            />
          </div>
        </div>
        ${window.im.tab == "contact" && html`
          <div class="peer-window">
            <${PeerWindow} peer=${display_peer} togglePeerInfo=${this.togglePeerInfo} />
          </div>
        `}
      </div>
    `, root);
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
            "peer_id": window.im.corresponder.id,
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

	onAuthorNameClick(msg, e) {
		e.preventDefault();
		e.stopPropagation();

		window.im.messenger.view.togglePeerInfo(msg.sender);
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

        if (msg.attachments > 0) {
            unpack_attachments_into_node(u(this.appEl.querySelector("#write")), msg.attachments);
        }

		this._render();
    }

    cancelEdit(render = true) {
        this.editMsg = null;
        this._clearAttachments();

        if (this.prevDraft != null) {
            this.currentDraft = String(this.prevDraft);
            this.prevDraft = null;
        }

        if (render == true) {
            this._render();
        }
    }

	removeReply(render = true) {
		this.replyTo = null;

        if (render == true) {
            this._render();
        }
    }

    toggleMessageSelection(msg, e) {
        if (msg.id == null) {
            return;
        }

        if (!this.isMessageSelected(msg)) {
            this.selectMessage(msg);
        } else {
            this.unselectMessage(msg);
        }
    }

    togglePeerInfo(sender = null) {
        console.log('toggle peer info ', window.im.tab)

        if (window.im.tab == 'contact') {
            window.im.selectTab('messenger');
            this.toggled_peer_obj = null;
        } else {
            this.toggled_peer_obj = sender;
           	if (typeof window.im !== 'undefined' && window.im.selectTab) {
            window.im.selectTab('contact');
        }
        }
    }

	onScrollDownButtonClick() {
		this._scrollToEnd();
	}

	async onMessagesScroll(e) {
		if (this.is_loading) return;
		this.is_loading = true;

		const _scroll = document.documentElement.scrollTop;

        if (_scroll < 21) {
            console.log("IM | Loading older chunk from API");
            // ── Scrolled near the top → load older messages (scroll UP) ──
            await window.im.corresponder._messagesLoad_UpFromLastChunk();
        } else {
            const scrollBottom = document.documentElement.scrollHeight - _scroll - document.documentElement.clientHeight;

            if (scrollBottom < 10) {
                // ── Scrolled near the bottom → load newer messages (scroll DOWN) ──
                if (window.im.corresponder._chunks_HasMoreNewerChunkRelativelyToCurrentChat()) {
                // There's already a newer chunk available without fetching
                    console.log('IM | Switching to a newer chunk');
                    await window.im.corresponder._messagesLoad_DownFromCurrentChunk();
                } else {
                    // No newer chunk loaded yet — fetch from API
                    console.log('IM | Loading newer chunk from API');
                    await window.im.corresponder._messagesLoad_DownFromCurrentChunk();
                }
            }

            if (scrollBottom > 600) {
                document.querySelector('.messenger-app--tab-messenger').classList.add('messenger-app--overscrolled');
            } else {
                document.querySelector('.messenger-app--tab-messenger').classList.remove('messenger-app--overscrolled');
            }
        }

        this.is_loading = false;
	}

    selectMessage(msg) {
        this.selected_messages.push(msg.id);
		this._render();
	}

	unselectMessage(msg) {
		const idx = this.selected_messages.indexOf(msg.id);
		if (idx !== -1) this.selected_messages.splice(idx, 1);
		this._render();
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

	unselect() {
		this.selected_messages = [];
		this._render();
	}

    // onSendMessageButtonClick
	async sendMessage() {
		const _tmp_atts = collect_attachments(u('.messenger-app--input---messagebox'));

		if (this.currentDraft === '' && _tmp_atts.length == 0) return false;

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

	hasChat(conversation) {
		return this.opened_tabs.indexOf(conversation) !== -1;
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

    setChat(conv, pushstate = true) {
        const pr = Number(this.current_chat);
        this.current_chat = this.opened_tabs.indexOf(conv);
        this.unselect();

        if (pr != this.current_chat) {
            this._clearAttachments();
            this.removeReply();
            this.cancelEdit();
        }

		if (this.opened_tabs.length > 1) {
			this.had_more_one_tab = true;
		}

		if (pushstate) {
			window.im._pushState('/im?sel=' + conv.peer.id);
		}
	}

	addChat(conv) {
		this.opened_tabs.push(conv);

		if (typeof window.im !== 'undefined' && window.im.updateTabs) {
			window.im.updateTabs();
		}

		return this.opened_tabs.length - 1;
	}

	getCurrentChat() {
		if (this.current_chat === null || this.current_chat === undefined) return null;
		return this.opened_tabs[this.current_chat] || null;
	}

	getTabsCount() {
		return this.opened_tabs.length;
	}

	preselectChat(conversation) {
		if (!this.hasChat(conversation)) {
			this.addChat(conversation);
		}
	}

	closeChat(conv) {
		const idx = this.opened_tabs.indexOf(conv);
		if (idx !== -1) { this.opened_tabs.splice(idx, 1) };

		if (typeof window.im !== 'undefined' && window.im.updateTabs) {
			window.im.updateTabs();
		}
	}

    _clearAttachments() {
        try {
            this.appEl.querySelector(".post-horizontal").innerHTML = "";
            this.appEl.querySelector(".post-vertical").innerHTML = "";
        } catch (e) {
            console.error(e);
        }
    }

	_saveDraft(to_chat) {
		if (!to_chat) return;
		this.drafts[to_chat.peer.id] = this.currentDraft;
		this.scrolls[to_chat.peer.id] = document.documentElement.scrollTop;
        this._eraseCurrentDraft();

        console.log(this.scrolls);
        console.log('Saved draft for ', to_chat, ", scroll: ", this.scrolls[to_chat.peer.id]);
	}

	_eraseDraftFor(chat) {
		this.drafts[chat.peer.id] = undefined;
		u('.messenger-app--input---messagebox .post-horizontal').html('');
		u('.messenger-app--input---messagebox .post-vertical').html('');
	}

	_eraseCurrentDraft() {
		this.currentDraft = '';
	}

	_loadDraft(for_chat) {
		if (!for_chat) return;
		const _draft = this.drafts[for_chat.peer.id];
		if (_draft && _draft !== '') {
            this.currentDraft = _draft;
        }
  		const _scroll = this.scrolls[for_chat.peer.id];
  		if (_scroll) {
 			this._scrollTo(_scroll);
  		} else {
 			this._scrollToEnd();
        }

        console.log("Loaded draft for ", for_chat, ", scroll: ", _scroll)
	}

    _scrollTo(scroll_progress) {
        console.log("scrolling page to: ", scroll_progress)
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

	_changeHeight() {
		let maybe_distance = 100;
		let tabs_height = u('.messages--peers-tabs').nodes[0].clientHeight;
		this.appEl.parentNode.style.height = window.outerHeight - tabs_height - maybe_distance + 'px';
	}

	// attachment

	showPhoto(e, msg, attachment) {
		if (typeof PhotoViewer === 'undefined') return;

		const photos = [];
		const ids = attachment.photo.owner_id + '_' + attachment.photo.id + (attachment.photo.access_key ? '_' + attachment.photo.access_key : '');

		msg.attachments.forEach(function (item) {
			if (item.type !== 'photo') return;

			const pid = item.photo.owner_id + '_' + item.photo.id + (item.photo.access_key ? '_' + item.photo.access_key : '');
			const src = item.photo.src_original || item.photo.photo_2560 || item.photo.photo_1280 || item.photo.photo_807 || item.photo.photo_604 || item.photo.photo_130;

			photos.push({
				owner_id: item.photo.owner_id,
				id: item.photo.id,
				url: src,
				access_key: item.photo.access_key,
			});
		});

		const first = photos.find(function (p) {
			return (p.owner_id + '_' + p.id) === (attachment.photo.owner_id + '_' + attachment.photo.id);
		});

		if (!first) {
			console.log("IM | Messenger | Opening photo | Not found ", attachment, " image in", photos)
			return;
		};

		console.log("IM | Messenger | Opening photo ", attachment, msg, photos)

		const __photoViewer = new PhotoViewer();
		__photoViewer.open(first.url, ids, {
			customContext: photos,
		});
	}

	showVideo(e, msg, attachment) {
		e.preventDefault();
		e.stopPropagation();
		OpenVideo([attachment.video.owner_id, attachment.video.id, (attachment.video.access_key ? attachment.video.access_key : null)]);
	}

	async showAudio(e, msg, attachment) {
		console.log("Opening audio ", attachment.audio)
		await showAudioWindow(attachment.audio.owner_id + "_" + attachment.audio.global_id);
	}
}

export class LongPollConnection {
  async create(group_id = null) {
    this.lp = await window.OVKAPI.call('messages.getLongPollServer', {});
    console.log("LP | Created connection to the current user");
  }

  getFirstCounter() {
    return this.lp.unread_count;
  }

  listen() {
    console.log("LP | New cycle of listening");
    let xhr = new XMLHttpRequest();
    const mode = 2 + 8 + 32 + 64 + 128;
    const connection_string = this.lp.server + '?key=' + this.lp.key + '&ts=' + this.lp.ts + '&pts=' + this.lp.pts + '&mode=' + mode;
    xhr.open('GET', connection_string, true);
    xhr.onload = () => {
      let data = JSON.parse(xhr.responseText);
      if (data?.updates?.length > 0)
        data.updates.forEach((event) => {
          window.im.event_handler.handle(event);
        });
      this.lp.ts = data.ts;
      this.listen();
    };
    xhr.send();
  }
}
