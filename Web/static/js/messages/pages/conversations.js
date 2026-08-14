import { ChatMessage, ChatGeneralForm } from '../components/messages.js';
import { ConversationListView } from "../components/common.js"
import { IMTab, IMPage } from './page.js';
import { html, render } from '../components/render.js';

export class ConversationsPage extends IMPage {
    static getPageId() {
        return "conversations";
    }

    isVisibleWhenHidden() {
        return true;
    }

    _update() {
        this.wRender();
    }

    async loadNext(e) {
        toggleUnclickability(e.target, true);

        await window.im.conversations.loadNext();
        this._update();
        toggleUnclickability(e.target, false);
    }

    // search

    async _onMessagesSearch(e) {
        const q = String(e.target.value);

        e.target.value = "";
        window.im.openTabByName("search", true, {
            "q": q
        });
    }

    _chatCreationModal() {
        window.im.openTabByName("friends", true, {
            referrer: "chat_creation"
        });
    }

    render(container) {
        const convs = window.im.conversations.convs;

        render(html`
        <${ConversationListView}
            conversations=${convs}
            hasMore=${window.im.conversations.has_more_items}
            onLoadMore=${(e) => this.loadNext(e)}
            onCreateChat=${() => this._chatCreationModal()}
            onSearch=${(e) => this._onMessagesSearch(e)}
        />
        `, container);
    }
}

export class Conversations {
    constructor() {
        this.total_convs = 0;
        this.CONVERSATIONS_PER_PAGE = 100;
        this.q = null;
        this.peer_id_search = null;
    }

    get convs() {
        // сортировка по дате последнего сообщения
        return (this.all_convs || []).slice(0).sort((a, b) => {
            return Number(b.last_updated) - Number(a.last_updated);
        });
    }

    get has_more_items() {
        if (!this.total_convs) return true;
        return this.loaded_convs_count < this.total_convs;
    }

    get loaded_convs_count() {
        if (!this.all_convs) return 0;
        return this.all_convs.length;
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
        const params = {
            extended: 1,
            count: this.CONVERSATIONS_PER_PAGE,
            offset: offset,
            fields: ChatGeneralForm.BASE_FIELDS,
        };
        let convs = await window.OVKAPI.call('messages.getConversations', params);

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

    _appendConvs(convs) {
        if (!this.all_convs) {
            this.all_convs = [];
        }

        convs.forEach((item) => {
            this.all_convs.push(item);
        });
    }

    async loadNext() {
        let convs = await this.getConversations(this.loaded_convs_count);
        console.log(convs)
        this._appendConvs(convs);
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
