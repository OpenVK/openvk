class MessagesChunk {
    constructor(items, do_reverse = false, count = 10, msg_offset = null) {
        this.messages = [];
        this.do_reverse = do_reverse;
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
    get first_messages() {
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

    getMessages() {
        if (this.do_reverse) {
            return this.messages.reverse();
        } else {
            return this.messages;
        }
    }
}

class ChatGeneralForm {
    static swag = 2000000000;
    static MESSAGES_PER_PAGE = 10;
    static base_fields = 'photo_100'

    constructor(item) {
        this.data = item || {};
        this.chunks = [];
        this.msg_offset = 0;
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

    async getMessages(offset = 0) {
        const messages = await window.OVKAPI.call('messages.getHistory', {
            'peer_id': this.id,
            'start_message_id': offset,
            'count': ChatGeneralForm.MESSAGES_PER_PAGE,
            'extended': 1,
            'fields': ChatGeneralForm.base_fields
        });

        const _l = _authorize(messages, (item) => {
            return item.from_id;
        }, (item, author) => {
            item.sender = new ChatGeneralForm(author);
        }, (item, arr) => {
            arr.push(new ChatMessage(item));
        });

        // messages comes from new to old
        return new MessagesChunk(_l, true);
    }

    getOldestMessage() {
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
    }

    async moveUp() {
        const _id = this.getOldestMessage().id;
        console.log(await this.getMessages(_id))
    }

    async sendMessage(msg) {
        const resp = await window.OVKAPI.call('messages.send', {
            'peer_id': this.id,
            'message': msg.text,
            'attachments': msg.str_attachments
        });

        console.info('sent message to ' + this.id)
    }

    _appendMessagesChunk(messages) {
        this._messages_inited = true;
        this.chunks.push(messages)
    }

    _getLocalMessages() {
        return this.messages;
    }

    _isMessagesInited() {
        return this._messages_inited;
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

function _authorize(arr, get_id = null, set_id = null, finalize = null) {
    let fin = [];

    arr.items.forEach(item => {
        const _id = get_id(item);
        const author = find_author(_id, arr.profiles, arr.groups)

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

    get sender() {
        return this.data.sender;
    }

    get text() {
        return escapeHtml(this.data.text);
    }

    get id() {
        return this.data.id;
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

        const msg = new ChatMessage();
        msg.data = {
            'id': id,
            'flags': flags,
            'peer': peer,
            'ts': ts,
            'text': text,
            'attachments': attachments,
            'random_id': randomId
        }

        return msg;
    }

    setText(text) {
        this.data.text = text;
    }
}
