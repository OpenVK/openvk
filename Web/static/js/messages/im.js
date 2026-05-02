// Class that joins conversations and messenger
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
        console.log(conv)
        // Move to "Messenger" tab and select chat
        this.messenger.view.preselectChat(conv);

        //const current_chat = this.messenger.view.getCurrentChat();
        this.messenger.view._saveDraft(this.messenger.view.getCurrentChat());

        if (!conv.peer._isMessagesInited()) {
            const messages = await conv.peer.getMessages();

            conv.peer._appendMessages(messages);
        }
        this.messenger.view.setChat(conv, false);
        this.messenger.view._loadDraft(conv);

        this.selectTab('messenger');
    }

    async _loadCurrent() {
        let _v = await window.OVKAPI.call('users.get', {'user_ids': window.openvk.current_id, 'fields': ChatGeneralForm.base_fields});
        this._currents = [new ChatGeneralForm(_v[0])];
        this._current_id = 0;
    }

    get current() {
        return this._currents[this._current_id];
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
            const _l = this.messenger.view.getChatWith(peer);
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

    get corresponder() {
        return this.messenger.view.getCurrentChat().peer;
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
