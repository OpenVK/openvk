/**
 * Each chunk gets a unique ID so ChatGeneralForm can track which chunk
 * is the "current" one without relying on array indices (which shift
 * when chunks are inserted).
 */
let _chunk_uid_counter = 0;

/**
 * Represents one page of messages fetched from the API.
 *
 * ┌──────────────────────────────────────────────────────────────────┐
 * │  Chunk orientation (do_reverse = true)                          │
 * │                                                                  │
 * │  The VK API `messages.getHistory` returns messages in            │
 * │  reverse-chronological order (newest first).                     │
 * │                                                                  │
 * │  Internal .messages array:  [newest ... oldest]                  │
 * │                                                                  │
 * │  first_message → .messages[last]  = oldest in chunk              │
 * │  latest_message → .messages[0]    = newest in chunk              │
 * │                                                                  │
 * │  getMessages() reverses the array so the renderer receives       │
 * │  messages in chronological order (oldest first).                 │
 * └──────────────────────────────────────────────────────────────────┘
 */
export class MessagesChunk {
  constructor(items, do_reverse = false, count = 10, msg_offset = null) {
    this.uid = _chunk_uid_counter++;
    this.messages = [];
    this.do_reverse = do_reverse;
    this.count = count;
    this.msg_offset = msg_offset;
    this.latest_message_index = 0;
    items.forEach((item) => {
      this.messages.push(item);
    });
  }

  /** Oldest message in this chunk (when do_reverse=true). */
  get first_message() {
    if (this.do_reverse) {
      return this.messages[this.messages.length - 1];
    } else {
      return this.messages[0];
    }
  }

  /** Newest message in this chunk (when do_reverse=true). */
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

  /** True when the API returned fewer messages than requested → no more pages. */
  isEnd() {
    return this.messages.length < this.count;
    //return this.messages.length < 1;
  }

  /**
   * Returns messages in the order the renderer expects.
   * When do_reverse=true, the internal array is newest-first so we reverse
   * to produce chronological order (oldest → newest).
   */
  getMessages() {
    if (this.do_reverse) {
      return Array.from(this.messages).reverse();
    } else {
      return this.messages;
    }
  }

  /** Prepend/append a single message (used when a new message arrives via LP). */
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
    static base_fields = 'photo_100,photo_200,photo_max,last_seen,photo_id,status,sex,can_write_private_message,can_invite,followers_count';

    constructor(item) {
        this.data = item || {};
        this.message_chunks = [];
        this.message_chunks_order = [];

        /**
        * UID of the chunk the user is currently scrolled to / anchored at.
        * Set after the first load. Changes when the user scrolls into a
        * newly loaded chunk (see _messagesLoad_UpFromCurrentChunk and
        * _messagesLoad_DownFromCurrentChunk).
        */
        this._currentChunkUid = null;

        /**
        * True when the API confirmed there are no older messages
        * (scrolling UP is exhausted).
        */
        this._end_reached = false;

        /**
        * True when the API confirmed there are no newer messages
        * (scrolling DOWN is exhausted — we are at the very end of the
        * conversation).
        */
        this._beginning_reached = false;

        this._messages_inited = false;
    }

    // ── identity ─────────────────────────────────────────────────────

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

    get supposed_type() {
        if (this.data.first_name) return 'user';
        if (this.data.name) return 'club';
        return 'chat';
    }

    get can_write() {
        return (this.data.can_write_private_message ?? 1) === 1;
    }

    canBeInvitedBy(group = null) {
        if (group != null) {
            return false;
        }

        return (this.data.can_invite ?? 1) === 1;
    }

    canUsersBeAddedBy(group = null) {
        if (group != null) {
            return false;
        }

        return this.data.admin_id === window.openvk.current_id;
    }

    get conversation_avatar_any() {
        if (this.id === window.openvk.current_id) {
            return "/assets/packages/static/openvk/img/im/saved_messages.png";
        }

        return this.avatar_any;
    }

    get avatar_any() {
        return this.data.photo_100 ?? '/assets/packages/static/openvk/img/im/chat_meaningless.jpg';
    }

    get avatar_big() {
        return this.data.photo_200;
    }

    get avatar_max() {
        return this.data.photo_max;
    }

    get conversations_full_name() {
        if (this.id === window.openvk.current_id) {
            return tr("saved_messages");
        }

        return this.full_name;
    }

    get full_name() {
        switch (this.supposed_type) {
            case 'user':
                return window.escapeHtml(this.data.first_name + ' ' + this.data.last_name);
            case 'club':
                return window.escapeHtml(this.data.name);
            case 'chat':
                return window.escapeHtml(this.data.title ?? tr("chat"));
            }
    }

    get conversations_name() {
        if (this.id === window.openvk.current_id) {
            return tr("saved_messages");
        }

        return this.name;
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

    get is_saved_messages() {
        return this.id === window.openvk.current_id;
    }

    get gender() {
        console.log(this.data.sex)
        if (this.data.sex == 1) {
            return 'female'
        }

        if (this.data.sex == 2) {
            return 'male'
        }

        return 'neutral';
    }

    get online_status_str() {
        if (this.data.followers_count) {
            return tr("followers", this.data.followers_count);
        }

        if (!this.data.last_seen) {
            return tr("im_was_online_unkown_" + this.gender).toLowerCase();
        }

        const time = this.data.last_seen.time;
        const date = new Date(time);
        const today = new Date();

        if (date.getDate() === today.getDate()) {
            return tr("im_was_online_today_" + this.gender, "00:00").toLowerCase();
        }

        if (date.getDate() === today.getDate() - 1) {
            return tr("im_was_online_yesterday_" + this.gender, "00:00").toLowerCase();
        }

        return tr("im_was_online_yesterday_" + this.gender, "00:00", "11.11.11").toLowerCase();
    }

    // ── chunk management ─────────────────────────────────────────────

    /**
    * Returns all chunks sorted newest-first.
    *
    * Sorting key: first_message (the *oldest* message when do_reverse=true)
    * descending, so the chunk whose oldest message is the most recent comes
    * first.
    *
    * Sorted order:  [newest_chunk, ..., oldest_chunk]
    * Indices:       [0            , ..., N-1          ]
    */
    get chunks() {
        return this.message_chunks.slice(0).sort((a, b) => {
            const aTime = a.first_message?.sent || 0;
            const bTime = b.first_message?.sent || 0;
            return bTime - aTime;
        });
    }

    /**
    * Returns ALL messages from ALL chunks in chronological order.
    * Iterates chunks from oldest to newest (reversed sorted order)
    * so that the flattened result is oldest-to-newest overall.
    */
    get messages() {
        const fnl = [];
        if (this._cached_all_messages != undefined) {
            return this._cached_all_messages;
        }

        const sorted = this.chunks; // newest-first
        for (let i = sorted.length - 1; i >= 0; i--) {
            sorted[i].getMessages().forEach((msg) => fnl.push(msg));
        }

        this._cached_all_messages = fnl;
        return fnl;
    }

    get divided_messages() {
        const dayChunks = [];
        const dateMap = new Map();

        // Iterate chunks from oldest to newest so messages are pushed
        // into each DayChunk in correct chronological order.
        const sorted = this.chunks; // newest-first
        for (let i = sorted.length - 1; i >= 0; i--) {
            const chunk = sorted[i];
            chunk.getMessages().forEach((msg) => {
                if (!msg.sent) return;
                if (msg.is_deleted_formally) return;
                const dateKey = msg.sort_date;

                if (!dateMap.has(dateKey)) {
                    const dayChunk = new DayChunk([]);
                    dayChunk.setDay(dateKey);
                    dayChunks.push(dayChunk);
                    dateMap.set(dateKey, dayChunk);
                }

                dateMap.get(dateKey)._pushMessage(msg);
            });
        }

        dayChunks.sort((a, b) => a.date.localeCompare(b.date));

        return dayChunks;
    }

    get is_muted() {
        return false;
    }

    _removeCache() {
        this._cached_all_messages = undefined;
    }

    // ── initial loading ──────────────────────────────────────────────

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

    /**
    * Fetch one page of messages from the API.
    *
    * @param {number|null} message_id  - start_message_id passed to VK API.
    *        When null the API returns the most recent messages.
    * @param {number} offset           - offset relative to start_message_id.
    *        Negative values go further back in history.
    */
    async getMessages(message_id, offset = 0) {
        const rev = true;
        const messages = new MessagesChunk([], rev);
        messages.latest_message_index = message_id;

        const params = {
            'start_message_id': message_id,
            'offset': offset,
            'peer_id': this.id,
        };

        await messages.fetch(params);

        return messages;
    }

    /**
    * Fetch messages *newer* than the given message_id.
    * Uses a trick: offset=-count to get `count` messages *after*
    * start_message_id (the VK API offset works in reverse when
    * start_message_id is set — negative offset goes toward newer).
    */
    async getMessages_NewerThan(message_id) {
        const rev = true;
        const messages = new MessagesChunk([], rev);
        messages.latest_message_index = message_id;

        const params = {
        'start_message_id': message_id,
        'offset': -(ChatGeneralForm.MESSAGES_PER_PAGE),
        'peer_id': this.id,
        };

        await messages.fetch(params);

        return messages;
    }

    /**
    * Fetch a chunk that includes the given message_id and insert it
    * into message_chunks. The new chunk becomes the current one.
    *
    * @param {number} messageId
    */
    async loadChunkByMessageId(messageId) {
        const msgs = await this.getMessages(messageId, 0);
        this._appendMessagesChunk(msgs, false);
        this._setCurrentChunkByUid(msgs.uid);
        this._removeCache();
        window.im.messenger.view._triggerUpdate();
    }

    // ── sending ──────────────────────────────────────────────────────

    async sendMessage(msg, reply_to = null, attachments = null) {
        this._pushNewMessage(msg);
        window.im.messenger.view._scrollToEnd();
        const datas = {
            'peer_id': this.id,
            'message': msg.text_raw,
            'attachment': msg.str_attachments,
        };

        if (reply_to != null) {
            datas['reply_to'] = reply_to.id;
        }

        if (attachments != null) {
            datas['attachment'] = attachments.join(',');
        }

        try {
            const resp = await window.OVKAPI.call('messages.send', datas);
            msg.data.id = resp;
            console.info('IM | Sent message to ' + this.id);
        } catch (e) {
            let d = String(e);
            if (d.startsWith("Error: Broker failure")) {
                d = d.replace("Error: Broker failure: ", "");
            }

            msg.data.error_text = d;
            msg.data.resend_params = datas;
            console.error('IM | Did not sent message to ' + this.id, ': ', e);
            window.im.messenger.view._triggerUpdate();
        }
    }

    _findMessageById(id) {
        let f = null;
        this.messages.forEach((e) => {
            if (f != null) return;
            if (e.id == id) f = e;
        });

        return f;
    }

    async _findMessageByIdFromApi(id) {
        return this._findMessageById(id);
    }

    /**
    * Push a newly-arrived message into the *newest* chunk
    * (the one at index 0 in the sorted chunks array).
    */
    _pushNewMessage(msg, conv = null, check_chunk = true) {
        console.log(msg)
        const newest = this._getNewestChunk(check_chunk);

        if (!newest && conv != null) {
            conv.updateLastMessage(msg);
            return;
        }

        newest._pushMessage(msg);
        this._removeCache();
        window.im.messenger.view._triggerUpdate();
    }

    /**
    * Returns the newest chunk (index 0 of the sorted array).
    * Creates an empty one if none exist.
    */
    _getNewestChunk(check_chunk = true) {
        const sorted = this.chunks;
        if (sorted.length === 0 && check_chunk == true) {
            const c = new MessagesChunk([]);
            this.message_chunks.push(c);
            return c;
        }
        return sorted[0];
    }

    /**
    * Returns the first (newest) chunk in the sorted array,
    * creating an empty one if needed.
    * Despite the misleading name, this gives us the NEWEST chunk.
    */
    _getMostActualChunk() {
        return this.message_chunks[0];
    }

    /**
    * Returns the LAST chunk in the sorted array — i.e. the OLDEST one.
    *
    * Despite the name "_getLatestChunk", it returns the oldest chunk
    * because `this.chunks` is sorted newest-first.
    *
    * Creates an empty chunk if none exist (when create_empty = true).
    */
    _getLatestChunk(create_empty = true) {
        if (create_empty && this.chunks[this.chunks.length - 1] == undefined) {
        console.log('IM | Adding empty chunk');
        const c = new MessagesChunk([]);
            this.message_chunks.push(c);
        }

        return this.chunks[this.chunks.length - 1];
    }

    // ── scrolling boundaries ─────────────────────────────────────────

    _isEndReached() {
        return this._end_reached ?? false;
    }

    _isBeginningReached() {
        return this._beginning_reached ?? false;
    }

    /**
    * Find the "current" chunk in the sorted chunks array.
    * The current chunk is the one the user is anchored to.
    *
    * Returns { chunk, index } or null if no current chunk is set.
    */
    _findCurrentChunk() {
        if (this._currentChunkUid == null) return null;
        const sorted = this.chunks;
        for (let i = 0; i < sorted.length; i++) {
            if (sorted[i].uid === this._currentChunkUid) {
                return { chunk: sorted[i], index: i };
            }
        }
        return null;
    }

    /**
    * Mark a chunk as the "current" one by its UID.
    */
    _setCurrentChunkByUid(uid) {
        this._currentChunkUid = uid;
    }

    // ── appending chunks ─────────────────────────────────────────────

    /**
    * Append a newly loaded chunk to the raw message_chunks array.
    * Before appending, sets it as the _currentChunk if none is set yet.
    */
    _appendMessagesChunk(messages, before = false, compare_with = null) {
        this._messages_inited = true;

        // если сообщений в чате очень мало, то будет дублирование.
        // грубое решение проблемы
        if (compare_with != null) {
            const ids = [];
            compare_with.messages.forEach(msg => {
                ids.push(msg.id);
            })

            messages.messages = messages.messages.filter((item) => { return !ids.includes(item.id) });
        }

        console.log(compare_with, messages)
        if (!before) {
            this.message_chunks.unshift(messages);
        } else {
            this.message_chunks.push(messages);
        }

        // First ever chunk → make it the current chunk
        if (this._currentChunkUid == null) {
            this._currentChunkUid = messages.uid;
        }
    }

    _isMessagesInited() {
        return this._messages_inited;
    }

    // ── scrolling UP (older messages) ─────────────────────────────────

    /**
    * Load older messages relative to the current chunk.
    *
    * Chunk layout (sorted newest-first):
    *
    *   [ newest_chunk, ..., current_chunk, ..., oldest_chunk ]
    *     ↑ index 0                       ↑ index N-1
    *
    * When scrolling UP (looking for older messages):
    * 1. Find the current chunk in the sorted array.
    * 2. If there is already a chunk *after* it (at a higher index = older),
    *    just mark that one as the new current — no fetch needed.
    * 3. Otherwise, fetch one page of messages older than the current
    *    chunk's oldest message and insert the new chunk. The new chunk
    *    becomes the current one.
    *
    * Scrolling stops (isEnd) when the API returns fewer messages than
    * requested — there are no older messages left.
    */
    async _messagesLoad_UpFromLastChunk() {
      console.log("End is reached: ", this._isEndReached());

        if (this._isEndReached()) return;

        const current = this._findCurrentChunk();

        // No current chunk yet → just return (shouldn't happen after init)
        if (!current) return;

        const sorted = this.chunks;

        // If there's already an older chunk loaded, just switch to it
        if (current.index < sorted.length - 1) {
            console.log(sorted)
            const olderChunk = sorted[current.index + 1];
            console.log(olderChunk)
            this._setCurrentChunkByUid(olderChunk.uid);
            window.im.messenger.view._triggerUpdate();

            // Scroll to keep position (the older chunk is above in the DOM)
            setTimeout(() => {
                const block = window.im.messenger.view.messagesListBlock;
                if (block) {
                    // Find the first message element of the newly active chunk
                    const firstMsg = olderChunk.getMessages()[0];
                    if (firstMsg && firstMsg.id) {
                        const el = block.querySelector(`[data-msg-id="${firstMsg.id}"]`);
                        if (el) el.scrollIntoView({ block: 'start' });
                    }
                }
            }, 1);
            return;
        }

        // ── No older chunk exists → fetch one ──
        const cur = current.chunk;
        const oldestMsgInCurrent = cur.first_message;
        if (!oldestMsgInCurrent) return;

        const msgs = await this.getMessages(oldestMsgInCurrent.id, 0);

        const prev_scroll = window.im.messenger.view.messagesListBlock
        ? window.im.messenger.view.messagesListBlock.scrollTop
        : 0;
        const prev_height = window.im.messenger.view.messagesListBlock
        ? window.im.messenger.view.messagesListBlock.scrollHeight
        : 0;

        if (!this._end_reached && msgs.messages.length > 0) {
            this._appendMessagesChunk(msgs, true, cur);
            this._setCurrentChunkByUid(msgs.uid);
            window.im.messenger.view._triggerUpdate();
        }

        console.log("isEnd: ", msgs.isEnd(), " count: ", msgs.messages.length);

        this._end_reached = msgs.isEnd();

        if (!this._end_reached) {
            setTimeout(() => {
                const block = window.im.messenger.view.messagesListBlock;
                if (block) {
                    const new_scroll = prev_scroll + (block.scrollHeight - prev_height);
                    window.im.messenger.view._scrollTo(new_scroll);
                }
            }, 1);
        }
}

  // ── scrolling DOWN (newer messages) ───────────────────────────────

  /**
   * Load newer messages relative to the current chunk.
   *
   * When scrolling DOWN (looking for newer messages):
   * 1. Find the current chunk in the sorted array.
   * 2. If there is already a chunk *before* it (at a lower index = newer),
   *    just switch to it — no fetch needed.
   * 3. Otherwise, fetch one page of messages newer than the current
   *    chunk's newest message. The new chunk becomes the current one.
   *
   * Scrolling stops (isBeginning) when the API returns fewer messages
   * than requested — there are no newer messages left.
   */
   async _messagesLoad_DownFromCurrentChunk() {
       if (this._isBeginningReached()) return;

       const current = this._findCurrentChunk();
       if (!current) return;

       const sorted = this.chunks;

        // If there's already a newer chunk loaded, just switch to it
        if (current.index > 0) {
            const newerChunk = sorted[current.index - 1];
            this._setCurrentChunkByUid(newerChunk.uid);
            window.im.messenger.view._triggerUpdate();

            // Scroll to keep the viewport stable
            setTimeout(() => {
                const block = window.im.messenger.view.messagesListBlock;
                if (block) {
                    const lastMsg = newerChunk.getMessages()[newerChunk.getMessages().length - 1];
                    if (lastMsg && lastMsg.id) {
                        const el = block.querySelector(`[data-msg-id="${lastMsg.id}"]`);
                        if (el) el.scrollIntoView({ block: 'end' });
                    }
                }
            }, 1);
            return;
        }

        // ── No newer chunk exists → fetch one ──
        const newestMsgInCurrent = current.chunk.latest_message;
        if (!newestMsgInCurrent) return;

        // Fetch messages newer than the newest message in the current chunk
        let msgs = [];

        try {
            msgs = await this.getMessages_NewerThan(newestMsgInCurrent.id);
        } catch (e) {
            console.error(e);
        }

        this._beginning_reached = msgs.isEnd();

        if (!this._beginning_reached) {
            this._appendMessagesChunk(msgs, false);
            this._setCurrentChunkByUid(msgs.uid);
            window.im.messenger.view._triggerUpdate();

            // If we were at the bottom-ish area, scroll to the bottom
            setTimeout(() => {
                window.im.messenger.view._scrollToEnd();
            }, 1);
        }
  }

  // ── guards used by the scroll handler ─────────────────────────────

  /**
   * Returns true when there are already newer chunks loaded
   * (relative to the current chunk).
   */
  _chunks_HasMoreNewerChunkRelativelyToCurrentChat() {
    const current = this._findCurrentChunk();
    if (!current) return false;
    // If current is not at index 0, there is at least one newer chunk
    return current.index > 0;
  }

  /**
   * Returns true when there are already older chunks loaded
   * (relative to the current chunk).
   */
  _chunks_HasMoreOlderChunkRelativelyToCurrentChat() {
    const current = this._findCurrentChunk();
    if (!current) return false;
    // If current is not at the last index, there is at least one older chunk
    return current.index < this.chunks.length - 1;
  }
}

// ── helper ─────────────────────────────────────────────────────────

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

// ── ChatMessage ────────────────────────────────────────────────────

export class ChatMessage {
    static AUTHOR_NAME_HIDE_TIMEOUT = 600; // 60 * 10

    doHideHead(another_msg) {
        let _time_eq = another_msg.data.date - this.data.date;
        return this.data.from_id == another_msg.data.from_id && _time_eq < ChatMessage.AUTHOR_NAME_HIDE_TIMEOUT && this.is_action == false;
    }

    constructor(item = {}) {
        this.data = item;
        this.has_not_loaded_attachments = false;

        if (item.reply_message != null) {
            if (typeof item.reply_message.attachments == "string" && item.reply_message.attachments.length > 0) {
                const a = item.reply_message.attachments.split(",");
                const n = [];
                a.forEach(i => {
                    const _type = i.split('_')[0].replace(/[0-9]/g, '');
                    const f = {};
                    f['type'] = _type;
                    f[_type] = {};

                    n.push(f);
                })

                item.reply_message.attachments = n;
            }

            this.data.reply_message = new ChatMessage(item.reply_message);
        }
    }

    async hydrateFromEvent(msg) {
        this.data = msg.data;

        if (this.has_not_loaded_attachments === true) {
            this.has_not_loaded_attachments = false;
        }
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

    get has_sender() {
        return this.data.from_id != null;
    }

    get text_raw() {
        return this.data.text;
    }

    get text_escaped() {
        return escapeHtml(this.data.text);
    }

    get text() {
        let text = escapeHtml(this.data.text)

        return nl2br(text);
    }

    get reply() {
        return this.data.reply_message;
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

    get is_reply() {
        return this.data.reply_message != null;
    }

    get is_error() {
        return this.data.error_text != null;
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

    get sort_date() {
        return month_day_string(this.sent);
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
        if (this.data.attachments && this.data.attachments.length > 0) {
            f = get_attachment_text(this.data.attachments[0]);
        }

        f += escapeHtml(this.data.text);

        return f;
    }

    get conv_summary_with_attachments() {
        let f = "";
        if (this.data.attachments && this.data.attachments.length > 0) {
            const c = this.data.attachments[0];

            switch (c.type) {
                case "photo":
                    f += `<img class="conv_prev_img" src="${c.photo.photo_75}">`;

                    console.log(this.data.text)
                    if (this.data.text.length == 0) {
                        f += get_attachment_text(this.data.attachments[0]);
                    }

                    break;
                default:
                    f += get_attachment_text(this.data.attachments[0]);
                    break;
            }

            f += " ";
        }

        f += ovk_proc_strtr(escapeHtml(this.data.text), 100);

        return f;
    }

    static async fromEvent(event) {
        const [, id, flags, peer, ts, subject, text, attachments, randomId] = event;
        let new_attachments = null;
        let reply_message = null;

        if (attachments['attach1']) {
            const temp_str = get_attachments_list_from_lp(attachments);
            new_attachments = await resolve_attachments(temp_str);
        }

        if (attachments['reply_to']) {
            const peer_obj = await window.im.conversations._findConvFromApi(peer);
            const reply_id = attachments['reply_to'];

            const __msg = await peer_obj.peer._findMessageByIdFromApi(reply_id);
            if (__msg != null) {
                reply_message = __msg;
            } else {
                reply_message = new ChatMessage({
                    'id': reply_id,
                    'text': '...'
                });
            }
        }

        const msg = new ChatMessage({
            'id': id,
            'flags': flags,
            'from_id': attachments.from ? attachments.from : peer,
            'date': ts,
            'peer': peer,
            'text': text,
            'attachments': new_attachments,
            'random_id': randomId,
            'reply_message': reply_message
        });
        msg._guessSender();

        console.log(msg.peer_id, msg.sender)
        return msg;
    }

    get is_deleted_formally() {
        return this.is_deleted && !this.is_deleted_by_me;
    }

    get is_deleted_by_me() {
        return this.data.deleted_by_me == 1;
    }

    get is_deleted() {
        return this.data.deleted == 1;
    }

    can_delete(club = null) {
        if (this.data.from_id == window.openvk.current_id) {
            return true;
        }

        return false;
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

    // if message has exclamation mark
    async tryToResend() {
        let r = String(this.data.error_text);
        this.data.error_text = null;
        window.im.messenger.view._triggerUpdate();

        try {
            const resp = await window.OVKAPI.call('messages.send', this.data.resend_params);
            this.data.id = resp;
            console.info('IM | Resent message to ' + this.id);
            this.data.error_text = null;
            this.data.resend_params = null;
        } catch (e) {
            this.data.error_text = r;
            console.error('IM | STILL can not send message to ' + this.id, ': ', e);
        }

        window.im.messenger.view._triggerUpdate();
    }

    shouldBeNotified() {
        if (this.data.from_id === window.openvk.current_id) {
            return false;
        }

        return true;
    }
}
