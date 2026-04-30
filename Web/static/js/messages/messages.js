class ChatGeneralForm {
    constructor(item, supposed_type = null) {
        this.base_fields = 'photo_100';
        this.data = item;
        this.supposed_type = supposed_type;
        this.messages = [];

        if (!supposed_type) {
            if (item.first_name) {
                this.supposed_type = 'user'
            } else if (item.name) {
                this.supposed_type = 'club'
            } else {
                this.supposed_type = 'chat' // or channel
            }
        }
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

    get id() {
        switch (this.supposed_type) {
            case 'user':
                return this.data.id
            case 'club':
                return this.data.id * -1
            case 'chat':
                return this.data.id + 2000000000000
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

        _authorize(messages, (item) => {
            return item.peer_id;
        }, (item, author) => {
            item.sender = new ChatGeneralForm(author);
        });

        return messages.items;
    }

    _appendMessages(messages) {
        messages.forEach(m => this.messages.push(m));
    }

    _getLocalMessages() {
        return this.messages;
    }
}

function _authorize(arr, get_id = null, set_id = null) {
    arr.items.forEach(item => {
        const _id = get_id(item);
        const author = find_author(_id, arr.profiles, arr.groups)

        set_id(item, author);
    })

    return arr;
}

class ChatMessage {
    constructor(item) {
        this.data = item;
    }
}
