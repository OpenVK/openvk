import { ChatGeneralForm } from './components/messages.js';
import { EventHandler } from './events.js';
import { Messenger, MessengerPage } from './pages/messenger.js';
import { Conversations, ConversationsPage } from './pages/conversations.js';
import { Friends, FriendsPage } from './pages/friends.js';
import { SearchPage } from './pages/search.js';
import { IMTab, IMPage } from './pages/page.js';

import { TabBar } from './components/common.js';

import { html, render as preactRender } from './components/render.js';

//const tr = window.tr;
//const u = window.u;

export class InstantMessagesAndRelated {
    constructor() {
        this.tabs = [];
        this.selectedTabId = null;

        this.header = new YellowHeader();
        this.root = null;

        this.usage_type = "current_user";
        this.usage_id = null;

        //this.current = new Currentness(this);
        this.cached_profiles = new ProfilesCache();
        this.event_handler = new EventHandler();
        this.state = new IMState(this);

        this.isReady = false;
        this.conversations = new Conversations();
        this.messenger = new Messenger();
        this.friends = new Friends();
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
        console.log("IM | Init");

        if (window.OVKAPI == null) {
            await new Promise((resolve) => setTimeout(resolve, 1000));
        }

        await this.state._loadCurrent();
        console.log(this.conversations, this.conversations.loadNext)
        await this.conversations.loadNext();
        /*
        this.lp = new LongPollConnection();
        await this.lp.create();
        this.lp.listen();

        this.updateCounter(this.lp.getFirstCounter());
        */
        this.isReady = true;
        console.log("IM | Inited");
    }

    async insertIn(container) {
        this.state.addLoadSkeleton(container);
        await this.waitLoad();

        console.log("IM | Insert in ", container);

        const node = u(`<div class="at_page" id="im_container"><div id="im_page_tabs"></div><div id="im_page_containers"></div></div>`)
        if (this.state.is_compact_mode_enabled == true) {
            node.addClass("compact");
        }

        container.insertAdjacentHTML("beforeend", node.last().outerHTML);

        this.root = container.querySelector("#im_container");

        //const found = await this._checkSel(new URL(location.href), sel_id);
        //if (!found) {
        //    this.selectTab('conversations');
       	//}

        this.openTabByName("conversations");
        this.state.removeLoadSkeleton(container);
        this.state._changeHeight(this.root);
    }

    updateTabs() {
        this._renderTabBar();
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

        this.header.changeByConvNumber(Number(window.im.conversations.total_convs));
    }

    selectTab(tab) {
        if (typeof tab != "number") {
            tab = this.tabs.indexOf(tab);
        }

        console.log("IM | Selected tab " + tab);

        this.selectedTabId = tab;
        this.root.querySelectorAll("#im_page_containers .im_page").forEach(item => {
            item.classList.add("hidden");
        });
        try {
            this.tabs.forEach(item => {
                if (item.shouldClose()) {
                    item.close();
                }
            });

            console.log("selectTab", tab);
            const _tab = this.tabs[tab];
            this.root.querySelector(`#im_page_containers .im_page[data-id="${_tab.getId()}"]`).classList.remove("hidden");
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
                break;
            case "search":
                got_class = SearchPage;
                break;
        }

        if (check_existing == true && got_class) {
            this.tabs.forEach(item => {
                console.log(item, got_class.getPageId())
                if (item.render_class.constructor.getPageId() == got_class.getPageId()) {
                    already_here = item;
                }
            })
        }

        if (already_here != null) {
            this.selectTab(this.tabs.indexOf(already_here));
        } else {
            got_tab = got_class.openTab(this.root, options);
            if (got_tab != null) {
                this.selectTab(this.addTab(got_tab));
                await got_tab.render();
            }
        }
    }

    addTab(tab) {
        this.tabs.push(tab);

        return this.tabs.indexOf(tab);
    }

    getVisibleTabs() {
        return this.tabs.filter(t => t.visible());
    }

    getSelectedTab(tab) {
        return this.tabs[this.selectedTabId];
    }

    getTabs() {
        return this.tabs.map(t => t.id);
    }
}

class IMVariants {
    constructor() {
        this.items = [];
    }

    setByIndex(id) {
        window.im = this.items[id];
    }

    add(item) {
        return this.items.push(item);
    }
}

class IMState {
    constructor(im_link) {
        this.link = im_link;
        this.items = [];
        this.item_index = 0;
    }

    get is_compact_mode_enabled() {
        return localStorage.getItem("tw.im.modern_mode") === "1";
    }

    getUnreadCounter() {
        return 0;
    }
    updateUnreadCounter() {
        // todo
    }

    async _loadCurrent() {
        let _v = await window.OVKAPI.call('users.get', {
            'user_ids': window.openvk.current_id,
            'fields': ChatGeneralForm.base_fields,
        });
        this.items.push(new ChatGeneralForm(_v[0]));
        this.item_index = 0;
        this.link.cached_profiles._addProfileCache(this.item_index);
    }

    async _checkSel(loc, sel_id = null) {
        const _sel = sel_id == null ? Number(loc.searchParams.get('sel')) : sel_id;
        if (!_sel) return;

        const peer = await this.conversations._resolveSel(_sel);

        if (peer) {
            const _l = this.messenger.view.getChatWith(peer);
            await this.selectChat(_l);
            return _l;
        } else {
            console.error('No peer with this id!');
        }
    }

    async setChatByPeerId(sel_id) {
        await this._checkSel(new URL(location.href), sel_id);
    }

    _pushState(url) {
        history.pushState({ 'from_messenger': 1 }, null, url);
    }

    _resolveState(e) {
        const _url = new URL(location.href);
        if (_url.searchParams.get('sel')) {
            this._checkSel(_url);
        } else {
            this.selectTab('conversations');
        }
    }

    setSwitching(val) {
        this.is_switching = val;
    }

    _toggleScrollMode(enable = true) {
        /*if (window.isMobile && window.isMobile()) {
            return;
        }*/

        if (window.im.is_compact_mode_enabled == true) {
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
        let maybe_distance = 100;
        let tabs_height = container.querySelector('#im_page_tabs').clientHeight;
        container.style.height = window.outerHeight - tabs_height - maybe_distance + 'px';
    }

    addLoadSkeleton(container) {
        container.insertAdjacentHTML("beforeend", `<span id="load_skeleton">LOADING!!!!!</span>`);
    }

    removeLoadSkeleton(container) {
        container.querySelector("#load_skeleton").remove();
    }

    get is_opened() {
        return location.pathname == "/im";
    }

    // Is messages page is open and messenger tab selected
    get is_active() {
        return this.tab == 'messenger' && this.is_opened == true;
    }
}

class SettingsPage extends IMPage {
    static getPageId() {
        return "settings";
    }

    render(container) {
        container.insertAdjacentHTML("beforeend", `
            <span>Im frontend</span>
            <div>
                <label><input type="checkbox">включить компактный режим</label>
                <label><input type="checkbox">включить дебаг кнопки</label>
            </div>
        `)
    }
}

class YellowHeader {
    setPageTitle(title) {
        document.title = title;
    }

    changeYellowHeader(text) {
        u(".page_yellowheader").html(text);
    }

    changeByConvNumber(conv_number) {
        if (conv_number > 7) {
            return tr("conversations_count_title", conv_number);
        }

        return tr("messages");
    }

    changeYellowHeaderByPeer(peer) {
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

    _addProfileCache(profile) {
        const similar = this._findCachedProfileById(profile.id);
        if (similar) {
            this.cached_profiles[this.cached_profiles.indexOf(similar)] = profile;
        } else {
            this.cached_profiles.push(profile);
        }
    }

    _moveToProfileCache(profiles, groups) {
        profiles.forEach((profile) => {
            this._addProfileCache(new ChatGeneralForm(profile));
        });
        groups.forEach((group) => {
            this._addProfileCache(new ChatGeneralForm(group));
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
    constructor() {
        this.stopped = false;
    }

    async create(group_id = null) {
        this.lp = await window.OVKAPI.call('messages.getLongPollServer', {});
        console.log("LP | Created connection to the current user");
    }

    stop() {
        this.stopped = true;
    }

    getFirstCounter() {
        return this.lp.unread_count;
    }

    listen() {
        console.log("LP | New cycle of listening");
        console.log(this.lp);
        let xhr = new XMLHttpRequest();
        const mode = 2 + 8 + 32 + 64 + 128;
        const connection_string = this.lp.server + '?key=' + this.lp.key + '&ts=' + this.lp.ts + '&pts=' + this.lp.pts + '&mode=' + mode;
        xhr.open('GET', connection_string, true);
        xhr.onload = () => {
            let data = JSON.parse(xhr.responseText);
            if (data?.updates?.length > 0)
                data.updates.forEach((event) => {
                    window.im.event_handler.handle(event);
                });
                this.lp.ts = data.ts;

                if (this.stopped == false) {
                    this.listen();
                }
            };
            xhr.send();
        }
}

export class IMDeprecated {
    constructor() {
        this.tabDefs = [
        { id: 'conversations', label: tr('messenger_tab_conversations'), visible: () => true },
        { id: 'messenger', label: tr('messenger_tab_messenger'), visible: () => (this.messenger?.view?.getTabsCount() ?? 0) > 0 },
        { id: 'search', label: tr('search_messages_tab'), visible: () => this.tab == "search" },
        { id: 'friends', label: () => { return (window.im.friends.referrer == 'chat_creation' ? tr('create_chat') : tr('im_friends_list')) }, visible: () => this.tab == "friends" },
        { id: 'contact', label: tr('contact_info'), visible: () => this.tab == "contact" },
        ];
        this.tab = '';
        this.is_switching = false;
        this.unread_counter = 0;
    }

    async _checkSel(loc, sel_id = null) {
        const _sel = sel_id == null ? Number(loc.searchParams.get('sel')) : sel_id;
        if (!_sel) return;

        const peer = await this.conversations._resolveSel(_sel);

        if (peer) {
            const _l = this.messenger.view.getChatWith(peer);
            await this.selectChat(_l);
            return _l;
        } else {
            console.error('No peer with this id!');
        }
    }

    async init() {
        if (window.OVKAPI == null) {
            await new Promise((resolve) => setTimeout(resolve, 1000));
        }

        this.cached_profiles = new ProfilesCache();
        this.event_handler = new EventHandler();
        await this._loadCurrent();

        if (!this.conversations) {
            this.conversations = new Conversations();
            await this.conversations.init();
        }

        if (!this.messenger) {
            this.messenger = new Messenger();
            await this.messenger.init();
        }

        if (!this.friends) {
            this.friends = new FriendsTab();
        }

        if (!this.search) {
            this.search = new SearchPage();
        }

        this.lp = new LongPollConnection();
        await this.lp.create();
        this.lp.listen();

        this.updateCounter(this.lp.getFirstCounter());

        this.isReady = true;
    }

    async waitLoad() {
        return new Promise(resolve => {
            const check = () => {
                if (this.isReady) {
                    resolve();
                } else {
                    setTimeout(check, 100);
                }
            };
            check();
        });
    }

    async setChatByPeerId(sel_id) {
        await this._checkSel(new URL(location.href), sel_id);
    }

    setPageTitle(title) {
        document.title = title;
    }

    closeChat(conv) {
        if (this.messenger.view.getTabsCount() - 1 == 0) {
            this.selectTab('conversations');
        } else {
            const _id = this.messenger.view.opened_tabs.indexOf(conv);
            this.selectChat(this.messenger.view.opened_tabs[Math.max(0, _id - 1)]);
        }

        this.messenger.view.closeChat(conv);
        this.messenger.view._render();
    }

	async selectChat(conv) {
	    if (this.is_switching == true) {
	      return;
	    }

        if (!conv || !conv.peer) {
            console.error("Cannot load conversation ", conv);
            return;
        }

        const cur_conv = this.messenger.view.getCurrentChat();
        console.log(cur_conv, conv)
        if (cur_conv && conv.peer.id == cur_conv.peer.id) {
            console.info('Already loaded conversation ', conv);

       	    this.messenger.view.setChat(conv, false);
       	    this.selectTab('messenger');
            return;
        }

	    this.setSwitching(true);

	    this.messenger.view.preselectChat(conv);

	    const _url = new URL(location.href);
	    // `start_from_id` allows jumping to a specific message in the conversation.
	    // When provided, the initial chunk is anchored to that message, letting the
	    // user scroll up (older) and down (newer) from there.
	    // Falls back to `start_from` for backward compatibility, then null (latest).
	    const _start_from_id = _url.searchParams.get('start_from');

	    this.messenger.view._saveDraft(this.messenger.view.getCurrentChat());
	    if (!conv.peer._isMessagesInited()) {
	        const messages = await conv.peer.getMessages(_start_from_id);
            conv.peer._appendMessagesChunk(messages);

            // т.к. последние на данный момент сообщения уже загружены
            if (_start_from_id == null) {
                conv.peer._beginning_reached = true;
            }
	    }

	    this.messenger.view.setChat(conv, false);
	    this.selectTab('messenger');
	    this.messenger.view._loadDraft(conv);
	    this.messenger.view._scrollToEnd();

        u(".messenger-app--input---messagebox textarea").last().focus();

	    this.changeYellowHeaderByPeer(conv.peer);
		this.setSwitching(false);
        this.setPageTitle(escapeHtml(ovk_proc_strtr(conv.peer.full_name, 100)));
	}

    get current() {
        return this._currents[this._current_id];
    }

    get corresponder() {
        try {
            return this.messenger.view.getCurrentChat().peer;
        } catch (e) {
            console.error(e);
        }
    }

    // counter

    updateCounter(new_number) {
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

    getCounter() {
        return this.unread_counter;
    }
    selectTab(tab_name, referrer = null) {
        if (this.tabs.indexOf(tab_name) == -1) {
            throw new Error('invalid tab');
        }

       	if (tab_name != "messenger") {
            const current_chat = this.messenger.view.getCurrentChat();
            if (current_chat != null) {
                this.messenger.view._saveDraft(current_chat);
            }

            this._toggleScrollMode(false);
       	} else {
            this._toggleScrollMode(true);
       	}

        this.tab = tab_name;
        this._renderTabBar();

        if (tab_name != "contact") {
            if (window.im.is_compact_mode_enabled && (tab_name == "conversations" || tab_name == "messenger")) {
                return;
            }

            u(".messenger-app--tab-messenger").removeClass("peer-shown");
            this.tabDefs.forEach((def) => {
                const win = this._getTabWindow(def.id);
                if (!win) return;

                if (def.id === tab_name) {
                    win.classList.remove('hidden');
                } else {
                    win.classList.add('hidden');
                }
            });
        } else {
            u(".messenger-app--tab-messenger").addClass("peer-shown");
        }

        switch (tab_name) {
            case 'conversations':

                if (!window.im.is_compact_mode_enabled) {
                    this.messenger.hide(this._getTabWindow('messenger'));
                    this.conversations.appear(this._getTabWindow('conversations'));
                } else {
                    this.messenger.appear(this._getTabWindow('messenger'));
                    this.conversations.appear(this._getTabWindow('conversations'));
                }

                this._pushState('/im');
                this.setPageTitle(tr("messenger_tab_conversations"));
                break;

                case 'messenger':
                if (!window.im.corresponder) {
                    this.selectTab('conversations');
                    return;
                }

                this.changeYellowHeaderByPeer(window.im.corresponder);

                this.conversations.hide(this._getTabWindow('conversations'));
                this.messenger.appear(this._getTabWindow('messenger'));

                try {
                    window.im._pushState('/im?sel=' + window.im.messenger.view.getCurrentChat().peer.id);
                } catch (e) {
                    console.error(e);
                }

                break;

            case 'search':

                this.search.appear(this._getTabWindow('search'));
                break;

            case 'friends':
                this.friends.appear(this._getTabWindow('friends'), referrer);
                break;

            case 'contact':
                this.messenger.view._render();

                if (typeof window.im !== 'undefined' && window.im.updateTabs) {
               	    window.im.updateTabs();
                }

                break;
    }
  }

}

(async () => {
    if (window.im == null) {
        window.im_variants = new IMVariants();
        window.im_variants.add(new InstantMessagesAndRelated());
        window.im_variants.setByIndex(0);
    }

    await window.im.init();
})()
