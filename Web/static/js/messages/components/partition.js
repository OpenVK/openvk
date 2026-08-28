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
    constructor(items = [], do_reverse = true, count = 20) {
        this.messages = [];
        this.do_reverse = do_reverse;
        this.count = count;
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

        this._cachedMessages = undefined;
        this._cachedDays = undefined;
        this._messagesInited = false;
        this._peer = peer;
        this._latest_chunk_id = 0;
        this.chunks.push(new MessageChunk([], true));
    }
    _getChunkKey(chunk) {
        const range = chunk?.id_range;
        return range ? `${range.first}:${range.last}` : null;
    }
    _invalidateCache() { this._cachedMessages = undefined; this._cachedDays = undefined; }
    isMessagesInited() { return this._messagesInited; }
    getLatestChunk() { return this.chunks[this._latest_chunk_id]; }
    getLatestMessage() {
        return this.getLatestChunk() // todo;
    }
    appendChunk(chunk, options = {}) {
        const key = this._getChunkKey(chunk);
        if (key != null && this._map.has(key)) {
            return this._map.get(key); // already loaded → reuse
        }

        const idx = this.chunks.length;
        this.chunks.push(chunk);
        if (key != null) this._map.set(key, idx);

        this._messagesInited = true;
        this._invalidateCache();
        return idx;
    }
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
    async findMessageByIdFromApi(id) {
        const found = this._chunks._findMessageById(id);
        return found;
    }

    /*
    get sorted() {
        return this.chunks.slice(0).sort(
            (a, b) => (b.latest_message?.id ?? -Infinity) - (a.latest_message?.id ?? -Infinity)
        );
    }

    getDayDividedMessages() {
        if (this._cachedDays != undefined) return this._cachedDays;

        const dayChunks = [];
        const dateMap = new Map();

        const sorted = this.sorted;
        console.log(sorted)
        for (let i = sorted.length - 1; i >= 0; i--) {
            sorted[i].getMessages().forEach((msg) => {
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
            const [monthA, dayA, yearA] = a.idate.split('/').map(Number);
            const [monthB, dayB, yearB] = b.idate.split('/').map(Number);

            if (monthA !== monthB) return monthA - monthB;

            if (dayA !== dayB) return dayA - dayB;

            return yearA - yearB;
        });
        this._cachedDays = dayChunks;
        return dayChunks;
    }*/

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
            })
        } catch (e) {
            return count;
        }

        return count;
    }

    // ── lookup / jumps ───────────────────────────────────────────

    /*getChunkIndexByMessageId(id) {
        const sorted = this.sorted;
        for (let i = 0; i < sorted.length; i++) {
            if (sorted[i].hasMessageId(id)) {
                return this.chunks.indexOf(sorted[i]);
            }
        }
        return -1;
    }*/

    /*_findMessageById(id) {
        let found = null;
        this.chunks.forEach((chunk) => {
            if (found) return;
            chunk.getMessages().forEach((m) => {
                if (found) return;
                if (m.id == id) found = m;
            });
        });
        return found;
    }*/

    async fetchRelatively(messageId, options = {}) {
        const chunk = new MessageChunk([], true, getChatMessageClass().MESSAGES_PER_PAGE);
        chunk._direction = options.older ? 'older' : (options.newer ? 'newer' : 'center');

        const params = {
            'peer_id': this._peer.id,
        };
        if (messageId != null) {
            params['start_message_id'] = messageId;
        } else {
            params['offset'] = options.newer ? getChatMessageClass().MESSAGES_PER_PAGE * -1 : 0;
        }

        await chunk.fetch(params);

        return chunk;
    }

    // ── new messages (user-sent / longpoll) ──────────────────────

    pushNewMessage(msg, conv = null, check_chunk = true) {
        const actual = this.getLatestChunk(check_chunk);

        /*if (!actual) {
            if (conv != null) {
                conv.updateLastMessage(msg)
            };
            return;
        }*/

        actual.pushMessage(msg);
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

    pushMessage(msg) {
        this.messages.push(msg);
    }
}

export class ScrollPosition {
    constructor(peer) {
        this.peer = peer;
        this.direction = "any";
        this.rootMessage = null;
        this.reachedOldestPosition = false;
        this.reachedNewestPosition = false;
    }

    static fromEnd(peer) {
        const n = new ScrollPosition(peer);
        n.direction = "end";

        return n;
    }

    static fromStart(peer) {
        const n = new ScrollPosition(peer);
        n.rootMessage = 0;
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
            const first_message = this.peer._chunks.getNewestMessage();
            let msgs = null;

            if (first_message) {
                msgs = await this.peer._chunks.fetchRelatively(first_message.id, { older: true });
            } else {
                msgs = await this.peer._chunks.fetchRelatively(null, { older: true });
            }

            if (!msgs.messages.length) {
                this.reachedOldestPosition = true;
                return;
            }

            if (msgs.messages.length < 20) {
                this.peer._chunks.appendChunk(msgs);
                this.reachedOldestPosition = true;
                return;
            }

            this.peer._chunks.appendChunk(msgs);
        }
    }

    result() {
        window.im.messenger.update();
    }
}
