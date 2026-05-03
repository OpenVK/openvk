// Class that joins conversations and messenger
window.im = new (class {
    constructor() {
        this.tabs = ['conversations', 'messenger'];
        this.tab = ''
    }

    // Get ID from ?sel= param and find conversation for it
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

        // lp
        this.lp = new LongPollConnection();
        await this.lp.create();
        this.lp.listen();
    }

    // Something between messenger and conversations

    closeChat(conv) {
        if (this.messenger.view.getTabsCount() - 1 == 0) {
            this.selectTab('conversations');
        } else {
            const _id = this.messenger.view.opened_tabs().indexOf(conv);
            this.selectChat(this.messenger.view.opened_tabs()[Math.max(0, _id - 1)])
        }

        this.messenger.view.closeChat(conv);
    }

    // Move to "Messenger" tab and select chat
    async selectChat(conv) {
        this.messenger.view.preselectChat(conv);

        const _url = new URL(location.href);
        const _start_from = _url.searchParams.get('start_from');

        //const current_chat = this.messenger.view.getCurrentChat();
        this.messenger.view._saveDraft(this.messenger.view.getCurrentChat());
        if (!conv.peer._isMessagesInited()) {
            const messages = await conv.peer.getMessages(_start_from);

            conv.peer._appendMessagesChunk(messages);
        }
        this.messenger.view.setChat(conv, false);
        this.selectTab('messenger');
        this.messenger.view._loadDraft(conv);
    }

    // Current user. Do not confuse with window.im.corresponder!
    async _loadCurrent() {
        let _v = await window.OVKAPI.call('users.get', {'user_ids': window.openvk.current_id, 'fields': ChatGeneralForm.base_fields});
        this._currents = [new ChatGeneralForm(_v[0])];
        this._current_id = 0;
    }

    get current() {
        return this._currents[this._current_id];
    }

    // Tabs
    _initTabs() {
        this.root.insertAdjacentHTML('beforeend', `
            <div class=".messenger-app--global-tabs tabs"></div>
        `);

        this.tabs.forEach(tab => {
            this.root.insertAdjacentHTML('beforeend', `
                <div class="messenger-app--tab-${tab}" data-window="${tab}"></div>    
            `);
            this.root.querySelector(".tabs").insertAdjacentHTML('beforeend', `
                <a data-tab="${tab}" onclick="window.im.selectTab('${tab}', event)" class="tab">
                    ${tab}
                </a>
            `);
        })
    }

    selectTab(tab_name) {
        if (this.tabs.indexOf(tab_name) == -1) {
            throw new Error('invalid tab');
        }

        this.tab = tab_name;
        // its so amazing to define check state via id not by class
        // and do this for tab's text

        switch (tab_name) {
            case 'conversations':
                this.conversations.appear(this._getTabWindow('conversations'));
                this.messenger.hide(this._getTabWindow('messenger'));
                this._pushState('/im');
                this._toggleScrollMode(false);

                break;
            case 'messenger':
                if (!window.im.corresponder) {
                    return;
                }

                this.conversations.hide(this._getTabWindow('conversations'));
                this.messenger.appear(this._getTabWindow('messenger'));
                this._toggleScrollMode(true);

                try {
                    window.im._pushState('/im?sel=' + window.im.messenger.view.getCurrentChat().peer.id);
                } catch(e) {
                    console.error(e);
                }

                break;
        }

        u(this.root).find(".tabs .tab").attr('id', '')
        u(this.root).find(`.tabs .tab[data-tab='${tab_name}']`).attr('id', 'activetabs')
    }

    _getTabWindow(tab_name) {
        return this.root.querySelector(`div[data-window="${tab_name}"]`)
    }

    _toggleScrollMode(enable = true) {
        if (enable) {
            u('body').addClass('no-scroll');
        } else {
            u('body').removeClass('no-scroll');
        }
    }

    // Messages of current chat
    get _messages() {
        try {
            const _chat = this.messenger.view.getCurrentChat();

            return _chat.peer._getLocalMessages();
        } catch(e) {
            console.error(e);
            return []
        }
    }

    // Current corresponder
    get corresponder() {
        try {
            return this.messenger.view.getCurrentChat().peer;
        } catch(e) {
            console.error(e);
        }
    }

    // """""Routing"""""
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

    // LongPoll listener

    onEventReceived(event) {
        if (!Array.isArray(event)) return;

        const code = event[0];

        switch (code) {
            case 1: { // MsgReplaceFlagsEvent
                const messageId = event[1];
                const flags = event[2];
                const peerId = event[3];
                console.log("Replace flags. MsgID:", messageId, "Flags:", flags, "Peer:", peerId);
                break;
            }

            case 2: { // MsgSetFlagsEvent
                const messageId = event[1];
                const mask = event[2];
                const peerId = event[3];
                console.log("Set flags (mask):", mask, "MsgID:", messageId);
                break;
            }

            case 3: { // MsgResetFlagsEvent
                const messageId = event[1];
                const mask = event[2];
                const peerId = event[3];
                console.log("Reset flags (mask):", mask, "для сообщения:", messageId);
                break;
            }

            case 4: { // NewMessageEvent
                const [, id, flags, peer, ts, subject, text, attachments, randomId] = event;
                console.log(`New msg [${id}] from ${peer}: ${text}`);
                if (attachments) console.log("Attached:", attachments);
                break;
            }

            case 5: { // UpdateMessageEvent
                const [, id, mask, peer, ts, text, attachments] = event;
                console.log(`Message ${id} update: ${text}`);
                break;
            }

            case 6: { // ReadIncomeBeforeEvent
                const peerId = event[1];
                const localId = event[2];
                console.log(`Incomes in chat ${peerId} checked as read until ID ${localId}`);
                break;
            }

            case 7: { // ReadOutcomeBeforeEvent
                const peerId = event[1];
                const localId = event[2];
                console.log(`Outcomes in chat ${peerId} checked as read until ID ${localId}`);
                break;
            }

            case 8: { // GotOnlineEvent
                const userId = event[1];
                const extra = event[2];
                const timestamp = event[3];
                console.log(`User ${userId} online (From: ${extra & 0xFF})`);
                break;
            }

            case 9: { // GotOfflineEvent
                const userId = event[1];
                const flags = event[2];
                console.log(`User ${userId} offline. Reason: ${flags === 1 ? 'timeout' : 'logout'}`);
                break;
            }

            case 10: { // ChatResetFlagsEvent
                const peerId = event[1];
                const mask = event[2];
                break;
            }

            case 11: { // ChatReplaceFlagsEvent
                const peerId = event[1];
                const flags = event[2];
                break;
            }

            case 12: { // ChatSetFlagsEvent
                const peerId = event[1];
                const mask = event[2];
                break;
            }

            case 13: { // MassDeleteMessagesEvent
                const peerId = event[1];
                const localId = event[2];
                console.log(`Mass delete messages ${peerId} until ${localId}`);
                break;
            }

            case 51: { // ChatSomethingChangedEvent
                const chatId = event[1];
                const self = event[2];
                console.log(`Something happened in chat ${chatId}${self ? " and its triggered by me" : ""}`);
                break;
            }

            case 61: { // IsDMTypingEvent
                const userId = event[1];
                const flags = event[2]; // 1 - пишет, 2 - аудио
                console.log(`${userId} is ${flags == 1 ? "typing" : "recording voice message"} in DMs`);
                break;
            }

            case 62: { // IsChatTypingEvent
                const userId = event[1];
                const chatId = event[2];
                const flags = event[3];
                console.log(`${userId} is ${flags == 1 ? "typing" : "recording voice message"} in chat ${chatId}`);
                break;
            }

            case 70: { // MakingACallEvent
                const userId = event[1];
                const callId = event[2];
                console.log(`Received incoming call ${callId} from ${userId}`);
                break;
            }

            case 80: { // CounterUpdateEvent
                const count = event[1];
                console.log("Unreads counter:", count);
                break;
            }

            case 114: { // NotificationSetEvent
                const peerId = event[1];
                const sound = event[2];
                const disabledUntil = event[3];
                console.log(`Notification settings updated for peer ${peerId}: sound is ${sound}`);
                break;
            }

            default:
                console.log("unknown event", code, event);
        }
    }
})()
