class ConversationsViewModel {
    constructor() {
        this.indexes_order = ko.observableArray([]);
        this.conversations_list = ko.pureComputed(() => {
            this._check_count();

            return window.im.conversations.convs;
        })
        this._check_count = ko.observable(0);
        this.has_more_items = ko.pureComputed(() => {
            this._check_count();
            // todo REWRITE
            if (!window.im.conversations.total_convs) {
                return true;
            }

            return window.im.conversations.loaded_convs_count < window.im.conversations.total_convs
        });
        // todo поменять
    }

    _update() {
        this._check_count(this._check_count() + 1);
    }

    _st() {
        this.conversations = ko.observableArray(window.im.conversations.convs);
    }

    async loadNext() {
        await window.im.conversations._loadNext();
        this._update();
    }

    _chatCreationModal() {
        const msg = new CMessageBox({
            title: 'chat creation',
            body: `<input placeholder="name" id="chat_create_name" type="text">`,
            buttons: [tr('create'), tr('cancel')],
            callbacks: [async () => {
                const name = msg.getNode().querySelector('#chat_create_name')

                await window.OVKAPI.call()
            }, () => {}]
        })
    }
}

class Conversations {
    constructor() {
        this.total_convs = 0;
        this.CONVERSATIONS_PER_PAGE = 100;
        this.template = `
        <div class="crp-list">
            <div>
                <input type="button" class="button" value="create chat" data-bind="event: { click: window.im.conversations.view._chatCreationModal }">
            </div>
            <div data-bind="foreach: conversations_list">
                <div class="crp-entry" data-bind="event: { click: async function(data, event) { await window.im.selectChat(this) } }">
                    <div class="crp-entry--image">
                        <img data-bind="attr: { src: peer.avatar_any }"
                        loading="lazy" />
                    </div>
                    <div class="crp-entry--info">
                        <a data-bind="attr: { href: peer.chat_url }, html: peer.full_name "></a><br/>
                    </div>
                    <div class="crp-entry--message"></div>
                </div>
            </div>
            <div data-bind="if: has_more_items, event: { click: loadNext }">
                ${tr('show_next')}
            </div>
        </div>
        `
    }

    async _resolveSel(sel) {
        let _ = null;

        try {
            this.convs.forEach(item => {
                if (item.peer.id === sel) {
                    _ = item;
                }
            });
        } catch(e) {
            console.error(e);
        }

        if (_) {
            return _.peer;
        }

        let _n = await ChatGeneralForm.resolveById(sel);
        if (!_n) {
            return null;
        }

        return new ChatGeneralForm(_n);
    }

    async getConversations(offset = 0) {
        // adding profiles to conversation items
        let convs = await window.OVKAPI.call("messages.getConversations", {
            extended: 1,
            count: this.CONVERSATIONS_PER_PAGE,
            offset: offset,
            fields: ChatGeneralForm.base_fields
        });
        const lists = [];

        // Профили выносятся в кэш, в peer будет создана ссылка
        convs.profiles.forEach(prof => {
            window.im.cached_profiles._addProfileCache(new ChatGeneralForm(prof));
        });
        convs.groups.forEach(group => {
            window.im.cached_profiles._addProfileCache(new ChatGeneralForm(group));
        });

        convs.items.forEach(item => {
            const id = item.conversation.peer.id;
            item.peer = window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(id);

            lists.push(new Conversation(item));
        })

        if (!this.total_convs) {
            this.total_convs = convs.count;
        }

        return lists;
    }

    get loaded_convs_count() {
        if (!this.all_convs) {
            return 0;
        }

        return this.all_convs.length;
    }

    _appendConvs(convs) {
        if (!this.all_convs) {
            this.all_convs = [];
        }

        convs.forEach(item => {
            this.all_convs.push(item);
            this.view.indexes_order.push(item.id);
        });
    }

    async _loadNext() {
        // хз какой тут оффсет может быть
        let convs = await this.getConversations(this.loaded_convs_count);
        this._appendConvs(convs);
    }

    async init() {
        this.view = new ConversationsViewModel();
        await this._loadNext();
        //this.view._st();
    }

    // когда перезагрузится страница то всё равно в другом порядке будет
    swapConvs(conv_1, conv_2) {

    }

    _findConv(id) {
        const _l = this.all_convs.filter(itm => {return itm.peer.id == id});
        if (_l[0] == undefined) {
            throw Error('Not found chat')
        }
    
        return _l[0];
    }

    async _findConvFromApi(id) {
        try {
            return this._findConv(id)
        } catch(e) {}

        const b = await ChatGeneralForm.resolveByIdAndReturnClass(id)
        if (!b) {
            throw Error('Not found chat')
        }

        const c = new Conversation({
            'peer': b
        })
    
        this.all_convs.push(c)

        return c
    }

    // Есть общий список со всеми переписками и есть массив с их порядком
    get convs() {
        /*const _ret = [];
        this.view.indexes_order().forEach(id => {
            _ret.push(this._findConv(id));
        });*/

        return this.all_convs.slice(0).sort((a, b) => {
            //console.log(a.peer.full_name, a.last_updated, '\n', b.peer.full_name, b.last_updated, Number(a.last_updated), Number(b.last_updated))
            return Number(b.last_updated) - Number(a.last_updated);
        });
    }

    hasAppeared(container) {
        return container.querySelector('.crp-list') != null;
    }

    appear(container) {
        container.classList.remove('hidden');
        if (this.hasAppeared(container)) {
            return;
        }

        this.node = container.insertAdjacentHTML('beforeend', this.template);
        document.documentElement.scroll({
            top: 0
        })

        ko.applyBindings(this.view, container);
    }

    hide(container) {
        container.classList.add('hidden')
    }
}

class Conversation {
    constructor(conversation_item) {
        this._conversation = conversation_item.conversation;
        this._last_message = new ChatMessage(conversation_item.last_message);
        this.peer = conversation_item.peer;
    }

    get last_message() {
        try {
            if (this.peer) {
                return this.peer._getLatestChunk().latest_message;
            }
        // no mesages
        } catch(e) {}

        return this._last_message;
    }

    get conversation() {
        return this._conversation;
    }

    get last_updated() {
        return this.last_message.sent;
    }

    get id() {
        return this.peer.id;
    }
}
