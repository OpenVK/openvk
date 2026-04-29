class ConversationsViewModel {
    constructor(initial = []) {
        this.items = initial.items
        this.conversations = ko.observableArray(this.convs);
    }

    selectChat(data) {
        this.openMessenger(data)
    }

    openMessenger(conv) {
        console.log('ТЫ ТОЛЬКО ЧТО КЛИКНУЛ НА !!! ', conv)
    }
}

window.conversations = new (class {
  constructor() {
    this.template = `
    <div class="crp-list scroll_container">
        <div data-bind="foreach: window.conversations.view.items">
            <div class="scroll_node crp-entry" data-bind="event: { click: function(data, event) { window.conversations.view.selectChat(this) } }">
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
        const author = find_author(_id, convs.profiles, [])

        item.peer = new ChatGeneralForm(author);
        console.log(item)
    })

    return convs;
  }

  async render(container = null) {
    container.insertAdjacentHTML('beforeend', this.template)
    // todo change
    if (window.OVKAPI == null) {
        await new Promise((resolve) => setTimeout(resolve, 1000));
    }

    const convs = await this.getConversations();
    this.view = new ConversationsViewModel(convs);
    console.log(conversations.view.convs)
    ko.applyBindings(this.view, container);
  }
})();
