class Messenger {
    async init() {
        this.appEl = document.querySelector(".messenger-app--messages");
        this.view = new MessengerViewModel();
        this.insert_type = 'page'; // page / fast_chat
        // fastchat sounds like a deutsch surname xd
    }

    appear(container = null) {
        container.classList.remove('hidden');
        if (this.appeared) {
            return;
        }

        this.appeared = true;
        container.insertAdjacentHTML('beforeend', this.view.template);

        ko.applyBindings(this.view, container);
    }

    hide(container) {
        container.classList.add('hidden');
    }

    async sendToCurrentCorresponder(model) {
        const text = model.currentDraft();
        const corresponder = window.im.corresponder;

        const msg = new ChatMessage();
        msg.setText(text);

        await corresponder.sendMessage(msg);
    }
}

class MessengerViewModel {
    constructor() {
        this.template = `
        <div>
            <div data-bind="foreach: opened_tabs" class="messages--peers-tabs">
                <a class="messages--peers-tab" data-bind="text: peer.name, event: { click: function() { window.im.selectChat(this) } }"></a>
            </div>
        </div>
        <div class="messenger-app">
            <div class="messenger-app--messages">
                <div data-bind="foreach: messages">
                    <div class="messenger-app--messages---message">
                        <img class="ava" data-bind="attr: { src: sender.avatar_any, alt: sender.name }" />
                        <div class="_content">
                            <a href="#" data-bind="attr: { href: sender.link }">
                                <strong data-bind="text: sender.name"></strong>
                            </a>
                            <span class="text" data-bind="html: text"></span>
                            <div data-bind="foreach: attachments" class="attachments">
                                <div class="msg-attach-j">
                                    <div data-bind="if: type === 'photo'" class="msg-attach-j-photo">
                                        <a data-bind="attr: { href: link }">
                                            <img data-bind="attr: { src: photo.url, alt: photo.caption  }" />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="time" align="right">
                            <span></span>
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
        this.messages = ko.pureComputed(function() {
            console.log()
            return window.im._messages;
        });
        this.currentDraft = ko.observable('');
        this.drafts = {};
        this.current_chat = ko.observable(null); // index of element

        /*
        this.loadHistory = _ => {
            window.Msg._loadHistory();
        };

        this.onMessagesScroll   = (model, e) => {
            if(e.target.scrollTop < 21)
                model.loadHistory();
        };
        this.onTextareaKeyPress = (model, e) => {

        };*/
    }

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

    sendMessage(model) {
        if(model.currentDraft() === "") return false;

        window.im.messenger.sendToCurrentCorresponder(model)

        this._eraseDraftFor({'peer': window.im.current});
        this._eraseCurrentDraft();
    }

    // why these methods are there???
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

        // какая разница
        // но тут проблема что я не выделил conversation из метода в класс и поэтому это странно
        if (!is) {
            return {
                'peer': chat_general_form
            };
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

    preselectChat(conversation) {
        if (!this.hasChat(conversation)) {
            this.addChat(conversation);
        }
    }

    // Drafts

    _saveDraft(to_chat) {
        if (!to_chat) {
            return;
        }

        this.drafts[to_chat.peer.id] = this.currentDraft();
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

    _loadDraft(for_chat) {
        console.log(for_chat)
        if (!for_chat) {
            return;
        }

        const _draft = this.drafts[for_chat.peer.id];
        if (!_draft || _draft == '') {
            return;
        }

        console.info('loaded draft for peer ' + for_chat.peer.id);

        this.currentDraft(_draft);
    }
}

class LongPollConnection {
    listenLongpool() {
        let xhr = new XMLHttpRequest();
        xhr.open("GET", "/im12", true);
        xhr.onload = () => {
            let data = JSON.parse(xhr.responseText);
            data.forEach(event => {
                event = event.event;
                if(event.type !== "newMessage")
                    return;
                //else if(event.message.sender.id !== {$correspondent->getId()})
                //    return;
                else if(this.offset >= event.message.uuid)
                    return void(console.warn());

                this.addMessage(event.message);
                //this.offset = event.message.uuid;
            });

            listenLongpool();
        };
        xhr.send();
    }

    constructor() {
        listenLongpool();
    }
}
