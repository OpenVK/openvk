class ChatGeneralForm {
    constructor(item, supposed_type = null) {
        this.data = item;
        this.supposed_type = supposed_type;

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
        switch (this.supposed_type) {
            case 'user':
                return '/im?sel=' + this.data.id
            case 'club':
                return '/im?sel=-' + this.data.id
            case 'chat':
                return '/im?sel='
        }
    }
}

class ChatMessage {
    constructor(item) {
        this.data = item;
    }
}
