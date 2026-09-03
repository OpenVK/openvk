//import { ChatGeneralForm, ChatMessage } from '../components/messages.js';
const { ChatGeneralForm, ChatMessage } = await es6import_Im(import.meta.url, '../components/messages.js');
//import { html, render } from '../components/render.js';
const { html, render } = await es6import_Im(import.meta.url, '../components/render.js');
//import { IMTab, IMPage } from './page.js';
const { IMTab, IMPage } = await es6import_Im(import.meta.url, './page.js');
//import { SearchPageTemplate } from "../components/extra.js";
const { SearchPageTemplate } = await es6import_Im(import.meta.url, "../components/extra.js");

export class SearchPage extends IMPage {
    constructor() {
        super();

        this.has_appeared = false;
        this.items = null;
        this.total_count = null;
    }

    static getPageId() { return "search";}
    shouldCloseOnExit() { return true; }

    async beforeRender() {
        if (this.items == null) {
            this.params = this._getParams(this.options.q, this.options.peer_id, 0, 25, this.options.date);
            
            let items = await this.search(this.params);

            this.items = [];
            items.items.forEach(item => {
                this.items.push(item);
            });

            this.total_count = items.count; 
        }
    }

    async render(container) {
        this.getNode().addClass("page-other");
        render(html`<${SearchPageTemplate} 
            q=${this.options.q} 
            date=${this.options.date}
            c=${this}
            onSearch=${(q, date) => this.onSearch(q, date)}
            onCancel=${() => this.onCancel()}
            />`, container);
    }

    _getParams(q, peer_id = null, offset = 0, perPage = 25, date = null) {
        const p = {
            "q": q || " ",
            "offset": offset,
            "count": perPage,
            "extended": 1,
            "fields": ChatGeneralForm.BASE_FIELDS
        };
        if (peer_id) { p["peer_id"] = peer_id; }
        if (date) { p["date"] = date; }
        if (window.im.state.getId() < 0) { p["group_id"] = Math.abs(window.im.state.getId()); }
        return p; 
    }

    async onSearch(query, date = null) {
        this.options.q = typeof query === "string" ? query : (query?.target?.value ?? "");
        if (date !== null && date !== undefined) {
            this.options.date = date;
        }
        this.items = null;

        await this.beforeRender(this.container);
        await this.render(this.container);
    }

    onCancel() {
        if (window.im) {
            window.im.selectTab("conversations");
        }
    }

    async search(params) {
        let vals = {};
        try {
            vals = await window.OVKAPI.call("messages.search", params);
        } catch(e) {
            return {
                "items": [],
                "count": 0,
                "error": String(e),
            };
        }

        if (vals.profiles || vals.groups) {
            window.im.cached_profiles._moveToProfileCache(vals.profiles, vals.groups, false);
        }

        const _l = _authorize(vals.items, null, null,
            (item) => {
                return item.from_id;
            },
            (item, author) => {
                item.sender = author;
            },
            (item, arr) => {
                arr.push(new ChatMessage(item));
            }
        );

        return {
            "count": vals.count || 0,
            "items": _l
        };
    }

    async moveOffset(e) {
        if (e && e.target) toggleUnclickability(e.target, true);

        let new_offset = (this.params.offset || 0) + (this.params.count || 25);
        this.params.offset = new_offset;
        const items = await this.search(this.params);
        items.items.forEach(item => {
            this.items.push(item);
        });

        if (e && e.target) toggleUnclickability(e.target, false);

        await this.render(this.container);
    }
}
