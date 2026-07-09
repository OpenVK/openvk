import { ChatMessage, ChatGeneralForm } from './messages.js';
import { Conversation } from './conversations.js';
import { render, html, PeerTabsView, ActionsBar, MessageListView, InputArea } from './components.js';

const u = window.u;
const collect_attachments = window.collect_attachments;

export class Messenger {
  async init() {
    this.insert_type = 'page';
    this.view = new MessengerViewModel();
  }

  hasAppeared(container) {
    return container.querySelector('.messenger-app') != null;
  }

  appear(container = null) {
    container.classList.remove('hidden');
    if (this.hasAppeared(container)) {
      this.view._loadDraft(this.view.getCurrentChat());
      this.view._render(container);
      return;
    }

    this.view._render(container);
    this.view.messagesListBlock = container.querySelector(".messenger-app--messages");
    this.view.messagesList  = container.querySelector(".messenger-app--messages-array");
    this.view.appEl = container;
  }

  hide(container) {
    container.classList.add('hidden');
  }

  async sendToCurrentCorresponder() {
    const view = this.view;
    const text = view.currentDraft;
    const reply_to = view.replyTo;
    let reply_param = null;
    let attachments_list = null;
    const corresponder = window.im.corresponder;

    const msg = new ChatMessage({
      'from_id': window.im.current.id,
      'peer_id': corresponder.id,
      'date': Math.round((new Date()).getTime() / 1000),
    });

    if (reply_to) {
      reply_param = reply_to;
    }

    const attachments = collect_attachments(u('.messenger-app--input---messagebox'));
    if (attachments.length > 0) {
      attachments_list = attachments;
    }

    msg._guessSender();
    msg.setText(text);

    return await corresponder.sendMessage(msg, reply_param, attachments_list);
  }
}

export class MessengerViewModel {
  constructor() {
    this.opened_tabs = [];
    this.currentDraft = '';
    this.replyTo = null;
    this.is_showing_profile = false;
    this.is_loading = false;
    this.drafts = {};
    this.scrolls = {};
    this.current_chat = null;
    this.messagesTrigger = 0;
    this.selected_messages = [];
    this.MAX_SELECTED_MESSAGES = 100;
    this.appEl = null;
    this.messagesListBlock = null;
  }

  _triggerUpdate() {
    this.messagesTrigger++;
    window.im.conversations.view._update();
    this._render();
  }

  _triggerUpdateSlightly() {
    this.messagesTrigger++;
    this._render();
  }

  _render(container) {
    const root = container || this.appEl;
    if (!root) return;

    const currentConv = this.getCurrentChat();
    const peer = currentConv ? currentConv.peer : null;

    render(html`
      <div id="chat-page" class="${this.is_showing_profile ? 'peer-shown' : ''}">
        <div class="chat-window">
          <${PeerTabsView} tabs=${this.opened_tabs} currentChat=${this.current_chat} />
          <${ActionsBar}
            count=${this.selected_messages.length}
            onDelete=${() => this.callDeletion()}
            onUnselect=${() => this.unselect()}
            onReply=${() => this.onReplyButtonClick()}
          />
          <div class="messenger-app">
            <div id="messenger-app--down-button" style="display:none"
                 onClick=${() => this._scrollToEnd()}>DOWN</div>
            <${MessageListView} messages=${peer ? peer.divided_messages : []} />
            <${InputArea}
              replyTo=${this.replyTo}
              onRemoveReply=${() => this.removeReply()}
              onSend=${() => this.sendMessage()}
              onKeyPress=${(e) => this.onTextareaKeyPress(e)}
              currentDraft=${this.currentDraft}
              onInput=${(e) => { this.currentDraft = e.target.value; }}
              togglePeerInfo=${(e) => this.togglePeerInfo() }
            />
          </div>
        </div>
        ${this.is_showing_profile && html`
          <div class="peer-window">
            <div><a onClick=${() => this.togglePeerInfo()}>${tr('back')}</a></div>
          </div>
        `}
      </div>
    `, root);
  }

  onTextareaKeyPress(e) {
    const ta = e.target;
    if (e.which === 13) {
      if (!e.metaKey && !e.shiftKey) {
        ta.blur();
        this.sendMessage();
        ta.focus();
        return false;
      }
    }
    return true;
  }

  onMessageClick(msg, e) {
    if (e.buttons !== 1 && e.type == 'mousemove') return;
    if (this.replyTo != null) return;

    const target = e.target;
    if (!target.matches('.text, .time span') || this.selected_messages.length > 0) {
      e.preventDefault();
      this.toggleMessageSelection(msg, e);
    }
  }

  onReplyButtonClick() {
    const ids = this.selected_messages;
    const current_chat = this.getCurrentChat();
    const m = current_chat.peer._findMessageById(ids[0]);
    this.unselect();
    this.replyTo = m;
    this._render();
  }

  removeReply() {
    this.replyTo = null;
    this._render();
  }

  toggleMessageSelection(msg, e) {
    if (!this.isMessageSelected(msg)) {
      this.selectMessage(msg);
    } else {
      this.unselectMessage(msg);
    }
  }

  togglePeerInfo() {
    this.is_showing_profile = !this.is_showing_profile;
    this._render();
  }

  onScrollDownButtonClick() {
    this._scrollToEnd();
  }

  async onMessagesScroll(e) {
    if (this.is_loading) return;
    this.is_loading = true;

    const _scroll = document.documentElement.scrollTop;

    if (_scroll < 21) {
      await window.im.corresponder._messagesLoad_UpFromLastChunk();
    } else {
      const scrollBottom = document.documentElement.scrollHeight - _scroll - document.documentElement.clientHeight;

      if (scrollBottom < 10) {
        if (window.im.corresponder._chunks_HasMoreNewerChunkRelativelyToCurrentChat()) {
          console.log('IM | Scrolled to the beginning');
        }
      }

      if (scrollBottom > 600) {
        document.querySelector('.messenger-app--tab-messenger').classList.add('messenger-app--overscrolled');
      } else {
        document.querySelector('.messenger-app--tab-messenger').classList.remove('messenger-app--overscrolled');
      }
    }

    this.is_loading = false;
  }

  selectMessage(msg) {
    this.selected_messages.push(msg.id);
    this._render();
  }

  unselectMessage(msg) {
    const idx = this.selected_messages.indexOf(msg.id);
    if (idx !== -1) this.selected_messages.splice(idx, 1);
    this._render();
  }

  isMessageSelected(msg) {
    return this.selected_messages.indexOf(msg.id) !== -1;
  }

  get selected_messages_count() {
    return this.selected_messages.length;
  }

  callDeletion() {
    const ids = this.selected_messages;

    const current_chat = this.getCurrentChat();
    const box = new CMessageBox({
      title: tr("message_deletion", ids.length),
      body: tr("message_deletion_confirm"),
      buttons: [tr('yes'), tr('no')],
      callbacks: [async () => {
        let ids2 = [];
        ids.forEach((item) => {
          let m = current_chat.peer._findMessageById(item);
          ids2.push(current_chat.peer.id + '_' + item);
          m.setDeleted(true);
        });
        await window.OVKAPI.call("messages.delete", {
          "message_ids": ids2.join(","),
          "peer_id": current_chat.peer.id
        })
        this._triggerUpdate();
        this.unselect();
      }, () => {}],
    });
  }

  unselect() {
    this.selected_messages = [];
    this._render();
  }

  async sendMessage() {
    const _tmp_atts = collect_attachments(u('.messenger-app--input---messagebox'));

    if (this.currentDraft === '' && _tmp_atts.length == 0) return false;

    this._scrollToEnd();

    window.im.messenger.sendToCurrentCorresponder().then(() => {
      this._scrollToEnd();
    });

    this._eraseDraftFor({ peer: window.im.current });
    this._eraseCurrentDraft();
    this.removeReply();
  }

  hasChat(conversation) {
    return this.opened_tabs.indexOf(conversation) !== -1;
  }

  getChatWith(chat_general_form) {
    let is = null;
    window.im.conversations.convs.forEach((item) => {
      if (item.peer.id == chat_general_form.id) {
        is = item;
        return;
      }
    });

    if (!is) {
      return new Conversation({ 'peer': chat_general_form });
    }

    return is;
  }

  setChat(conv, pushstate = true) {
    this.current_chat = this.opened_tabs.indexOf(conv);
    this.unselect();

    if (pushstate) {
      window.im._pushState('/im?sel=' + conv.peer.id);
    }
  }

  addChat(conv) {
    this.opened_tabs.push(conv);
    return this.opened_tabs.length - 1;
  }

  getCurrentChat() {
    if (this.current_chat === null || this.current_chat === undefined) return null;
    return this.opened_tabs[this.current_chat] || null;
  }

  getTabsCount() {
    return this.opened_tabs.length;
  }

  preselectChat(conversation) {
    if (!this.hasChat(conversation)) {
      this.addChat(conversation);
    }
  }

  closeChat(conv) {
    const idx = this.opened_tabs.indexOf(conv);
    if (idx !== -1) this.opened_tabs.splice(idx, 1);
  }

  _saveDraft(to_chat) {
    if (!to_chat) return;
    this.drafts[to_chat.peer.id] = this.currentDraft;
    this.scrolls[to_chat.peer.id] = document.documentElement.scrollTop;
    this._eraseCurrentDraft();
  }

  _eraseDraftFor(chat) {
    this.drafts[chat.peer.id] = undefined;
    u('.messenger-app--input---messagebox .post-horizontal').html('');
    u('.messenger-app--input---messagebox .post-vertical').html('');
  }

  _eraseCurrentDraft() {
    this.currentDraft = '';
  }

  _loadDraft(for_chat) {
    if (!for_chat) return;
    const _draft = this.drafts[for_chat.peer.id];
    if (_draft && _draft !== '') {
      this.currentDraft = _draft;
    }
    const _scroll = this.scrolls[for_chat.peer.id];
    if (_scroll) {
      this._scrollTo(_scroll);
    } else {
      this._scrollToEnd();
    }
  }

  _scrollTo(scroll_progress) {
    document.documentElement.scroll({ top: scroll_progress });
  }

  _scrollToEnd() {
    this._scrollTo(document.documentElement.scrollHeight);
  }

  _changeHeight() {
    let maybe_distance = 100;
    let tabs_height = u('.messages--peers-tabs').nodes[0].clientHeight;
    this.appEl.parentNode.style.height = window.outerHeight - tabs_height - maybe_distance + 'px';
  }
}

export class LongPollConnection {
  async create() {
    this.lp = await window.OVKAPI.call('messages.getLongPollServer', {});
  }

  listen() {
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
      this.listen();
    };
    xhr.send();
  }
}
