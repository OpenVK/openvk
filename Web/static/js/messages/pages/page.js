export class IMTab {
    constructor() {
        this.render_class = null;
        this.options = {};
    }

    getName() {
        return this.render_class.getTabName();
    }

    async render() {
        await this.render_class.render();
        this.render_class.is_rendered_firstly = true;
    }
}

export class IMPage {
    constructor() {
        this.container = null;
        this.id = null;
        this.is_rendered_firstly = false;
    }

    getTabName() { return "..." }
    async render(options = {}) {}
    static openTab(main_container, options = {}) {
        const new_class = new this();
        new_class.id = String(options.id ?? (new Date()).getTime());
        main_container.insertAdjacentHTML("beforeend", `<div class="im_page" data-id="${new_class.id}"></div>`);
        new_class.container = main_container.querySelector(`.im_page[data-id="${new_class.id}"]`);

        const tab = new IMTab();
        tab.render_class = new_class;
        tab.options = options;

        return tab;
    }
}
