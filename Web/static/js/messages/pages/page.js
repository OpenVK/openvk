export class IMTab {
    constructor() {
        this.render_class = null;
        this.options = {};
    }

    getName() {
        return this.render_class.getTabName();
    }

    updateHeader(header) {
        return this.render_class.updateHeader(header);
    }

    async render() {
        await this.render_class.wRender();
        this.render_class.is_rendered_firstly = true;
    }

    visible() {
        if (this.render_class.isVisibleWhenHidden()) {
            return true;
        }

        return this.isActive();
    }

    shouldClose() {
        return this.render_class.shouldCloseOnExit() || this.render_class.container == null;
    }

    isDisablesScroll() {
        return this.render_class.isDisablesScroll();
    }

    close() {
        window.im.tabs = window.im.tabs.filter(tab => tab != this);
    }

    getId() {
        return this.render_class.id;
    }

    getPageId() {
        return this.render_class.constructor.getPageId();
    }

    isActive() {
        return window.im.selectedTabId == window.im.tabs.indexOf(this);
    }
}

export class IMPage {
    constructor() {
        this.container = null;
        this.id = null;
        this.is_rendered_firstly = false;
        this.options = {};
    }

    async wRender(options = {}) {
        this.container.classList.remove("hidden");

        if (this.is_rendered_firstly == true) {
            await this.render(this.container);
            return;
        }

        await this.beforeRender(this.container);
        await this.render(this.container);
        //document.documentElement.scroll({ top: 0 });
    }
    getNode() { return u(this.container) }
    async update(options = {}) { await this.wRender(options); }
    updateHeader(header) { header.changeByConvNumber(0); }
    isVisibleWhenHidden() { return false; }
    shouldCloseOnExit() { return this.container == null; }
    isDisablesScroll() { return false; }
    static getPageId() { return "default"; }
    getTabName() { return tr("messenger_tab_" + this.constructor.getPageId()) }
    async beforeRender(container) {}
    async render(container) {}
    afterOpen() {}
    static openTab(main_container, options = {}) {
        const new_class = new this();
        new_class.id = String(options.id ?? (new Date()).getTime());
        new_class.options = options;
        main_container.querySelector("#im_page_containers").insertAdjacentHTML("beforeend", `<div class="im_page" data-id="${new_class.id}"></div>`);
        new_class.container = main_container.querySelector(`.im_page[data-id="${new_class.id}"]`);

        const tab = new IMTab();
        tab.render_class = new_class;
        tab.options = options;

        new_class.afterOpen();

        return tab;
    }
    addLoadSkeleton(container) { 
        container.insertAdjacentHTML("beforeend", `<span id="load_skeleton">LOADING!!!!!</span>`);
    }
    removeLoadSkeleton(container) { container.querySelector("#load_skeleton").remove(); }

}
