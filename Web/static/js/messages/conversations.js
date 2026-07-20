import { ChatMessage, ChatGeneralForm } from './messages.js';
import { render, html, ConversationListView } from './components.js';

const tr = window.tr;

export class ConversationsViewModel {
    constructor() {
        this._counter = 0;
    }

    _update() {
        this._counter++;
        const container = document.querySelector('div[data-window="conversations"]');
        if (container && !container.classList.contains('hidden')) {
            window.im.conversations._render(container);
        }
    }

    async loadNext() {
        await window.im.conversations._loadNext();
        this._update();
    }

    _chatCreationModal() {
        window.im.selectTab("friends", "chat_creation");
    }
}

export class Conversations {
    constructor() {
        this.total_convs = 0;
        this.CONVERSATIONS_PER_PAGE = 100;
        this.q = null;
    }

    async _resolveSel(sel) {
        let _ = null;

        try {
        this.convs.forEach((item) => {
            if (item.peer.id === sel) {
            _ = item;
            }
        });
        } catch (e) {
        console.error(e);
        }

        if (_) {
            return _.peer;
        }

        let _n = await ChatGeneralForm.resolveById(sel);
        if (!_n) {
        return null;
        }

        return new ChatGeneralForm(_n);
    }

    async getConversations(offset = 0) {
        let convs = await window.OVKAPI.call('messages.getConversations', {
            extended: 1,
            count: this.CONVERSATIONS_PER_PAGE,
            offset: offset,
            fields: ChatGeneralForm.base_fields,
        });

        const lists = [];

        convs.profiles?.forEach((prof) => {
            window.im.cached_profiles._addProfileCache(new ChatGeneralForm(prof));
        });
        convs.groups?.forEach((group) => {
            window.im.cached_profiles._addProfileCache(new ChatGeneralForm(group));
        });
        convs.chats?.forEach((group) => {
            window.im.cached_profiles._addProfileCache(new ChatGeneralForm(group));
        });

        convs.items.forEach((item) => {
            const id = item.conversation.peer.id;
            item.peer = window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(id);
            lists.push(new Conversation(item));
        });

        if (!this.total_convs) {
            this.total_convs = convs.count;
        }

        return lists;
    }

    get loaded_convs_count() {
        if (!this.all_convs) return 0;
        return this.all_convs.length;
    }

    _appendConvs(convs) {
        if (!this.all_convs) {
            this.all_convs = [];
        }

        convs.forEach((item) => {
            this.all_convs.push(item);
        });
    }

    async _loadNext() {
        let convs = await this.getConversations(this.loaded_convs_count);
        this._appendConvs(convs);
    }

    async init() {
        this.view = new ConversationsViewModel();
        await this._loadNext();
    }

    swapConvs(conv_1, conv_2) {}

    _findConv(id) {
        console.log("Trying to find convo with id", id)
        const _l = this.all_convs.filter((itm) => itm.peer.id == id);
        if (_l[0] == undefined) {
            throw Error('Not found chat');
        }
        return _l[0];
    }

    async _findConvFromApi(id) {
        try {
            return this._findConv(id);
        } catch (e) {
            console.error(e);
        }

        const b = await ChatGeneralForm.resolveByIdAndReturnClass(id);
        if (!b) {
            throw Error('Not found chat '+ id);
        }

        console.log("Not found chat with id ", id, ", returning a new one.")
        const c = new Conversation({ 'peer': b });
        this.all_convs.push(c);
        return c;
    }

    get convs() {
        return (this.all_convs || []).slice(0).sort((a, b) => {
        return Number(b.last_updated) - Number(a.last_updated);
        });
    }

    get has_more_items() {
        if (!this.total_convs) return true;
        return this.loaded_convs_count < this.total_convs;
    }

    hasAppeared(container) {
        return container.querySelector('.crp-list') != null;
    }

    appear(container) {
        container.classList.remove('hidden');
        if (this.hasAppeared(container)) {
            this._render(container);
            return;
        }

        this._render(container);
        document.documentElement.scroll({ top: 0 });
    }

    _render(container) {
        const convs = this.convs;

        render(html`
        <${ConversationListView}
            conversations=${convs}
            hasMore=${this.has_more_items}
            onLoadMore=${() => this.view.loadNext()}
            onCreateChat=${() => this.view._chatCreationModal()}
            onSearch=${(e) => this._onMessagesSearch(e)}
        />
        `, container);
    }

    hide(container) {
        container.classList.add('hidden');
    }

    // search

    async _onMessagesSearch(e) {
        this.q = String(e.target.value);

        e.target.value = "";
        window.im.selectTab("search");
    }
}

export class Conversation {
    constructor(conversation_item) {
        this._conversation = conversation_item.conversation;
        this._last_message = new ChatMessage(conversation_item.last_message);
        this.peer = conversation_item.peer;
        this.activity_updated = new Date();
        this.current_activity = {};
    }

    hasActivity() {
        return this.getActivityMsg()[1].length > 0;
    }

    getActivityMsg() {
        let s = "";
        let names = [];
        if (this.peer.supposed_type == "chat") {
            const a = Object.entries(this.current_activity ?? {});

            a.forEach(item => {
                console.log(item[1])
                if (item[1].conv) {
                    names.push(item[1].conv.peer.name);
                }
            })

            switch (names.length) {
                case 0:
                    break;
                case 1:
                    s = tr("messenger_typing_one_user", names[0]);
                    break;
                case 2:
                    s = tr("messenger_typing_two_users", names[0], names[1]);
                    break;
                case 3:
                    s = tr("messenger_typing_three_users", names[0], names[1], names[2]);
                    break;
                default:
                    s = tr("messenger_typing_other", names.length)
                    break
            }

            console.log(s, names)
        } else {
            const v = Object.values(this.current_activity);

            if (v.length > 0) {
                names.push("peer");
                if (v[0].variant == "writing") {
                    s = tr("messenger_typing_between_two")
                }
            }
        }

        return [s, names];
    }

    updateLastMessage(msg) {
        this._last_message = msg;
    }

    async setTyping(user_ids = [], variant = "writing") {
        console.log("ryihjiyhyt", user_ids)
        const REMOVE_TYPING_TIMEOUT = 5000;

        for (const item of user_ids) {
            console.log(item)
            const val = {
                "variant": variant,
                "conv": await window.im.conversations._findConvFromApi(Number(item))
            };

            console.log(val);
            this.current_activity[item] = val;
        }

        console.log("this.current_activity", this.current_activity);
        window.im.messenger.view._triggerUpdate();

        this.activity_updated = new Date();
        const old = new Date(this.activity_updated);

        setTimeout(() => {
            console.log(this.activity_updated.getTime(), old.getTime())
            if (this.activity_updated.getTime() == old.getTime()) {
                console.info("IM | Conversations | Wiped activity for ", this, "!")
                this.current_activity = {};
                window.im.messenger.view._triggerUpdate();
            }
        }, REMOVE_TYPING_TIMEOUT);
    }

    get last_message() {
        try {
            if (this.peer) {
                return this.peer._getLatestChunk(false).latest_message;
            }
        } catch (e) {}

        return this._last_message;
    }

    get conversation() {
        return this._conversation;
    }

    get last_updated() {
        if (!this.last_message) return null;
        return this.last_message.sent;
    }

    get id() {
        return this.peer.id;
    }
}
