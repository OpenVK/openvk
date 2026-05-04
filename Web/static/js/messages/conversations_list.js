class ConversationsViewModel {
    constructor() {
        this.indexes_order = ko.observableArray([]);
        // todo поменять
    }

    _st() {
        this.conversations = ko.observableArray(window.im.conversations.convs);
    }
}

class Conversations {
    constructor() {
        this.template = `
        <div class="crp-list scroll_container">
            <div data-bind="foreach: window.im.conversations.convs">
                <div class="scroll_node crp-entry" data-bind="event: { click: async function(data, event) { await window.im.selectChat(this) } }">
                    <div class="crp-entry--image">
                        <img data-bind="attr: { src: peer.avatar_any }"
                        loading="lazy" />
                    </div>
                    <div class="crp-entry--info">
                        <a data-bind="attr: { href: peer.chat_url }, html: peer.name "></a><br/>
                    </div>
                    <div class="crp-entry--message"></div>
                </div>
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

    async getConversations() {
        // adding profiles to conversation items
        let convs = await window.OVKAPI.call("messages.getConversations", {
            extended: 1,
            fields: ChatGeneralForm.base_fields
        });

        return _authorize(convs, (item) => {
            return item.conversation.peer.id
        }, (item, author) => {
            item.peer = new ChatGeneralForm(author);
        }, (item, lists) => {
            lists.push(new Conversation(item));
        });;
    }

    async init() {
        this.all_convs = await this.getConversations();
        // new -> old
        this.view = new ConversationsViewModel();
        this.all_convs.forEach(item => {
            this.view.indexes_order.push(item.id);
        });
        this.view._st();
    }

    // когда перезагрузится страница то всё равно в другом порядке будет
    swapConvs(conv_1, conv_2) {

    }

    _findConv(id) {
        const _l = this.all_convs.filter(itm => {return itm.peer.id == id});

        return _l[0];
    }

    // Есть общий список со всеми переписками и есть массив с их порядком
    get convs() {
        const _ret = [];
        this.view.indexes_order().forEach(id => {
            _ret.push(this._findConv(id));
        });

        return _ret;
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

        ko.applyBindings(this.view, container);
    }

    hide(container) {
        container.classList.add('hidden')
    }
}
