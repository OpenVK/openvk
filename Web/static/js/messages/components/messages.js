export class Draft {
    constructor() {
        this.text = null;
        this.attachments_html = [];
        this.scroll = null;
        this.editMsg = null;
    }

    static fromPage(page) {
        const d = new Draft();
        d.text = page.getCurrentText();
        d.attachments_html = page.getCurrentAttachments();
        d.scroll = page.getScroll();
        d.editMsg = window.im.messenger.editMsg;

        return d;
    }

    loadToPage(page) {
        console.log("applying ", this, " to ", page);
        if (this.text != null) {
            window.im.messenger.currentDraft = this.text;
            page.container.querySelector(".messenger-app--input---messagebox textarea").value = this.text;
        }
        if (this.attachments_html[0] != null) {
            page.container.querySelector(".post-horizontal").innerHTML = this.attachments_html[0];
        }
        if (this.attachments_html[1] != null) {
            page.container.querySelector(".post-vertical").innerHTML = this.attachments_html[1];
        }
        if (this.editMsg) {
            console.log("this.editMsg", this.editMsg);
            window.im.messenger.editMsg = this.editMsg;
        } else {
            window.im.messenger.editMsg = null;
        }

        this.loadScroll(page);
    }

    loadScroll(page) {
        if (this.scroll != null) {
            page._scrollTo(this.scroll);
        } else {
            console.log(this);
            page._scrollToEnd();
        }
    }
}

class ChatMembers {
    constructor(peer_id) {
        this.items = [];
        this.total_count = 0;
        this.peer_id = peer_id;
        this.offset = 0;
        this.perPage = 10;
    }

    async load(offset = 0) {
        const v = await window.OVKAPI.call("messages.getConversationMembers", {
            "peer_id": this.peer_id,
            "extended": 1,
            "offset": offset,
        });
        this.total_count = v.count;
        v.items.forEach(item => {
            this.items.push(item);
        });
        this.offset += this.perPage;
    }
}

// rememba:
// top is down, down is top, bc messages are inverted.

// ───────────────────────────────────────────────────────────────
//  MessageChunk — a single loaded window of messages.
//
//  Internal storage may be newest-first (`do_reverse = true`) or
//  chronological. `getMessages()` always returns chronologically
//  (oldest → newest).
// ───────────────────────────────────────────────────────────────
export class MessageChunk {
    static _uidCounter = 0;

    constructor(items = [], do_reverse = true, count = ChatGeneralForm.MESSAGES_PER_PAGE) {
        this.uid = "chunk_" + (++MessageChunk._uidCounter);
        this.messages = [];
        this.do_reverse = do_reverse;
        this.count = count;
        this.msg_offset = null;

        // Anchor used when this chunk was fetched from the API.
        this._start_message_id = null;
        // Boundary flags: is there more data beyond this chunk?
        this._at_oldest = false; // no older messages before this chunk
        this._at_newest = false; // no newer messages after this chunk

        items.forEach((item) => this.messages.push(item));
    }

    /** Oldest message inside the chunk. */
    get first_message() {
        return this.do_reverse ? this.messages[this.messages.length - 1] : this.messages[0];
    }

    /** Newest message inside the chunk. */
    get latest_message() {
        return this.do_reverse ? this.messages[0] : this.messages[this.messages.length - 1];
    }

    /** The [first, last] message-id interval covered by this chunk (map key / search). */
    get id_range() {
        const first = this.first_message?.id;
        const last  = this.latest_message?.id;
        if (first == null || last == null) return null;
        return { first, last };
    }

    getMessages() {
        return this.do_reverse ? Array.from(this.messages).reverse() : this.messages;
    }

    _pushMessage(msg) {
        if (this.do_reverse) this.messages.unshift(msg);
        else this.messages.push(msg);
    }

    /** Does this chunk lexically contain the given message id? */
    hasMessageId(id) {
        const range = this.id_range;
        if (!range) return false;
        return id >= range.first && id <= range.last;
    }

    /** Load messages from the IM backend into this chunk. */
    async fetch(data) {
        const params = {
            'count': ChatGeneralForm.MESSAGES_PER_PAGE,
            'extended': 1,
            'fields': ChatGeneralForm.BASE_FIELDS,
        };

        const operator = window.im.state.getOperator();
        if (operator && operator.supposed_type == "club") {
            params["group_id"] = Math.abs(operator.id);
        }

        Object.assign(params, data);
        // No anchor = start from the very newest; drop the param.
        const anchored = params.start_message_id != null;
        if (!anchored) delete params.start_message_id;

        const messages = await window.OVKAPI.call('messages.getHistory', params);

        window.im.cached_profiles._moveToProfileCache(messages.profiles, messages.groups);

        const _l = _authorize(
            messages.items,
            messages.profiles,
            messages.groups,
            (item) => item.from_id,
            (item, author) => { item.sender = new ChatGeneralForm(author); },
            (item, arr) => { arr.push(new ChatMessage(item)); }
        );

        this.messages = _l;
        this._start_message_id = anchored ? data.start_message_id : null;

        // Fewer messages than a full page ⇒ no more data in that direction.
        // The direction is set on the params by the caller (older/newer/center).
        const short_page = this.messages.length < this.count;
        if (this._direction === 'older') {
            this._at_oldest = short_page;
            this._at_newest = false;
        } else if (this._direction === 'newer') {
            this._at_newest = short_page;
            this._at_oldest = false;
        } else {
            // Center anchor / first load — be conservative in both directions.
            this._at_oldest = short_page;
            this._at_newest = short_page;
        }
        return this;
    }

    get isEnd() {
        return this._at_oldest;
    }

    get isBeginning() {
        return this._at_newest;
    }

    /** Drop messages whose ids are already known (dedupe on overlap). */
    _dropDuplicates(knownIds) {
        this.messages = this.messages.filter((m) => !knownIds.has(m.id));
    }
}

// ───────────────────────────────────────────────────────────────
//  Chunks — container that owns all loaded MessageChunk of a peer.
//
//  * chunks[]   : the array of chunks. Chunks are ONLY appended to
//                 the end (append-only, never spliced).
//  * _map       : "firstMessageId:lastMessageId" → index in chunks[].
//  * _actualIdx : index of the "actual" (newest) chunk. Longpoll and
//                 sent messages are appended there.
//  * _activeIdx : index of the chunk the user is currently viewing
//                 (anchor). It may differ from _actualIdx when the
//                 user jumped into the middle of the conversation.
// ───────────────────────────────────────────────────────────────
export class Chunks {
    constructor(peerId = 0) {
        this._peerId = peerId;
        this.chunks = [];
        this._map = new Map();      // key → index in this.chunks
        this._actualIdx = null;     // newest chunk (longpoll / send target)
        this._activeIdx = null;     // chunk near the current view
        this._startedFrom = null;   // null = loaded from the newest side

        this._atOldest = false;     // no older messages exist
        this._atNewest = false;     // no newer messages exist
        this._messagesInited = false;

        this._cachedMessages = undefined;
        this._cachedDays = undefined;
    }

    // ── internal helpers ─────────────────────────────────────────

    _getChunkKey(chunk) {
        const range = chunk?.id_range;
        return range ? `${range.first}:${range.last}` : null;
    }

    _invalidateCache() {
        this._cachedMessages = undefined;
        this._cachedDays = undefined;
    }

    /** Chunks ordered newest-first by their latest message id. */
    get sorted() {
        return this.chunks.slice(0).sort(
            (a, b) => (b.latest_message?.id ?? -Infinity) - (a.latest_message?.id ?? -Infinity)
        );
    }

    // ── actual / active chunk ────────────────────────────────────

    /** The "actual" (newest) chunk — the longpoll/send target. */
    _getActualChunk(create = true) {
        if (this._actualIdx != null && this.chunks[this._actualIdx]) {
            return this.chunks[this._actualIdx];
        }

        const sorted = this.sorted;
        if (sorted.length === 0) {
            if (!create) return null;
            const chunk = new MessageChunk([], true);
            this._appendChunk(chunk);
            return chunk;
        }

        const newest = sorted[0];
        this._actualIdx = this.chunks.indexOf(newest);
        return newest;
    }

    /** The chunk the user is currently anchored at / viewing. */
    _getActiveChunk() {
        if (this._activeIdx != null && this.chunks[this._activeIdx]) {
            return this.chunks[this._activeIdx];
        }
        return this._getActualChunk(false);
    }

    _setActiveChunk(chunk) {
        const idx = this.chunks.indexOf(chunk);
        if (idx !== -1) this._activeIdx = idx;
    }

    // ── registration (append-only) ───────────────────────────────

    /**
     * Append a chunk to the END of the array and register it in the map.
     * Returns the chunk's index (or the existing index if already loaded).
     */
    _appendChunk(chunk, options = {}) {
        const key = this._getChunkKey(chunk);
        if (key != null && this._map.has(key)) {
            return this._map.get(key); // already loaded → reuse
        }

        const idx = this.chunks.length;
        this.chunks.push(chunk);
        if (key != null) this._map.set(key, idx);

        // Deduplicate overlap with a known chunk when requested.
        if (options.overlapWith) {
            const known = new Set(options.overlapWith.getMessages().map((m) => m.id));
            chunk._dropDuplicates(known);
        }

        // Manage the actual pointer: the newest-loaded chunk is "actual".
        const actual = this.chunks[this._actualIdx];
        const chunkNewestId = chunk.latest_message?.id ?? -Infinity;
        if (!actual || chunkNewestId > (actual.latest_message?.id ?? -Infinity)) {
            this._actualIdx = idx;
        }

        // Boundary flags.
        if (chunk._at_oldest) this._atOldest = true;
        if (chunk._at_newest) this._atNewest = true;

        this._messagesInited = true;
        this._invalidateCache();
        return idx;
    }

    // ── composition / rendering ──────────────────────────────────

    /** All loaded messages chronologically (oldest → newest). */
    getMessages() {
        if (this._cachedMessages != undefined) return this._cachedMessages;

        const sorted = this.sorted; // newest-first
        const fnl = [];
        for (let i = sorted.length - 1; i >= 0; i--) {
            sorted[i].getMessages().forEach((msg) => fnl.push(msg));
        }

        this._cachedMessages = fnl;
        return fnl;
    }

    /** Messages grouped into DayChunk views, chronological. */
    getDayDividedMessages() {
        if (this._cachedDays != undefined) return this._cachedDays;

        const dayChunks = [];
        const dateMap = new Map();

        const sorted = this.sorted;
        console.log(sorted)
        for (let i = sorted.length - 1; i >= 0; i--) {
            sorted[i].getMessages().forEach((msg) => {
                if (!msg.sent) return;
                if (msg.is_deleted_formally) return;

                const dateKey = msg.sort_date;
                if (!dateMap.has(dateKey)) {
                    const dayChunk = new DayChunk([], false);
                    dayChunk.setDay(dateKey);
                    dayChunk.idate = msg.sent.toLocaleDateString();
                    dayChunks.push(dayChunk);
                    dateMap.set(dateKey, dayChunk);
                }
                dateMap.get(dateKey)._pushMessage(msg);
            });
        }

        dayChunks.sort((a, b) => {
            const [monthA, dayA, yearA] = a.idate.split('/').map(Number);
            const [monthB, dayB, yearB] = b.idate.split('/').map(Number);

            if (monthA !== monthB) return monthA - monthB;

            if (dayA !== dayB) return dayA - dayB;

            return yearA - yearB;
        });
        this._cachedDays = dayChunks;
        return dayChunks;
    }

    /** Oldest message across all loaded chunks (scroll-up search start). */
    getFirstMessage() {
        const sorted = this.sorted; // newest-first → last is oldest
        return sorted.length ? sorted[sorted.length - 1].first_message : null;
    }

    // ── lookup / jumps ───────────────────────────────────────────

    /**
     * Search interval over the map: the index of the chunk that
     * lexically contains `id`, or -1 if the id lies in a gap /
     * hasn't been loaded.
     */
    getChunkIndexByMessageId(id) {
        const sorted = this.sorted;
        for (let i = 0; i < sorted.length; i++) {
            if (sorted[i].hasMessageId(id)) {
                return this.chunks.indexOf(sorted[i]);
            }
        }
        return -1;
    }

    _findMessageById(id) {
        let found = null;
        this.chunks.forEach((chunk) => {
            if (found) return;
            chunk.getMessages().forEach((m) => {
                if (found) return;
                if (m.id == id) found = m;
            });
        });
        return found;
    }

    /**
     * Fetch a fresh chunk anchored around `messageId`.
     *   options.older → the page BEFORE the anchor (scroll up)
     *   options.newer → the page AFTER the anchor   (scroll down)
     *   otherwise     → the page anchored ON the message (jump)
     */
    async _fetchChunkAround(messageId, options = {}) {
        const chunk = new MessageChunk([], true, ChatGeneralForm.MESSAGES_PER_PAGE);
        chunk._direction = options.older ? 'older' : (options.newer ? 'newer' : 'center');

        const params = {
            'offset': options.newer ? -(ChatGeneralForm.MESSAGES_PER_PAGE) : 0,
            'peer_id': this._peerId,
        };
        if (messageId != null) params['start_message_id'] = messageId;

        await chunk.fetch(params);
        return chunk;
    }

    /**
     * Load (or reuse) a chunk anchored around `messageId`, then jump to it.
     * The chunk becomes the active (view) chunk, but NOT the actual one.
     */
    async loadChunkByMessageId(messageId) {
        const existingIdx = this.getChunkIndexByMessageId(messageId);
        if (existingIdx !== -1) {
            this._activeIdx = existingIdx;
            return this.chunks[existingIdx];
        }

        const chunk = await this._fetchChunkAround(messageId);
        const idx = this._appendChunk(chunk);
        this._activeIdx = idx;
        return chunk;
    }

    // ── new messages (user-sent / longpoll) ──────────────────────

    _pushNewMessage(msg, conv = null, check_chunk = true) {
        const actual = this._getActualChunk(check_chunk);

        if (!actual) {
            if (conv != null) conv.updateLastMessage(msg);
            return;
        }

        actual._pushMessage(msg);
        this._invalidateCache();
        window.im.messenger.update();
    }

    // ── scroll direction handling ────────────────────────────────

    /** Scroll UP → toward OLDER messages. */
    async _messagesLoad_UpFromLastChunk() {
        if (this._atOldest) return;

        const active = this._getActiveChunk();
        if (!active) return;

        const sorted = this.sorted;
        const idxInSorted = sorted.indexOf(active);

        // An older chunk is already loaded → just anchor on it.
        if (idxInSorted < sorted.length - 1) {
            const older = sorted[idxInSorted + 1];
            this._setActiveChunk(older);
            this._scrollAnchorTo(older.first_message?.id, 'start');
            return;
        }

        // No older chunk loaded → fetch one before the active chunk.
        const oldest = active.first_message;
        if (!oldest) return;

        const msgs = await this._fetchChunkAround(oldest.id, { older: true });
        if (!msgs.messages.length) {
            this._atOldest = true;
            return;
        }

        this._appendChunk(msgs, { overlapWith: active });
        this._atOldest = this._atOldest || msgs.isEnd;
        this._setActiveChunk(msgs);
        window.im.messenger.update();
    }

    /**
     * Scroll DOWN → toward NEWER messages.
     * If the user is anchored in the middle (active !== actual), this is
     * a plain DOM scroll — no chunk switch and nothing is fetched. Use
     * scrollToNewest() to jump back to the newest side.
     */
    async _messagesLoad_DownFromCurrentChunk() {
        if (this._atNewest) return;

        const active = this._getActiveChunk();
        if (!active) return;
        const actual = this._getActualChunk(false);

        // Middle of the conversation → do not reveal newer chunks on scroll-down.
        if (active !== actual) return;

                const newest = active.latest_message;
        if (!newest) return;

        let msgs;
        try {
            msgs = await this._fetchChunkAround(newest.id, { newer: true });
        } catch (e) {
            console.error(e);
            return;
        }

        this._atNewest = this._atNewest || msgs.isBeginning;
        if (msgs.messages.length === 0) {
            this._atNewest = true;
            return;
        }

        this._appendChunk(msgs, { overlapWith: active });
        this._setActiveChunk(msgs);
        window.im.messenger.update();
        window.im.messenger.view._scrollToEnd();
    }

    /** Is there a loaded chunk newer than what is currently being viewed? */
    _chunks_HasMoreNewerChunkRelativelyToCurrentChat() {
        const active = this._getActiveChunk();
        if (!active) return false;
        return active !== this._getActualChunk(false);
    }

    /** Is there a loaded chunk older than what is currently being viewed? */
    _chunks_HasMoreOlderChunkRelativelyToCurrentChat() {
        const active = this._getActiveChunk();
        if (!active) return false;
        const sorted = this.sorted;
        return sorted.indexOf(active) < sorted.length - 1;
    }

    /** Jump back to the newest side (the "return to newest" / DOWN button). */
    scrollToNewest() {
        this._setActiveChunk(this._getActualChunk(false));
        window.im.messenger.update();
        window.im.messenger.view._scrollToEnd();
    }

    _scrollAnchorTo(messageId, block) {
        setTimeout(() => {
            const blockEl = window.im.messenger.view.messagesListBlock;
            const el = (blockEl || document).querySelector?.(`[data-msg-id="${messageId}"]`);
            if (el) el.scrollIntoView({ block });
        }, 1);
    }

    // ── state queries used by callers ────────────────────────────

    _isMessagesInited() {
        return this._messagesInited;
    }

    _isEndReached() {
        return this._atOldest;
    }

    _isBeginningReached() {
        return this._atNewest;
    }

    _getLatestChunk(create_empty = true) {
        return this._getActualChunk(create_empty);
    }

    _getMostActualChunk() {
        return this._getActualChunk(false);
    }
}

// Day grouping wrapper produced by Chunks.getDayDividedMessages().
export class DayChunk {
    constructor(items = [], do_reverse = false) {
        this.messages = [];
        this.do_reverse = do_reverse;
        this.date = null;
        this.idate = null;
        items.forEach((item) => this.messages.push(item));
    }

    setDay(date) {
        this.date = date;
    }

    get readable_date() {
        return this.date;
    }

    getMessages() {
        return this.messages;
    }

    _pushMessage(msg) {
        this.messages.push(msg);
    }
}

export class ChatGeneralForm {
    static CHAT_RUBICON = 2000000000;
    static MESSAGES_PER_PAGE = 20;
    static BASE_FIELDS = 'photo_100,photo_200,photo_max,last_seen,photo_id,status,sex,can_write_private_message,can_invite,followers_count,is_messages_blocked';
    static SAVED_MESSAGES_AVATAR = "/assets/packages/static/openvk/img/im/saved_messages.png";
    static CHAT_NO_AVATAR = "/assets/packages/static/openvk/img/im/chat_meaningless.jpg";

    constructor(item) {
        this.data = item || {};
        this._chunks = new Chunks(this.id);
        this.pinned_message_chunks = [];

        this._messages_inited = false;
        this._members = null;
        this._total_members_count = 0;
    }

    // ── identity ─────────────────────────────────────────────────────

    get id() {
        switch (this.supposed_type) {
            case 'user':
                return this.data.id;
            case 'club':
                return this.data.id * -1;
            case 'chat':
                if (this.data.id < this.CHAT_RUBICON) {
                    return this.data.id + this.CHAT_RUBICON;
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
        return (this.data.can_write_private_message ?? this.data.can_write ?? 1) === 1;
    }

    showAsJson() {
        fastError(`<textarea>${JSON.stringify(this.data, null, 4)}</textarea>`);
    }

    canBeInvitedBy(group = null) {
        if (group != null) {
            return false;
        }

        return (this.data.can_invite ?? 1) === 1;
    }

    isAdmin() {
        return this.data.admin_id === window.openvk.current_id;
    }

    can(thing, relatively_current_group = null) { // unified function
        switch(thing) {
            case "update_title":
            case "invite_new":
            case "update_avatar":
                return this.isAdmin() && this.supposed_type == "chat";
            case "leave_chat":
                return this.supposed_type == "chat";
            case "view_invite_links":
                return this.supposed_type == "chat" && false;
        }

        return true;
    }

    canUsersBeAddedBy(group = null) {
        if (group != null) {
            return false;
        }

        return this.isAdmin();
    }

    canPinMessages(group = null) {
        if (group != null) {
            return false;
        }

        return this.isAdmin();
    }

    canUpdateAvatar(as_group = null) {
        if (as_group != null) {
            return false;
        }

        if (this.supposed_type != "chat") {
            return false;
        }

        return this.isAdmin();
    }

    canUpdateTitle(as_group = null) {
        if (as_group != null) {
            return false;
        }

        if (this.supposed_type != "chat") {
            return false;
        }

        return this.isAdmin();
    }

    canViewInviteLinks(as_group = null)
    {
        if (as_group != null) {
            return false;
        }

        if (this.supposed_type != "chat") {
            return false;
        }

        return this.isAdmin();
    }

    canLeaveChat() {
        return this.supposed_type == "chat";
    }

    get conversation_avatar_any() {
        if (this.id === window.openvk.current_id) {
            return ChatGeneralForm.SAVED_MESSAGES_AVATAR;
        }

        return this.avatar_any;
    }

    get avatar_any() {
        return this.data.photo_100 ?? ChatGeneralForm.CHAT_NO_AVATAR;
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
        return this.id === window.im.state.getId();
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
        if (this.supposed_type == "chat") {
            return Number(this._total_members_count) + " members"
        }

        if (this.data.followers_count) {
            return tr("followers", this.data.followers_count);
        }

        if (!this.data.last_seen) {
            return tr("im_was_online_unkown_" + this.gender).toLowerCase();
        }

        const time = this.data.last_seen.time;
        const date = new Date(time * 1000);
        const today = new Date();
        const sameMonth = date.getMonth() === today.getMonth();
        const timeStr = date.toLocaleTimeString(navigator.language, { hour: '2-digit', minute: '2-digit' });
        const dayStr = date.toLocaleDateString(navigator.language, {
            month: '2-digit',
            day: '2-digit'
        });

        if ((Math.floor(today.getTime() / 1000) - Math.floor(date.getTime() / 1000)) <= 300) {
            return tr("online")
        }

        if (sameMonth && date.getDate() === today.getDate()) {
            return tr("im_was_online_today_" + this.gender, timeStr).toLowerCase();
        }

        if (sameMonth && date.getDate() === today.getDate() - 1) {
            return tr("im_was_online_yesterday_" + this.gender, timeStr).toLowerCase();
        }

        return tr("im_was_online_other_" + this.gender, timeStr, dayStr).toLowerCase();
    }

    get messages() {
        return this._chunks.getMessages();
    }

    get divided_messages() {
        return this._chunks.getDayDividedMessages();
    }

    get is_muted() {
        return false;
    }

    // ── initial loading ──────────────────────────────────────────────

    static async resolveById(id) {
        if (id == 0) {
            return window.im._current;
        }

        if (id > this.CHAT_RUBICON) {
            const __ = await window.OVKAPI.call('messages.getConversationsById', { 'peer_ids': id, 'fields': ChatGeneralForm.BASE_FIELDS });

            if (!__ || __.items.length == 0) {
                return null;
            }
            return __.items[0].conversation.peer;
        } else {
            if (id > 0) {
                const __ = await window.OVKAPI.call('users.get', { 'user_ids': id, 'fields': ChatGeneralForm.BASE_FIELDS });
                if (__[0].first_name == "DELETED" && __[0].deactivated == "deleted") {
                    return null;
                }
                return __[0];
            } else {
                const __ = await window.OVKAPI.call('groups.getById', { 'group_ids': Math.abs(id), 'fields': ChatGeneralForm.BASE_FIELDS });
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
        const chunk = new MessageChunk([], true, ChatGeneralForm.MESSAGES_PER_PAGE);
        chunk._start_message_id = message_id ?? null;
        chunk._direction = 'center';

        const params = {
            'offset': offset,
            'peer_id': this.id,
        };
        if (message_id != null) params['start_message_id'] = message_id;

        await chunk.fetch(params);

        return chunk;
    }

    // ── delegation to this._chunks ───────────────────────────────

    _appendMessagesChunk(messages, before = false, compare_with = null) {
        const options = compare_with ? { overlapWith: compare_with } : {};
        this._chunks._appendChunk(messages, options);
    }

    _isMessagesInited() {
        return this._chunks._isMessagesInited();
    }

    _pushNewMessage(msg, conv = null, check_chunk = true) {
        this._chunks._pushNewMessage(msg, conv, check_chunk);
    }

    _findMessageById(id) {
        return this._chunks._findMessageById(id);
    }

    async _findMessageByIdFromApi(id) {
        const found = this._findMessageById(id);
        return found;
    }

    _getLatestChunk(create_empty = true) {
        return this._chunks._getLatestChunk(create_empty);
    }

    _messagesLoad_UpFromLastChunk() {
        return this._chunks._messagesLoad_UpFromLastChunk();
    }

    _messagesLoad_DownFromCurrentChunk() {
        return this._chunks._messagesLoad_DownFromCurrentChunk();
    }

    _chunks_HasMoreNewerChunkRelativelyToCurrentChat() {
        return this._chunks._chunks_HasMoreNewerChunkRelativelyToCurrentChat();
    }

    _chunks_HasMoreOlderChunkRelativelyToCurrentChat() {
        return this._chunks._chunks_HasMoreOlderChunkRelativelyToCurrentChat();
    }

    async loadChunkByMessageId(messageId) {
        const chunk = await this._chunks.loadChunkByMessageId(messageId);
        window.im.messenger.update();
        return chunk;
    }

    getFirstMessage() {
        return this._chunks.getFirstMessage();
    }

    scrollToNewest() {
        return this._chunks.scrollToNewest();
    }

    // boundary flags set by im.js on init
    set _beginning_reached(v) { this._chunks._atNewest = !!v; }
    get _beginning_reached() { return this._chunks._atNewest; }
    set _end_reached(v) { this._chunks._atOldest = !!v; }
    get _end_reached() { return this._chunks._atOldest; }

    async sendMessage(msg, reply_to = null, attachments = null, wait_until_send = null, push_callback = null) {
        this._pushNewMessage(msg);
        if (push_callback) {
            push_callback();
        }
        const datas = {
            'peer_id': this.id,
            'message': msg.text_raw,
            'attachment': msg.str_attachments,
        };

        if (window.im.usage_type == "group") {
            datas["group_id"] = Math.abs(window.im.state.getOperator().id);
        }

        if (reply_to != null) {
            datas['reply_to'] = reply_to.id;
        }

        if (attachments != null) {
            datas['attachment'] = attachments.join(',');
        }

        if (wait_until_send != null) {
            await new Promise(function (r) { setTimeout(r, wait_until_send); });
        }

        if (msg.is_deleted == true) {
            console.info('IM | Maybe message send interrupted, so does not sending. ', this.id, msg);
            return;
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
            window.im.messenger.update();
        }
    }

    // update

    async updateTitle(title) {
        if (this.supposed_type != "chat") {
            return;
        }

        alert("ты хочешь поменять название на " + title + " но эта функция ещё не сделана .")
    }

    async updateAvatar(blob) {
        if (this.supposed_type != "chat") {
            return;
        }

        const group_id = null;

        const params = {
            "chat_id": this.id - ChatGeneralForm.CHAT_RUBICON,
            "group_id": group_id
        }
        const v = await window.OVKAPI.call("photos.getChatUploadServer", params);
        const upload_url = v.upload_url;
        const fd = new FormData();
        fd.append("photo", blob);

        const f = await fetch(upload_url, {
            method: "POST",
            body: fd
        })
        const j = await f.json();
        const photo = j.photo;
        const hash = j.hash;
        const v1 = await window.OVKAPI.call("messages.setChatPhoto", {
            "file": photo,
            "hash": hash,
            "chat_id": this.id - ChatGeneralForm.CHAT_RUBICON,
        });

        return v1;
    }

    /* etc */

    get is_club_messages_blocked() {
        if (window.im.state.getId() < 0) {
            return this.data.is_me_blocked == 1;
        }
        return this.data.is_messages_blocked == 1;
    }

    async toggleClubMessages(event, action = true) {
        let state = action == "enable";
        // true - enable, false - forbid
        let r = null;
        const currentId = window.im.state.getId();
        const params = {};
        event.target.classList.add("lagged");
        if (currentId < 0) {
            params["group_id"] = Math.abs(currentId);
            params["owner_id"] = Math.abs(this.id);
            if (state) {
                r = await window.OVKAPI.call("groups.unban", params);
                this.data.is_me_blocked = 0;
            } else {
                r = await window.OVKAPI.call("groups.ban", params);
                this.data.is_me_blocked = 1;
            }
        } else {
            params["group_id"] = Math.abs(this.id);
            if (state) {
                r = await window.OVKAPI.call("messages.allowMessagesFromGroup", params);
                this.data.is_messages_blocked = 0;
            } else {
                r = await window.OVKAPI.call("messages.denyMessagesFromGroup", params);
                this.data.is_messages_blocked = 1;
            }
        }

        event.target.classList.remove("lagged");

        window.im.getTab("contact").render_class.update();
        window.im.messenger.update();
    }

    // members

    _hasLoadedMembers() {
        return this._members != null;
    }

    async _setMembers(offset = 0) {
        this._members = new ChatMembers(this.id);
        await this._members.load(offset);
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

    get peer_object() {
        return window.im.conversations._findConv(this.data.peer_id).peer;
    }

    get has_sender() {
        return this.data.from_id != null;
    }

    get is_gift() {
        try {
            let is = false;
            this.data.attachments.forEach(item => {
                if (item.type == "gift") {
                    is = true;
                }
            })

            return is;
        } catch(e) {
            return false;
        }
    }

    get text_raw() {
        return this.data.text;
    }

    get text_escaped() {
        return escapeHtml(this.data.text);
    }

    get text() {
        if (this.is_gift) {
            const msg = this.data.attachments[0].gift.message;
            if (msg == "") {
                return ("(" + tr("message_no_text") + ")").toLowerCase();
            }

            return msg;
        }

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
        return this.data.peer_id;
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

    getConvDay(always_with_year = false) {
        const date = this.sent;
        if (always_with_year == false && date.getFullYear() == new Date().getFullYear()) {
            return date.toLocaleDateString(navigator.language);
        } else {
            return date.toLocaleDateString(navigator.language, {
                month: '2-digit',
                day: '2-digit'
            })
        }
    }

    get conv_day() {
        return this.getConvDay();
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

    static async fromEvent(event, im = null) {
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

        console.log(attachments.from, peer)
        const msg = new ChatMessage({
            'id': id,
            'flags': flags,
            'from_id': attachments.from ? Number(attachments.from) : peer,
            'date': ts,
            'peer': peer,
            'peer_id': peer,
            'text': text,
            'attachments': new_attachments,
            'random_id': randomId,
            'reply_message': reply_message
        });
        msg._guessSender();

        // temp fix
        if (im && msg.peer_id == im.state.getOperator().id) {
            console.error("IM | WRONG PEER FROM EVENT!!!!!! USING ATTACHMENTS.FROM")
            msg.data.peer_id = Number(attachments.from);
        }

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

    get is_sticker() {
        return this.data.is_sticker == 1;
    }

    get is_got_edited() {
        return this.data.edited == 1 || this.data.edited == true;
    }

    isPinned() {
        return this.data.is_pinned == 1;
    }

    setPinned(val) {
        this.data.is_pinned = val;
    }

    canEdit(group = null) {
        if (this.data.can_edit != null) {
            return this.data.can_edit === 1;
        }

        if (group != null) {
            return false;
        }

        if (this.is_action == true) {
            return false;
        }

        if (this.is_sticker == true) {
            return false;
        }

        // return this.data.can_edit;
        return this.data.from_id === window.im.state.getId();
    }

    canPin(club = null) {
        const peer = this.peer_object;
        if (peer.supposed_type == "chat") {
            return peer.canPinMessages();
        }

        return peer.can_write;
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

    async setAttachmentsFromLP(data) {
        let new_attachments = null;
        if (data['attach1']) {
            const temp_str = get_attachments_list_from_lp(data);
            new_attachments = await resolve_attachments(temp_str);
        }

        this.data.attachments = new_attachments;
    }

    // if message has exclamation mark
    async tryToResend() {
        let r = String(this.data.error_text);
        this.data.error_text = null;
        window.im.messenger.update();

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

        window.im.messenger.update();
    }

    async edit(text, attachments = []) {
        let resp = null;
        try {
            const params = {
                "peer_id": this.peer_id,
                "message_id": this.id,
                "message": text,
                "keep_forward_messages": 1,
                "attachment": attachments.join(",")
            };
            const g = window.im.state.getId();
            if (g < 0) {
                params["group_id"] = Math.abs(g);
            }

            resp = await window.OVKAPI.call("messages.edit", params);
        } catch (e) {
            fastError(String(e));
            console.error(e);
            return;
        }

        this.data.text = text;
        this.data.edited = true;

        window.im.messenger.update();

        console.log("successfuly edited ", this, resp)
    }

    async togglePin(action) {
        let method = "pin";
        if (action == false) {
            method = "unpin";
        }

        const g = window.im.state.getId();
        try {
            const params = {
                "peer_id": this.peer_id,
                "message_id": this.id,
            };
            if (g < 0) {
                params["group_id"] = Math.abs(g);
            }
            let resp = await window.OVKAPI.call("messages." + method, params);
        } catch (e) {
            fastError(String(e));
            console.error(e);
            return;
        }

        this.setPinned(Boolean(action));
    }

    shouldBeNotified() {
        if (this.data.from_id === window.openvk.current_id) {
            return false;
        }

        return true;
    }
}
