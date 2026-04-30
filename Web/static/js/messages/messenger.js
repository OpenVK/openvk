class Messenger {
    constructor() {
        this.opened_tabs = [];
        this.template = `
    <div class="messenger-app">
        <div class="messenger-app--messages">
            <div data-bind="foreach: messages">
                <div class="messenger-app--messages---message" data-bind="css: { unread: !read }">
                    <img class="ava" data-bind="attr: { src: sender.avatar, alt: sender.name }" />
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
                        <span data-bind="html: timing.sent"></span>
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
    }

    async init() {
        this.view = new MessengerViewModel();
    }

    appear(container = null) {
        if (this.appeared) {
            container.classList.remove('hidden');
            return;
        }

        this.appeared = true;
        container.insertAdjacentHTML('beforeend', this.template);

        ko.applyBindings(this.view, container);
    }

    hide(container) {
        container.classList.add('hidden');
    }

    selectChat(conversation) {
        
    }
}

class MessengerViewModel {
    constructor() {
        // todo: чел может открыть несколько чатов, для каждого из них нужно сохранять прокрутку. поэтому надо сделать несколько view model и в массиве всё держать
        this.messages = ko.observableArray([]);
        this.messageContent = ko.observable("");

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
}

window.im = new (class {
    constructor() {
        this.tabs = ['conversations', 'messenger'];
        this.tab = ''
    }

    async init(container) {
        // todo change
        if (window.OVKAPI == null) {
            await new Promise((resolve) => setTimeout(resolve, 1000));
        }

        this.root = container;
        this._initTabs();

        this.conversations = new Conversations();
        await this.conversations.init();
        this.messenger = new Messenger();
        await this.messenger.init();
        this.selectTab('conversations');
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
                break;
            case 'messenger':
                this.conversations.hide(this._getTabWindow('conversations'));
                this.messenger.appear(this._getTabWindow('messenger'));
                break;
        }
    }

    _initTabs() {
        // todo rewrite on kojs
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

    _getTabWindow(tab_name) {
        return this.root.querySelector(`div[data-window="${tab_name}"]`)
    }

    selectChat(conv) {
        this.selectTab('messenger');
        this.messenger.selectChat(conv);
    }
})()
