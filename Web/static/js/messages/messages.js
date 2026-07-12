export class MessagesChunk {
  constructor(items, do_reverse = false, count = 10, msg_offset = null) {
    this.messages = [];
    this.do_reverse = do_reverse;
    this.count = count;
    this.msg_offset = msg_offset;
    this.latest_message_index = 0;
    items.forEach((item) => {
      this.messages.push(item);
    });
  }

  get first_message() {
    if (this.do_reverse) {
      return this.messages[this.messages.length - 1];
    } else {
      return this.messages[0];
    }
  }

  get latest_message() {
    if (!this.do_reverse) {
      return this.messages[this.messages.length - 1];
    } else {
      return this.messages[0];
    }
  }

  async fetch(data) {
    const params = {
      'count': ChatGeneralForm.MESSAGES_PER_PAGE,
      'extended': 1,
      'fields': ChatGeneralForm.base_fields,
    };

    Object.assign(params, data);
    const messages = await window.OVKAPI.call('messages.getHistory', params);

    window.im.cached_profiles._moveToProfileCache(messages.profiles, messages.groups);

    const _l = _authorize(messages.items, messages.profiles, messages.groups, (item) => {
      return item.from_id;
    }, (item, author) => {
      item.sender = new ChatGeneralForm(author);
    }, (item, arr) => {
      arr.push(new ChatMessage(item));
    });

    _l.forEach((msg) => {
      this.messages.push(msg);
    });
  }

  isEnd() {
    return this.messages.length < this.count;
  }

  getMessages() {
    if (this.do_reverse) {
      return Array.from(this.messages).reverse();
    } else {
      return this.messages;
    }
  }

  _pushMessage(msg) {
    if (this.do_reverse) {
      this.messages.unshift(msg);
    } else {
      this.messages.push(msg);
    }
  }
}

export class DayChunk extends MessagesChunk {
  setDay(date) {
    this.date = date;
  }

  get readable_date() {
    return this.date;
  }
}

export class ChatGeneralForm {
  static chat_number = 2000000000;
  static MESSAGES_PER_PAGE = 20;
  static base_fields = 'photo_100';

  constructor(item) {
    this.data = item || {};
    this.message_chunks = [];
    this.message_chunks_order = [];
  }

  get id() {
    switch (this.supposed_type) {
      case 'user':
        return this.data.id;
      case 'club':
        return this.data.id * -1;
      case 'chat':
        if (this.data.id < this.chat_number) {
          return this.data.id + this.chat_number;
        } else {
          return this.data.id;
        }
    }
  }

  get is_deleted_by_me() {
    return this.data.deleted_by_me == 1;
  }

  get is_deleted() {
    return this.data.deleted == 1;
  }

  get supposed_type() {
    if (this.data.first_name) return 'user';
    if (this.data.name) return 'club';
    return 'chat';
  }

  get can_write() {
    return true;
  }

  get avatar_any() {
    return this.data.photo_100 ?? '/assets/packages/static/openvk/img/im/chat_meaningless.jpg';
  }

  get full_name() {
    switch (this.supposed_type) {
      case 'user':
        return window.escapeHtml(this.data.first_name + ' ' + this.data.last_name);
      case 'club':
        return window.escapeHtml(this.data.name);
      case 'chat':
        return window.escapeHtml(this.data.title);
    }
  }

  get name() {
    switch (this.supposed_type) {
      case 'user':
        return window.escapeHtml(this.data.first_name);
      case 'club':
        return window.escapeHtml(this.data.name);
      case 'chat':
        return window.escapeHtml(this.data.title);
    }
  }

  get page_url() {
    switch (this.supposed_type) {
      case 'user':
        return '/id' + this.data.id;
      case 'club':
        return '/club' + this.data.id;
    }
  }

  get chat_url() {
    return '/im?sel=' + this.id;
  }

  get chunks() {
    return this.message_chunks.slice(0).sort((a, b) => {
      const aTime = a.first_message?.sent || 0;
      const bTime = b.first_message?.sent || 0;
      return bTime - aTime;
    });
  }

  get messages() {
    const fnl = [];
    if (this._cached_all_messages != undefined) {
      return this._cached_all_messages;
    }

    this.chunks.forEach((chunk) => {
      chunk.getMessages().forEach((msg) => {
        fnl.push(msg);
      });
    });

    this._cached_all_messages = fnl;
    return fnl;
  }

  get divided_messages() {
    const dayChunks = [];
    const dateMap = new Map();
    this.chunks.forEach((chunk) => {
      chunk.getMessages().forEach((msg) => {
        if (!msg.sent) return;
        const dateKey = msg.conv_day;

        if (!dateMap.has(dateKey)) {
          const dayChunk = new DayChunk([]);
          dayChunk.setDay(dateKey);
          dayChunks.push(dayChunk);
          dateMap.set(dateKey, dayChunk);
        }

        dateMap.get(dateKey)._pushMessage(msg);
      });
    });

    dayChunks.sort((a, b) => a.date.localeCompare(b.date));

    return dayChunks;
  }

  _removeCache() {
    this._cached_all_messages = undefined;
  }

  static async resolveById(id) {
    if (id == 0) {
      return window.im._current;
    }

    if (id > this.chat_number) {
      const __ = await window.OVKAPI.call('messages.getConversationsById', { 'peer_ids': id, 'fields': ChatGeneralForm.base_fields });

      if (!__ || __.items.length == 0) {
        return null;
      }
      return __.items[0].conversation.peer;
    } else {
      if (id > 0) {
        const __ = await window.OVKAPI.call('users.get', { 'user_ids': id, 'fields': ChatGeneralForm.base_fields });
        return __[0];
      } else {
        const __ = await window.OVKAPI.call('groups.getById', { 'group_ids': Math.abs(id), 'fields': ChatGeneralForm.base_fields });
        if (__[0].type == 'undefined') {
          return null;
        }
        return __[0];
      }
    }
  }

  static async resolveByIdAndReturnClass(id) {
    const c = await ChatGeneralForm.resolveById(id);
    if (c == null) return undefined;
    return new ChatGeneralForm(c);
  }

  async getMessages(message_id, offset = 0) {
    const rev = true;
    const messages = new MessagesChunk([], rev);
    messages.latest_message_index = message_id;
    await messages.fetch({
      'start_message_id': message_id,
      'offset': 0,
      'peer_id': this.id,
    });

    return messages;
  }

  async sendMessage(msg, reply_to = null, attachments = null) {
    this._pushNewMessage(msg);
    window.im.messenger.view._scrollToEnd();
    const datas = {
      'peer_id': this.id,
      'message': msg.text,
      'attachments': msg.str_attachments,
    };

    if (reply_to != null) {
      datas['reply_to'] = reply_to.id;
    }

    if (attachments != null) {
      datas['attachment'] = attachments.join(',');
    }

    const resp = await window.OVKAPI.call('messages.send', datas);
    msg.data.id = resp;
    console.info('IM | Sent message to ' + this.id);
  }

  _findMessageById(id) {
    let f = null;
    this.messages.forEach((e) => {
      if (f != null) return;
      if (e.id == id) f = e;
    });

    return f;
  }

  _pushNewMessage(msg) {
    this._getLatestChunk()._pushMessage(msg);
    this._removeCache();
    window.im.messenger.view._triggerUpdate();
  }

  _getMostActualChunk() {
    return this.message_chunks[0];
  }

  _getLatestChunk(create_empty = true) {
    if (create_empty && this.chunks[this.chunks.length - 1] == undefined) {
      console.log('IM | Adding empty chunk')//, this.chunks, this.data)
      const c = new MessagesChunk([]);
      this.message_chunks.push(c);
    }

    return this.chunks[this.chunks.length - 1];
  }

  _isEndReached() {
    return this._end_reached ?? false;
  }

  _appendMessagesChunk(messages, before = false) {
    this._messages_inited = true;
    let id = this.message_chunks.push(messages);
  }

  _isMessagesInited() {
    return this._messages_inited;
  }

  async _messagesLoad_UpFromLastChunk() {
    if (this._isEndReached()) return;

    const _id = this._getLatestChunk().first_message;
    const msgs = await this.getMessages(_id.id);

    this._end_reached = msgs.isEnd();

    const prev_scroll = window.im.messenger.view.messagesListBlock.scrollTop;
    const prev_height = window.im.messenger.view.messagesListBlock.scrollHeight;

    if (!this._end_reached) {
      this._appendMessagesChunk(msgs, true);
      window.im.messenger.view._triggerUpdate();
    }

    if (!this._end_reached) {
      setTimeout(() => {
        let new_scroll = prev_scroll + (window.im.messenger.view.messagesListBlock.scrollHeight - prev_height);
        window.im.messenger.view._scrollTo(new_scroll);
      }, 1);
    }
  }

  _chunks_HasMoreNewerChunkRelativelyToCurrentChat() {
    return false;
  }
}

function _authorize(items, profiles = null, groups = null, get_id = null, set_id = null, finalize = null) {
  let fin = [];

  items.forEach((item) => {
    const _id = get_id(item);
    let author = null;

    if (!profiles && !groups) {
      author = window.im.cached_profiles._findCachedProfileById(_id);
    } else {
      author = window.find_author(_id, profiles, groups);
    }

    set_id(item, author);

    if (finalize) {
      finalize(item, fin);
    }
  });

  if (finalize) {
    return fin;
  }
}

export class ChatMessage {
  static AUTHOR_NAME_HIDE_TIMEOUT = 600; // 60 * 10

  doHideHead(another_msg) {
    let _time_eq = another_msg.data.date - this.data.date;
    return this.data.from_id == another_msg.data.from_id && _time_eq < ChatMessage.AUTHOR_NAME_HIDE_TIMEOUT && this.is_action == false;
  }

  constructor(item = {}) {
    this.data = item;
  }

  _guessSender() {
    this.data.sender = window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(this.data.from_id);
  }

  get sent() {
    return new Date(this.data.date * 1000);
  }

  get sender() {
    if (!this.data.sender) {
      this._guessSender();
    }

    return this.data.sender;
  }

  get text() {
    return escapeHtml(this.data.text);
  }

  get global_id() {
    return this.data.global_id;
  }

  get id() {
    return this.data.id;
  }

  get is_action() {
    return this.data.action != null;
  }

  get peer_id() {
    return this.data.peer;
  }

  get from_id() {
    return this.data.from_id;
  }

  get attachments() {
    const _at = this.data.attachments;
    if (!_at) return [];
    return _at;
  }

  get str_attachments() {
    const _at = this.attachments;
    if (_at.length == 0) return '';
  }

  get readable_date() {
    return this.sent.toLocaleTimeString(navigator.language, {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    });
  }

  get conv_date() {
    const date = this.sent;
    let is_today = date.toDateString() == new Date().toDateString();

    const diffMs = Date.now() - date;
    const diffHours = diffMs / (1000 * 60 * 60);
    const isLessThan6Hours = diffHours >= 0 && diffHours < 6;

    if (isLessThan6Hours) {
      return this.readable_date;
    }

    return this.conv_day;
  }

  get conv_day() {
    const date = this.sent;
    if (date.getFullYear() == new Date().getFullYear()) {
      return date.toLocaleDateString(navigator.language)
    } else {
      return date.toLocaleDateString(navigator.language, {
        month: '2-digit',
        day: '2-digit'
      })
    }
  }

  get conv_summary() {
    let f = "";
    if (this.data.attachments.length > 0) {
      f = ("(" + tr(this.data.attachments[0].type) + ")").toLowerCase();
      f += " ";
    }

    f += this.data.text;
    return ovk_proc_strtr(f, 100);
  }

  static fromEvent(event) {
    const [, id, flags, peer, ts, subject, text, attachments, randomId] = event;

    const msg = new ChatMessage({
      'id': id,
      'flags': flags,
      'from_id': attachments.from,
      'date': ts,
      'peer': peer,
      'text': text,
      'attachments': attachments,
      'random_id': randomId,
    });
    msg._guessSender();

    return msg;
  }

  setDeleted(by_me = false) {
    this.data.deleted = 1;
    if (by_me) {
      this.data.deleted_by_me = 1;
    }
    this.data.text = tr('message_is_deleted');
    this.data.attachments = [];
  }

  setText(text) {
    this.data.text = text;
  }
}
