const { ChatGeneralForm, ChatMessage } = await es6import_Im(import.meta.url, '../components/messages.js');
const { html, render } = await es6import_Im(import.meta.url, '../components/render.js');
const { IMPage } = await es6import_Im(import.meta.url, './page.js');

export class ImportantPage extends IMPage {
    constructor() {
        super();
        this.items = null;
        this.total_count = null;
    }

    static getPageId() { return "important"; }
    getName() { return tr("important_messages") || "Важные"; }
    shouldCloseOnExit() { return false; }
    visible() { return true; }

    async beforeRender() {
        if (this.items == null) {
            let res = {};
            try {
                res = await window.OVKAPI.call("messages.getImportantMessages", {
                    count: 30,
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
            const _l = _authorize(rawItems, null, null,
                (item) => item.from_id,
                (item, author) => { item.sender = author; },
                (item, arr) => { arr.push(new ChatMessage(item)); }
            );

            this.items = _l;
            this.total_count = res.messages ? res.messages.count : (res.count || _l.length);
        }
    }

    async render(container) {
        this.getNode().addClass("page-other");
        render(html`
            <div class="important-messages-page">
                <div class="chat-tab-2-header" style="padding: 10px 14px; border-bottom: 1px solid var(--bg-slightly-border); display: flex; justify-content: space-between; align-items: center;">
                    <b>${tr("important_messages") || "Важные сообщения"} (${this.total_count || 0})</b>
                </div>
                <div class="important-messages-list" style="padding: 10px 14px;">
                    ${(!this.items || this.items.length === 0) ? html`
                        <div style="padding: 30px; text-align: center; color: #888;">${tr("no_important_messages") || "Здесь пока нет важных сообщений."}</div>
                    ` : this.items.map(msg => html`
                        <div class="important-msg-card" style="padding: 8px 12px; margin-bottom: 8px; border: 1px solid var(--bg-slightly-border); border-radius: 3px; background: var(--bg-page); cursor: pointer;" onClick=${() => { window.im.messenger.goToMessage(msg); }}>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 11px;">
                                <b style="color: var(--link-color, #2a5885);">${msg.sender?.getName ? msg.sender.getName() : ('id' + msg.from_id)}</b>
                                <span style="color: #888;">${msg.getDate(2)}</span>
                            </div>
                            <div class="important-msg-text" dangerouslySetInnerHTML=${{ __html: msg.getText(false) }} style="font-size: 12px;" />
                        </div>
                    `)}
                </div>
            </div>
        `, container);
    }
}
