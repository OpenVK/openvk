class ChatGeneralForm {
    constructor(item) {
        this.base_fields = 'photo_100';
        this.data = item;
        this.messages = [];
        this.swag = 2000000000000;
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

    get avatar_any() {
        return this.data.photo_100;
    }

    get name() {
        switch (this.supposed_type) {
            case 'user':
                return this.data.first_name
            case 'club':
                return this.data.name
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

    static async resolveById(id) {
        if (id == 0) {
            return window.im._current;
        }

        if (id > this.swag) {
            // chats or smth
            return;
        } else {
            if (id > 0) {
                const __ = await window.OVKAPI.call('users.get', {'user_ids': id})

                return __[0]
            } else {
                const __ = await window.OVKAPI.call('groups.getById', {'group_ids': Math.abs(id)})
                if (__[0].type == 'undefined') {
                    return null;
                }

                return __[0]
            }
        }
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

    async getMessages(offset = 0) {
        const count = 10;
        const messages = await window.OVKAPI.call('messages.getHistory', {
            'peer_id': this.id,
            'offset': offset,
            'count': count,
            'extended': 1,
            'fields': this.base_fields
        });

        const _l = _authorize(messages, (item) => {
            return item.from_id;
        }, (item, author) => {
            item.sender = new ChatGeneralForm(author);
        }, (item, arr) => {
            arr.push(new ChatMessage(item));
        });

        return _l;
    }

    _appendMessages(messages) {
        this._messages_inited = true;
        messages.forEach(m => this.messages.push(m));
    }

    _getLocalMessages() {
        return this.messages;
    }

    _isMessagesInited() {
        return this._messages_inited;
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
    constructor(item) {
        this.data = item;
    }

    get sender() {
        return this.data.sender;
    }

    get text() {
        return this.data.text;
    }

    get attachments() {
        return this.data.attachments;
    }
}
