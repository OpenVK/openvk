import { ChatGeneralForm } from './messages.js';
import { EventHandler } from './events.js';
import { Messenger, LongPollConnection } from './messenger.js';
import { Conversations } from './conversations.js';

const tr = window.tr;
const u = window.u;

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

export class IM {
  constructor() {
    this.tabs = ['conversations', 'messenger'];
    this.tab = '';
  }

  async _checkSel(loc) {
    const _sel = Number(loc.searchParams.get('sel'));
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

  async init(container) {
    if (window.OVKAPI == null) {
      await new Promise((resolve) => setTimeout(resolve, 1000));
    }

    this.cached_profiles = new ProfilesCache();
    this.event_handler = new EventHandler();
    this.root = container;
    await this._loadCurrent();
    this._initTabs();

    if (!this.conversations) {
      this.conversations = new Conversations();
      await this.conversations.init();
    }

    if (!this.messenger) {
      this.messenger = new Messenger();
      await this.messenger.init();
    }

    const found = await this._checkSel(new URL(location.href));
    if (!found) {
      this.selectTab('conversations');
    }

    this.lp = new LongPollConnection();
    await this.lp.create();
    this.lp.listen();
  }

  closeChat(conv) {
    if (this.messenger.view.getTabsCount() - 1 == 0) {
      this.selectTab('conversations');
    } else {
      const _id = this.messenger.view.opened_tabs.indexOf(conv);
      this.selectChat(this.messenger.view.opened_tabs[Math.max(0, _id - 1)]);
    }

    this.messenger.view.closeChat(conv);
  }

  async selectChat(conv) {
    this.messenger.view.preselectChat(conv);

    const _url = new URL(location.href);
    const _start_from = _url.searchParams.get('start_from');

    this.messenger.view._saveDraft(this.messenger.view.getCurrentChat());
    if (!conv.peer._isMessagesInited()) {
      const messages = await conv.peer.getMessages(_start_from);
      conv.peer._appendMessagesChunk(messages);
    }

    this.messenger.view.setChat(conv, false);
    this.selectTab('messenger');
    this.messenger.view._loadDraft(conv);
    this.messenger.view._scrollToEnd();
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
    const tabsContainer = document.createElement('div');
    tabsContainer.className = 'messenger-app--global-tabs tabs';
    this.root.appendChild(tabsContainer);

    this.tabs.forEach((tab) => {
      const tabWindow = document.createElement('div');
      tabWindow.className = 'messenger-app--tab-' + tab;
      tabWindow.setAttribute('data-window', tab);
      this.root.appendChild(tabWindow);

      const tabLink = document.createElement('a');
      tabLink.setAttribute('data-tab', tab);
      tabLink.className = 'tab';
      tabLink.textContent = tr('messenger_tab_' + tab);
      tabLink.onclick = (e) => this.selectTab(tab, e);
      tabsContainer.appendChild(tabLink);
    });
  }

  selectTab(tab_name) {
    if (this.tabs.indexOf(tab_name) == -1) {
      throw new Error('invalid tab');
    }

    this.tab = tab_name;

    switch (tab_name) {
      case 'conversations':
        this.conversations.appear(this._getTabWindow('conversations'));
        this.messenger.hide(this._getTabWindow('messenger'));
        this._pushState('/im');
        this._toggleScrollMode(false);
        break;

      case 'messenger':
        if (!window.im.corresponder) {
          return;
        }

        this.conversations.hide(this._getTabWindow('conversations'));
        this.messenger.appear(this._getTabWindow('messenger'));
        this._toggleScrollMode(true);

        try {
          window.im._pushState('/im?sel=' + window.im.messenger.view.getCurrentChat().peer.id);
        } catch (e) {
          console.error(e);
        }

        break;
    }

    u(this.root).find('.tabs .tab').attr('id', '');
    u(this.root).find(`.tabs .tab[data-tab='${tab_name}']`).attr('id', 'activetabs');
  }

  _getTabWindow(tab_name) {
    return this.root.querySelector(`div[data-window="${tab_name}"]`);
  }

  _toggleScrollMode(enable = true) {
    if (window.isMobile && window.isMobile()) {
      return;
    }

    if (enable) {
      u('body').addClass('no-scroll');
    } else {
      u('body').removeClass('no-scroll');
    }
  }

  get is_active() {
    const is_chat_page = location.pathname.startsWith('/im');
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
}
