import { render, html, MessageBubble } from './components.js';
import { ChatGeneralForm, ChatMessage } from './messages.js';

export const SearchPage = ({ }) => {
    const query = window.im.conversations.q;
    const count = window.im.search.total_count;
    const items = window.im.search.items;
    const loaded_count = items.length;

    return html`
        <div id="search-page-im">
            <div class="search-up">
                <input class="search_input" onChange=${(e) => { window.im.conversations._onMessagesSearch(e) }} type="text" default="${tr('search_messages')}" value="${query}" />
            </div>
            <div class="search-summary">
                <b>${tr("messages_search_count", count)}</b>
            </div>
            <div>
                ${items.map((msg) => {
                    return html`<${MessageBubble} msg=${msg} />`
                })}
            </div>
            ${loaded_count < count && html`
            <div onClick=${(e) => { window.im.search.moveOffset() }} class="show_more crp-load-more">
                ${tr('show_next')}
            </div>`}
        </div>
  `;
};

export class SearchTab {
    constructor() {
        this.has_appeared = false;
        this.items = null;
        this.total_count = null;
    }

    appear(container) {
        console.log("IM | Search | Tab appear");

        this.items = null;
        this.container = container;
        this.params = {};

        this._render();
    }

    async _render() {
        if (this.items == null) {
            this.params = this._getParams(window.im.conversations.q, null);
            const items = await this.search(this.params);
            this.items = [];
            items.items.forEach(item => {
                this.items.push(item);
            });

            this.total_count = items.count;
        }

        render(html`<${SearchPage} />`, this.container);
    }

    _getParams(q, peer_id = null, offset = 0, perPage = 25, date = null) {
        return {
            "q": q,
            "peer_id": peer_id ?? 0,
            "offset": offset,
            "count": perPage,
            "extended": 1,
            "fields": ChatGeneralForm.base_fields
        };
    }

    async search(params) {
        const vals = await window.OVKAPI.call("messages.search", params);

        window.im.cached_profiles._moveToProfileCache(vals.profiles, vals.groups);

        const _l = _authorize(vals.items, vals.profiles, vals.groups,
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
        e.target.classList.add("lagged");

        let new_offset = this.params.offset + this.params.count;
        this.params.offset = new_offset;
        const items = await this.search(this.params);
        items.items.forEach(item => {
            this.items.push(item);
        });

        e.target.classList.add("remove");

        this._render();
    }
}
