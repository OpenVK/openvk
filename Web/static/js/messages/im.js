//import { ChatGeneralForm } from './components/messages.js';
const { ChatGeneralForm } = await es6import_Im(import.meta.url, './components/messages.js');
//import { FastChatsBar, FastChatsWindow } from './components/extra.js';
const { FastChatsRoot } = await es6import_Im(import.meta.url, './components/fastchats.js');
//import { EventHandler } from './events.js';
const { EventHandler } = await es6import_Im(import.meta.url, './events.js');
//import { Messenger, MessengerPage, ContactPage, ChatTopicPreviewPage } from './pages/messenger.js';
const { Messenger, MessengerPage, ContactPage, ChatTopicPreviewPage } = await es6import_Im(import.meta.url, './pages/messenger.js');
//import { Conversations, ConversationsPage } from './pages/conversations.js';
const { Conversations, ConversationsPage, Conversation } = await es6import_Im(import.meta.url, './pages/conversations.js');
//import { Friends, FriendsPage } from './pages/friends.js';
const { Friends, FriendsPage } = await es6import_Im(import.meta.url, './pages/friends.js');
const { SearchPage } = await es6import_Im(import.meta.url, './pages/search.js');
const { ImportantPage } = await es6import_Im(import.meta.url, './pages/important.js');
const { ChatInvitePreviewPage } = await es6import_Im(import.meta.url, './pages/invite.js');
//import { IMTab, IMPage } from './pages/page.js';
const { IMTab, IMPage } = await es6import_Im(import.meta.url, './pages/page.js');
//import { TabBar } from './components/common.js';
const { TabBar } = await es6import_Im(import.meta.url, './components/common.js');
//import { html, render as preactRender } from './components/render.js';
const { html, render } = await es6import_Im(import.meta.url, './components/render.js');
const { imLog, isImVerboseLogging } = await es6import_Im(import.meta.url, './logger.js');

const preactRender = render;
//const tr = window.tr;
//const u = window.u;

export { imLog, isImVerboseLogging };

export function isImWarningRemoved() {
    return localStorage.getItem("tw.im.remove_warning") === "1" || localStorage.getItem("im.remove_warning") === "1";
}

export function isVerboseLogging() {
    return isImVerboseLogging();
}

export class InstantMessagesAndRelated {
    constructor(group_id = null) {
        this.tabs = [];
        this.selectedTabId = null;

        this.header = new YellowHeader();
        this.root = null;

        this.usage_type = "current_user";
        this.usage_id = null;
        this.report_data = null;

        //this.current = new Currentness(this);
        this.cached_profiles = new ProfilesCache();
        this.event_handler = new EventHandler(this);
        this.state = new IMState(this, group_id);

        this.log = imLog;
        this.isReady = false;
        this.is_initing = false;
        this.conversations = new Conversations();
        this.messenger = new Messenger();
        this.friends = new Friends();

        this.fastChats = new FastChats();
    }

    get is_ready() {
        return this.isReady;
    }

    async waitLoad() {
        return new Promise(resolve => {
            const check = () => {
                if (this.isReady == true) {
                    resolve();
                } else {
                    setTimeout(check, 100);
                }
            };
            check();
        });
    }

    async init(do_init = true, ignore_initless = false) {
        if (this.is_initing == true || this.isReady == true) {
            return;
        }

        if (!isImWarningRemoved() && !ignore_initless) {
            imLog("Init skipped: agreement (im.remove_warning) is not accepted");
            return;
        }

        // иначе будут лишние запросы
        try {
            if (window.openvk && window.openvk.current_id == 0) {
                this.isReady = true;
                return;
            }

            if (!ignore_initless) {
                if (window.openvk.disable_ajax == 1 || Number(localStorage.getItem('ux.disable_ajax_routing') ?? 0) == 1 || Number(localStorage.getItem('tw.im.disable_messenger') ?? 0) == 1) {
                    if (location.pathname != "/im") {
                        this.isReady = true;
                        return;
                    }
                }
            }
            if (!do_init) {
                return;
            }
        } catch (e) {
            console.error(e);
        }

        this.is_initing = true;
        imLog("IM | Init", this.state);

        if (window.OVKAPI == null) {
            await new Promise((resolve) => setTimeout(resolve, 1000));
        }

        await this.state._loadCurrent();

        try {
            await this.conversations.loadNext(this);
        } catch (e) {
            fastError(String(e));
        }

        if (this.lp) {
            this.lp.stop();
        }

        this.lp = new LongPollConnection(this);
        await this.lp.create(Math.abs(this.state.group_id));
        this.lp.listen();

        this.state._updateCounter(this.lp.getFirstCounter());

        this.isReady = true;
        this.is_initing = false;
        imLog("Inited");
    }

    static async insertAsReport(container, data) {
        return await InstantMessagesAndRelated.insertIn(container, null, false, true, data);
    }

    static async insertIn(container, as = null, fastchat = false, rewrite_tabs = true, report_data = null) {
        if ((!window.openvk || window.openvk.current_id == 0) && report_data == null) {
            const skeleton = container ? container.querySelector("#load_skeleton") : null;
            if (skeleton) skeleton.remove();
            return null;
        }

        let self = window.im_variants.getCurrentUser();
        const b = new URL(location.href);

        if (!isImWarningRemoved() && report_data == null) {
            if (fastchat) {
                return null;
            }

            const accepted = await self._showAgreement();
            if (!accepted) {
                const skeleton = container ? container.querySelector("#load_skeleton") : null;
                if (skeleton) skeleton.remove();

                if (container) {
                    const titleText = (typeof tr === 'function' ? tr('messages_agreement_declined_title') : null) || 'Соглашение отклонено';
                    const descText = (typeof tr === 'function' ? tr('messages_agreement_declined') : null) || 'Вы отклонили пользовательское соглашение сообщений. Чтобы получить доступ к сообщениям, необходимо принять соглашение.';
                    const btnText = (typeof tr === 'function' ? tr('messages_agreement_accept_btn') : null) || 'Принять соглашение';

                    container.innerHTML = `
                        <div class="container_gray" style="margin-top: -10px;">
                            <center style="background: white; border: #DEDEDE solid 1px; padding: 25px 20px;">
                                <img src="/assets/packages/static/openvk/img/oof.apng" style="width: 120px; max-width: 25%; margin-bottom: 10px;" />
                                <h3 style="margin: 0 0 10px; color: #333; font-size: 15px;">${escapeHtml(titleText)}</h3>
                                <span style="color: #707070; margin: 0 0 15px; display: block; max-width: 480px; line-height: 1.4; font-size: 13px;">
                                    ${escapeHtml(descText)}
                                </span>
                                <button class="button" id="_im_accept_agreement_btn" style="margin-top: 5px;">${escapeHtml(btnText)}</button>
                            </center>
                        </div>
                    `;

                    const acceptBtn = container.querySelector("#_im_accept_agreement_btn");
                    if (acceptBtn) {
                        acceptBtn.onclick = async () => {
                            acceptBtn.classList.add("lagged");
                            container.innerHTML = `
                                <div id="load_skeleton" class="im_page_loader">
                                    <img src="/assets/packages/static/openvk/img/loading_mini.gif" alt="..." />
                                </div>
                            `;
                            await InstantMessagesAndRelated.insertIn(container, as, fastchat, rewrite_tabs, report_data);
                        };
                    }
                }

                return null;
            }
        }

        if (report_data != null) {
            self.report_data = report_data;
        }

        if (as != null) {
            imLog("?as= detected", as);
            self = window.im_variants.getForGroup(Number(as));
            window.im_variants.set(self);
            if (!self.isReady) { await self.init(); }

            b.searchParams.set("as", String(as));
        } else {
            window.im_variants.set(self);
            if (!self.isReady) { await self.init(true, report_data != null); }

            b.searchParams.delete("as");
        }

        self.state.isFastchat = fastchat;
        await self.waitLoad();

        imLog("Insert in", container, "fastchat:", fastchat);

        if (!container.querySelector("#im_container")) {
            const node = u(`<div id="im_container"><div id="im_page_tabs"></div><div id="im_page_containers"></div></div>`)
            if (!fastchat) { node.addClass("at_page"); }
            if (!fastchat && self.state.is_compact_mode_enabled == true) { node.addClass("compact"); }

            container.insertAdjacentHTML("beforeend", node.last().outerHTML);
        }

        if (rewrite_tabs == true) {
            self.rewriteTabs(container);
        }

        //const found = await this._checkSel(new URL(location.href), sel_id);
        //if (!found) {
        //    this.selectTab('conversations');
        //}

        try {
            await self.state._checkSel(b);
            self.state._changeHeight(self.root);
            self.state.removeLoadSkeleton(container);
        } catch (e) {
            console.error(e);
        }

        return self;
    }

    // вот там заглушку добавь, вот там глянь чёто отвалилось, вот там короче придумал добавь в функции аргумент чтобы сделать такое исключение в логике, чтобы мессенджер думал что он показывает от имени репортнувшего, оо пиздец бля, и ещё перезагрузи страницу 1000 раз
    static async reportShowMessageContext(event, report_id, peer_id, message_id, author_id) {
        const container = event.target.closest("#msg_context_place");
        event.target.remove();
        imLog("reportShowMessageContext", report_id, peer_id, message_id, author_id);

        const f = await InstantMessagesAndRelated.insertAsReport(container, {
            "report_id": report_id,
            "peer_id": peer_id,
            "message_id": message_id,
            "author_id": author_id
        });
        window.im = f;
        f.openTabByName("conversations");
        /*
        const messenger = new Messenger();
        messenger._window = new MessengerPage();
        messenger._window.container = container;

        const convo = new Conversation({
            "peer": await ChatGeneralForm.resolveByIdAndReturnClass(peer_id)
        });
        console.log(convo);
        messenger.setChat(convo);
        const c = await convo.getEndScrollPosition().loadOlder();
        convo.getEndScrollPosition().result();
        await messenger.view.render(container, {}, messenger);
        messenger.goToMessage({
            "peer_id": peer_id,
            "id": message_id
        }, convo, false);
        u(".messages--peers-tabs").attr("style", "display:none");
        //const im = await window.im_class.insertIn(document.querySelector("#msg_context_place"), null, false, false, global_id);*/
    }

    _showAgreement() {
        return new Promise((resolve, reject) => {
            const msg = new CMessageBox({
                title: tr('confirmation'),
                body: `
                    <p>${tr("messages_agreement_1")}</p>
                    <ul>
                        <li>${tr("messages_agreement_2")}</li>
                        <li>${tr("messages_agreement_3")}</li>
                        <li>${tr("messages_agreement_4")}</li>
                        <li>${tr("messages_agreement_5")}</li>
                    </ul>
                `,
                close_on_buttons: false,
                unique_name: 'agreement',
                buttons: [tr('cancel'), tr('ok')],
                callbacks: [() => {
                    msg.close();
                    resolve(false);
                }, () => {
                    localStorage.setItem("tw.im.remove_warning", "1");
                    localStorage.setItem("im.remove_warning", "1");
                    msg.close();
                    resolve(true);
                }]
            });
        });
    }

    rewriteTabs(container) {
        const oldRoot = this.root ? this.root : null;
        this.root = container.querySelector("#im_container");

        this.tabs.forEach(item => {
            try {
                if (this.root == oldRoot) {
                    return;
                }
                imLog("rewriteTabs root:", this.root, oldRoot);
                item.render_class.changeContainer(this.root);
                item.render();
            } catch (e) {
                console.error(e);
            }
        })
    }

    updateTabs() {
        this._renderTabBar();
    }

    _toCGF(obj) {
        return new ChatGeneralForm(obj);
    }

    _renderTabBar() {
        if (!this.root) return;

        let wrap = this.root.querySelector('#im_page_tabs');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'im_page_tabs';
            this.root.insertAdjacentElement('afterbegin', wrap);
        }

        preactRender(html`
        <${TabBar}
            tabs=${this.getVisibleTabs()}
            activeTab=${this.getSelectedTab()}
            onTabSelect=${(id) => this.selectTab(id)}
        />
        `, wrap);
    }

    selectTab(tab) {
        if (typeof tab != "number") {
            tab = this.tabs.indexOf(tab);
        }

        imLog("Selected tab " + tab);

        this.state._toggleScrollMode(false);

        this.selectedTabId = tab;
        if (this.root) {
            this.root.querySelectorAll("#im_page_containers .im_page").forEach(item => {
                imLog("Hide tab", item);
                item.classList.add("hidden");
            });
        }
        try {
            const _tab = this.tabs[tab];
            this.tabs.forEach(item => {
                if (_tab != item && item.shouldClose()) {
                    imLog("Closed tab", item);
                    item.close();
                }
            });

            if (_tab.isDisablesScroll()) { this.state._toggleScrollMode(true); }

            _tab.updateHeader(this.header);
            _tab.render_class.updUrl();

            const b = this.root ? this.root.querySelector(`#im_page_containers .im_page[data-id="${_tab.getId()}"]`) : null;
            if (!b && this.root) {
                imLog("Tab not found, inserting again", _tab);
                const pageContainers = this.root.querySelector("#im_page_containers");
                if (pageContainers) {
                    pageContainers.insertAdjacentHTML("beforeend", `
                        <div class="im_page" data-id="${_tab.getId()}"></div>
                    `);
                }
            }

            imLog("Show tab", _tab);
            if (this.root) {
                _tab.showTab(this.root);
            }
        } catch (e) {
            console.error(e);
        }

        this.updateTabs();
    }

    async openTabByName(tab, check_existing = true, options = {}) {
        let got_tab = null;
        let got_class = null;
        let already_here = null;

        switch (tab) {
            default:
                console.error("no tab with name: ", tab);
                break;
            case "settings":
                got_class = SettingsPage;
                break;
            case "conversations":
                got_class = ConversationsPage;
                break;
            case "messenger":
                got_class = MessengerPage;
                break;
            case "friends":
                got_class = FriendsPage;
                break;
            case "contact":
                got_class = ContactPage;
                break;
            case "search":
                got_class = SearchPage;
                break;
            case "chat_preview_topic":
                got_class = ChatTopicPreviewPage;
                break;
            case "important":
                got_class = ImportantPage;
                break;
            case "chat_invite":
                got_class = ChatInvitePreviewPage;
                break;
        }

        if (check_existing == true && got_class) {
            this.tabs.forEach(item => {
                if (item.getPageId() == got_class.getPageId()) {
                    already_here = item;
                }
            })
        }

        if (already_here != null) {
            if (options && options.q !== undefined && already_here.render_class && typeof already_here.render_class.onSearch === 'function') {
                already_here.render_class.onSearch(options.q, options.date ?? null);
            }
            this.selectTab(this.tabs.indexOf(already_here));

            return already_here;
        } else {
            try {
                if (!this.root) {
                    this.root = document.querySelector("#im_container");
                }
                got_tab = got_class.openTab(this.root, options);
                if (got_tab != null) {
                    if (this.root) got_tab.render_class.addLoadSkeleton(this.root);
                    imLog("Opened tab class:", got_tab.render_class);
                    this.selectTab(this.addTab(got_tab));
                    await got_tab.render();
                    if (this.root) got_tab.render_class.removeLoadSkeleton(this.root);
                }
            } catch (e) {
                console.error(e);
            }

            return got_tab;
        }

    }

    addTab(tab) {
        this.tabs.push(tab);

        return this.tabs.indexOf(tab);
    }
    getVisibleTabs() {
        const vals = this.tabs.filter(t => t.visible() && t.getPageId() != "conversations");
        return [this.getTab("conversations"), ...vals];
    }
    getSelectedTab(tab) { return this.tabs[this.selectedTabId]; }
    getSelectedTabId() {
        try {
            return this.getSelectedTab().getPageId()
        } catch (e) {
            console.error(e);
            return null;
        }
    }
    getTabs() { return this.tabs.map(t => t.id); }
    getTab(id) { return this.tabs.find(t => t.getPageId() == id); }
}

class IMVariants {
    constructor() {
        this.items = [];
        this.currentIndex = 0;
    }

    setByIndex(id) {
        this.currentIndex = id;
        window.im = this.items[id];
    }

    set(im_obj) {
        const id = this.items.indexOf(im_obj);
        this.setByIndex(id);
    }

    getCurrent() { return this.items[this.currentIndex]; }
    getCurrentUser() { return this.items[0]; }
    getForX(id) {
        if (id > 0) {
            return this.getCurrentUser();
        } else {
            return this.getForGroup(id);
        }
    }

    getForGroup(group_id) {
        let found = null;
        this.items.forEach(item => {
            imLog("Check item state:", item.state, group_id);
            if (item.state.group_id == group_id) {
                imLog("Found variant for group:", item.state, group_id);
                found = item;
            }
        });

        if (found == null) {
            found = new InstantMessagesAndRelated(group_id);
            found.usage_type = "group";
            this.add(found);
        }

        return found;
    }

    getCompromise() {
        if (window.im.state.is_active) {
            return window.im;
        } else {
            return this.getCurrentUser();
        }
    }

    add(item) { return this.items.push(item); }
}

class IMState {
    constructor(im_link, group_id = null) {
        this.link = im_link;
        this.items = [];
        this.item_index = 0;
        this.group_id = group_id;
        this.isFastchat = false;
    }

    getId() {
        try {
            return this.getOperator().id;
        } catch (e) {
            return window.openvk.current_id;
        }
    }
    get is_compact_mode_enabled() { return localStorage.getItem("tw.im.modern_mode") === "1"; }
    get is_debug() { return localStorage.getItem("tw.im.debug") === "1"; }
    get is_opened() { return location.pathname == "/im"; }
    get is_active() {
        try {
            return window.im.getSelectedTabId() == "messenger" && this.is_opened == true;
        } catch (e) { return false; }
    }
    get is_group() { return this.group_id != null }

    getUnreadCounter() {
        return this.unread_counter || 0;
    }

    async fetchUnreadCounter() {
        try {
            if (!window.OVKAPI) return this.unread_counter || 0;
            const params = {};
            if (this.group_id != null) {
                params.group_id = Math.abs(this.group_id);
            }
            const res = await window.OVKAPI.call('messages.getUnreadConversations', params);
            const count = (res && typeof res.count === 'number') ? res.count : (typeof res === 'number' ? res : 0);
            this._updateCounter(count);
            return count;
        } catch (e) {
            console.error("Error fetching unread counter from API:", e);
            return this.unread_counter || 0;
        }
    }

    _updateCounter(new_number) {
        this.unread_counter = Number(new_number) || 0;

        const bElements = document.querySelectorAll(".im_counter b");
        bElements.forEach(el => {
            el.innerHTML = String(this.unread_counter);
        });

        const cntElements = document.querySelectorAll(".im_counter");
        cntElements.forEach(el => {
            if (this.unread_counter < 1) {
                el.classList.remove("shown");
                el.classList.add("zero_counter");
            } else {
                el.classList.add("shown");
                el.classList.remove("zero_counter");
            }
        });
    }

    async _loadCurrent() {
        if (this.group_id == null) {
            if (this.link.report_data) {
                this.items.push(ChatGeneralForm.resolveById(this.link.report_data.author_id));
            } else {
                let _v = await window.OVKAPI.call('users.get', {
                    'user_ids': window.openvk.current_id,
                    'fields': ChatGeneralForm.BASE_FIELDS,
                });
                this.items.push(new ChatGeneralForm(_v[0]));
            }
        } else {
            let _v = await window.OVKAPI.call('groups.getById', {
                'group_ids': Math.abs(this.group_id),
                'fields': ChatGeneralForm.BASE_FIELDS,
            });
            this.items.push(new ChatGeneralForm(_v[0]));
        }

        this.link.cached_profiles._addProfileCache(this.getOperator());
        this.item_index = 0;
    }

    getOperator() {
        if (!this.items[this.item_index]) {
            return new ChatGeneralForm();
        }

        return this.items[this.item_index];
    }

    getCurrentConvo() {
        const v = this.link.messenger.getCurrentChat();
        if (!v) {
            return new ChatGeneralForm();
        }

        return v;
    }

    async _checkSel(loc, sel_id = null) {
        if (!this.is_opened) {
            return;
        }

        try {
            await this.link.openTabByName("conversations");
        } catch (e) {
            console.error(e);
        }

        const _sel = sel_id == null ? Number(loc.searchParams.get('sel')) : sel_id;
        const joinByTopic = loc ? loc.searchParams.get("joinByTopic") : null;
        const joinCode = loc ? (loc.searchParams.get("join") || loc.searchParams.get("invite")) : null;
        const as = loc ? loc.searchParams.get("as") : null;

        if (joinCode) {
            this.link.openTabByName("chat_invite", true, {
                joinCode: joinCode
            });
            return;
        }

        if (joinByTopic != null) {
            this.link.openTabByName("chat_preview_topic", true, {
                "topic": joinByTopic
            });
        }

        if (_sel) {
            const peer = await this.link.conversations._resolveSel(_sel);
            if (peer) {
                const _l = this.link.messenger.getChatWith(peer);
                await this.link.messenger.selectConversation(_l);

                const hashMatch = (loc.hash || location.hash || "").match(/#?msg-?\d+-(\d+)/);
                const queryMsgId = loc.searchParams.get("msgid") || loc.searchParams.get("msg_id") || loc.searchParams.get("msg");
                const targetMsgId = hashMatch ? Number(hashMatch[1]) : (queryMsgId ? Number(queryMsgId) : null);
                if (targetMsgId) {
                    setTimeout(() => {
                        this.link.messenger.goToMessage({ id: targetMsgId, peer_id: _sel }, _l);
                    }, 50);
                }

                return _l;
            } else {
                console.error('No peer with this id!', sel_id);
            }
        }

        const u = new URL(location.href);
        if (u.searchParams.get("as") != as) {
            this._pushState(loc.toString());
        }
    }

    async setChatByPeerId(sel_id) {
        await this._checkSel(new URL(location.href), sel_id);
    }

    _pushState(url) {
        if (this.isFastchat == true) {
            imLog.info("this.isFastchat: ", this.isFastchat, " url: ", url);
            return;
        }

        history.pushState({ 'from_messenger': 1 }, null, url);
    }

    async _resolveState() {
        const _url = new URL(location.href);
        if (this.isFastchat == false && _url.searchParams.get('sel')) {
            this._checkSel(_url);
        } else {
            await this.link.openTabByName('conversations');
        }
    }

    setSwitching(val) {
        this.is_switching = val;
    }

    _toggleScrollMode(enable = true) {
        /*if (window.isMobile && window.isMobile()) {
            return;
        }*/

        if (this.isFastchat || !this.is_opened) {
            u('body').removeClass('no-scroll');

            try {
                const wrap = document.querySelector('body #fastchats_related #fastchats_chat #wrap');
                if (wrap) {
                    if (typeof wrap.scroll === 'function') {
                        wrap.scroll({ top: 0 });
                    } else if (typeof wrap.scrollTo === 'function') {
                        wrap.scrollTo({ top: 0 });
                    } else {
                        wrap.scrollTop = 0;
                    }
                }
            } catch (e) { }
            return;
        }

        if (enable) {
            if (!u('body').hasClass('no-scroll')) {
                window._prevScroll = scrollY;
            }

            u('body').addClass('no-scroll');
        } else {
            scrollTo(0, window._prevScroll);

            window._prevScroll = null;
            u('body').removeClass('no-scroll');
        }
    }

    _changeHeight(container) {
        if (this.isFastchat) {
            return;
        }

        let maybe_distance = 145;
        let tabs_height = container.querySelector('#im_page_tabs').clientHeight;
        //container.style.minHeight = window.outerHeight - tabs_height - maybe_distance + 'px';
    }

    async _resolvePosition(url = null, from_msg = false, firstLoad = false) {
        imLog("_resolvePosition");

        if (window.openvk.current_id == 0 || window.openvk.disable_ajax == 1) {
            return;
        }

        if (!url) {
            url = location.href
        }

        const n_url = url ? new URL(url) : null;
        let should_fullsize = n_url ? n_url.pathname == "/im" : false;
        if (from_msg) { should_fullsize = true; }

        if (should_fullsize) {
            imLog("position is in page");

            u('.page_content').html('');

            if (!firstLoad) {
                await window.im_class.insertIn(document.querySelector('.page_content'), n_url.searchParams.get("as"));
            }
            u('body').addClass("no_footer");

            await this._resolveState();
            this.link.fastChats.hide();
            this.isFastchat = false;
        } else {
            imLog("position is in fastchats");
            if (!this.link.fastChats.isInserted) {
                await this.link.fastChats.insertSelf();
            } else {
                this.link.fastChats.show();
                this.isFastchat = true;
            }
        }
    }

    addLoadSkeleton(container) {
        container.insertAdjacentHTML("beforeend", `<div id="load_skeleton" class="im_page_loader"><img src="/assets/packages/static/openvk/img/loading_mini.gif" alt="..." /></div>`);
    }

    removeLoadSkeleton(container) {
        try {
            if (container && container.querySelector("#load_skeleton")) {
                container.querySelector("#load_skeleton").remove();
            } else {
                u("#load_skeleton").remove();
            }
        } catch (e) {
            u("#load_skeleton").remove();
        }
    }

    isCommon() {
        return window.im_variants.items.indexOf(this.link) == window.im_variants.currentIndex;
    }

    isCurrentUser() {
        return this.link.usage_type == "current_user";
    }
}

class SettingsPage extends IMPage {
    static getPageId() { return "settings"; }

    render(container) {
        this.getNode().addClass("page-other");

        const show_mail = location.hostname == "openvk.org";
        container.insertAdjacentHTML("beforeend", `
            <div style="box-sizing: border-box;padding: 40% 20%;height: 100%;background: var(--common-2);">
                <div>
                    <b>Openvk IM</b>
                    <label style="display:block;"><input id="im.24h" type="checkbox">${tr("im_option_24h_format") || "24-часовой формат времени"}</label>
                    <label style="display:block;"><input id="im.modern_mode" type="checkbox">${tr("im_option_compact_mode")} (beta)</label>
                    <label style="display:block;"><input id="im.debug" type="checkbox">${tr("im_option_debug")}</label>
                    <label style="display:block;"><input id="viewers.photo.list" type="checkbox">${tr("im_option_photo_viewer")} (Beta)</label>
                    ${show_mail ? `<p><a onclick="window.im.messenger.selectConversationByPeerId(window.openvk.dev_id)">${tr("report_bug")}</a></p>` : ""}
                </div>
            </div>
        `);
        container.querySelectorAll("input").forEach((item) => {
            if (item.id === "im.24h") {
                const val = localStorage.getItem("tw." + item.id);
                item.checked = val !== null ? val === "1" : true;
            } else {
                item.checked = localStorage.getItem("tw." + item.id) == "1" || false;
            }
            item.addEventListener("change", (e) => {
                localStorage.setItem("tw." + e.target.id, Number(e.target.checked));
            });
        });
    }
}

class YellowHeader {
    setPageTitle(title) {
        document.title = title;
    }

    changeYellowHeader(text, append_switch_button = true) {
        if (window.im.state.isFastchat == true) {
            u("body #fastchats_chat #fastchat_head b").html(text);
            return;
        }

        u(".page_yellowheader").html(text);
    }

    changeByConvNumber(conv_number) {
        if (conv_number > 7) {
            return this.changeYellowHeader(tr("conversations_count_title", conv_number));
        }

        return this.changeYellowHeader(tr("messages"));
    }

    changeYellowHeaderByPeer(peer) {
        if (window.im.state.is_group) {
            this.changeYellowHeader(tr("group_messages"));
            return;
        }

        switch (peer.supposed_type) {
            case "chat":
                this.changeYellowHeader(tr("conversation_title_chat"));
                break;
            case "user":
                if (peer.id === window.openvk.current_id) {
                    this.changeYellowHeader(tr("saved_messages"));
                    break;
                }

                this.changeYellowHeader(tr("conversation_title_user", escapeHtml(ovk_proc_strtr(peer.getName(), 50))));
                break;
            case "club":
                this.changeYellowHeader(tr("conversation_title_club"));
                break;
        }
    }
}

class ProfilesCache {
    constructor() {
        this.cached_profiles = [];
        this._pending_fetches = new Set();
    }

    _addProfileCache(profile, remove_current = true) {
        if (!profile) return;
        const similar = this._findCachedProfileById(profile.id);
        if (similar) {
            if (remove_current == false) {
                return;
            }

            this.cached_profiles[this.cached_profiles.indexOf(similar)] = profile;
        } else {
            this.cached_profiles.push(profile);
        }
    }

    _moveToProfileCache(profiles = [], groups = [], remove_current = true) {
        (profiles || []).forEach((profile) => {
            if (profile && typeof profile === 'object' && profile.id != null) {
                this._addProfileCache(new ChatGeneralForm(profile), remove_current);
            }
        });
        (groups || []).forEach((group) => {
            if (group && typeof group === 'object' && group.id != null) {
                this._addProfileCache(new ChatGeneralForm(group), remove_current);
            }
        });
    }

    _findCachedProfileById(id) {
        if (id == null) return null;
        const similar = this.cached_profiles.filter((item) => item.id == id);
        if (similar.length == 0) return null;
        return similar[0];
    }

    _findProfile(id) {
        return this._findCachedProfileByIdEvenIfNotCached(id);
    }

    _findCachedProfileByIdEvenIfNotCached(id) {
        if (id == null) return null;
        const found = this._findCachedProfileById(id);
        if (found) return found;

        if (id && !this._pending_fetches.has(id)) {
            this._pending_fetches.add(id);
            const isGroup = Number(id) < 0;
            const apiMethod = isGroup ? 'groups.getById' : 'users.get';
            const params = isGroup ? { group_ids: Math.abs(id), fields: 'photo_50,photo_100,online' } : { user_ids: id, fields: 'photo_50,photo_100,online,sex' };

            window.OVKAPI.call(apiMethod, params).then(res => {
                if (Array.isArray(res) && res.length > 0) {
                    this._addProfileCache(new ChatGeneralForm(res[0]));
                    if (window.im?.messenger) {
                        window.im.messenger.update();
                    }
                }
            }).catch(e => {
                console.error("Failed to fetch profile for ID " + id, e);
            });
        }

        return null;
    }
}

export class LongPollConnection {
    constructor(im) {
        this.stopped = false;
        this.link = im;
    }

    async create(group_id = null) {
        const params = {};
        if (group_id) {
            params.group_id = group_id;
        }
        this.lp = await window.OVKAPI.call('messages.getLongPollServer', params);
        imLog("LP | Created connection to the current user");
    }

    stop() {
        this.stopped = true;
    }

    get is_stopped() {
        return this.stopped == true || (!this.link.state.isCommon() && !this.link.state.isCurrentUser());
    }

    getFirstCounter() {
        return this.lp.unread_count;
    }

    listen() {
        if (this.is_stopped) {
            imLog("LP | stop is set, not listening.");
            return;
        }

        imLog("LP | New cycle of listening");
        let xhr = new XMLHttpRequest();
        const mode = 2 + 8 + 32 + 64 + 128;
        const connection_string = this.lp.server + '?key=' + this.lp.key + '&ts=' + this.lp.ts + '&pts=' + this.lp.pts + '&mode=' + mode;
        xhr.open('GET', connection_string, true);
        xhr.onload = () => {
            let data = JSON.parse(xhr.responseText);
            if (data?.updates?.length > 0)
                data.updates.forEach((event) => {
                    this.link.event_handler.handle(event);
                });
            this.lp.ts = data.ts;

            if (this.stopped == false) {
                this.listen();
            }
        };
        xhr.send();
    }
}

export class FastChats {
    static PINNED_LIMIT = 50;

    constructor() {
        this.isInserted = false;
        this._hasLoadedData = false;
        this.topZIndex = 10000;
        this.onlineWindow = {
            isOpened: false,
            friends: [],
            allFriends: [],
            showAll: false,
            searchQuery: "",
            position: null,
            zIndex: 10000,
            isFocused: false
        };
        this.openedChats = [];
        this.currentUserId = window.openvk ? window.openvk.current_id : 0;
        this.currentUserAvatar = "";
    }

    getPinnedPeersIds() {
        return this.openedChats.map(c => c.peerId);
    }

    pinPeer(convo) {
        const uid = typeof convo === "number" ? convo : convo.id;
        this.openChat(uid, false, true);
    }

    unpinPeer(convo) {
        const uid = typeof convo === "number" ? convo : convo.id;
        this.closeChat(uid);
    }

    shouldBeShown() {
        return isImWarningRemoved() && !window.im.state.is_opened && this.currentUserId > 0;
    }

    async insertSelf() {
        if (!isImWarningRemoved() || !this.shouldBeShown()) return;

        let container = document.querySelector("#fastchats_container");
        if (!container) {
            container = document.createElement("div");
            container.id = "fastchats_container";
            document.body.appendChild(container);
        }

        this.isInserted = true;
        if (!this._hasLoadedData) {
            this._hasLoadedData = true;
            await this.loadInitialData();
        }
        this.render();
    }

    async loadInitialData() {
        try {
            if (this.currentUserId > 0) {
                const userRes = await window.OVKAPI.call('users.get', {
                    user_ids: this.currentUserId,
                    fields: 'photo_50'
                });
                if (userRes && userRes[0]) {
                    this.currentUserAvatar = userRes[0].photo_50;
                }
            }

            await this.loadOnlineFriends();
            this.restoreSavedState();
        } catch (e) {
            console.error("FastChats | loadInitialData error:", e);
        }
    }

    async loadOnlineFriends() {
        try {
            const res = await window.OVKAPI.call('friends.get', {
                fields: 'first_name,last_name,photo_50,online,last_seen,sex',
                order: 'hints'
            });
            if (res && res.items) {
                const sorted = [...res.items].sort((a, b) => {
                    const aOn = (a.online === 1 || a.online === true) ? 1 : 0;
                    const bOn = (b.online === 1 || b.online === true) ? 1 : 0;
                    return bOn - aOn;
                });
                this.onlineWindow.allFriends = sorted;
                this.onlineWindow.friends = sorted.filter(f => f.online === 1 || f.online === true);
            }
        } catch (e) {
            console.error("FastChats | loadOnlineFriends error:", e);
        }
    }

    restoreSavedState() {
        try {
            const saved = localStorage.getItem("im.fastchats.state");
            if (saved) {
                const parsed = JSON.parse(saved);
                if (parsed.onlineWindowOpened !== undefined) {
                    this.onlineWindow.isOpened = parsed.onlineWindowOpened;
                }
                if (parsed.onlinePosition) {
                    this.onlineWindow.position = parsed.onlinePosition;
                }
                if (Array.isArray(parsed.chats)) {
                    parsed.chats.forEach(c => {
                        this.openChat(c.peerId, c.isMinimized, false, c.position, c.zIndex);
                    });
                }
            }
        } catch (e) {
            console.error("FastChats | restoreSavedState error:", e);
        }
    }

    saveState() {
        try {
            const state = {
                onlineWindowOpened: this.onlineWindow.isOpened,
                onlinePosition: this.onlineWindow.position,
                chats: this.openedChats.map(c => ({
                    peerId: c.peerId,
                    isMinimized: c.isMinimized,
                    position: c.position,
                    zIndex: c.zIndex
                }))
            };
            localStorage.setItem("im.fastchats.state", JSON.stringify(state));
        } catch (e) {
            console.error("FastChats | saveState error:", e);
        }
    }

    focusChat(peerId) {
        this.topZIndex++;
        const chat = this.openedChats.find(c => c.peerId === peerId);
        if (chat) {
            chat.zIndex = this.topZIndex;
            chat.isFocused = true;
        }
        this.openedChats.forEach(c => {
            if (c.peerId !== peerId) c.isFocused = false;
        });
        this.onlineWindow.isFocused = false;
        this.render();
    }

    moveChat(peerId, pos) {
        const chat = this.openedChats.find(c => c.peerId === peerId);
        if (chat) {
            chat.position = pos;
            this.saveState();
            this.render();
        }
    }

    focusOnline() {
        this.topZIndex++;
        this.onlineWindow.zIndex = this.topZIndex;
        this.onlineWindow.isFocused = true;
        this.openedChats.forEach(c => c.isFocused = false);
        this.render();
    }

    moveOnline(pos) {
        this.onlineWindow.position = pos;
        this.saveState();
        this.render();
    }

    async openChat(peerId, isMinimized = false, save = true, position = null, zIndex = null) {
        this.topZIndex++;
        let chat = this.openedChats.find(c => c.peerId === peerId);
        if (chat) {
            chat.isMinimized = isMinimized;
            chat.zIndex = this.topZIndex;
            chat.isFocused = true;
            this.focusChat(peerId);
            if (save) this.saveState();
            if (!isMinimized) {
                if (chat.firstUnreadMsgId) {
                    setTimeout(() => this.scrollToUnread(peerId), 50);
                } else {
                    setTimeout(() => this.scrollToBottom(peerId), 50);
                }
            }
            return;
        }

        const friend = this.onlineWindow.allFriends.find(f => f.id === peerId) || this.onlineWindow.friends.find(f => f.id === peerId);
        const initialTitle = friend ? (friend.first_name + " " + friend.last_name) : "...";
        const initialPhoto = friend ? (friend.photo_50 || "") : "";

        chat = {
            peerId,
            title: initialTitle,
            photo: initialPhoto,
            messages: [],
            isMinimized,
            isLoading: true,
            hasMore: false,
            text: "",
            unreadCount: 0,
            firstUnreadMsgId: null,
            position: position,
            zIndex: zIndex || this.topZIndex,
            isFocused: true
        };
        this.openedChats.forEach(c => c.isFocused = false);
        this.onlineWindow.isFocused = false;
        this.openedChats.push(chat);
        this.render();

        try {
            let title = initialTitle;
            let photo = initialPhoto;

            if (title === "..." || !photo) {
                if (peerId > 0 && peerId < 2000000000) {
                    const uRes = await window.OVKAPI.call('users.get', { user_ids: peerId, fields: 'photo_50' });
                    if (uRes && uRes[0]) {
                        title = uRes[0].first_name + " " + uRes[0].last_name;
                        photo = uRes[0].photo_50;
                    }
                } else if (peerId < 0) {
                    const gRes = await window.OVKAPI.call('groups.getById', { group_ids: Math.abs(peerId), fields: 'photo_50' });
                    if (gRes && gRes[0]) {
                        title = gRes[0].name;
                        photo = gRes[0].photo_50;
                    }
                } else if (peerId >= 2000000000) {
                    const cRes = await window.OVKAPI.call('messages.getChat', { chat_id: peerId - 2000000000, fields: 'photo_50' });
                    if (cRes) {
                        title = cRes.title;
                        photo = cRes.photo_50;
                    }
                }

                chat.title = title;
                chat.photo = photo;
            }

            try {
                const conv = window.im?.conversations?._findConv(peerId);
                const unreadCount = Number(conv?.unread_count || 0);
                const fetchCount = Math.min(100, Math.max(15, unreadCount + 5));
                const histRes = await window.OVKAPI.call('messages.getHistory', {
                    peer_id: peerId,
                    count: fetchCount,
                    extended: 1
                });

                if (histRes && Array.isArray(histRes.items)) {
                    chat.messages = histRes.items.reverse();
                    chat.hasMore = histRes.count > chat.messages.length;
                    chat.unreadCount = histRes.unread || 0;

                    let firstUnread = null;
                    if (chat.unreadCount > 0) {
                        for (const msg of chat.messages) {
                            const isOut = msg.from_id === this.currentUserId || msg.out === 1;
                            if (!isOut && (msg.read_state === 0 || msg.read_state === false || msg.unread === 1)) {
                                firstUnread = msg;
                                break;
                            }
                        }
                    }
                    chat.firstUnreadMsgId = firstUnread ? firstUnread.id : null;
                } else {
                    chat.messages = [];
                    chat.hasMore = false;
                }
            } catch (histErr) {
                console.warn("FastChats | messages.getHistory failed (new dialog):", histErr);
                chat.messages = [];
                chat.hasMore = false;
            }

            chat.isLoading = false;
            this.render();
            if (save) this.saveState();

            if (!isMinimized) {
                if (chat.firstUnreadMsgId) {
                    setTimeout(() => this.scrollToUnread(peerId), 50);
                } else {
                    setTimeout(() => this.scrollToBottom(peerId), 50);
                }
            }
        } catch (e) {
            console.error("FastChats | openChat error:", e);
            chat.isLoading = false;
            this.render();
        }
    }

    closeChat(peerId) {
        this.openedChats = this.openedChats.filter(c => c.peerId !== peerId);
        this.saveState();
        this.render();
    }

    toggleChat(peerId) {
        const chat = this.openedChats.find(c => c.peerId === peerId);
        if (chat) {
            chat.isMinimized = !chat.isMinimized;
            this.focusChat(peerId);
            if (!chat.isMinimized) {
                if (chat.unreadCount > 0 && !chat.firstUnreadMsgId) {
                    for (const msg of chat.messages) {
                        const isOut = msg.from_id === this.currentUserId || msg.out === 1;
                        if (!isOut && (msg.read_state === 0 || msg.read_state === false || msg.unread === 1)) {
                            chat.firstUnreadMsgId = msg.id;
                            break;
                        }
                    }
                }
                chat.unreadCount = 0;
                if (chat.firstUnreadMsgId) {
                    setTimeout(() => this.scrollToUnread(peerId), 50);
                } else {
                    setTimeout(() => this.scrollToBottom(peerId), 50);
                }
            }
            this.saveState();
            this.render();
        }
    }

    async loadOlderMessages(peerId) {
        const chat = this.openedChats.find(c => c.peerId === peerId);
        if (!chat || chat.isLoading) return;

        chat.isLoading = true;
        try {
            const histRes = await window.OVKAPI.call('messages.getHistory', {
                peer_id: peerId,
                count: 15,
                offset: chat.messages.length,
                extended: 1
            });

            if (histRes && histRes.items && histRes.items.length > 0) {
                const olderMsgs = histRes.items.reverse();
                chat.messages = [...olderMsgs, ...chat.messages];
                chat.hasMore = histRes.count > chat.messages.length;
            } else {
                chat.hasMore = false;
            }
        } catch (e) {
            console.error("FastChats | loadOlderMessages error:", e);
        }
        chat.isLoading = false;
        this.render();
    }

    onTextChange(peerId, text) {
        const chat = this.openedChats.find(c => c.peerId === peerId);
        if (chat) {
            chat.text = text;
        }
    }

    async sendMessage(peerId) {
        const chat = this.openedChats.find(c => Number(c.peerId) === Number(peerId));
        if (!chat || !chat.text || !chat.text.trim()) return;

        const text = chat.text.trim();
        chat.text = "";
        const randomId = Date.now();

        const tempMsg = {
            id: `temp_${randomId}`,
            random_id: randomId,
            from_id: this.currentUserId,
            peer_id: peerId,
            text: text,
            date: Math.floor(Date.now() / 1000),
            out: 1,
            author_name: tr('you') || 'Вы',
            author_photo: this.currentUserAvatar
        };
        chat.messages.push(tempMsg);
        this.render();
        this.scrollToBottom(peerId);

        try {
            const sendRes = await window.OVKAPI.call('messages.send', {
                peer_id: peerId,
                message: text,
                random_id: randomId
            });

            const realId = Number((sendRes && sendRes.response) || sendRes || randomId);
            tempMsg.id = realId;
        } catch (e) {
            console.error("FastChats | sendMessage error:", e);
        }
    }

    async sendSticker(peerId, stickerId, packId = null, stickerData = null) {
        const chat = this.openedChats.find(c => Number(c.peerId) === Number(peerId));
        if (!chat) return;

        if (!stickerData && typeof window.findStickerData === 'function') {
            stickerData = window.findStickerData(stickerId);
        }

        const sId = Number(stickerId);
        const pId = Number(packId || (stickerData ? stickerData.product_id : 0));
        const photo128 = stickerData?.photo_128 || (pId ? `/sticker/${pId}/${sId}_128.webp` : '');
        const photo256 = stickerData?.photo_256 || photo128;
        const photo512 = stickerData?.photo_512 || (pId ? `/sticker/${pId}/${sId}_512.webp` : photo128);

        const animUrl = stickerData?.animation_url || (stickerData?.animations && stickerData?.animations[0]?.url) || '';

        const stickerObj = {
            id: sId,
            sticker_id: sId,
            product_id: pId,
            photo_64: photo128,
            photo_128: photo128,
            photo_256: photo256,
            photo_352: photo512,
            photo_512: photo512,
            width: 512,
            height: 512,
            animation_url: animUrl || (stickerData?.is_animated && pId ? `/sticker/${pId}/${sId}_512.json` : ''),
            is_animated: Boolean(animUrl || stickerData?.is_animated),
            animations: stickerData?.animations || (animUrl ? [{ type: 'light', url: animUrl }] : []),
            images: stickerData?.images || [
                { url: photo128, width: 128, height: 128 },
                { url: photo256, width: 256, height: 256 },
                { url: photo512, width: 512, height: 512 }
            ]
        };

        const randomId = Date.now();
        const tempMsg = {
            id: `temp_${randomId}`,
            random_id: randomId,
            from_id: this.currentUserId,
            peer_id: peerId,
            text: "",
            date: Math.floor(Date.now() / 1000),
            out: 1,
            author_name: tr('you') || 'Вы',
            author_photo: this.currentUserAvatar,
            attachments: [{
                type: 'sticker',
                sticker: stickerObj
            }]
        };
        chat.messages.push(tempMsg);
        this.render();
        this.scrollToBottom(peerId);

        try {
            const sendRes = await window.OVKAPI.call('messages.send', {
                peer_id: peerId,
                attachment: 'sticker' + sId,
                random_id: randomId
            });

            const realId = Number((sendRes && sendRes.response) || sendRes || randomId);
            tempMsg.id = realId;
        } catch (e) {
            console.error("FastChats | sendSticker error:", e);
        }
    }

    onKeyDown(e, peerId) {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            this.sendMessage(peerId);
        }
    }

    scrollToBottom(peerId) {
        const el = document.querySelector(`#fc_messages_${peerId}`);
        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    }

    scrollToUnread(peerId) {
        const el = document.querySelector(`#fc_unread_${peerId}`);
        const list = document.querySelector(`#fc_messages_${peerId}`);
        if (el && list) {
            el.scrollIntoView({ block: 'start' });
        } else {
            this.scrollToBottom(peerId);
        }
    }

    toggleOnlineWindow() {
        this.onlineWindow.isOpened = !this.onlineWindow.isOpened;
        this.focusOnline();
        this.saveState();
        this.render();
    }

    closeOnlineWindow() {
        this.onlineWindow.isOpened = false;
        this.saveState();
        this.render();
    }

    onOnlineSearch(query) {
        this.onlineWindow.searchQuery = query;
        this.render();
    }

    onOnlineFriendClick(friend) {
        this.openChat(friend.id, false, true);
    }

    selectConversation(e, convo) {
        const uid = typeof convo === "number" ? convo : convo.id;
        this.openChat(uid, false, true);
    }

    isShown() {
        return this.isInserted;
    }

    show() {
        if (!this.shouldBeShown()) return;
        const container = document.querySelector("#fastchats_container");
        if (container) {
            container.style.display = "flex";
        } else {
            this.insertSelf();
        }
    }

    hide() {
        const container = document.querySelector("#fastchats_container");
        if (container) {
            container.style.display = "none";
        }
    }

    updateSelf() {
        this.render();
    }

    async update() {
        await this.loadOnlineFriends();
        this.render();
    }

    async onNewMessage(msg) {
        if (!msg) return;

        const currentUserId = Number(this.currentUserId || (window.openvk ? window.openvk.current_id : 0));
        const rawPeer = (msg.data && (msg.data.peer_id || msg.data.peer)) || msg.peer_id || msg.peer || msg.from_id || (msg.data && msg.data.from_id) || 0;
        const peerId = Number(rawPeer);
        if (!peerId || peerId >= 2000000000) return;

        const msgFlags = Number((msg.data && msg.data.flags) || msg.flags || 0);
        const isOut = Boolean((msgFlags & 2) || (typeof msg.isOut === 'function' && msg.isOut()) || (typeof msg.isMine === 'function' && msg.isMine()) || msg.out === 1);

        const fromId = isOut ? currentUserId : Number((msg.data && msg.data.from_id) || msg.from_id || peerId);
        const msgId = Number((msg.data && msg.data.id) || msg.id || Date.now());
        const text = (msg.data && msg.data.text) || (typeof msg.getText === 'function' ? msg.getText() : '') || msg.text || msg.body || '';
        const date = Number((msg.data && msg.data.date) || msg.date || Math.floor(Date.now() / 1000));
        const randomId = Number((msg.data && msg.data.random_id) || msg.random_id || 0);

        let chat = this.openedChats.find(c => Number(c.peerId) === peerId);

        if (chat) {
            // De-duplicate outgoing messages
            if (isOut) {
                const existing = chat.messages.find(m =>
                    (Number(m.id) === msgId) ||
                    (randomId && Number(m.random_id) === randomId) ||
                    (m.out === 1 && m.text === text && Math.abs(date - m.date) < 30)
                );

                if (existing) {
                    existing.id = msgId;
                    if (randomId) existing.random_id = randomId;
                    const newAtts = (msg.data && msg.data.attachments) || msg.attachments || [];
                    if (newAtts && newAtts.length > 0 && (!existing.attachments || existing.attachments.length === 0)) {
                        existing.attachments = newAtts;
                    }
                    this.render();
                    return;
                }
            } else {
                if (chat.messages.some(m => Number(m.id) === msgId)) {
                    return;
                }
            }

            chat.messages.push({
                id: msgId,
                from_id: fromId,
                peer_id: peerId,
                text: text,
                date: date,
                out: isOut ? 1 : 0,
                author_name: isOut ? (tr('you') || 'Вы') : chat.title,
                author_photo: isOut ? this.currentUserAvatar : chat.photo,
                attachments: (msg.data && msg.data.attachments) || msg.attachments || []
            });

            if (!chat.isMinimized && chat.isFocused) {
                window.OVKAPI.call('messages.markAsRead', {
                    peer_id: peerId,
                    start_message_id: msgId
                }).catch(console.error);
            } else if (!isOut) {
                chat.unreadCount = (chat.unreadCount || 0) + 1;
            }

            this.render();
            if (!chat.isMinimized) {
                this.scrollToBottom(peerId);
            }
        } else if (!isOut && !window.im.state.is_opened) {
            await this.openChat(peerId, true, true);
            const openedChat = this.openedChats.find(c => Number(c.peerId) === peerId);
            if (openedChat) {
                openedChat.unreadCount = 1;
                this.render();
            }
        }
    }

    onEditMessage(peerId, msgId, text) {
        const chat = this.openedChats.find(c => Number(c.peerId) === Number(peerId));
        if (chat && chat.messages) {
            const msg = chat.messages.find(m => Number(m.id) === Number(msgId));
            if (msg) {
                msg.text = text;
                this.render();
            }
        }
    }

    setUserOnline(userId, isOnline) {
        const friend = this.onlineWindow.friends.find(f => f.id === userId);
        if (friend) {
            friend.online = isOnline ? 1 : 0;
            if (!isOnline) {
                friend.last_seen = { time: Math.floor(Date.now() / 1000) };
            }
            this.render();
        } else if (isOnline) {
            this.loadOnlineFriends();
        }
    }

    onEscapePressed() {
        // Find focused chat to minimize
        const focusedChat = this.openedChats.find(c => c.isFocused && !c.isMinimized);
        if (focusedChat) {
            focusedChat.isMinimized = true;
            this.saveState();
            this.render();
            return;
        }

        if (this.onlineWindow.isFocused && this.onlineWindow.isOpened) {
            this.onlineWindow.isOpened = false;
            this.saveState();
            this.render();
        }
    }

    toggleShowAllOnline() {
        this.onlineWindow.showAll = !this.onlineWindow.showAll;
        this.render();
    }

    render() {
        const container = document.querySelector("#fastchats_container");
        if (!container) return;
        container.style.display = "flex";

        preactRender(html`
            <${FastChatsRoot}
                onlineWindow=${this.onlineWindow}
                openedChats=${this.openedChats}
                currentUserId=${this.currentUserId}
                currentUserAvatar=${this.currentUserAvatar}
                onOnlineSearch=${(q) => this.onOnlineSearch(q)}
                onOnlineFriendClick=${(f) => this.onOnlineFriendClick(f)}
                onOnlineToggle=${() => this.toggleOnlineWindow()}
                onOnlineClose=${() => this.closeOnlineWindow()}
                onOnlineFocus=${() => this.focusOnline()}
                onOnlineMove=${(pos) => this.moveOnline(pos)}
                onOnlineToggleShowAll=${() => this.toggleShowAllOnline()}
                onChatToggle=${(id) => this.toggleChat(id)}
                onChatClose=${(id) => this.closeChat(id)}
                onChatLoadOlder=${(id) => this.loadOlderMessages(id)}
                onChatTextChange=${(id, txt) => this.onTextChange(id, txt)}
                onChatSend=${(id) => this.sendMessage(id)}
                onChatKeyDown=${(e, id) => this.onKeyDown(e, id)}
                onChatFocus=${(id) => this.focusChat(id)}
                onChatMove=${(id, pos) => this.moveChat(id, pos)}
            />
        `, container);
    }
}

// Global Esc key listener for fastchats
if (!window._fc_esc_inited) {
    window._fc_esc_inited = true;
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            if (window.im && window.im.fastChats) {
                window.im.fastChats.onEscapePressed();
            }
        }
    });
}

(async () => {
    if (window.openvk.current_id == 0) {
        return;
    }

    window.im_class = InstantMessagesAndRelated;

    if (window.im == null) {
        window.im_variants = new IMVariants();
        window.im_variants.add(new InstantMessagesAndRelated());
        window.im_variants.setByIndex(0);
    }

    if (!isImWarningRemoved()) {
        return;
    }

    await window.im.init();

    if (!window.im.state.is_opened && window.router && !window.router.isAjaxDisabled()) {
        window.im.state._resolvePosition(null);
    }
})()
