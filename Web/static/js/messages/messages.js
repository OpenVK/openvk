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

class ChatGeneralForm {
    static swag = 2000000000;
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
        const y = [];
        this.message_chunks_order.forEach(order => {
            y.push(this.message_chunks[order]);
        })

        return y;
    }

    get messages() {
        const fnl = [];
        this.chunks.forEach(chunk => {
            chunk.getMessages().forEach(msg => {
                fnl.push(msg);

            })
        });

        return fnl;
    }

    static async resolveById(id) {
        if (id == 0) {
            return window.im._current;
        }

        if (id > this.swag) {
            // chats or smth
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

    async getMessages(message_id, offset = 0) {
        const rev = true;
        const messages = new MessagesChunk([], rev);
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
        const resp = await window.OVKAPI.call('messages.send', {
            'peer_id': this.id,
            'message': msg.text,
            'attachments': msg.str_attachments
        });

        console.info('sent message to ' + this.id)
    }

    _pushNewMessage(msg) {
        this._getMostActualChunk()._pushMessage(msg);
        window.im.messenger.view._triggerUpdate();
    }

    _getMostActualChunk() {
        return this.message_chunks[0];
    }

    _getLatestChunk() {
        return this.message_chunks[this.message_chunks.length - 1];
    }

    _isEndReached() {
        return this._end_reached ?? false;
    }

    _appendMessagesChunk(messages, before = false) {
        this._messages_inited = true;
        let id = this.message_chunks.push(messages);
        // todo относительно какого то чанка
        if (before) {
            this.message_chunks_order.unshift(id - 1);
        } else {
            this.message_chunks_order.push(id - 1);
        }
    }

    _isMessagesInited() {
        return this._messages_inited;
    }

    // Pagination
    async _messagesLoad_UpFromLastChunk() {
        if (this._isEndReached()) {
            return;
        }

        const _id = this._getLatestChunk().first_message;
        const msgs = await this.getMessages(_id.id);

        this._end_reached = msgs.isEnd();
        this._appendMessagesChunk(msgs, true);
        window.im.messenger.view._triggerUpdate();
    }
}

class Conversation {
    constructor(conversation_item) {
        this._conversation = conversation_item.conversation;
        this._last_message = new ChatMessage(conversation_item.last_message);
        this.peer = conversation_item.peer;
    }

    get last_message() {
        return this._last_message;
    }

    get conversation() {
        return this._conversation;
    }

    get id() {
        return this.peer.id;
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

    // Sender задаётся в другом файле
    get sender() {
        return this.data.sender;
    }

    get text() {
        return escapeHtml(this.data.text);
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

    static fromEvent(event) {
        const [, id, flags, peer, ts, subject, text, attachments, randomId] = event;

        // todo: add ts here
        const msg = new ChatMessage({
            'id': id,
            'flags': flags,
            'from_id': attachments.from,
            'peer': peer,
            'text': text,
            'attachments': attachments,
            'random_id': randomId
        });
        msg._guessSender();

        return msg;
    }

    setText(text) {
        this.data.text = text;
    }
}
