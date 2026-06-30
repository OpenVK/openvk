class Messenger {
    async init() {
        this.insert_type = 'page'; // page / fast_chat
        this.view = new MessengerViewModel();
        // this.view = new MessengerViewModel();
        // fastchat sounds like a deutsch surname xd
    }

    hasAppeared(container) {
        return container.querySelector('.messenger-app') != null;
    }

    appear(container = null) {
        container.classList.remove('hidden');
        if (this.hasAppeared(container)) {
            this.view._loadDraft(this.view.getCurrentChat())
            return;
        }

        container.insertAdjacentHTML('beforeend', this.view.template);
        this.view.appEl = container.querySelector(".messenger-app");
        this.view.messagesListBlock = container.querySelector(".messenger-app--messages");
        this.view.messagesList = container.querySelector(".messenger-app--messages-array");

        ko.applyBindings(this.view, container);
    }

    hide(container) {
        container.classList.add('hidden');
    }

    async sendToCurrentCorresponder(model) {
        const text = model.currentDraft();
        const reply_to = model.replyTo()
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

        const attachments = collect_attachments(u('.messenger-app--input---messagebox'))
        if (attachments.length > 0) {
            attachments_list = attachments;
        }

        msg._guessSender();
        msg.setText(text);

        return await corresponder.sendMessage(msg, reply_param, attachments_list)
        //corresponder._pushNewMessage(msg);
    }
}

class MessengerViewModel {
    constructor() {
        this.msg_template_ko = `
        <div class="messenger-app--messages---message" data-bind="css: { 
        'msg-selected': window.im.messenger.view.isMessageSelected(msg), 
        'same-author': $index() > 0 && chunk.messages[$index() - 1].doHideHead(msg)}, 
        event: { mousedown: window.im.messenger.view.onMessageClick }">
            <div class="messenger-app--messages---message--wrap">
                <div class="_avatar">
                    <img class="ava" data-bind="attr: { src: sender.avatar_any, alt: sender.full_name }" />
                </div>
                <div class="_content">
                    <a class="_sender" href="#" data-bind="attr: { href: sender.link }">
                        <strong data-bind="text: sender.full_name"></strong>
                    </a>
                    <span class="text" data-bind="html: text"></span>
                    <div data-bind="foreach: { data: attachments, as: 'attachment' }" class="attachments">
                        <div class="msg-attach-j">
                            <div data-bind="if: attachment.type === 'photo'" class="msg-attach-j-photo">
                                <a data-bind="attr: { href: attachment.photo.link }">
                                    <img data-bind="attr: { src: attachment.photo.photo_130, alt: '...'  }" />
                                </a>
                            </div>
                            <div data-bind="if: attachment.type === 'video'" class="msg-attach-j-video">
                                <a data-bind="attr: { href: '/video' + attachment.video.owner_id + '_' + attachment.video.id }">
                                    <span data-bind="text: attachment.video.title "></span>
                                </a>
                            </div>
                            <div data-bind="if: attachment.type === 'doc'" class="msg-attach-j-doc">
                                <a data-bind="attr: { href: '/doc' + attachment.doc.owner_id + '_' + attachment.doc.id }">
                                    <span data-bind="text: attachment.doc.title "></span>
                                </a>
                            </div>
                            <div data-bind="if: attachment.type === 'audio'" class="msg-attach-j-audio">
                                <a data-bind="text: attachment.audio.artist "></a>
                                —
                                <span data-bind="text: attachment.audio.title "></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="time">
                <div data-bind="if: msg.id != null">
                    <span data-bind="html: msg.id"></span>
                    <span data-bind="html: msg.readable_date"></span>
                </div>
            </div>
        </div>
        `
        this.chat_template = `
        <div>
            <div data-bind="foreach: opened_tabs" class="messages--peers-tabs">
                <div class="messages--peers-tab">
                    <a data-bind="text: peer.name, event: { click: function() { window.im.selectChat(this) } }"></a>
                    <span class="messages--peers-tab-close" data-bind="text: 'x', event: { click: function () { window.im.closeChat(this) } }"></span>
                </div>    
            </div>
        </div>
        <div class="messages--actions" data-bind="css: { 'shown': window.im.messenger.view.selected_messages_count > 0 }">
            <div>
                <a data-bind="text: 'delete', event: { 'click': window.im.messenger.view.callDeletion }"></a>
            </div>
            <div>
                <a data-bind="text: 'unselect', event: { 'click': window.im.messenger.view.unselect }"></a>
            </div>
            <div data-bind="if: window.im.messenger.view.selected_messages_count == 1">
                <a data-bind="text: 'reply', event: { 'click': window.im.messenger.view.onReplyButtonClick }"></a>
            </div>
        </div>
        <div class="messenger-app" data-bind="event: { scroll: onMessagesScroll }">
            <div data-bind="event: { click: onScrollDownButtonClick }" id="messenger-app--down-button">DOWN</div>
            <div class="messenger-app--messages">
                <div class="messenger-app--messages-array" data-bind="foreach: { data: messages, as: 'chunk' } ">
                    <div class="messenger-app--messages-day">
                        <div class="messenger-app--messages-day-time">
                            <b data-bind="html: chunk.readable_date"></b>
                        </div>
                        <div data-bind="foreach: { data: chunk.messages, as: 'msg' } ">
                            ${this.msg_template_ko}
                        </div>
                    </div>    
                </div>
            </div>
            <div class="messenger-app-end" id="write" data-bind="css: { 'reply-selected': replyTo }">
                <div class="input-reply" data-bind="if: replyTo">
                    <span data-bind="html: replyTo.text"></span>
                    <span data-bind="text: 'close', event: { click: removeReply }"></span>
                </div>
                <div class="post-buttons">
                    <div class="messenger-app--input">
                        <img class="ava" data-bind="attr: { src: window.im.current.avatar_any, alt: window.im.current.full_name }" />
                        <div class="messenger-app--input---messagebox">
                            <textarea
                                data-bind="value: currentDraft, event: { keydown: onTextareaKeyPress }"
                                name="message"
                                class="small-textarea"
                                placeholder="${tr('enter_message')}"></textarea>
                            
                            <div class="post-horizontal"></div>
                            <div class="post-vertical"></div>
                            <div class="input--messagebox-buttons">
                                <button data-bind="event: { click: sendMessage }" class="button">${tr('send')}</button>
                                <div>
                                    <a class='menu_toggler'>
                                        ${tr('attach')}
                                    </a>

                                    <div id="wallAttachmentMenu" class="up_direction hidden">
                                        <a class="header menu_toggler">
                                            ${tr('attach')}
                                        </a>
                                        <a id="__photoAttachment">
                                            <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/application-x-egon.png" />
                                            ${tr('photo')}
                                        </a>
                                        <a id="__videoAttachment">
                                            <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/application-vnd.rn-realmedia.png" />
                                            ${tr('video')}
                                        </a>
                                        <a id="__audioAttachment">
                                            <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/audio-ac3.png" />
                                            ${tr('audio')}
                                        </a>
                                        <a id="__documentAttachment">
                                            <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/application-octet-stream.png" />
                                            ${tr('document')}
                                        </a>
                                        <a onclick="initGraffiti(event);">
                                            <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/actions/draw-brush.png" />
                                            ${tr('graffiti')}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <img class="ava" data-bind="event: { click: togglePeerInfo }, attr: { src: window.im.corresponder.avatar_any, alt: window.im.corresponder.full_name }" />
                    </div>
                </div>
            </div>
        </div>
        `
        this.peer_template = `
            <div>
                <a data-bind="event: { click: togglePeerInfo }">back</a>
            </div>
        `
        this.template = `
            <div id="chat-page" data-bind="css: { 'peer-shown': is_showing_profile }">
                <div class="chat-window">${this.chat_template}</div>
                <div class="peer-window">${this.peer_template}</div>
            </div>
        `
        this.opened_tabs = ko.observableArray([]);
        this.currentDraft = ko.observable('');
        this.replyTo = ko.observable(null);
        this.is_showing_profile = ko.observable(false);
        this.is_loading = ko.observable(false);
        this.drafts = {};
        this.scrolls = {};
        this.current_chat = ko.observable(null); // index of element
        this.messagesTrigger = ko.observable(0);
        this.selected_messages = ko.observableArray([]);
        this.messages = ko.pureComputed(() => {
            this.messagesTrigger();
            const _chat = this.getCurrentChat();
            if (!_chat) {
                return [];
            }

            return _chat.peer.divided_messages;
        });

        // consts
        this.MAX_SELECTED_MESSAGES = 100;
    }

    _triggerUpdate() {
        // Updates messages and conversations.
        this._triggerUpdateSlightly();
        window.im.conversations.view._update();
    }

    _triggerUpdateSlightly() {
        // Updates messages.
        this.messagesTrigger(this.messagesTrigger() + 1);
    }

    // Events

    onTextareaKeyPress(model, e) {
        // Clicking "Enter" at textarea
        const ta = e.target;
        if(e.which === 13) {
            if(!e.metaKey && !e.shiftKey) {
                ta.blur();
                this.sendMessage(model);
                ta.focus();

                return false;
            }
        }
        
        return true;
    }

    onMessageClick(model, e) {
        if (e.buttons !== 1 && e.type == 'mousemove') {
            return;
        }

        if (window.im.messenger.view.replyTo() != null) {
            return;
        }

        const target = e.target;
        if (!target.matches('.text, .time span') || window.im.messenger.view.selected_messages_count > 0) {
            e.preventDefault();
            window.im.messenger.view.toggleMessageSelection(model, e);
        }
    }

    onReplyButtonClick(model, e) {
        const ids = this.selected_messages();

        const current_chat = this.getCurrentChat();
        const m = current_chat.peer._findMessageById(ids[0]);

        this.unselect();
        this.replyTo(m);
    }

    removeReply(model, e) {
        this.replyTo(undefined);
    }

    toggleMessageSelection(model, e) {
        // Toggles message selection.
        if (!this.isMessageSelected(model)) {
            this.selectMessage(model);
        } else {
            this.unselectMessage(model);
        }
        //this._triggerUpdateSlightly();
    }

    togglePeerInfo(model, e) {
        if (this.is_showing_profile()) {
            this.is_showing_profile(false);
            return;
        }

        this.is_showing_profile(true);
    }

    onScrollDownButtonClick() {
        this._scrollToEnd()
    }

    async onMessagesScroll(model, e) {
        // Scroll event. If scroll < 21, tries to load more older messages
        // If scroll is near at the end, tries to load more newer messages if is neccessary
        if (this.is_loading()) {
            return;
        }

        this.is_loading(true);
        //const _scroll = e.target.scrollTop;
        const _scroll = document.documentElement.scrollTop;
        // Scrolling up
        if (_scroll < 21) {
            await window.im.corresponder._messagesLoad_UpFromLastChunk();
        } else { // Scrolling down
            // Cocojambo
            const scrollBottom = document.documentElement.scrollHeight - _scroll - document.documentElement.clientHeight

            if (scrollBottom < 10) {
                if (window.im.corresponder._chunks_HasMoreNewerChunkRelativelyToCurrentChat()) {
                    console.log('IM | Scrolled to the beginning')
                }
            }

            if (scrollBottom > 600) {
                console.log('IM | Scrolled too up')
                document.querySelector('.messenger-app--tab-messenger').classList.add('messenger-app--overscrolled')
            } else {
                document.querySelector('.messenger-app--tab-messenger').classList.remove('messenger-app--overscrolled')
            }
        }
        this.is_loading(false);
    }

    // messages select

    selectMessage(msg) {
        this.selected_messages.push(msg.id);
    }

    unselectMessage(msg) {
        this.selected_messages.remove(msg.id);
    }

    isMessageSelected(msg) {
        return this.selected_messages().indexOf(msg.id) != -1;
    }

    get selected_messages_count() {
        // Count of the selected messages
        return this.selected_messages().length;
    }

    callDeletion() {
        // Shows deletion confirmation. The messages that are going to be deleted are the selected messages
        const ids = this.selected_messages();

        console.log('IM | Going to delete ' + ids.length + ' messages')

        const current_chat = this.getCurrentChat();
        const msg = new CMessageBox({
            title: 'MESAGE DELETIONS',
            body: 'SURE?',
            buttons: ['YESSS', 'No'],
            callbacks: [async () => {
                const delete_for_all = true;
                let ids2 = [];
                ids.forEach(item => {
                    let m = current_chat.peer._findMessageById(item);
                    ids2.push(current_chat.peer.id + '_' + item);
                    m.setDeleted(true);
                })
                this._triggerUpdate();
                this.unselect()

                // there will be deletion event received
                /*await window.OVKAPI.call('messages.delete', {
                    'message_ids': ids2,
                    'delete_for_all': Number(delete_for_all)
                });*/

            }, () => {}]
        })
    }

    unselect() {
        // Removes selection from all messages
        this.selected_messages([]);
    }

    // Actions

    async sendMessage(model) {
        const _tmp_atts = collect_attachments(u('.messenger-app--input---messagebox'));

        if(model.currentDraft() === "" && _tmp_atts.length == 0) return false;
        if (true) {
            this._scrollToEnd();
        }

        window.im.messenger.sendToCurrentCorresponder(model).then(e => {
            // TODO: do not move if scrolled too up
            if (true) {
                this._scrollToEnd();
            }
        })

        this._eraseDraftFor({'peer': window.im.current});
        this._eraseCurrentDraft();
        this.removeReply();
    }

    // Chat tabs

    hasChat(conversation) {
        // Is tab with this conversation exists.
        return this.opened_tabs().indexOf(conversation) != -1;
    }

    getChatWith(chat_general_form) {
        // Finds conversation object or returns new from chat
        let is = null;

        window.im.conversations.convs.forEach(item => {
            if (item.peer.id == chat_general_form.id) {
                is = item;
                return;
            }
        })

        if (!is) {
            return new Conversation({
                'peer': chat_general_form
            });
        }

        return is;
    }

    setChat(conv, pushstate = true) {
        // Selects another chat
        console.log('IM | Set chat to ' + conv.peer.id);
        this.current_chat(this.opened_tabs().indexOf(conv));
        this.unselect()

        if (pushstate) {
            window.im._pushState('/im?sel=' + conv.peer.id);
        }
    }

    addChat(conv) {
        // Opens tab with some peer
        return this.opened_tabs.push(conv) - 1;
    }

    getCurrentChat() {
        // Gets opened chat tab
        return this.opened_tabs()[this.current_chat()];
    }

    getTabsCount() {
        return this.opened_tabs().length;
    }

    preselectChat(conversation) {
        if (!this.hasChat(conversation)) {
            this.addChat(conversation);
        }
    }

    closeChat(conv) {
        // Removes tab with this conversation
        this.opened_tabs.remove(conv);
    }

    // Drafts

    _saveDraft(to_chat) {
        // Saves text at textarea and saves scroll
        if (!to_chat) {
            return;
        }

        this.drafts[to_chat.peer.id] = this.currentDraft();
        this.scrolls[to_chat.peer.id] = document.documentElement.scrollTop;
        console.info('IM | Saved draft for peer ' + to_chat.peer.id);
        this._eraseCurrentDraft();
    }

    _eraseDraftFor(chat) {
        // Removes draft (when it was sent or smth)
        this.drafts[chat.peer.id] = undefined;
        console.info('erased draft for ' + chat.peer.id);

        u('.messenger-app--input---messagebox .post-horizontal').html('')
        u('.messenger-app--input---messagebox .post-vertical').html('')
    }

    _eraseCurrentDraft() {
        this.currentDraft('');
    }

    _loadDraft(for_chat) {
        if (!for_chat) {
            return;
        }

        const _draft = this.drafts[for_chat.peer.id];
        if (_draft && _draft != '') {
            console.info('loaded draft for peer ' + for_chat.peer.id);

            this.currentDraft(_draft);
        }
        const _scroll = this.scrolls[for_chat.peer.id];
        if (_scroll) {
            this._scrollTo(_scroll);
        } else {
            this._scrollToEnd();
        }
    }

    _scrollTo(scroll_progress) {
        console.log( this.scrolls)
        document.documentElement.scroll({
            top: scroll_progress
        });
    }

    _scrollToEnd() {
        this._scrollTo(document.documentElement.scrollHeight);
    }

    _changeHeight() {
        let maybe_distance = 100;
        let tabs_height = u('.messages--peers-tabs').nodes[0].clientHeight;
        this.appEl.parentNode.style.height = window.outerHeight - tabs_height - maybe_distance + 'px';
    }
}

class LongPollConnection {
    async create() {
        this.lp = await window.OVKAPI.call('messages.getLongPollServer', {});
    }

    listen() {
        let xhr = new XMLHttpRequest();
        const mode = 2 + 8 + 32 + 64 + 128 // w/ attach, extended, pts, w/ extra, w/ random_id
        const connection_string = this.lp.server + '?key='+this.lp.key + '&ts=' + this.lp.ts + '&pts=' + this.lp.pts + "&mode=" + mode;
        xhr.open("GET", connection_string, true);
        xhr.onload = () => {
            let data = JSON.parse(xhr.responseText);
            if (data?.updates?.length > 0)
                data.updates.forEach(event => {
                    window.im.event_handler.handle(event);
                });
            this.lp.ts = data.ts;
            this.listen();
        };
        xhr.send();
    }
}
