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
    constructor(items = [], do_reverse = true, count = null, id = null) {
        this.messages = [];
        this.do_reverse = do_reverse;
        this.count = count || 20;
        this.id = id;

        items.forEach((item) => this.messages.push(item));
    }

    /** Oldest message inside the chunk. */
    get first_message() {
        if (!this.messages || this.messages.length === 0) return null;
        return this.do_reverse ? this.messages[this.messages.length - 1] : this.messages[0];
    }

    /** Newest message inside the chunk. */
    get latest_message() {
        if (!this.messages || this.messages.length === 0) return null;
        return this.do_reverse ? this.messages[0] : this.messages[this.messages.length - 1];
    }

    /** The [first, last] message-id interval covered by this chunk (map key / search). */
    get id_range() {
        if (!this.messages || this.messages.length === 0) return null;
        let first = this.first_message?.id;
        let last = this.latest_message?.id;
        if (first == null && this.first_message?.data?.is_sending) {
            first = 0;
        }
        if (last == null && this.latest_message?.data?.is_sending) {
            for (let i = this.messages.length - 1; i > -1; i--) {
                if (this.messages[i] && this.messages[i].id != null) {
                    last = this.messages[i].id + 1;
                    break;
                }
            }
            if (last == null) last = 1;
        }
        if (first == null || last == null) {
            return null;
        }
        return { first, last };
    }

    getMessages() {
        return this.do_reverse ? Array.from(this.messages).reverse() : this.messages;
    }

    pushMessage(msg) {
        if (this.do_reverse) this.messages.unshift(msg);
        else this.messages.push(msg);
    }

    /** Does this chunk lexically or directly contain the given message id? */
    hasMessageId(id) {
        if (id == null) return false;
        const targetId = Number(id);
        if (this.messages && this.messages.some(m => m && Number(m.id) === targetId)) {
            return true;
        }
        const range = this.id_range;
        if (!range) return false;
        return targetId >= range.first && targetId <= range.last;
    }

    /** Load messages from the IM backend into this chunk. */
    async fetch(data = {}) {
        const defaultCount = (getChatGeneralForm()).MESSAGES_PER_PAGE || 20;
        const count = data.count || this.count || defaultCount;
        this.count = count;
        const params = {
            'count': count,
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

        console.log("IM | messages.getHistory ",params);

        let messages = null;
        try {
            messages = await window.OVKAPI.call('messages.getHistory', params);
        } catch (e) {
            console.error("IM | messages.getHistory error:", e);
            messages = { count: 0, items: [], profiles: [], groups: [] };
        }

        if (!messages) messages = { count: 0, items: [], profiles: [], groups: [] };
        if (!messages.items) messages.items = [];
        if (!messages.profiles) messages.profiles = [];
        if (!messages.groups) messages.groups = [];

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
}

export class Chunks {
    constructor(peer) {
        this.chunks = [];
        this._map = new Map();
        this._messagesInited = false;
        this._peer = peer;
        this._latest_chunk_id = 0;
        this.chunks.push(new MessageChunk([], true, 20, "start"));
        this.invalidateCache = false;
    }
    _getChunkKey(chunk) {
        const range = chunk?.id_range;
        return range ? `${range.first}:${range.last}` : null;
    }
    _invalidateCache() { this.invalidateCache = true; }
    isMessagesInited() { return this._messagesInited; }
    getLatestChunk() { return this.chunks[this._latest_chunk_id]; }
    getLatestMessage() { return this.getLatestChunk() ? this.getLatestChunk().latest_message : null; }
    appendChunk(chunk, replace_actual = true) {
        let key = this._getChunkKey(chunk);
        let idx = 0;
        if (key != null && this._map.has(key)) {
            console.log("IM | Chunk reuse");
            return this._map.get(key); // already loaded → reuse
        }

        if (this._messagesInited == false && replace_actual == true) {
            this.chunks[0] = chunk;
            key = this._getChunkKey(chunk);
        } else {
            this.chunks.push(chunk);
            idx = this.chunks.length - 1;
            console.log("IM | Chunk ", idx);
        }

        if (key != null) this._map.set(key, idx);

        this._messagesInited = true;
        this._invalidateCache();
        return idx;
    }
    getMessages() {
        if (this._cachedMessages != undefined && !this.invalidateCache) return this._cachedMessages;

        const sorted = this.sorted; // newest-first
        const fnl = [];
        const seen = new Set();
        for (let i = sorted.length - 1; i >= 0; i--) {
            sorted[i].getMessages().forEach((msg) => {
                if (msg && msg.id && !seen.has(msg.id)) {
                    seen.add(msg.id);
                    fnl.push(msg);
                }
            });
        }

        this._cachedMessages = fnl;
        return fnl;
    }
    async findMessageByIdFromApi(id) {
        const found = this._findMessageById(id);
        return found;
    }

    get sorted() {
        return this.chunks.slice(0).sort(
            (a, b) => (b.latest_message?.id ?? -Infinity) - (a.latest_message?.id ?? -Infinity)
        );
    }

    getNewestMessage() {
        const sorted = this.sorted; // newest-first → last is oldest
        return sorted.length ? sorted[sorted.length - 1].first_message : null;
    }

    getUnreadCount() {
        let count = 0;

        try {
            this.getMessages().forEach(item => {
                if (!item.isRead() && item.data.from_id != window.im.state.getId()) {
                    count += 1;
                }
            });
        } catch (e) {
            return count;
        }

        return count;
    }

    // ── lookup / jumps ───────────────────────────────────────────

    _findMessageById(id) {
        if (id == null) return null;
        let found = null;
        const numId = Number(id);
        this.chunks.forEach((chunk) => {
            if (found) return;
            chunk.getMessages().forEach((m) => {
                if (found || !m) return;
                if (m.id == id || Number(m.id) === numId ||
                    m.data?.conversation_message_id == id || Number(m.data?.conversation_message_id) === numId ||
                    m.data?.local_id == id || Number(m.data?.local_id) === numId) {
                    found = m;
                }
            });
        });
        return found;
    }

    async fetchRelatively(messageId, options = {}) {
        const perPage = (getChatGeneralForm()).MESSAGES_PER_PAGE || 20;
        const chunk = new MessageChunk([], true, perPage);
        chunk._direction = options.older ? 'older' : (options.newer ? 'newer' : 'center');

        const params = {
            'peer_id': this._peer.id,
            'count': perPage,
        };
        if (messageId != null) {
            params['start_message_id'] = messageId;
            params['offset'] = options.older ? 1 : (options.newer ? -perPage : 0);
        }

        await chunk.fetch(params);

        return chunk;
    }

    // ── new messages (user-sent / longpoll) ──────────────────────

    pushNewMessage(msg, conv = null, check_chunk = true) {
        const actual = this.getLatestChunk(check_chunk);
        if (actual) {
            actual.pushMessage(msg);
        }
        this._invalidateCache();
        window.im.messenger.update();
    }

    /** Jump back to the newest side (the "return to newest" / DOWN button). */
    scrollToNewest() {
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
}

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

    pushMessage(msg) {
        this.messages.push(msg);
    }
}

export class ScrollPosition {
    static MAX_RENDERED_CHUNKS = 10;

    constructor(peer) {
        this.peer = peer;
        this.direction = "any";
        this.relyMessageId = null;
        this.reachedOldestPosition = false;
        this.reachedNewestPosition = false;
        this.windowStartIndex = null;
        this.windowEndIndex = null;
        this._cachedMessages = undefined;
        this._cachedDays = undefined;
    }

    _invalidateCache() {
        this._cachedMessages = undefined;
        this._cachedDays = undefined;
        if (this.peer && this.peer._chunks) {
            this.peer._chunks._invalidateCache();
        }
    }

    recenter(messageId) {
        this.relyMessageId = messageId != null ? Number(messageId) : null;
        this.direction = (this.relyMessageId == null) ? "end" : "any";
        this.windowStartIndex = null;
        this.windowEndIndex = null;
        this.reachedOldestPosition = false;
        this.reachedNewestPosition = (this.direction === "end");
        this._invalidateCache();
    }

    static fromEnd(peer) {
        const n = new ScrollPosition(peer);
        n.direction = "end";
        n.reachedNewestPosition = true;
        return n;
    }

    static fromStart(peer) {
        const n = new ScrollPosition(peer);
        n.relyMessageId = 0;
        return n;
    }

    getMessages() {
        const chr = this.returnChronologicalDivision();
        const fnl = [];
        const seen = new Set();
        chr.forEach((chunk) => {
            chunk.getMessages().forEach((msg) => {
                if (msg && msg.id && !seen.has(msg.id)) {
                    seen.add(msg.id);
                    fnl.push(msg);
                }
            });
        });
        return fnl;
    }

    getChronologicalChunks() {
        const chunks = this.peer._chunks.chunks || [];
        return chunks
            .filter((chunk) => chunk && (chunk.id_range || (chunk.messages && chunk.messages.length > 0)))
            .sort((a, b) => (a.first_message?.id ?? a.messages?.[0]?.id ?? -Infinity) - (b.first_message?.id ?? b.messages?.[0]?.id ?? -Infinity));
    }

    returnChronologicalDivision() {
        const allChunks = this.getChronologicalChunks();
        if (allChunks.length === 0) return [];

        const maxChunks = ScrollPosition.MAX_RENDERED_CHUNKS;

        if (this.windowEndIndex === null || this.windowStartIndex === null) {
            if (this.direction === "end" || this.relyMessageId == null) {
                this.windowEndIndex = allChunks.length - 1;
                this.windowStartIndex = Math.max(0, this.windowEndIndex - maxChunks + 1);
            } else {
                let anchorIdx = -1;
                for (let i = 0; i < allChunks.length; i++) {
                    if (allChunks[i].hasMessageId(this.relyMessageId)) {
                        anchorIdx = i;
                        break;
                    }
                }
                if (anchorIdx === -1) {
                    anchorIdx = allChunks.length - 1;
                }
                const half = Math.floor(maxChunks / 2);
                this.windowStartIndex = Math.max(0, anchorIdx - half);
                this.windowEndIndex = Math.min(allChunks.length - 1, this.windowStartIndex + maxChunks - 1);
            }
        }

        this.windowStartIndex = Math.max(0, Math.min(this.windowStartIndex, allChunks.length - 1));
        this.windowEndIndex = Math.max(this.windowStartIndex, Math.min(this.windowEndIndex, allChunks.length - 1));

        if (this.windowEndIndex - this.windowStartIndex + 1 > maxChunks) {
            if (this.direction === "end") {
                this.windowStartIndex = this.windowEndIndex - maxChunks + 1;
            } else {
                this.windowEndIndex = this.windowStartIndex + maxChunks - 1;
            }
        }

        if (this.windowEndIndex < allChunks.length - 1) {
            this.reachedNewestPosition = false;
        }
        if (this.windowStartIndex > 0) {
            this.reachedOldestPosition = false;
        }

        return allChunks.slice(this.windowStartIndex, this.windowEndIndex + 1);
    }

    getDayDividedMessages() {
        if (this.peer._chunks.invalidateCache == false && this._cachedDays != undefined) return this._cachedDays;

        const chr = this.returnChronologicalDivision();
        const dayChunks = [];
        const dateMap = new Map();
        const seenMsgIds = new Set();

        for (let i = 0; i < chr.length; i++) {
            chr[i].getMessages().forEach((msg) => {
                if (!msg) return;
                if (msg.id != null) {
                    if (seenMsgIds.has(msg.id)) return;
                    seenMsgIds.add(msg.id);
                }

                if (!msg.getSentTime()) return;
                if (msg.isDeleted(0)) return;

                const dateKey = msg.getDate(1);
                if (!dateMap.has(dateKey)) {
                    const dayChunk = new DayChunk([], false);
                    dayChunk.setDay(dateKey);
                    dayChunk.idate = msg.getSentTime().toLocaleDateString();
                    dayChunk.msg_date = msg.getSentTime();
                    dayChunks.push(dayChunk);
                    dateMap.set(dateKey, dayChunk);
                }
                dateMap.get(dateKey).pushMessage(msg);
            });
        }

        dayChunks.sort((a, b) => {
            const dateA = a.msg_date ? a.msg_date.getTime() : 0;
            const dateB = b.msg_date ? b.msg_date.getTime() : 0;
            return dateA - dateB;
        });

        this._cachedDays = dayChunks;
        this.peer._chunks.invalidateCache = false;
        return dayChunks;
    }

    async loadOlder() {
        if (this.reachedOldestPosition) {
            return;
        }

        const allChunks = this.getChronologicalChunks();
        if (this.windowStartIndex === null) {
            this.returnChronologicalDivision();
        }

        if (this.windowStartIndex > 0) {
            this.windowStartIndex--;
            if (this.windowEndIndex - this.windowStartIndex + 1 > ScrollPosition.MAX_RENDERED_CHUNKS) {
                this.windowEndIndex--;
                this.reachedNewestPosition = false;
            }
            this._invalidateCache();
            return;
        }

        const oldestChunk = allChunks[0];
        const oldestMsgId = oldestChunk ? oldestChunk.first_message?.id : null;

        const msgs = await this.peer._chunks.fetchRelatively(oldestMsgId, { older: true });
        if (!msgs || !msgs.messages.length) {
            this.reachedOldestPosition = true;
            this.peer._chunks._messagesInited = true;
            return;
        }

        const existingIds = new Set();
        allChunks.forEach(c => c.getMessages().forEach(m => { if (m && m.id) existingIds.add(m.id); }));
        const hasOlder = msgs.messages.some(m => m && m.id && !existingIds.has(m.id));
        if (!hasOlder) {
            this.reachedOldestPosition = true;
            this.peer._chunks._messagesInited = true;
            return;
        }

        this.peer._chunks.appendChunk(msgs);

        this.windowStartIndex = 0;
        this.windowEndIndex = Math.min(this.getChronologicalChunks().length - 1, (this.windowEndIndex ?? 0) + 1);
        if (this.windowEndIndex - this.windowStartIndex + 1 > ScrollPosition.MAX_RENDERED_CHUNKS) {
            this.windowEndIndex = this.windowStartIndex + ScrollPosition.MAX_RENDERED_CHUNKS - 1;
            this.reachedNewestPosition = false;
        }

        this._invalidateCache();
        const expectedCount = msgs.count || 20;
        if (msgs.messages.length < expectedCount) {
            this.reachedOldestPosition = true;
        }
    }

    async loadNewer() {
        if (this.reachedNewestPosition) {
            return;
        }

        const allChunks = this.getChronologicalChunks();
        if (this.windowEndIndex === null) {
            this.returnChronologicalDivision();
        }

        if (this.windowEndIndex < allChunks.length - 1) {
            this.windowEndIndex++;
            if (this.windowEndIndex - this.windowStartIndex + 1 > ScrollPosition.MAX_RENDERED_CHUNKS) {
                this.windowStartIndex++;
                this.reachedOldestPosition = false;
            }
            if (this.windowEndIndex >= allChunks.length - 1 && this.direction === "end") {
                this.reachedNewestPosition = true;
            }
            this._invalidateCache();
            return;
        }

        const newestChunk = allChunks[allChunks.length - 1];
        const newestMsgId = newestChunk ? newestChunk.latest_message?.id : null;
        if (newestMsgId == null) {
            this.reachedNewestPosition = true;
            this.peer._chunks._messagesInited = true;
            return;
        }

        const msgs = await this.peer._chunks.fetchRelatively(newestMsgId, { newer: true });
        if (!msgs || !msgs.messages.length) {
            this.reachedNewestPosition = true;
            this.peer._chunks._messagesInited = true;
            return;
        }

        const existingIds = new Set();
        allChunks.forEach(c => c.getMessages().forEach(m => { if (m && m.id) existingIds.add(m.id); }));
        const hasNewer = msgs.messages.some(m => m && m.id && !existingIds.has(m.id));
        if (!hasNewer) {
            this.reachedNewestPosition = true;
            return;
        }

        this.peer._chunks.appendChunk(msgs);

        const updatedChunks = this.getChronologicalChunks();
        this.windowEndIndex = updatedChunks.length - 1;
        if (this.windowEndIndex - this.windowStartIndex + 1 > ScrollPosition.MAX_RENDERED_CHUNKS) {
            this.windowStartIndex = this.windowEndIndex - ScrollPosition.MAX_RENDERED_CHUNKS + 1;
            this.reachedOldestPosition = false;
        }

        this._invalidateCache();
        const expectedCount = msgs.count || 20;
        if (msgs.messages.length < expectedCount) {
            this.reachedNewestPosition = true;
        }
    }

    result() {
        window.im.messenger.update();
    }
}
