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

    showTab(root) {
        if (this.render_class && this.render_class.container) {
            this.render_class.container.classList.remove("hidden");
            this.render_class.showHook();
        }
    }

    shouldClose() {
        return this.render_class.shouldCloseOnExit() || this.render_class.container == null;
    }

    isDisablesScroll() {
        return this.render_class.isDisablesScroll();
    }

    close() {
        if (this.render_class && this.render_class.container) {
            try {
                this.render_class.container.remove();
            } catch (e) {}
        }
        window.im.tabs = window.im.tabs.filter(tab => tab != this);
        if (window.im && typeof window.im.updateTabs === 'function') {
            window.im.updateTabs();
        }
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

    async wRender(options = {}, is_update = false) {
        // this.container.classList.remove("hidden");
        if (this.is_rendered_firstly == true) {
            await this.render(this.container);
            return;
        }

        await this.beforeRender(this.container);
        await this.render(this.container);
        await this.afterFirstRender(this.container);
        //document.documentElement.scroll({ top: 0 });
    }
    getNode() { return u(this.container) }
    async update(options = {}) { await this.wRender(options, true); }
    updUrl() {}
    updateHeader(header) { header.changeByConvNumber(0); }
    isVisibleWhenHidden() { return false; }
    shouldCloseOnExit() { return this.container == null; }
    isDisablesScroll() { return window.im.state.is_compact_mode_enabled == true; }
    static getPageId() { return "default"; }
    getTabName() { return tr("messenger_tab_" + this.constructor.getPageId()) }
    async beforeRender(container) {}
    async render(container) {}
    async afterFirstRender(container) {}
    afterOpen() {}
    changeContainer(main_container) {
        if (!main_container) return;
        const pageContainers = main_container.querySelector("#im_page_containers");
        if (pageContainers) {
            const existing = pageContainers.querySelector(`.im_page[data-id="${this.id}"]`);
            if (existing) {
                this.container = existing;
            } else {
                pageContainers.insertAdjacentHTML("beforeend", `<div class="im_page" data-id="${this.id}"></div>`);
                this.container = main_container.querySelector(`.im_page[data-id="${this.id}"]`);
            }
        }
    }
    static openTab(main_container, options = {}) {
        const new_class = new this();
        new_class.id = String(options.id ?? (new Date()).getTime());
        new_class.options = options;

        new_class.changeContainer(main_container);

        const tab = new IMTab();
        tab.render_class = new_class;
        tab.options = options;

        new_class.afterOpen();

        return tab;
    }
    addLoadSkeleton(container, remove_before = false) {
        if (!container) return;
        if (remove_before == true) { container.innerHTML = ""; }
        container.insertAdjacentHTML("afterbegin", `<div id="load_skeleton" class="im_page_loader"><img src="/assets/packages/static/openvk/img/loading_mini.gif" alt="..." /></div>`);
    }
    removeLoadSkeleton(container) { 
        try { 
            if (container && container.querySelector && container.querySelector("#load_skeleton")) {
                container.querySelectorAll("#load_skeleton").forEach(el => el.remove());
            }
        } catch(e) {}
        try { 
            document.querySelectorAll("#load_skeleton").forEach(el => el.remove());
        } catch(e) {}
    }
    showHook() {}
}
