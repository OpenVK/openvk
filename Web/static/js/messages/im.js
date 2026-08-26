import { ChatGeneralForm } from './components/messages.js';
import { FastChatsBar, FastChatsWindow } from './components/extra.js';
import { EventHandler } from './events.js';
import { Messenger, MessengerPage, ContactPage, ChatTopicPreviewPage } from './pages/messenger.js';
import { Conversations, ConversationsPage } from './pages/conversations.js';
import { Friends, FriendsPage } from './pages/friends.js';
import { SearchPage } from './pages/search.js';
import { IMTab, IMPage } from './pages/page.js';

import { TabBar } from './components/common.js';

import { html, render as preactRender } from './components/render.js';

//const tr = window.tr;
//const u = window.u;

export class InstantMessagesAndRelated {
    constructor(group_id = null) {
        this.tabs = [];
        this.selectedTabId = null;

        this.header = new YellowHeader();
        this.root = null;

        this.usage_type = "current_user";
        this.usage_id = null;

        //this.current = new Currentness(this);
        this.cached_profiles = new ProfilesCache();
        this.event_handler = new EventHandler(this);
        this.state = new IMState(this, group_id);

        this.isReady = false;
        this.is_initing = false;
        this.conversations = new Conversations();
        this.messenger = new Messenger();
        this.friends = new Friends();

        this.fastChats = new FastChats();
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

    async init() {
        if (this.is_initing == true) {
            return;
        }

        // иначе будут лишние запросы
        try {
            if (window.openvk.current_id == 0 || window.openvk.disable_ajax == 1 || Number(localStorage.getItem('ux.disable_ajax_routing') ?? 0) == 1 || Number(localStorage.getItem('tw.im.disable_messenger') ?? 0) == 1) {
                if (location.pathname != "/im") {
                    return;
                }
            }
        } catch(e) {
            console.error(e);
        }

        this.is_initing = true;
        console.trace();
        console.log("IM | Init", this.state);

        if (window.OVKAPI == null) {
            await new Promise((resolve) => setTimeout(resolve, 1000));
        }

        await this.state._loadCurrent();

        try {
            await this.conversations.loadNext();
        } catch(e) {
            fastError(String(e));
        }

        this.lp = new LongPollConnection(this);
        await this.lp.create(Math.abs(this.state.group_id));
        this.lp.listen();

        this.state._updateCounter(this.lp.getFirstCounter());

        this.isReady = true;
        this.is_initing = false;
        console.log("IM | Inited");
    }

    static async insertIn(container, as = null, fastchat = false, rewrite_tabs = true) {
        let self = window.im_variants.getCurrentUser();
        const b = new URL(location.href)

        if (localStorage.getItem("tw.im.remove_warning") !== "1") {
            if (fastchat) {
                return;
            }

            await self._showAgreement();
        }

        if (as != null) {
            console.log("IM | ?as= detected", as);
            self = window.im_variants.getForGroup(Number(as));
            window.im_variants.set(self);
            if (!self.is_ready) { await self.init(); }

            b.searchParams.set("as", String(as))
        } else {
            window.im_variants.set(self);
            if (!self.is_ready) { await self.init(); }

            b.searchParams.delete("as")
        }

        self.state.addLoadSkeleton(container);
        self.state.isFastchat = fastchat;
        await self.waitLoad();

        console.log("IM | Insert in ", container, " fastchat: ", fastchat);

        const node = u(`<div id="im_container"><div id="im_page_tabs"></div><div id="im_page_containers"></div></div>`)
        if (!fastchat) { node.addClass("at_page"); }
        if (!fastchat && self.state.is_compact_mode_enabled == true) { node.addClass("compact"); }

        container.insertAdjacentHTML("beforeend", node.last().outerHTML);

        if (rewrite_tabs == true) {
            self.rewriteTabs(container);
        }

        //const found = await this._checkSel(new URL(location.href), sel_id);
        //if (!found) {
        //    this.selectTab('conversations');
       	//}

        try {
            await self.state._checkSel(b);
        } catch(e) {
            console.error(e);
        }

        self.state._changeHeight(self.root);
        self.state.removeLoadSkeleton(container);
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
                buttons: [tr('cancel'), tr('i_agree')],
                callbacks: [() => {
                    msg.close()
                    resolve(false)
                }, () => {
                    localStorage.setItem("tw.im.remove_warning", "1");
                    msg.close();
                    resolve(true);
                }]
            })
        })
    }

    rewriteTabs(container) {
        const oldRoot = this.root ? this.root : null;
        this.root = container.querySelector("#im_container");

        this.tabs.forEach(item => {
            try {
                if (this.root == oldRoot) {
                    return;
                }
                console.log(this.root, oldRoot)
                item.render_class.changeContainer(this.root);
                item.render();
            } catch(e) {
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

        console.log("IM | Selected tab " + tab);

        this.state._toggleScrollMode(false);

        this.selectedTabId = tab;
        this.root.querySelectorAll("#im_page_containers .im_page").forEach(item => {
            console.log("IM | Hide tab", item);
            item.classList.add("hidden");
        });
        try {
            const _tab = this.tabs[tab];
            this.tabs.forEach(item => {
                if (_tab != item && item.shouldClose()) {
                    console.log("IM | Closed tab", item);
                    item.close();
                }
            });

            if (_tab.isDisablesScroll()) { this.state._toggleScrollMode(true); }

            _tab.updateHeader(this.header);
            _tab.render_class.updUrl();

            const b = this.root.querySelector(`#im_page_containers .im_page[data-id="${_tab.getId()}"]`);
            if (!b) {
                console.log("IM | Tab not found, inserting again", _tab);
                this.root.querySelector("#im_page_containers").insertAdjacentHTML("beforeend", `
                    <div class="im_page" data-id="${_tab.getId()}"></div>
                `);
            }

            console.log("IM | Show tab", _tab);
            _tab.showTab(this.root);
        } catch(e) {
            console.error(e);
        }

        this.updateTabs();
    }

    async openTabByName(tab, check_existing = true, options = {}) {
        let got_tab = null;
        let got_class = null;
        let already_here = null;

        switch(tab) {
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
        }

        if (check_existing == true && got_class) {
            this.tabs.forEach(item => {
                if (item.getPageId() == got_class.getPageId()) {
                    already_here = item;
                }
            })
        }

        if (already_here != null) {
            this.selectTab(this.tabs.indexOf(already_here));

            return already_here;
        } else {
            try {
                got_tab = got_class.openTab(this.root, options);
                if (got_tab != null) {
                    got_tab.render_class.addLoadSkeleton(this.root);
                    console.log(got_tab.render_class)
                    this.selectTab(this.addTab(got_tab));
                    await got_tab.render();
                    got_tab.render_class.removeLoadSkeleton(this.root);
                }
            } catch(e) {
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
    getSelectedTabId() { return this.getSelectedTab().getPageId() }
    getTabs() { return this.tabs.map(t => t.id); }
    getTab(id) { return this.tabs.find(t => t.getPageId() == id);}
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
            console.log(item.state, group_id)
            if (item.state.group_id == group_id) {
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
        return this.getOperator().id;
    }
    get is_compact_mode_enabled() { return localStorage.getItem("tw.im.modern_mode") === "1"; }
    get is_debug() { return localStorage.getItem("tw.im.debug") === "1"; }
    get is_opened() { return location.pathname == "/im"; }
    get is_active() { 
        try {
            return window.im.getSelectedTabId() == "messenger" && this.is_opened == true;
        } catch(e) { return false; }
    }
    get is_group() { return this.group_id != null }

    getUnreadCounter() {
        let counter = 0;
        this.link.conversations.all_convs.forEach(item => {
            if (!item.is_read) { counter += 1; }
        });

        return counter;
    }

    _updateCounter(new_number) {
        this.unread_counter = new_number;

        u(".im_counter b").html(new_number);

        if (this.unread_counter < 1) {
            u(".im_counter").removeClass("shown");
            u(".im_counter").addClass("zero_counter");
        } else {
            u(".im_counter").addClass("shown");
            u(".im_counter").removeClass("zero_counter");
        }
    }

    async _loadCurrent() {
        if (this.group_id == null) {
            let _v = await window.OVKAPI.call('users.get', {
                'user_ids': window.openvk.current_id,
                'fields': ChatGeneralForm.BASE_FIELDS,
            });
            this.items.push(new ChatGeneralForm(_v[0]));
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
        return this.items[this.item_index];
    }

    getCurrentConvo() {
        return this.link.messenger.getCurrentChat();
    }

    async _checkSel(loc, sel_id = null) {
        if (!this.is_opened) {
            return;
        }

        try {
            await this.link.openTabByName("conversations");
        } catch(e) {
            console.error(e);
        }

        const _sel = sel_id == null ? Number(loc.searchParams.get('sel')) : sel_id;
        const joinByTopic = loc ? loc.searchParams.get("joinByTopic") : null;
        const as = loc ? loc.searchParams.get("as") : null;

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
            console.info("this.isFastchat: ", this.isFastchat, " url: ", url)
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
                u('body #fastchats_related #fastchats_chat #wrap').last().scroll({ top: 0 });
            } catch(e) {
                console.error(e);
            }
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
        container.style.minHeight = window.outerHeight - tabs_height - maybe_distance + 'px';
    }

    async _resolvePosition(url = null, from_msg = false, firstLoad = false) {
        console.log("IM | _resolvePosition");

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
            console.log("IM | position is in page");

            u('.page_content').html('');

            if (!firstLoad) {
                window.im_class.insertIn(document.querySelector('.page_content'), n_url.searchParams.get("as"));
            }
            u('body').addClass("no_footer");

            await this._resolveState();
            this.link.fastChats.hide();
            this.isFastchat = false;
        } else {
            console.log("IM | position is in fastchats");
            if (!this.link.fastChats.isInserted) {
                await this.link.fastChats.insertSelf();
            } else {
                this.link.fastChats.updateSelf();
                this.isFastchat = true;
            }

            this.link.fastChats.show();

            if (this.link.fastChats.isShown()) {
                this.link.fastChats.hideChatBar();
            }
        }
    }

    addLoadSkeleton(container) {
        container.insertAdjacentHTML("beforeend", `<div id="load_skeleton" class="im_page_loader"><img src="/assets/packages/static/openvk/img/loading_mini.gif" alt="..." /></div>`);
    }

    removeLoadSkeleton(container) {
        try {
            container.querySelector("#load_skeleton").remove();
        } catch(e) {
            u("#im_container #load_skeleton").remove();
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
            <div style="padding: 10px 10px;">
                <div>
                    <label style="display:block;"><input id="im.modern_mode" type="checkbox">Compact mode (beta)</label>
                    <label style="display:block;"><input id="im.debug" type="checkbox">Debug buttons</label>
                    <label style="display:block;"><input id="viewers.photo.list" type="checkbox">Photo viewer enchantements</label>
                    ${show_mail ? `<p><a onclick="window.im.messenger.selectConversationByPeerId(1381)">Сообщить об ошибке</a></p>` : ""}
                </div>
            </div>
        `);
        container.querySelector("input").addEventListener("change", (e) => {
            localStorage.setItem("tw." + e.target.id, Number(e.target.checked));
        });
        container.querySelectorAll("input").forEach((item) => {
            item.checked = localStorage.getItem("tw." + item.id) == "1" || false;
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

        try {
            append_switch_button = window.im.getSelectedTab().getPageId() == "conversations";
        } catch(e) {}

        if (append_switch_button == true) {
            u(".page_yellowheader").append(`
            <div style="float: right;">
                <span><b><a onclick="imSwitchCurrent(event)">${tr("messenger_switch_current")}</a></b></span>
            </div>`);
        }
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

                this.changeYellowHeader(tr("conversation_title_user", escapeHtml(ovk_proc_strtr(peer.name, 50))));
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
    }

    _addProfileCache(profile, remove_current = true) {
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

    _moveToProfileCache(profiles, groups, remove_current = true) {
        profiles.forEach((profile) => {
            this._addProfileCache(new ChatGeneralForm(profile), remove_current);
        });
        groups.forEach((group) => {
            this._addProfileCache(new ChatGeneralForm(group), remove_current);
        });
    }

    _findCachedProfileById(id) {
        const similar = this.cached_profiles.filter((item) => item.id == id);
        if (similar.length == 0) return null;
        return similar[0];
    }

    _findCachedProfileByIdEvenIfNotCached(id) {
        return this._findCachedProfileById(id);
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
        console.log("LP | Created connection to the current user");
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
            console.log("LP | stop is set, not listening.");
        };

        console.log("LP | New cycle of listening");
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
    }
    getPinnedPeersIds() {
        if (localStorage.getItem("im.fastchat.tabs") == null) {
            localStorage.setItem("im.fastchat.tabs", "[]");
        }

        const d = localStorage.getItem("im.fastchat.tabs");
        try {
            return JSON.parse(d);
        } catch(e) {
            console.error(e);
            return [];
        }
    }
    async getPinnedPeers() {
        const ids = this.getPinnedPeersIds();
        const peers = [];

        ids.forEach(async id => {
            peers.push(await window.im.conversations._findConvFromApi(id));
        });
        return peers;
    }
    pinPeer(convo) {
        const peers = this.getPinnedPeersIds();
        const uid = typeof convo == "number" ? convo : convo.id;

        if (peers.indexOf(uid) !== -1) {
            return;
        }

        peers.push(uid);

        localStorage.setItem("im.fastchat.tabs", JSON.stringify(peers));
    }
    unpinPeer(convo) {
        let peers = this.getPinnedPeersIds();
        const uid = typeof convo == "number" ? convo : convo.id;
        peers = peers.filter(item => { return item != uid });
        localStorage.setItem("im.fastchat.tabs", JSON.stringify(peers));
    }
    shouldBeShown() { return !window.im.state.is_opened }
    async insertSelf() {
        if (localStorage.getItem("tw.im.remove_warning") !== "1") {
            return;
        }

        u("body").append(`
        <div id="fastchats_related">
            <div id="fastchats_chat">
                <div id="fastchat_head">
                    <b></b>

                    <div style="display: flex;gap: 5px;">
                        <span id="fastchat_reveal" class="f_act" onclick="window.im.fastChats.reveal()"></span>
                        <span id="fastchat_close" class="f_act" onclick="window.im.fastChats.close()"></span>
                    </div>
                </div>
                <div id="wrap"></div>
            </div>
            <div id="fastchats"></div>
        </div>`);

        await this.update();
        this.isInserted = true;

        window.im_class.insertIn(document.querySelector('#fastchats_related #fastchats_chat #wrap'), null, true);
    }
    async updateSelf() {
        window.im.rewriteTabs(document.querySelector('#fastchats_related #fastchats_chat #wrap'));
    }
    async update() {
        const peers = await this.getPinnedPeers();
        preactRender(html`<${FastChatsBar} pinnedItems=${peers} convos=${window.im.conversations} />`, document.querySelector("#fastchats"));
    }
    show() {
        u("body #fastchats_related").addClass("shown");
        this.update();
    }
    hide() {
        u("body #fastchats_related").removeClass("shown");
    }
    onEntryPointClick() {
        this.toggleChatBar();
        window.im.openTabByName("conversations");
    }

    selectConversation(e, convo) {
        if (e.target.matches(".fastchat_close")) {
            this.unpinPeer(convo);
            window.im.messenger.closeChat(convo);

            this.update();
            return;
        }

        try {
            if (convo.id == window.im.messenger.getCurrentChat().id && u("body #fastchats_related #fastchats_chat").hasClass("shown")) {
                this.hideChatBar();
                return;
            }
        } catch(e) {
            console.error(e);
        }

        this.showChatBar();
        window.im.messenger.selectConversation(convo, true);
    }

    toggleChatBar() { 
        if (window.im.getSelectedTabId() == "messenger") {
            window.im.openTabByName("conversations");
            return;
        }

        if(u("body #fastchats_related #fastchats_chat").hasClass("shown")) {
            this.hideChatBar();
        } else {
            this.showChatBar();
        }
    };
    showChatBar() {
        u("body #fastchats_related #fastchats_chat").attr("style", "display:block;");
        u("body #fastchats_related #fastchats_chat").addClass("fading_state1");
        setTimeout(() => {
            u("body #fastchats_related #fastchats_chat").removeClass("fading_state1");
            u("body #fastchats_related #fastchats_chat").addClass("shown");
        }, 200);
    }
    hideChatBar() {
        u("body #fastchats_related #fastchats_chat").addClass("fading_state2");
        setTimeout(() => {
            u("body #fastchats_related #fastchats_chat").removeClass("fading_state2");
            u("body #fastchats_related #fastchats_chat").removeClass("shown");
        u("body #fastchats_related #fastchats_chat").attr("style", "");
        }, 200);
    }
    isShown() { return u("body #fastchats_related #fastchats_chat").hasClass("shown") }
    close() { this.hideChatBar(); }
    reveal() {
        if (window.im.getSelectedTabId() == "conversations") {
            window.router.route({
                "url": "/im"
            });
        }
        if (window.im.getSelectedTabId() == "messenger") {
            const chat = window.im.messenger.getCurrentChat();
            window.router.route({
                "url": "/im?sel=" + (chat ? chat.peer.id : "")
            });
        }

        this.hideChatBar();
    }
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

    await window.im.init();

    if (!window.im.state.is_opened && window.router && !window.router.isAjaxDisabled()) {
        window.im.state._resolvePosition(null);
    }
})()
