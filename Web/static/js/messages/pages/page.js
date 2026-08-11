export class IMTab {
    constructor() {
        this.render_class = null;
        this.options = {};
    }

    getName() {
        return this.render_class.getTabName();
    }

    async render() {
        await this.render_class.wRender();
        this.render_class.is_rendered_firstly = true;
    }

    visible() {
        return true;
    }

    getId() {
        return this.render_class.id;
    }

    isActive() {
        return window.im.selectedTabId == window.im.tabs.indexOf(this);
    }
}

export class IMPage {
    static getPageId() {
        return "default";
    }

    constructor() {
        this.container = null;
        this.id = null;
        this.is_rendered_firstly = false;
    }

    async wRender(options = {}) {
        this.container.classList.remove("hidden");

        if (this.is_rendered_firstly == true) {
            this.render(this.container);
            return;
        }

        this.render(this.container);
        //document.documentElement.scroll({ top: 0 });
    }

    getTabName() { return this.constructor.getPageId() }
    async render(options = {}) {}
    afterOpen() {}
    static openTab(main_container, options = {}) {
        const new_class = new this();
        new_class.id = String(options.id ?? (new Date()).getTime());
        main_container.querySelector("#im_page_containers").insertAdjacentHTML("beforeend", `<div class="im_page" data-id="${new_class.id}"></div>`);
        new_class.container = main_container.querySelector(`.im_page[data-id="${new_class.id}"]`);

        const tab = new IMTab();
        tab.render_class = new_class;
        tab.options = options;

        new_class.afterOpen();

        return tab;
    }
}
