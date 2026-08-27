import { getChatMessageClass, getChatGeneralForm } from './messages.js';
//const { getChatMessageClass, getChatGeneralForm } = await es6import_Im(import.meta.url, "./messages.js");

// rememba me:
// top is down, down is top, bc messages are inverted.
// MessageChunk — a single loaded window of messages.
// ───────────────────────────────────────────────────────────────
//  Internal storage may be newest-first (`do_reverse = true`) or
//  chronological. `getMessages()` always returns chronologically
//  (oldest → newest).
// ───────────────────────────────────────────────────────────────
// Класс Chunks - это список MessageChunk, которые относятся к peer'у. ScrollPosition - это положение прокрутки на странице, чанки относительно которых вращается пользователь.
// У каждого convo (можно было бы и в peer, но поздно) есть endScrollPosition и обычная scorllPosition. Если пользователь никуда не перемещался, скролл идёт с конца вверх, и не может такого быть чтобы ниже что-то было.
// В случае, если чел перейдёт по ссылке реплая, задастся уже обычный scrollPositon, который будет вычислять чанки относительно того, с которым было сообщение

export class MessageChunk {
    static _uidCounter = 0;

    constructor(items = [], do_reverse = true, count = 20) {
        this.uid = "chunk_" + (++MessageChunk._uidCounter);
        this.messages = [];
        this.do_reverse = do_reverse;
        this.count = count;
        this.msg_offset = null;

        // Anchor used when this chunk was fetched from the API.

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
        const last = this.latest_message?.id;
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
            'count': (getChatGeneralForm()).MESSAGES_PER_PAGE,
            'extended': 1,
            'fields': (getChatGeneralForm()).BASE_FIELDS,
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

        const ChatGeneralForm = (getChatGeneralForm());
        const ChatMessageClass = (getChatMessageClass());
        const _l = _authorize(
            messages.items,
            messages.profiles,
            messages.groups,
            (item) => item.from_id,
            (item, author) => { item.sender = new ChatGeneralForm(author); },
            (item, arr) => { arr.push(new ChatMessageClass(item)); }
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
                    dayChunk.msg_date = msg.sent;
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

    getUnreadCount() {
        let count = 0;

        this.getMessages().forEach(item => {
            if (!item.is_read && item.data.from_id != window.im.state.getId()) {
                count += 1;
            }
        })

        return count;
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
        const chunk = new MessageChunk([], true, getChatMessageClass().MESSAGES_PER_PAGE);
        chunk._direction = options.older ? 'older' : (options.newer ? 'newer' : 'center');

        const params = {
            'offset': options.newer ? -(getChatMessageClass().MESSAGES_PER_PAGE) : 0,
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
        this.msg_date = null;
        this.idate = null;
        items.forEach((item) => this.messages.push(item));
    }

    setDay(date) {
        this.date = date;
    }

    get readable_date() {
        return this.date;
    }

    get day() {
        return this.msg_date;
    }

    getMessages() {
        return this.messages;
    }

    _pushMessage(msg) {
        this.messages.push(msg);
    }
}

export class ScrollPosition {
    constructor(peer) {
        this.peer = peer;
        this.reachedOldestPosition = false;
        this.reachedNewestPosition = false;
    }

    static fromEnd(peer) {
        const n = new ScrollPosition(peer);
        n.direction = "end";

        return n;
    }

    getMessages() {

    }

    getDayDividedMessages() {
        if (this.direction == "end") {
            return this.peer._chunks.getDayDividedMessages();
        }
    }

    async loadOlder() {
        if (this.reachedOldestPosition == true) {
            console.log("IM | reachedOldestPosition");
            return;
        }

        if (this.direction == "end") {
            const first_message = this.peer._chunks.getFirstMessage();
            console.log(first_message)

            const msgs = await this.peer._chunks._fetchChunkAround(first_message.id, { older: true });
            console.log(msgs.messages, msgs.messages.length)
            if (!msgs.messages.length) {
                this.reachedOldestPosition = true;
                return;
            }

            if (msgs.messages.length < 20) {
                this.peer._chunks._appendChunk(msgs);
                this.reachedOldestPosition = true;
                return;
            }

            this.peer._chunks._appendChunk(msgs);
        }
    }

    result() {
        window.im.messenger.update();
    }
}
