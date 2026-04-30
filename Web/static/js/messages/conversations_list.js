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
                <div class="scroll_node crp-entry" data-bind="event: { click: function(data, event) { window.im.selectChat(this) } }">
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

    async getConversations() {
        // adding profiles to conversation items
        let convs = await window.OVKAPI.call("messages.getConversations", {
            extended: 1,
            fields: 'photo_100'
        });
        convs.items.forEach(item => {
            const _id = item.conversation.peer.id
            const author = find_author(_id, convs.profiles, convs.groups)

            item.peer = new ChatGeneralForm(author);
            console.log(item)
        })

        return convs.items;
    }

    async init() {
        this.convs = await this.getConversations();
        this.view = new ConversationsViewModel();
    }

    appear(container) {
        if (this.appeared) {
            container.classList.remove('hidden');

            return;
        }

        this.node = container.insertAdjacentHTML('beforeend', this.template);
        this.appeared = true;

        // todo unapply when unused???
        ko.applyBindings(this.view, container);
    }

    hide(container) {
        container.classList.add('hidden')
    }
}
