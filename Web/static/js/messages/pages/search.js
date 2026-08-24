import { ChatGeneralForm, ChatMessage } from '../components/messages.js';
import { html, render } from '../components/render.js';
import { IMTab, IMPage } from './page.js';
import { SearchPageTemplate } from "../components/extra.js";

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
            this.params = this._getParams(this.options.q, this.options.peer_id);
            
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
            c=${this}
            onSearch=${(e) => {this.onSearch(e)}}
            />`, container);
    }

    _getParams(q, peer_id = null, offset = 0, perPage = 25, date = null) {
        const p = {
            "q": q,
            "offset": offset,
            "count": perPage,
            "extended": 1,
            "fields": ChatGeneralForm.BASE_FIELDS
        };
        if (peer_id) { p["peer_id"] = peer_id; }
        if (window.im.state.getId() < 0) { p["group_id"] = Math.abs(window.im.state.getId()); }
        return p; 
    }

    async onSearch(event) {
        this.options.q = event.target.value;
        this.items = null;

        await this.beforeRender(this.container);
        await this.render(this.container);
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

        window.im.cached_profiles._moveToProfileCache(vals.profiles, vals.groups, false);

        const _l = _authorize(vals.items, null, null,
            (item) => {
                return item.from_id;
            },
            (item, author) => {
                item.sender = new ChatGeneralForm(author);
            },
            (item, arr) => {
                arr.push(new ChatMessage(item));
            }
        );

        return {
            "count": vals.count,
            "items": _l
        };
    }

    async moveOffset(e) {
        toggleUnclickability(e.target, true);

        let new_offset = this.params.offset + this.params.count;
        this.params.offset = new_offset;
        const items = await this.search(this.params);
        items.items.forEach(item => {
            this.items.push(item);
        });

        toggleUnclickability(e.target, false);

        this._render();
    }
}
