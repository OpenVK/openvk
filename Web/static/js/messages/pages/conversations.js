//import { ChatMessage, ChatGeneralForm } from '../components/messages.js';
const { ChatMessage, ChatGeneralForm } = await es6import_Im(import.meta.url, "../components/messages.js");
//import { ConversationListView } from "../components/common.js"
const { ConversationListView } = await es6import_Im(import.meta.url, "../components/common.js");
//import { IMTab, IMPage } from './page.js';
const { IMTab, IMPage } = await es6import_Im(import.meta.url, "./page.js");
//import { html, render } from '../components/render.js';
const { ScrollPosition } = await es6import_Im(import.meta.url, "../components/partition.js");
const { html, render } = await es6import_Im(import.meta.url, "../components/render.js");

export class ConversationsPage extends IMPage {
    static getPageId() { return "conversations"; }
    isVisibleWhenHidden() { return true; }
    getTabName() {
        if (this.isForward()) {
            return tr("messenger_tab_conversations_forward")
        }

        return tr("messenger_tab_conversations")
    }
    shouldCloseOnExit() { return this.container == null || this.isForward(); }
    updateHeader(header) { header.changeByConvNumber(Number(window.im.conversations.total_convs)); }
    _update() { this.wRender(); }
    isForward() { return this.options.forward != null }

    async loadNext(e) {
        toggleUnclickability(e.target, true);

        await window.im.conversations.loadNext();
        this._update();
        toggleUnclickability(e.target, false);
    }

    // search

    async _onMessagesSearch(e, from_tab = false) {
        const q = String(e.target.value);
        e.target.value = "";

        console.log(q)
        window.im.openTabByName("search", true, {
            "q": q
        });
    }

    _chatCreationModal() {
        window.im.openTabByName("friends", true, {
            referrer: "chat_creation"
        });
    }

    updUrl() {
        const url = new URL(location.href);
        url.searchParams.delete("sel");
        url.searchParams.delete("joinByTopic");
        window.im.state._pushState(url.toString());
    }

    render(container) {
        this.getNode().addClass("page-conversations");
        let orig_convs = window.im.conversations.convs;
        let convs = [];

        if (window.im.conversations.isShowingUnread) {
            orig_convs.forEach(item => {
                if (!item.isRead()) {
                    convs.push(item);
                }
            });
        } else {
            convs = orig_convs;
        }

        render(html`
        <${ConversationListView}
            conversations=${convs}
            hasMore=${window.im.conversations.has_more_items}
            onLoadMore=${(e) => this.loadNext(e)}
            onCreateChat=${() => this._chatCreationModal()}
            onSearch=${(e) => this._onMessagesSearch(e)}
            isForward=${this.isForward()}
            page=${this}
            unreadMode=${window.im.conversations.isShowingUnread}
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
        this.all_convs = [];
        this.isShowingUnread = false;
    }

    getWindow() { return window.im.getTab("conversations").render_class; }
    update() { return this.getWindow().update(); }

    get convs() {
        // сортировка по дате последнего сообщения
        return (this.all_convs || []).slice(0).sort((a, b) => {
            return Number(b.last_updated) - Number(a.last_updated);
        });
    }

    get has_more_items() {
        if (!this.total_convs) return false;
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

        if (window.im.state.group_id) {
            params.group_id = Math.abs(window.im.state.group_id);
        }

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
            if (!item.peer) {
                const isChat = id >= ChatGeneralForm.CHAT_RUBICON || item.conversation?.peer?.type === 'chat';
                const localId = isChat && id >= ChatGeneralForm.CHAT_RUBICON ? id - ChatGeneralForm.CHAT_RUBICON : id;
                const fallbackData = {
                    id: isChat ? localId : id,
                    type: isChat ? 'chat' : (id < 0 ? 'club' : 'user')
                };
                if (item.conversation?.chat_settings) {
                    Object.assign(fallbackData, item.conversation.chat_settings);
                }
                item.peer = new ChatGeneralForm(fallbackData);
                window.im.cached_profiles._addProfileCache(item.peer);
            }
            if (item.peer) {
                if (item.conversation?.chat_settings?.members) {
                    item.peer.data.members = item.conversation.chat_settings.members;
                }
                if (!item.peer.data.title && item.conversation?.chat_settings?.title) {
                    item.peer.data.title = item.conversation.chat_settings.title;
                }
                if (item.conversation?.chat_settings?.pinned_message) {
                    item.peer.data.pinned_message = item.conversation.chat_settings.pinned_message;
                }
                if (item.conversation?.pinned_message) {
                    item.peer.data.pinned_message = item.conversation.pinned_message;
                }
            }
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

    async loadNext(im = null) {
        let convs = [];
        console.log(im.report_data)
        if (im && im.report_data) {
            convs = [await this._findConvFromApi(im.report_data.peer_id)];
        } else {
            convs = await this.getConversations(this.loaded_convs_count);
        }

        this._appendConvs(convs);
    }

    _findConv(id) {
        //console.log("Trying to find convo with id", id)
        const _l = this.all_convs.filter((itm) => itm.peer && itm.peer.id == id);
        if (_l[0] == undefined) {
            throw Error('Not found chat, id: ' + String(id));
        }
        return _l[0];
    }

    async _findConvFromApi(id, check_cached = false) {
        try {
            return this._findConv(id);
        } catch (e) {
            console.error(e);
        }

        let b = null;
        if (check_cached) {
            b = window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(id);
        }

        if (!b) {
            b = await ChatGeneralForm.resolveByIdAndReturnClass(id);
        }

        if (!b) {
            throw Error('Not found chat ' + id);
        }

        console.log("Not found chat with id ", id, ", returning a new one.")
        const convPayload = { 'peer': b };
        if (b.data && b.data._full_conversation) {
            convPayload['conversation'] = b.data._full_conversation;
        }
        const c = new Conversation(convPayload);
        this.all_convs.push(c);
        return c;
    }

    toggleMode(mode) {
        switch (mode) {
            case "unread":
                this.isShowingUnread = true;
                this.update();
                break;
            case "all":
                this.isShowingUnread = false;
                this.update();
                break;
        }
    }
}

export class Conversation {
    constructor(conversation_item) {
        this._conversation = conversation_item.conversation;
        this._last_message = conversation_item.last_message ? new ChatMessage(conversation_item.last_message) : null;
        this.peer = conversation_item.peer;
        this.activity_updated = new Date();
        this.current_activity = {};
        this.draft = null;
        this._endScrollPosition = ScrollPosition.fromEnd(this.peer);
        this._scroll = null;
    }

    hasScrollPosition() { return this._scroll != null; }
    getEndScrollPosition() { return this._endScrollPosition; }
    getScrollPosition() { return this.hasScrollPosition() ? this._scroll : this.getEndScrollPosition(); }
    setDraft(draft) { this.draft = draft }
    clearDraft() { this.draft = null }
    hasActivity() { return this.peer ? this.getActivityMsg()[1].length > 0 : false; }
    getActivityMsg() {
        let s = "";
        let names = [];
        if (this.peer && this.peer.supposed_type == "chat") {
            const a = Object.entries(this.current_activity ?? {});

            a.forEach(item => {
                if (item[1].conv && item[1].conv.peer) {
                    names.push(item[1].conv.peer.getName());
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
            if (names.length > 0) {
                s = s + "...";
            }
        } else if (this.peer) {
            const v = Object.values(this.current_activity);

            if (v.length > 0) {
                names.push("peer");
                if (v[0].variant == "writing") {
                    s = tr("messenger_typing_between_two")
                }
                s = s + "...";
            }
        }

        return [s, names];
    }
    async setTyping(user_ids = [], variant = "writing") {
        const REMOVE_TYPING_TIMEOUT = 5000;

        console.log("IM | Conversations | ", this, " is writing")

        for (const item of user_ids) {
            const val = {
                "variant": variant,
                "conv": await window.im.conversations._findConvFromApi(Number(item))
            };

            this.current_activity[item] = val;
        }

        window.im.messenger.update();

        this.activity_updated = new Date();
        const old = new Date(this.activity_updated);

        setTimeout(() => {
            if (this.activity_updated.getTime() == old.getTime()) {
                console.info("IM | Conversations | Wiped activity for ", this, "!")
                this.current_activity = {};
                window.im.messenger.update();
            }
        }, REMOVE_TYPING_TIMEOUT);
    }
    updateLastMessage(msg) { this._last_message = msg; }
    get id() { return this.peer ? this.peer.id : (this._conversation?.peer?.id || 0); }

    get last_message() {
        try {
            if (this.peer && this.peer._chunks) {
                const msg = this.peer._chunks.getLatestMessage();
                if (msg) {
                    return msg;
                }
            }
        } catch (e) {
            console.error(e);
        }

        return this._last_message;
    }

    set last_message(val) {
        this._last_message = val ? (val instanceof ChatMessage ? val : new ChatMessage(val)) : null;
    }

    get conversation() { return this._conversation; }
    get last_updated() { if (!this.last_message) { return null; } else { return this.last_message.getSentTime(); } }
    isRead() { return this.unread_count == 0; }

    get unread_count() {
        if (this._unread_count !== undefined) {
            return this._unread_count;
        }
        if (this.peer && this.peer._chunks && this.peer._chunks.isMessagesInited()) {
            return this.peer._chunks.getUnreadCount();
        }

        try {
            return (this._conversation && this._conversation.unread_count) ? this._conversation.unread_count : 0;
        } catch (e) {
            return 0;
        }
    }

    set unread_count(val) {
        this._unread_count = Number(val) || 0;
        if (this._conversation) {
            this._conversation.unread_count = this._unread_count;
        }
    }

    pushMessage(msg, conv = null, check_chunk = true) {
        if (this.peer && this.peer._chunks) this.peer._chunks.pushNewMessage(msg, conv, check_chunk);
    }

    findMessageById(id) {
        return (this.peer && this.peer._chunks) ? this.peer._chunks._findMessageById(id) : null;
    }

    getPinnedMessage() {
        if (this._conversation) {
            if (this._conversation.current_pinned_message) return this._conversation.current_pinned_message;
            if (this._conversation.pinned_message) return this._conversation.pinned_message;
            if (this._conversation.chat_settings && this._conversation.chat_settings.pinned_message) return this._conversation.chat_settings.pinned_message;
        }
        if (this.peer && this.peer.data) {
            if (this.peer.data.pinned_message) return this.peer.data.pinned_message;
            if (this.peer.data.chat_settings && this.peer.data.chat_settings.pinned_message) return this.peer.data.chat_settings.pinned_message;
            if (this.peer.data.current_pinned_message) return this.peer.data.current_pinned_message;
        }
        if (this.peer && this.peer._chunks && this.peer._chunks.chunks) {
            for (const chunk of this.peer._chunks.chunks) {
                if (chunk && chunk.messages) {
                    for (const m of chunk.messages) {
                        if (m && m.data && (m.data.is_pinned == 1 || m.data.is_pinned === true)) {
                            return m.data;
                        }
                    }
                }
            }
        }
        return null;
    }

    setPinnedMessage(msgData) {
        if (!this._conversation) this._conversation = {};
        this._conversation.pinned_message = msgData;
        this._conversation.current_pinned_message = msgData;
        if (this._conversation.chat_settings) {
            this._conversation.chat_settings.pinned_message = msgData;
        }
        if (this.peer && this.peer.data) {
            this.peer.data.pinned_message = msgData;
            this.peer.data.current_pinned_message = msgData;
            if (this.peer.data.chat_settings) {
                this.peer.data.chat_settings.pinned_message = msgData;
            }
        }
        if (this.peer && this.peer._chunks && this.peer._chunks.chunks) {
            for (const chunk of this.peer._chunks.chunks) {
                if (chunk && chunk.messages) {
                    for (const m of chunk.messages) {
                        if (m && m.data) {
                            if (!msgData) {
                                m.data.is_pinned = 0;
                            } else if (m.data.id == msgData.id || m.data.conversation_message_id == msgData.conversation_message_id) {
                                m.data.is_pinned = 1;
                            } else {
                                m.data.is_pinned = 0;
                            }
                        }
                    }
                }
            }
        }
    }

    getPinnedMessageObject() {
        const pin = this.getPinnedMessage();
        if (!pin) return null;
        if (pin instanceof ChatMessage) return pin;
        const msg = new ChatMessage(pin);
        if (!msg.peer_id && this.id) msg.data.peer_id = this.id;
        return msg;
    }

    getPinnedMessageId() {
        const pin = this.getPinnedMessage();
        return pin ? (pin.id || pin.conversation_message_id || null) : null;
    }

    hasPinned() {
        return this.getPinnedMessageId() != null;
    }
}
