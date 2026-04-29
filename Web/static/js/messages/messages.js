class ChatGeneralForm {
    constructor(item, supposed_type = null) {
        this.data = item;
        this.supposed_type = supposed_type;
    }

    get avatar_any() {
        return this.data.photo_100;
    }

    get name() {
        return this.data.first_name;
    }

    get chat_url() {

    }
}
