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
        const first = this.first_message?.id;
        let last = this.latest_message?.id;
        if (first == null || last == null) {
            if (last == null && this.latest_message?.data?.is_sending) {
                for (let i = this.messages.length - 1; i > -1; i--) {
                    if (this.messages[i] && this.messages[i].id) {
                        last = this.messages[i].id + 1;
                        break;
                    }
                }
            } else {
                return null;
            }
        }
        return (first != null && last != null) ? { first, last } : null;
    }

    getMessages() {
        return this.do_reverse ? Array.from(this.messages).reverse() : this.messages;
    }

    pushMessage(msg) {
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

        console.log("IM | messages.getHistory ",params);

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

    async fetchRelatively(messageId, options = {}) {
        const perPage = 20;
        const chunk = new MessageChunk([], true, perPage);
        chunk._direction = options.older ? 'older' : (options.newer ? 'newer' : 'center');

        const params = {
            'peer_id': this._peer.id,
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
        this.olderIndexes = [];
        this.newerIndexes = [];
        this.olderOffset = 0;
        this.newerOffset = 0;
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

    returnChronologicalDivision() {
        console.log("IM | returnChronologicalDivision");
        const chunks = this.peer._chunks.chunks;
        const map = this.peer._chunks._map;

        let anchorIndex = 0;
        if (this.direction != "end" && this.relyMessageId != null) {
            for (const [range, idx] of map.entries()) {
                const sep = range.split(":");
                const first = parseInt(sep[0], 10);
                const last = parseInt(sep[1], 10);
                if (this.relyMessageId >= first && this.relyMessageId <= last) {
                    anchorIndex = idx;
                    break;
                }
            }
        }

        const visible = new Set([]);
        this.olderIndexes.forEach((i) => visible.add(i));
        visible.add(anchorIndex);
        this.newerIndexes.forEach((i) => visible.add(i));

        const ordered = [];
        visible.forEach((i) => {
            const chunk = chunks[i];
            if (chunk && chunk.id_range) {
                ordered.push(chunk);
            }
        });

        ordered.sort(
            (a, b) => (a.first_message?.id ?? -Infinity) - (b.first_message?.id ?? -Infinity)
        );

        // Windowing: limit maximum rendered chunks to 10
        if (ordered.length > ScrollPosition.MAX_RENDERED_CHUNKS) {
            if (this.direction === "end" || this.newerIndexes.length === 0) {
                // Scrolling up: keep the oldest chunks, unload the bottom (newest) chunks from DOM
                const trimmed = ordered.slice(0, ScrollPosition.MAX_RENDERED_CHUNKS);
                this.reachedNewestPosition = false;
                return trimmed;
            } else if (this.olderIndexes.length === 0) {
                // Scrolling down: keep the newest chunks, unload the top (oldest) chunks from DOM
                const trimmed = ordered.slice(ordered.length - ScrollPosition.MAX_RENDERED_CHUNKS);
                this.reachedOldestPosition = false;
                return trimmed;
            } else {
                // Middle navigation: keep sliding window
                const start = Math.max(0, ordered.length - ScrollPosition.MAX_RENDERED_CHUNKS);
                return ordered.slice(start, start + ScrollPosition.MAX_RENDERED_CHUNKS);
            }
        }

        return ordered;
    }

    getDayDividedMessages() {
        if (this.peer._chunks.invalidateCache == false && this._cachedDays != undefined) return this._cachedDays;

        const chr = this.returnChronologicalDivision();
        const dayChunks = [];
        const dateMap = new Map();
        const seenMsgIds = new Set();

        for (let i = 0; i < chr.length; i++) {
            chr[i].getMessages().forEach((msg) => {
                if (!msg || !msg.id) return;
                if (seenMsgIds.has(msg.id)) return;
                seenMsgIds.add(msg.id);

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
        if (this.reachedOldestPosition == true) {
            console.log("IM | reachedOldestPosition");
            return;
        }

        const chr = this.returnChronologicalDivision();
        let oldestMsgId = null;

        if (chr.length > 0 && chr[0] && chr[0].first_message) {
            oldestMsgId = chr[0].first_message.id;
        } else {
            const newest = this.peer._chunks.getNewestMessage();
            oldestMsgId = newest ? newest.id : null;
        }

        // Check if older chunk is already cached in chunks array
        let foundInCache = false;
        if (oldestMsgId != null) {
            for (let i = 0; i < this.peer._chunks.chunks.length; i++) {
                const chunk = this.peer._chunks.chunks[i];
                if (chunk && chunk.latest_message && chunk.latest_message.id < oldestMsgId) {
                    if (this.olderIndexes.indexOf(i) === -1) {
                        this.olderIndexes.push(i);
                        foundInCache = true;
                        break;
                    }
                }
            }
        }

        if (foundInCache) {
            if (this.olderIndexes.length + this.newerIndexes.length > ScrollPosition.MAX_RENDERED_CHUNKS + 2) {
                if (this.newerIndexes.length > 0) {
                    this.newerIndexes.shift();
                    this.reachedNewestPosition = false;
                }
            }
            this._invalidateCache();
            return;
        }

        const msgs = await this.peer._chunks.fetchRelatively(oldestMsgId, { older: true });

        console.log(msgs);
        if (!msgs || !msgs.messages.length) {
            this.reachedOldestPosition = true;
            return;
        }

        const idx = this.peer._chunks.appendChunk(msgs);
        console.log("IM | new older chunk id: ", idx, this.olderIndexes);

        if (this.olderIndexes.indexOf(idx) === -1) {
            this.olderIndexes.push(idx);
        }

        // Drop distant newer chunks from visible render when scrolling far up
        if (this.olderIndexes.length + this.newerIndexes.length > ScrollPosition.MAX_RENDERED_CHUNKS + 2) {
            if (this.newerIndexes.length > 0) {
                this.newerIndexes.shift();
                this.reachedNewestPosition = false;
            }
        }

        this._invalidateCache();
        if (msgs.messages.length < 20) {
            this.reachedOldestPosition = true;
        }
    }

    async loadNewer() {
        if (this.reachedNewestPosition == true) {
            console.log("IM | reachedNewestPosition");
            return;
        }

        const chr = this.returnChronologicalDivision();
        if (!chr.length) return;

        const newestChunk = chr[chr.length - 1];
        if (!newestChunk || !newestChunk.latest_message) return;

        const newestMsgId = newestChunk.latest_message.id;

        // Check if newer chunk is already cached in memory
        let foundInCache = false;
        for (let i = 0; i < this.peer._chunks.chunks.length; i++) {
            const chunk = this.peer._chunks.chunks[i];
            if (chunk && chunk.first_message && chunk.first_message.id > newestMsgId) {
                if (this.newerIndexes.indexOf(i) === -1 && this.olderIndexes.indexOf(i) === -1) {
                    this.newerIndexes.push(i);
                    foundInCache = true;
                    break;
                }
            }
        }

        if (foundInCache) {
            if (this.olderIndexes.length + this.newerIndexes.length > ScrollPosition.MAX_RENDERED_CHUNKS + 2) {
                if (this.olderIndexes.length > 0) {
                    this.olderIndexes.shift();
                    this.reachedOldestPosition = false;
                }
            }
            this._invalidateCache();
            return;
        }

        const msgs = await this.peer._chunks.fetchRelatively(newestMsgId, { newer: true });

        if (!msgs || !msgs.messages.length) {
            this.reachedNewestPosition = true;
            return;
        }

        const idx = this.peer._chunks.appendChunk(msgs);
        if (this.newerIndexes.indexOf(idx) === -1) {
            this.newerIndexes.push(idx);
        }

        // Drop distant older chunks from visible render when scrolling far down
        if (this.olderIndexes.length + this.newerIndexes.length > ScrollPosition.MAX_RENDERED_CHUNKS + 2) {
            if (this.olderIndexes.length > 0) {
                this.olderIndexes.shift();
                this.reachedOldestPosition = false;
            }
        }

        this._invalidateCache();
        if (msgs.messages.length < 20) {
            this.reachedNewestPosition = true;
        }
    }

    async result() {
        await window.im.messenger.update();
    }
}
