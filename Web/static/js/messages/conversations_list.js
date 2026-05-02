class ConversationsViewModel {
    constructor() {
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
        this.convs.forEach(item => {
            if (item.peer.id == sel) {
                _ = item;
            }
        })

        if (_) {
            return _.peer;
        }

        return await ChatGeneralForm.resolveById(sel);
    }

    async getConversations() {
        // adding profiles to conversation items
        let convs = await window.OVKAPI.call("messages.getConversations", {
            extended: 1,
            fields: 'photo_100'
        });

        _authorize(convs, (item) => {
            return item.conversation.peer.id
        }, (item, author) => {
            item.peer = new ChatGeneralForm(author);
        });

        return convs.items;
    }

    async init() {
        this.convs = await this.getConversations();
        this.view = new ConversationsViewModel();
    }

    appear(container) {
        container.classList.remove('hidden');
        if (this.appeared) {
            return;
        }

        this.node = container.insertAdjacentHTML('beforeend', this.template);
        this.appeared = true;

        ko.applyBindings(this.view, container);
    }

    hide(container) {
        container.classList.add('hidden')
    }
}
