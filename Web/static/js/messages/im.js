import { ChatGeneralForm } from './messages.js';
import { EventHandler } from './events.js';
import { Messenger, LongPollConnection } from './messenger.js';
import { Conversations } from './conversations.js';
import { SearchTab } from './search.js';
import { render, html, TabBar, FriendsPage, ContactPage } from './components.js';

const tr = window.tr;
const u = window.u;

class ProfilesCache {
    constructor() {
        this.cached_profiles = [];
        this.unread_counter = 0;
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

export class IM {
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
    }

    get visibleTabs() {
        return this.tabDefs.filter(t => t.visible());
    }

    get tabs() {
        return this.tabDefs.map(t => t.id);
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
            this.search = new SearchTab();
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

    async initImPage(container, sel_id = null) {
        this.addLoadSkeleton(container);
        await this.waitLoad();
        this.root = container;
        this._initTabs();
        const found = await this._checkSel(new URL(location.href), sel_id);
        if (!found) {
            this.selectTab('conversations');
       	}
    }

    async setChatByPeerId(sel_id) {
        await this._checkSel(new URL(location.href), sel_id);
    }

    addLoadSkeleton(container) {
        container.innerHTML = "";
    }

    setPageTitle(title) {
        document.title = title;
    }

    changeYellowHeader(text) {
        u(".page_yellowheader").html(text);
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

    async _loadCurrent() {
        let _v = await window.OVKAPI.call('users.get', {
            'user_ids': window.openvk.current_id,
            'fields': ChatGeneralForm.base_fields,
        });
        this._currents = [new ChatGeneralForm(_v[0])];
        this._current_id = 0;
        this.cached_profiles._addProfileCache(this.current);
    }

    get current() {
        return this._currents[this._current_id];
    }

    _initTabs() {
        this.tabDefs.forEach((tab) => {
            const tabWindow = document.createElement('div');
            tabWindow.className = 'messenger-app--tab-' + tab.id + ' hidden';
            tabWindow.setAttribute('data-window', tab.id);
            this.root.appendChild(tabWindow);
        });

        this._renderTabBar();
    }

    _renderTabBar() {
        if (!this.root) return;

        let wrap = this.root.querySelector('#tabs-wr2');
        if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'tabs-wr2';
        this.root.insertAdjacentElement('afterbegin', wrap);
        }

        render(html`
        <${TabBar}
            tabs=${this.visibleTabs}
            activeTab=${this.tab}
            onTabSelect=${(id) => this.selectTab(id)}
        />
        `, wrap);

        this.changeYellowHeader(tr("conversations_count_title", Number(window.im.conversations.total_convs)));
    }

    updateTabs() {
        this._renderTabBar();
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
                this.conversations.appear(this._getTabWindow('conversations'));
                this.messenger.hide(this._getTabWindow('messenger'));
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

    _getTabWindow(tab_name) {
        return this.root.querySelector(`div[data-window="${tab_name}"]`);
    }

    _toggleScrollMode(enable = true) {
        /*if (window.isMobile && window.isMobile()) {
            return;
        }*/

        if (enable) {
            u('body').addClass('no-scroll');
        } else {
            u('body').removeClass('no-scroll');
        }
    }

    // Is messages page is open and messenger tab selected
    get is_active() {
        const is_chat_page = location.pathname == '/im';
        return this.tab == 'messenger' && is_chat_page;
    }

    get corresponder() {
        try {
            return this.messenger.view.getCurrentChat().peer;
        } catch (e) {
            console.error(e);
        }
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
}

class FriendsTab {
    constructor() {
        this.friends = [];
        this.total_count = null;
        this.has_inited = false;
        this.last_offset = 0;
        this.has_appeared = false;

        this.referrer = null;
        this.selected_friends = [];
    }

    async loadFriends(offset = 0, count = 10) {
        let res = await window.OVKAPI.call('friends.get', {
            offset: offset,
            count: 100,
            fields: ChatGeneralForm.base_fields,
        });

        this.last_offset = offset;
        if (this.total_count == null) {
            this.total_count = res.count;
        }

        res.items.forEach(item => {
            this.friends.push(new ChatGeneralForm(item));
        })
    }

    onFriendClick(e, peer) {
        if (this.referrer == "chat_creation") {
            const id = peer.id;
            const t = e.target;
            const f = t.closest(".friends-list-item");

            if (peer.canBeInvitedBy() == false) {
                makeError(tr("error_user_forbid_invites"), 'Red', 10000, 'forbid_invites' + peer.id);
                f.querySelector('input').checked = false;
                return;
            }

            if (this.selected_friends.indexOf(id) == -1) {
                this.selected_friends.push(id);
                f.classList.add("friends-selected");
                f.querySelector('input').checked = true;
            } else {
                this.selected_friends = this.selected_friends.filter(item => item !== id);
                f.classList.remove("friends-selected");
                f.querySelector('input').checked = false;
            }

            console.log(e, this.selected_friends)
            return;
        }

        window.im.setChatByPeerId(peer.id);
    }

    isSelected(peer) {
        return this.selected_friends.indexOf(peer.id) != -1;
    }

    onCreateChat(e) {
        e.target.classList.add("lagged");

        const ids = this.selected_friends;

        // пустые беседы нужны!!
        if (ids.length < 0) {
            fastError(tr("error_chat_not_enough_friends"));
            e.target.classList.remove("lagged");
            return;
        }

        const msg = new CMessageBox({
            title: tr("create_chat"),
            body: `<div><span>${tr('name_your_chat')}</span><input id="chatInputTitle" type="text"></div>`,
            close_on_buttons: false,
            buttons: [tr('create'), tr('cancel')],
            callbacks: [() => {
                let title = '';
                title = document.querySelector("#chatInputTitle").value;
                window.OVKAPI.call('messages.createChat', {
                    'title': title,
                    'user_ids': ids,
                }).then((resp) => {
                    e.target.classList.remove("lagged");
                    msg.close();

                    window.im.setChatByPeerId(resp + 2000000000);
                }).catch(err => {
                    fastError(String(err));
                });
            }, () => {msg.close()}]
        })

    }

    async loadNext() {
        await this.loadFriends(this.last_offset + 10);
    }

    _appear(container) {
        this._render(container);
    }

    _render(container) {
        const ref = this.referrer;

        render(html`
        <${FriendsPage}
            friends=${this.friends}
            count=${this.total_count}
            referrer=${ref}
            onFriendClick=${(e, peer) => this.onFriendClick(e, peer)}
            onCreateChat=${(e) => this.onCreateChat(e)}
            isSelected=${(peer) => this.isSelected(peer)}
            onLoadMore=${() => this.loadNext()}
        />
        `, container);
    }

    appear(container, referrer = null) {
        this.referrer = referrer;
        this.selected_friends = []; // nulling

        container.classList.remove('hidden');

        if (this.has_inited == false) {
            this.loadFriends().then(() => {
                this.has_inited = true;
                this._appear(container)
            })
        } else {
            this._appear(container)
        }
    }
}

(async () => {
    if (window.im == null) {
        window.im = new IM();
    }

    await window.im.init();
})()
