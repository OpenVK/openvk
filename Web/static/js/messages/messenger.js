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
            return;
        }

        container.insertAdjacentHTML('beforeend', this.view.template);
        this.view.appEl = container.querySelector(".messenger-app");
        this.view.messagesList = container.querySelector(".messenger-app--messages-array");
        this.view._changeHeight();

        ko.applyBindings(this.view, container);
    }

    hide(container) {
        container.classList.add('hidden');
    }

    async sendToCurrentCorresponder(model) {
        const text = model.currentDraft();
        const corresponder = window.im.corresponder;

        const msg = new ChatMessage({
            'peer': window.im.current.id,
            'date': Number(new Date()) - 1000
        });
        msg._guessSender();
        msg.setText(text);

        const _sent = await corresponder.sendMessage(msg)
        //corresponder._pushNewMessage(msg);
    }
}

class MessengerViewModel {
    constructor() {
        this.template = `
        <div>
            <div data-bind="foreach: opened_tabs" class="messages--peers-tabs">
                <div>
                    <a class="messages--peers-tab" data-bind="text: peer.name, event: { click: function() { window.im.selectChat(this) } }"></a>
                    <span class="messages--peers-tab-close" data-bind="text: 'x', event: { click: function () { window.im.closeChat(this) } }"></span>
                </div>    
            </div>
        </div>
        <div class="messages--actions" data-bind="css: { 'shown': window.im.messenger.view.selected_messages_count > 0 }">
            <div>
                <a data-bind="text: 'delete', event: { 'click': window.im.messenger.view.callDeletion }"></a>
            </div>
        </div>
        <div class="messenger-app">
            <div class="messenger-app--messages" data-bind="event: { scroll: onMessagesScroll }">
                <div class="messenger-app--messages-array" data-bind="foreach: { data: messages, as: 'msg' } ">
                    <div class="messenger-app--messages---message" data-bind="css: { 'msg-selected': window.im.messenger.view.isMessageSelected(msg), 'same-author': $index() > 0 && $parent.messages()[$index() - 1].doHideHead(msg)}, event: { click: function() { window.im.messenger.view.toggleMessageSelection(msg) } }">
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
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="time">
                            <span data-bind="html: id"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="messenger-app--input">
                <img class="ava" data-bind="attr: { src: window.im.current.avatar_any, alt: window.im.current.full_name }" />
                <div class="messenger-app--input---messagebox">
                    <textarea
                            data-bind="value: currentDraft, event: { keydown: onTextareaKeyPress }"
                            name="message"
                            placeholder="${tr('enter_message')}"></textarea>
                    <button data-bind="event: { click: sendMessage }" class="button">${tr('send')}</button>
                </div>
                <img class="ava" data-bind="attr: { src: window.im.corresponder.avatar_any, alt: window.im.corresponder.full_name }" />
            </div>
        </div>
        `
        this.opened_tabs = ko.observableArray([]);
        this.currentDraft = ko.observable('');
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

            return _chat.peer.messages;
        });
    }

    _triggerUpdate() {
        this._triggerUpdateSlightly();
        window.im.conversations.view._update();
    }

    _triggerUpdateSlightly() {
        this.messagesTrigger(this.messagesTrigger() + 1);
    }

    // Events

    onTextareaKeyPress(model, e) {
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

    toggleMessageSelection(model, e) {
        if (!this.isMessageSelected(model)) {
            this.selectMessage(model);
        } else {
            this.unselectMessage(model);
        }
        this._triggerUpdateSlightly();
    }

    async onMessagesScroll(model, e) {
        if (this.is_loading()) {
            return;
        }

        this.is_loading(true);
        const _scroll = e.target.scrollTop;
        // Прокрутка вверх
        if (_scroll < 21) {
            await window.im.corresponder._messagesLoad_UpFromLastChunk();
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
        return this.selected_messages().length;
    }

    callDeletion() {
        const ids = this.selected_messages();
        const msg = new CMessageBox({
            title: ids.length,
            body: 'SURE?',
            buttons: ['YESSS', 'No'],
            callbacks: [async () => {
                const delete_for_all = true;
                let ids2 = [];
                ids.forEach(item => {
                    ids2.push(this.getCurrentChat().peer.id + '_' + item);
                })

                await window.OVKAPI.call('messages.delete', {
                    'message_ids': ids2,
                    'delete_for_all': Number(delete_for_all)
                });
            }, () => {}]
        })
    }

    // Actions

    sendMessage(model) {
        if(model.currentDraft() === "") return false;

        window.im.messenger.sendToCurrentCorresponder(model)

        this._eraseDraftFor({'peer': window.im.current});
        this._eraseCurrentDraft();
    }

    // why these methods are there???
    // Chat tabs

    hasChat(conversation) {
        return this.opened_tabs().indexOf(conversation) != -1;
    }

    getChatWith(chat_general_form) {
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
        this.current_chat(this.opened_tabs().indexOf(conv));

        if (pushstate) {
            window.im._pushState('/im?sel=' + conv.peer.id);
        }
    }

    addChat(conv) {
        return this.opened_tabs.push(conv) - 1;
    }

    getCurrentChat() {
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
        this.opened_tabs.remove(conv);
    }

    // Drafts

    _saveDraft(to_chat) {
        // Also saves scroll progress
        if (!to_chat) {
            return;
        }

        this.drafts[to_chat.peer.id] = this.currentDraft();
        this.scrolls[to_chat.peer.id] = this.messagesList.scrollTop;
        console.info('saved draft for peer ' + to_chat.peer.id);
        this._eraseCurrentDraft();
    }

    _eraseDraftFor(chat) {
        this.drafts[chat.peer.id] = undefined;
        console.info('erased draft for ' + chat.peer.id);
    }

    _eraseCurrentDraft() {
        this.currentDraft('');
    }

    _scrollTo(scroll_progress) {
        this.messagesList.scrollTop = scroll_progress;
    }

    _scrollToEnd() {
        this._scrollTo(this.messagesList.scrollHeight);
    }

    // todo change
    _changeHeight() {
        let maybe_distance = 100;
        let tabs_height = u('.messages--peers-tabs').nodes[0].clientHeight;
        this.appEl.parentNode.style.height = window.outerHeight - tabs_height - maybe_distance + 'px';
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
            console.log(data);
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
