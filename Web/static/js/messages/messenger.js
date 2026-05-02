class Messenger {
    async init() {
        this.view = new MessengerViewModel();
        this.insert_type = 'page'; // page / fastchat
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
}

class MessengerViewModel {
    constructor() {
        this.template = `
        <div>
            <div data-bind="foreach: opened_tabs">
                <a data-bind="text: peer.name, event: { click: function() { window.im.messenger.view.setChat(this) } }"></a>
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
                <img class="ava" alt="{$thisUser->getCanonicalName()}" />
                <div class="messenger-app--input---messagebox">
                    <textarea
                            data-bind="value: messageContent"
                            name="message"
                            placeholder="{_enter_message}"></textarea>
                    <button class="button">{_send}</button>
                </div>
                <img class="ava" alt="{$correspondent->getCanonicalName()}" />
            </div>
        </div>
        `
        this.opened_tabs = ko.observableArray([]);
        this.messages = ko.pureComputed(function() {
            console.log()
            return window.im._messages;
        });
        this.messageContent = ko.observable('');
        this.current_chat = ko.observable(null); // index of element

        /*this.sendMessage = model => {
            if(model.messageContent() === "") return false;
            
            window.Msg.sendMessage(model.messageContent());
            model.messageContent("");
        };
        this.loadHistory = _ => {
            window.Msg._loadHistory();
        };

        this.onMessagesScroll   = (model, e) => {
            if(e.target.scrollTop < 21)
                model.loadHistory();
        };
        this.onTextareaKeyPress = (model, e) => {
            if(e.which === 13) {
                if(!e.metaKey && !e.shiftKey) {
                    let ta = u("textarea[name=message]").nodes[0];
                    ta.blur(); //Fix update
                    model.sendMessage(model);
                    ta.focus();
                    
                    return false;
                }
            }
            
            return true;
        };*/
    }

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
}

window.im = new (class {
    constructor() {
        this.tabs = ['conversations', 'messenger'];
        this.tab = ''
    }

    async init(container) {
        if (window.OVKAPI == null) {
            await new Promise((resolve) => setTimeout(resolve, 1000));
        }

        this.root = container;
        await this._loadCurrent();
        this._initTabs();

        if (!this.conversations) {
            this.conversations = new Conversations();
            await this.conversations.init();
        }

        if (!this.messenger) {
            this.messenger = new Messenger();
            await this.messenger.init();
        }

        const found = await this._checkSel(new URL(location.href));
        if (!found) {
            this.selectTab('conversations');
        }
    }

    selectTab(tab_name) {
        if (this.tabs.indexOf(tab_name) == -1) {
            throw new Error('invalid tab');
        }

        this.tab = tab_name;
        // its so amazing to define check state via id not by class
        // and do this for tab's text
        u(this.root).find(".tabs .tab").attr('id', '')
        u(this.root).find(`.tabs .tab[data-tab='${tab_name}']`).attr('id', 'activetabs')

        switch (tab_name) {
            case 'conversations':
                this.conversations.appear(this._getTabWindow('conversations'));
                this.messenger.hide(this._getTabWindow('messenger'));
                this._pushState('/im');
                break;
            case 'messenger':
                this.conversations.hide(this._getTabWindow('conversations'));
                this.messenger.appear(this._getTabWindow('messenger'));
                try {
                    window.im._pushState('/im?sel=' + window.im.messenger.view.getCurrentChat().peer.id);
                } catch(e) {
                    console.error(e);
                }

                break;
        }
    }

    async selectChat(conv) {
        this.messenger.view.preselectChat(conv);

        //const current_chat = this.messenger.view.getCurrentChat();
        if (!conv.peer._isMessagesInited()) {
            const messages = await conv.peer.getMessages();

            conv.peer._appendMessages(messages);
        }
        this.messenger.view.setChat(conv, false);

        this.selectTab('messenger');
    }

    async _loadCurrent() {
        this._current = await window.OVKAPI.call('users.get', {'user_ids': window.openvk.current_id, 'fields': 'photo_100'})
        this._current = this._current[0];
    }

    _initTabs() {
        this.root.insertAdjacentHTML('beforeend', `
            <div class="tabs"></div>
        `);

        this.tabs.forEach(tab => {
            this.root.insertAdjacentHTML('beforeend', `
                <div data-window="${tab}"></div>    
            `);
            this.root.querySelector(".tabs").insertAdjacentHTML('beforeend', `
                <a data-tab="${tab}" onclick="window.im.selectTab('${tab}', event)" class="tab">
                    ${tab}
                </a>
            `);
        })
    }

    async _checkSel(loc) {
        const _sel = Number(loc.searchParams.get('sel'));
        if (!_sel) {
            return;
        }

        const peer = await this.conversations._resolveSel(_sel);

        if (peer) {
            const _item = new ChatGeneralForm(peer);
            const _l = this.messenger.view.getChatWith(_item);
            await this.selectChat(_l);

            return _l;
        } else {
            console.error('No peer with this id!')
        }
    }

    _getTabWindow(tab_name) {
        return this.root.querySelector(`div[data-window="${tab_name}"]`)
    }

    get _messages() {
        try {
            const _chat = this.messenger.view.getCurrentChat();

            return _chat.peer._getLocalMessages();
        } catch(e) {
            console.error(e);
            return []
        }
    }

    _pushState(url) {
        history.pushState({'from_messenger': 1}, null, url);
    }

    _resolveState(e) {
        const _url = new URL(location.href);
        if (_url.searchParams.get('sel')) {
            this._checkSel(_url);
        } else {
            this.selectTab('conversations');
        }
    }
})()
