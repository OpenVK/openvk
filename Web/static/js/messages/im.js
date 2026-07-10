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
    const _tabsContainer = document.createElement('div');
    this.root.insertAdjacentHTML('afterbegin', `
      <div id="tabs-wr" class="messenger-app--global-tabs tabs">
        <div class="inner-tabs"></div>
        <div id="spec-actions">
          <a>${tr('to_friendslist')}</a>
        </div>
      </div>
    `);

    const tabsContainer = document.createElement('div');
    tabsContainer.className = '';
    _tabsContainer.appendChild(tabsContainer);

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
      this.root.querySelector("#tabs-wr .inner-tabs").appendChild(tabLink);
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

u(document).on("click", "#_message_send", async (e) => {
  e.preventDefault();

  await showUserDialog(Number(e.target.dataset.eid));
})

async function showUserDialog(userId) {
  const is_club = userId < 0;
  let userData = null;

  if (is_club) {
    userData = await window.OVKAPI.call('groups.getById', {
      group_ids: Math.abs(userId),
      fields: 'photo_200,online,last_seen',
    });
  } else {
    userData = await window.OVKAPI.call('users.get', {
      user_ids: userId,
      fields: 'photo_200,online,last_seen',
    });
  }

  if (!userData || !userData[0]) return;

  const user = userData[0];
  const isOnline = user.online == 1;
  const lastSeen = user.last_seen
    ? new Date(user.last_seen.time * 1000).toLocaleString()
    : '';
  const avatar = user.photo_200 || user.photo_max || '';
  const fullName = window.escapeHtml(user.first_name + ' ' + user.last_name);

  const dialogId = 'user-dialog-' + random_int(0, 99999);
  const bodyId = dialogId + '-body';

  const html = `
<style>
#${dialogId} .udlg-wrap {
padding: 16px;
min-width: 420px;
}
#${dialogId} .udlg-user {
display: flex;
align-items: center;
gap: 12px;
padding-bottom: 12px;
border-bottom: 1px solid #e5e5e5;
margin-bottom: 12px;
}
#${dialogId} .udlg-avatar {
width: 64px;
height: 64px;
border-radius: 50%;
object-fit: cover;
flex-shrink: 0;
}
#${dialogId} .udlg-info {
flex: 1;
}
#${dialogId} .udlg-name {
font-size: 15px;
font-weight: 700;
color: #222;
}
#${dialogId} .udlg-online {
font-size: 12px;
margin-top: 2px;
}
#${dialogId} .udlg-online.online {
color: #4bb34b;
}
#${dialogId} .udlg-online.offline {
color: #999;
}
#${dialogId} .udlg-goto {
display: block;
font-size: 12px;
color: #4a76a8;
margin-bottom: 8px;
cursor: pointer;
}
#${dialogId} .udlg-goto:hover {
text-decoration: underline;
}
#${dialogId} .udlg-input-area {
display: flex;
flex-direction: column;
gap: 6px;
}
#${dialogId} .udlg-textarea {
width: 100%;
min-height: 60px;
resize: vertical;
box-sizing: border-box;
padding: 8px;
font-size: 13px;
border: 1px solid #d3d9de;
border-radius: 4px;
}
#${dialogId} .udlg-actions {
display: flex;
justify-content: space-between;
align-items: center;
}
#${dialogId} .udlg-attach-btn {
cursor: pointer;
color: #4a76a8;
font-size: 13px;
}
#${dialogId} .udlg-attach-btn:hover {
text-decoration: underline;
}
#${dialogId} .udlg-attach-menu {
display: flex;
gap: 6px;
flex-wrap: wrap;
margin-top: 6px;
}
#${dialogId} .udlg-attach-menu a {
font-size: 12px;
color: #4a76a8;
cursor: pointer;
padding: 4px 8px;
background: #f0f2f5;
border-radius: 4px;
}
#${dialogId} .udlg-attach-menu a:hover {
background: #e0e4e8;
}
#${dialogId} .udlg-send {
flex-shrink: 0;
}
#${dialogId} .post-horizontal {
display: flex;
gap: 6px;
flex-wrap: wrap;
margin-top: 4px;
}
#${dialogId} .post-horizontal .upload-item {
position: relative;
display: inline-block;
}
#${dialogId} .post-horizontal .upload-item img {
max-height: 60px;
border-radius: 4px;
}
#${dialogId} .post-horizontal .upload-delete {
position: absolute;
top: -6px;
right: -6px;
background: #e74c3c;
color: #fff;
border-radius: 50%;
width: 18px;
height: 18px;
font-size: 12px;
line-height: 18px;
text-align: center;
cursor: pointer;
}
</style>
<div class="udlg-wrap" id="${bodyId}">
<div class="udlg-user">
  <img class="udlg-avatar" src="${avatar}" alt="" />
  <div class="udlg-info">
    <div class="udlg-name">${fullName}</div>
    <div class="udlg-online ${isOnline ? 'online' : 'offline'}">
      ${isOnline ? tr('online') : (lastSeen ? tr('last_seen') + ' ' + lastSeen : tr('offline'))}
    </div>
  </div>
</div>
<div id="write" class="udlg-input-area">
  <a class="udlg-goto">${tr('go_to_dialog')} &rarr;</a>
  <textarea class="udlg-textarea small-textarea" placeholder="${tr('enter_message')}"></textarea>
  <div class="post-horizontal"></div>
  <div class="udlg-actions">
    <div>
      <a class="udlg-attach-btn menu_toggler">${tr('attach')}</a>
      <div class="udlg-attach-menu up_direction hidden" id="wallAttachmentMenu">
        <a id="__photoAttachment">${tr('photo')}</a>
        <a id="__videoAttachment">${tr('video')}</a>
        <a id="__audioAttachment">${tr('audio')}</a>
        <a id="__documentAttachment">${tr('document')}</a>
        <a onclick="initGraffiti(event)">${tr('graffiti')}</a>
      </div>
    </div>
    <button class="button udlg-send" data-uid="${userId}">${tr('send')}</button>
  </div>
</div>
</div>`;

  const msg = new CMessageBox({
    title: fullName,
    body: html,
    buttons: [tr('close')],
    callbacks: [Function.noop],
  });

  const node = msg.getNode();

  u(node).on('click', '.udlg-send', async function(e) {
    const btn = e.currentTarget;
    const targetUserId = parseInt(btn.dataset.uid);
    if (!targetUserId) return;

    const wrap = btn.closest('.udlg-wrap');
    const textarea = wrap.querySelector('.udlg-textarea');
    const text = textarea ? textarea.value.trim() : '';
    if (!text) return;

    btn.disabled = true;
    btn.textContent = tr('sending');

    try {
      await window.OVKAPI.call('messages.send', {
        peer_id: targetUserId,
        message: text,
      });
      msg.close();
    } catch (err) {
      btn.disabled = false;
      btn.textContent = tr('send');
      fastError(tr('error_sending_message'));
    }
  });

  u(node).on('click', '.menu_toggler', function(e) {
    const menu = u(e.target).closest('.udlg-actions').find('#wallAttachmentMenu');
    if (menu.hasClass('hidden')) {
      menu.removeClass('hidden');
    } else {
      menu.addClass('hidden');
    }
  });
}
