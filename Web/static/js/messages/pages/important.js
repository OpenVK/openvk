const { ChatGeneralForm, ChatMessage } = await es6import_Im(import.meta.url, '../components/messages.js');
const { html, render } = await es6import_Im(import.meta.url, '../components/render.js');
const { IMPage } = await es6import_Im(import.meta.url, './page.js');
const { SearchMessageItem } = await es6import_Im(import.meta.url, '../components/extra.js');

export class ImportantPage extends IMPage {
    constructor() {
        super();
        this.items = null;
        this.total_count = null;
        this.offset = 0;
        this.count = 25;
        this.query = "";
        this.isLoading = false;
    }

    static getPageId() { return "important"; }
    getName() { return tr("important_messages"); }
    shouldCloseOnExit() { return true; }
    visible() { return true; }

    async loadMessages(offset = 0, isAppend = false) {
        if (this.isLoading) return;
        this.isLoading = true;
        let res = {};
        try {
            res = await window.OVKAPI.call("messages.getImportantMessages", {
                offset: offset,
                count: this.count,
                extended: 1,
                fields: ChatGeneralForm.BASE_FIELDS
            });
        } catch (e) {
            console.error("Failed to load important messages:", e);
            res = { count: 0, items: [], profiles: [], groups: [] };
        }

        if (res.profiles || res.groups) {
            window.im.cached_profiles._moveToProfileCache(res.profiles, res.groups, false);
        }

        const rawItems = res.messages ? res.messages.items : (res.items || []);
        const authorized = [];
        (rawItems || []).forEach(item => {
            const author = window.im.cached_profiles ? window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(item.from_id) : null;
            item.sender = author;
            authorized.push(new ChatMessage(item));
        });

        if (isAppend) {
            this.items = (this.items || []).concat(authorized);
        } else {
            this.items = authorized;
        }

        this.offset = offset + rawItems.length;
        this.total_count = res.messages ? res.messages.count : (res.count || this.items.length);
        this.isLoading = false;
    }

    async beforeRender() {
        if (this.items == null) {
            await this.loadMessages(0, false);
        }
    }

    async moveOffset() {
        await this.loadMessages(this.offset, true);
        await this.render(this.container);
    }

    onSearch(query) {
        this.query = typeof query === "string" ? query : (query?.target?.value ?? "");
        this.render(this.container);
    }

    onCancel() {
        if (window.im) {
            const myTab = (window.im.tabs || []).find(t => t.render_class === this);
            if (myTab) {
                myTab.close();
            }

            const targetPageId = this.options?.referrer || "conversations";
            const targetTab = window.im.getTab(targetPageId) || window.im.getTab("conversations");
            if (targetTab) {
                window.im.selectTab(targetTab);
            } else {
                window.im.openTabByName("conversations");
            }
        }
    }

    async render(container) {
        this.getNode().addClass("page-other");

        const query = this.query || "";
        const allItems = this.items || [];
        const filteredItems = query.trim() ? allItems.filter(msg => {
            const q = query.trim().toLowerCase();
            const text = (typeof msg.getText === 'function' ? msg.getText(true) : (msg.data?.text || msg.text || '')).toLowerCase();
            const senderName = (msg.sender?.getName ? msg.sender.getName() : '').toLowerCase();
            const chatTitle = (msg.data?.title || msg.title || '').toLowerCase();
            return text.includes(q) || senderName.includes(q) || chatTitle.includes(q);
        }) : allItems;

        const count = this.total_count || allItems.length;
        const loaded_count = allItems.length;

        const handleKeyDown = (e) => {
            if (e.key === "Enter") {
                this.onSearch(e.target.value);
            }
        };

        const handleClear = (e) => {
            const input = e.target.closest('.im-search-input-box')?.querySelector('input');
            if (input) {
                input.value = "";
                input.focus();
            }
            this.onSearch("");
        };

        const handleSearchClick = (e) => {
            const input = e.target.closest('.im-search-toolbar')?.querySelector('.im-search-input');
            this.onSearch(input ? input.value : query);
        };

        render(html`
            <div id="search-page-im" class="important-page-im">
                <div class="im-search-toolbar">
                    <div class="im-search-input-box">
                        <input 
                            type="text" 
                            class="search_input im-search-input" 
                            placeholder="${tr('search_messages_tab')}" 
                            value="${query}" 
                            onInput=${(e) => this.onSearch(e.target.value)}
                            onKeyDown=${handleKeyDown}
                        />
                        ${query ? html`<div class="im-search-clear" title="${tr('clear')}" onClick=${handleClear}>×</div>` : ""}
                    </div>
                    <input 
                        type="button" 
                        class="button im-search-btn" 
                        value="${tr('search_messages_tab')}" 
                        onClick=${handleSearchClick} 
                    />
                    <div class="im-search-cancel-btn">
                        <a onClick=${() => this.onCancel()}>${tr('cancel')}</a>
                    </div>
                </div>

                <div class="im-search-results">
                    ${filteredItems.length === 0 ? html`
                        <div class="im-search-empty">
                            ${query ? tr('im_search_not_found') : tr('no_important_messages')}
                        </div>
                    ` : filteredItems.map((msg) => html`
                        <${SearchMessageItem} msg=${msg} query=${query} />
                    `)}
                </div>

                ${(loaded_count < count && !query.trim()) ? html`
                    <div onClick=${() => this.moveOffset()} class="show_more crp-load-more">
                        ${tr('show_next')}
                    </div>
                ` : null}
            </div>
        `, container);
    }
}
