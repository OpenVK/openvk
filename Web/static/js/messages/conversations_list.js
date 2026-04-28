window.conversations = new (class {
  constructor() {
    this.template = Handlebars.compile(`
<div class="crp-list scroll_container">
  {{#each items as | conversation convId | }}
    <div class="conversation">
      <span>{{convId}}</span>
    </div>
  {{/each}}
</div>
      `);
  }

  async getConversations() {}

  async render(container = null) {
    // todo change
    if (window.OVKAPI == null) {
      await new Promise((resolve) => setTimeout(resolve, 1000));
    }

    const convs = await window.OVKAPI.call("messages.getConversations", {
      extended: 1,
    });
    console.log(convs);
    container.append(this.template(convs));
  }
})();
