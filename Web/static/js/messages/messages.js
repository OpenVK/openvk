class MessagesChunk {
    constructor(items, do_reverse = false, count = 10, msg_offset = null) {
        this.messages = [];
        this.do_reverse = do_reverse; // True - сообщения от новых к старым
        this.count = count;
        this.msg_offset = msg_offset;
        this.latest_message_index = 0;
        let _index = 0;
        items.forEach(item => {
            this.messages.push(item);
            _index += 1;
        })
    }

    // not oldest, but first in array
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
            'fields': ChatGeneralForm.base_fields
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

        _l.forEach(msg => {
            this.messages.push(msg);
        })
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

class DayChunk extends MessagesChunk {
    setDay(date) {
        this.date = date;
    }

    get readable_date() {
        return this.date;
    }
}

class ChatGeneralForm {
    // Representation of User, Club or Chat. Its not divided to the "info" part and messages part

    static swag = 2000000000; // ids of the chats are bigger than that number
    static MESSAGES_PER_PAGE = 20;
    static base_fields = 'photo_100'

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
                return this.data.id + this.swag;
        }
    }

    get is_deleted_by_me() {
        return this.data.deleted_by_me == 1
    }

    get is_deleted() {
        return this.data.deleted == 1
    }

    get supposed_type() {
        if (this.data.first_name) {
            return 'user';
        }

        if (this.data.name) {
            return 'club';
        }

        return 'chat';
    }

    get can_write() {
        return true;
    }

    get avatar_any() {
        return this.data.photo_100;
    }

    get full_name() {
        switch (this.supposed_type) {
            case 'user':
                return escapeHtml(this.data.first_name + ' ' + this.data.last_name);
            case 'club':
                return escapeHtml(this.data.name);
        }
    }

    get name() {
        switch (this.supposed_type) {
            case 'user':
                return escapeHtml(this.data.first_name);
            case 'club':
                return escapeHtml(this.data.name);
        }
    }

    get page_url() {
        switch (this.supposed_type) {
            case 'user':
                return '/id' + this.data.id
            case 'club':
                return '/club' + this.data.id
        }
    }

    get chat_url() {
        return '/im?sel=' + this.id
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
            return this._cached_all_messages
        }

        this.chunks.forEach(chunk => {
            chunk.getMessages().forEach(msg => {
                fnl.push(msg);

            })
        });

        this._cached_all_messages = fnl;
        return fnl;
    }

    get divided_messages() {
        const dayChunks = [];
        const dateMap = new Map();
        this.chunks.forEach(chunk => {
            chunk.getMessages().forEach(msg => {
            if (!msg.sent) return;
            const dateKey = msg.sent.toISOString().split('T')[0];

            if (!dateMap.has(dateKey)) {
                const dayChunk = new DayChunk([]);
                dayChunk.setDay(dateKey);
                dayChunks.push(dayChunk);
                dateMap.set(dateKey, dayChunk);
            }

            dateMap.get(dateKey)._pushMessage(msg);
            //fnl.push(msg);
            })
        })

        dayChunks.sort((a, b) => a.date.localeCompare(b.date));

        return dayChunks;
    }

    _removeCache() {
        this._cached_all_messages = undefined
    }

    static async resolveById(id) {
        if (id == 0) {
            return window.im._current;
        }

        if (id > this.swag) {
            // chats or smth
            // TODO
            return;
        } else {
            if (id > 0) {
                const __ = await window.OVKAPI.call('users.get', {'user_ids': id, 'fields': ChatGeneralForm.base_fields})

                return __[0]
            } else {
                const __ = await window.OVKAPI.call('groups.getById', {'group_ids': Math.abs(id), 'fields': ChatGeneralForm.base_fields})
                if (__[0].type == 'undefined') {
                    return null;
                }

                return __[0]
            }
        }
    }

    static async resolveByIdAndReturnClass(id) {
        const c = await ChatGeneralForm.resolveById(id)
        if (c == null) {
            return undefined
        }

        return new ChatGeneralForm(c)
    }

    async getMessages(message_id, offset = 0) {
        const rev = true;
        const messages = new MessagesChunk([], rev);
        messages.latest_message_index = message_id;
        await messages.fetch({
            'start_message_id': message_id,
            'offset': 0,
            'peer_id': this.id
        });

        return messages;
    }

    /*getOldestMessage() {
        let latest = null;
        this.chunks.forEach(ch => {
            let _l = ch.latest_message;
            if (!latest) {
                latest = _l;
                return;
            }

            if (latest.id > _l.id) {
                latest = _l;
            }
        })

        return latest;
    }*/

    async sendMessage(msg) {
        this._pushNewMessage(msg);
        const resp = await window.OVKAPI.call('messages.send', {
            'peer_id': this.id,
            'message': msg.text,
            'attachments': msg.str_attachments,
        }); // returns id
        msg.data.id = resp
        console.info('IM | Sent message to ' + this.id)
    }

    _findMessageById(id) {
        let f = null;
        this.messages.forEach(e => {
            if (f != null) {
                return;
            }

            if (e.id == id) {
                f = e;
            }
        })

        return f;
    }

    _pushNewMessage(msg) {
        console.log('IM | Pushed msg ', msg)
        this._getLatestChunk()._pushMessage(msg);
        this._removeCache()
        window.im.messenger.view._triggerUpdate();
    }

    _getMostActualChunk() {
        return this.message_chunks[0];
    }

    _getLatestChunk() {
        if (this.chunks[this.chunks.length - 1] == undefined) {
            this.chunks.push(new MessagesChunk([]))
        }

        return this.chunks[this.chunks.length - 1];
    }

    _isEndReached() {
        return this._end_reached ?? false;
    }

    _appendMessagesChunk(messages, before = false) {
        this._messages_inited = true;
        let id = this.message_chunks.push(messages);
        // todo относительно какого то чанка
        /*if (before) {
            this.message_chunks_order.unshift(id - 1);
        } else {
            this.message_chunks_order.push(id - 1);
        }*/
    }

    _isMessagesInited() {
        return this._messages_inited;
    }

    // Pagination
    async _messagesLoad_UpFromLastChunk() {
        console.log('IM | Scrolling chat ' + this.id + '.')

        if (this._isEndReached()) {
            return;
        }

        const _id = this._getLatestChunk().first_message;
        const msgs = await this.getMessages(_id.id);

        this._end_reached = msgs.isEnd();

        const prev_scroll = window.im.messenger.view.messagesListBlock.scrollTop;
        const prev_height = window.im.messenger.view.messagesListBlock.scrollHeight;

        if (!this._end_reached) {
            this._appendMessagesChunk(msgs, true);
            window.im.messenger.view._triggerUpdate();
        } else {
            console.log('IM | End of chat ' +this.id+ ' is reached!')
        }

        if (!this._end_reached) {
            setTimeout(() => {
                let new_scroll = prev_scroll + (window.im.messenger.view.messagesListBlock.scrollHeight - prev_height);

                window.im.messenger.view._scrollTo(new_scroll)
            }, 1)
        }
    }

    _chunks_HasMoreNewerChunkRelativelyToCurrentChat() {
        return false;
    }
}

function _authorize(items, profiles = null, groups = null, get_id = null, set_id = null, finalize = null) {
    let fin = [];

    items.forEach(item => {
        const _id = get_id(item);
        let author = null;

        if (!profiles && !groups) {
            author = window.im.cached_profiles._findCachedProfileById(_id);
        } else {
            author = find_author(_id, profiles, groups);
        }

        set_id(item, author);

        if (finalize) {
            finalize(item, fin);
        }
    })

    if (finalize) {
        return fin;
    }

    return arr;
}

class ChatMessage {
    static AUTHOR_NAME_HIDE_TIMEOUT = 100;

    doHideHead(another_msg) {
        let _time_eq = this.data.date - another_msg.data.date;
        // если прошло больше минуты с отправки
        return this.data.from_id == another_msg.data.from_id && _time_eq < ChatMessage.AUTHOR_NAME_HIDE_TIMEOUT;
    }

    constructor(item = {}) {
        this.data = item;
    }

    _guessSender() {
        //if (!this.data.sender) {
        this.data.sender = window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(this.data.from_id);
    }

    get sent() {
        return new Date(this.data.date * 1000);
    }

    // Sender задаётся в другом файле
    get sender() {
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

    get peer_id() {
        return this.data.peer;
    }

    get from_id() {
        return this.data.from_id;
    }

    get attachments() {
        const _at = this.data.attachments;
        if (!_at) {
            return []
        }

        return _at;
    }

    get str_attachments() {
        const _at = this.attachments;
        if (_at.length == 0) {
            return '';
        }
    }

    get readable_date() {
        // в зависимости от локализации конечно
        return this.sent.toLocaleTimeString('ru-RU', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    }

    static fromEvent(event) {
        const [, id, flags, peer, ts, subject, text, attachments, randomId] = event;

        // todo: add ts here
        const msg = new ChatMessage({
            'id': id,
            'flags': flags,
            'from_id': attachments.from,
            'date': ts,
            'peer': peer,
            'text': text,
            'attachments': attachments,
            'random_id': randomId
        });
        msg._guessSender();

        return msg;
    }

    setDeleted(by_me = false) {
        // I'm afraid to remove message from array, so it will just remove text and attachment
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
