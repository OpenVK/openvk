import { MessageChunk, Chunks, DayChunk } from "./partition.js";
//const { MessageChunk, Chunks, DayChunk } = await es6import_Im(import.meta.url, "./partition.js");

export class Draft {
    constructor() {
        this.text = null;
        this.attachments_html = [];
        this.scroll = null;
        this.editMsg = null;
        this.forwarded_msg = null;
    }

    static fromPage(page) {
        const d = new Draft();
        d.text = page.getCurrentText();
        d.attachments_html = page.getCurrentAttachments();
        d.scroll = page.getScroll();
        d.editMsg = window.im.messenger.editMsg;
        d.forwarded_msg = window.im.messenger.forwarded_msg;

        return d;
    }

    loadToPage(page) {
        console.log("applying ", this, " to ", page);
        if (this.text != null) {
            window.im.messenger.currentDraft = this.text;
            page.container.querySelector(".messenger-app--input---messagebox textarea").value = this.text;
        }
        if (this.attachments_html[0] != null) {
            page.container.querySelector(".post-horizontal").innerHTML = this.attachments_html[0];
        }
        if (this.attachments_html[1] != null) {
            page.container.querySelector(".post-vertical").innerHTML = this.attachments_html[1];
        }
        if (this.editMsg) {
            console.log("this.editMsg", this.editMsg);
            window.im.messenger.editMsg = this.editMsg;
        } else {
            window.im.messenger.editMsg = null;
        }
        if (this.forwarded_msg) {
            window.im.messenger.setForwarded(this.forwarded_msg);
        }

        this.loadScroll(page);
    }

    loadScroll(page) {
        if (this.scroll != null) {
            page._scrollTo(this.scroll);
        } else {
            console.log(this);
            page._scrollToEnd();
        }
    }
}

class ChatMembers {
    constructor(link) {
        this.items = [];
        this.total_count = 0;
        this.peer_id = link.id;
        this.offset = 0;
        this.perPage = 50;
    }

    async load(offset = 0) {
        try {
            const v = await window.OVKAPI.call("messages.getConversationMembers", {
                "peer_id": this.peer_id,
                "extended": 1,
                "fields": "photo_50,photo_100,online,last_seen,sex,screen_name"
            });
            if (v.profiles || v.groups) {
                if (window.im.cached_profiles && typeof window.im.cached_profiles._moveToProfileCache === 'function') {
                    window.im.cached_profiles._moveToProfileCache(v.profiles || [], v.groups || []);
                }
            }
            this.total_count = v.count || (v.items ? v.items.length : 0);
            this.items = [];
            (v.items || []).forEach(item => {
                const memberId = item.member_id || item.id;
                let profile = (v.profiles || []).find(p => p.id == memberId) || (v.groups || []).find(g => -g.id == memberId) || null;
                this.items.push({
                    ...item,
                    member_id: memberId,
                    profile: profile
                });
            });
            this.offset = (v.items || []).length;
        } catch (e) {
            console.error("IM | Failed to load conversation members", e);
        }
    }
}

export function getChatGeneralForm() {
    return ChatGeneralForm;
}

export function getChatMessageClass() {
    return ChatMessage;
}

export class ChatGeneralForm {
    static CHAT_RUBICON = 2000000000;
    static MESSAGES_PER_PAGE = 20;
    static BASE_FIELDS = 'photo_100,photo_200,photo_max,last_seen,photo_id,status,sex,can_write_private_message,can_invite,followers_count,is_messages_blocked';
    static SAVED_MESSAGES_AVATAR = "/assets/packages/static/openvk/img/im/saved_messages.png";
    static CHAT_NO_AVATAR = "/assets/packages/static/openvk/img/im/chat_meaningless.jpg";

    constructor(item) {
        this.data = item || {};
        this._chunks = new Chunks(this);
        this.pinned_message_chunks = [];

        this._messages_inited = false;
        this.members = null;
    }

    get CHAT_RUBICON() {
        return ChatGeneralForm.CHAT_RUBICON;
    }

    // ── identity ─────────────────────────────────────────────────────

    get id() {
        switch (this.supposed_type) {
            case 'user':
                return Number(this.data.id);
            case 'club':
                return Number(this.data.id) * -1;
            case 'chat':
                if (Number(this.data.id) < ChatGeneralForm.CHAT_RUBICON) {
                    return Number(this.data.id) + ChatGeneralForm.CHAT_RUBICON;
                } else {
                    return Number(this.data.id);
                }
        }
    }

    get supposed_type() {
        if (this.data.type === 'chat') return 'chat';
        if (this.data.type === 'user') return 'user';
        if (this.data.type === 'club' || this.data.type === 'group') return 'club';
        if (this.data.first_name) return 'user';
        if (this.data.name) return 'club';
        return 'chat';
    }

    isAdmin() {
        if (this.supposed_type != "chat") {
            return false;
        }
        const currentUserId = window.openvk ? window.openvk.current_id : window.im?.state?.getId();
        if (this.data.admin_id === currentUserId) return true;
        if (this.data.chat_settings) {
            if (this.data.chat_settings.admin_id === currentUserId) return true;
            if (this.data.chat_settings.is_admin) return true;
            if (Array.isArray(this.data.chat_settings.admin_ids) && this.data.chat_settings.admin_ids.includes(currentUserId)) return true;
        }
        return false;
    }

    can(thing, relatively_current_group = null) { // unified function
        switch (thing) {
            case "write":
                return (this.data.can_write_private_message ?? this.data.can_write ?? 1) === 1;
            case "update_title":
            case "invite_new":
            case "update_avatar":
                return this.isAdmin() && this.supposed_type == "chat";
            case "leave_chat":
                return this.supposed_type == "chat";
            case "view_invite_links":
                return this.supposed_type == "chat" && false;
            case "pin":
                return this.supposed_type == "chat" ? this.isAdmin() : false;
            case "invite":
                return (this.data.can_invite ?? 1) === 1;
        }

        return true;
    }

    isILeft() {
        return false;
    }

    get has_custom_avatar() {
        if (this.supposed_type !== 'chat') return true;
        if (this.data.photo_id) return true;
        const p = this.getAvatar();
        if (!p) return false;
        if (typeof p === 'string' && (p.includes('chat_meaningless') || p.includes('camera_'))) return false;
        return true;
    }

    get members_ids() {
        if (this.data.members && Array.isArray(this.data.members)) {
            return this.data.members;
        }
        if (this.data.users && Array.isArray(this.data.users)) {
            return this.data.users.map(u => typeof u === 'object' ? (u.id || u.member_id) : u);
        }
        if (this.data.chat_settings?.members && Array.isArray(this.data.chat_settings.members)) {
            return this.data.chat_settings.members;
        }
        if (this.data.chat_settings?.active_ids && Array.isArray(this.data.chat_settings.active_ids)) {
            return this.data.chat_settings.active_ids;
        }
        if (this._members && this._members.items && Array.isArray(this._members.items)) {
            return this._members.items.map(m => m.member_id || m.id || m);
        }
        return [];
    }

    getMosaicAvatars() {
        const memberIds = this.members_ids;
        if (!memberIds || memberIds.length === 0) {
            return [];
        }

        const avatars = [];
        for (const mId of memberIds) {
            if (avatars.length >= 4) break;
            const prof = window.im?.cached_profiles?._findCachedProfileById(mId);
            if (prof && prof.getAvatar()) {
                avatars.push(prof.getAvatar());
            } else {
                avatars.push('/assets/packages/static/openvk/img/camera_100.png');
            }
        }
        return avatars;
    }

    getAvatar(size = "mid", count_self = false) {
        if (this.isSavedMessages() && count_self) {
            return ChatGeneralForm.SAVED_MESSAGES_AVATAR;
        }

        let ava = null;
        switch (size) {
            case "mid":
                ava = this.data.photo_100;
                break;
            case "big":
                ava = this.data.photo_200;
                break;
            case "max":
                ava = this.data.photo_max;
                break;
        }

        if (!ava && this.supposed_type == "chat") {
            return ChatGeneralForm.CHAT_NO_AVATAR;
        }

        return ava ?? '/assets/packages/static/openvk/img/camera_100.png';
    }
    hasAvatar() { return this.data.photo_200 != null }
    getName(count_self = false, short = false) {
        if (count_self && this.isSavedMessages()) {
            return tr("saved_messages");
        }

        switch (this.supposed_type) {
            case 'user':
                if (short) {
                    return this.data.first_name;
                }

                return ((this.data.first_name || '') + ' ' + (this.data.last_name || '')).trim();
            case 'club':
                return this.data.name || '';
            case 'chat':
                return this.data.title || tr("chat");
        }
    }

    getPageUrl() {
        switch (this.supposed_type) {
            case 'user':
                return '/id' + this.data.id;
            case 'club':
                return '/club' + this.data.id;
        }
    }

    getChatUrl() {
        return '/im?sel=' + this.id;
    }

    isSavedMessages() {
        return this.id === window.im.state.getId();
    }

    getGender() {
        if (this.data.sex == 1) {
            return 'female'
        }

        if (this.data.sex == 2) {
            return 'male'
        }

        return 'neutral';
    }

    getOnlineStatusString() {
        if (this.data.followers_count) {
            return tr("followers", this.data.followers_count);
        }

        if (this.data.members_count) {
            return tr("members_count", this.data.members_count);
        }

        if (!this.data.last_seen) {
            return tr("im_was_online_unkown_" + this.getGender()).toLowerCase();
        }

        const time = this.data.last_seen.time;
        const date = new Date(time * 1000);
        const today = new Date();
        const sameMonth = date.getMonth() === today.getMonth();
        const timeStr = date.toLocaleTimeString(navigator.language, { hour: '2-digit', minute: '2-digit' });
        const dayStr = date.toLocaleDateString(navigator.language, {
            month: '2-digit',
            day: '2-digit'
        });

        if ((Math.floor(today.getTime() / 1000) - Math.floor(date.getTime() / 1000)) <= 300) {
            return tr("online")
        }

        if (sameMonth && date.getDate() === today.getDate()) {
            return tr("im_was_online_today_" + this.getGender(), timeStr).toLowerCase();
        }

        if (sameMonth && date.getDate() === today.getDate() - 1) {
            return tr("im_was_online_yesterday_" + this.getGender(), timeStr).toLowerCase();
        }

        return tr("im_was_online_other_" + this.getGender(), timeStr, dayStr).toLowerCase();
    }

    isMuted() {
        return false;
    }

    // ── initial loading ──────────────────────────────────────────────

    static async resolveById(id) {
        if (id == 0) {
            return window.im._current;
        }

        if (id >= ChatGeneralForm.CHAT_RUBICON) {
            const __ = await window.OVKAPI.call('messages.getConversationsById', { 'peer_ids': id, 'fields': ChatGeneralForm.BASE_FIELDS, 'extended': 1 });

            if (!__ || !__.items || __.items.length == 0) {
                return null;
            }
            const conv = __.items[0].conversation || {};
            const chatSettings = conv.chat_settings || {};
            const chatData = (__.chats && __.chats.length > 0) ? __.chats[0] : {};
            const peerData = Object.assign({ id: id, type: 'chat' }, chatSettings, chatData);
            if (conv.pinned_message) peerData.pinned_message = conv.pinned_message;
            if (chatSettings.pinned_message) peerData.pinned_message = chatSettings.pinned_message;
            peerData._full_conversation = conv;
            return peerData;
        } else {
            if (id > 0) {
                const __ = await window.OVKAPI.call('users.get', { 'user_ids': id, 'fields': ChatGeneralForm.BASE_FIELDS });
                if (__[0].first_name == "DELETED" && __[0].deactivated == "deleted") {
                    return null;
                }
                return __[0];
            } else {
                const __ = await window.OVKAPI.call('groups.getById', { 'group_ids': Math.abs(id), 'fields': ChatGeneralForm.BASE_FIELDS });
                if (__[0].type == 'undefined') {
                    return null;
                }
                return __[0];
            }
        }
    }

    static async resolveByIdAndReturnClass(id) {
        const c = await ChatGeneralForm.resolveById(id);
        if (c == null) return undefined;
        return new ChatGeneralForm(c);
    }

    isMessagesInited() { return this._chunks.isMessagesInited(); }

    // переход к действиям

    async sendMessage(msg, reply_to = null, attachments = null, wait_until_send = null, push_callback = null, forward_msgs = null) {
        this._chunks.pushNewMessage(msg);
        if (push_callback) {
            push_callback();
        }
        const datas = {
            'peer_id': this.id,
            'message': msg.getText(true),
            //'attachment': msg.getStringAttachments(), не помню что это
        };

        if (window.im.usage_type == "group") {
            datas["group_id"] = Math.abs(window.im.state.getOperator().id);
        }

        if (reply_to != null) {
            datas['reply_to'] = reply_to.id;
        }

        if (attachments != null) {
            datas['attachment'] = attachments.join(',');
        }

        if (forward_msgs != null && forward_msgs.length && forward_msgs.length > 0) {
            const fwd = [];
            let peer_id = null;
            forward_msgs.forEach(item => {
                const fId = item.id;
                if (fId) fwd.push(fId);
                peer_id = item.peer_id || item.data?.peer_id;
            });

            datas['forward_messages'] = fwd.join(',');
            datas['forward'] = JSON.stringify({
                "peer_id": peer_id,
                "conversation_message_ids": fwd,
                "message_ids": fwd
            });
            msg.data.fwd_messages = forward_msgs.slice(0);
        }

        if (wait_until_send != null) {
            await new Promise(function (r) { setTimeout(r, wait_until_send); });
        }

        if (msg.is_deleted == true) {
            console.info('IM | Maybe message send interrupted, so does not sending. ', this.id, msg);
            return;
        }

        try {
            const resp = await window.OVKAPI.call('messages.send', datas);
            if (typeof resp === 'object' && resp !== null) {
                msg.data.id = resp.message_id || resp.id;
                msg.data.conversation_message_id = resp.conversation_message_id || resp.cmid;
                msg.data.local_id = msg.data.conversation_message_id;
            } else {
                msg.data.id = resp;
                const prevMsg = this._chunks ? this._chunks.getLatestMessage() : null;
                if (prevMsg && prevMsg.data && (prevMsg.data.conversation_message_id || prevMsg.data.local_id)) {
                    const prevCmid = prevMsg.data.conversation_message_id || prevMsg.data.local_id;
                    msg.data.conversation_message_id = prevCmid + 1;
                    msg.data.local_id = msg.data.conversation_message_id;
                }
            }
            msg.data.is_sending = false;
            console.info('IM | Sent message to ' + this.id);
            if (this._chunks) {
                this._chunks._invalidateCache();
            }
            const conv = window.im?.conversations ? window.im.conversations._findConv(this.id) : null;
            if (conv && typeof conv.getScrollPosition === 'function' && conv.getScrollPosition()) {
                conv.getScrollPosition()._invalidateCache();
            }
            if (window.im?.messenger) {
                window.im.messenger.update();
            }
            if (window.im?.conversations) {
                window.im.conversations.update();
            }
        } catch (e) {
            let d = String(e);
            if (d.startsWith("Error: Broker failure")) {
                d = d.replace("Error: Broker failure: ", "");
            }

            msg.data.error_text = d;
            msg.data.resend_params = datas;
            msg.data.is_sending = false;
            console.error('IM | Did not sent message to ' + this.id, ': ', e);
            if (this._chunks) {
                this._chunks._invalidateCache();
            }
            if (window.im?.messenger) {
                window.im.messenger.update();
            }
        }
    }

    // update

    async updateTitle(title) {
        if (this.supposed_type != "chat") {
            return;
        }

        const chatId = this.id > ChatGeneralForm.CHAT_RUBICON ? (this.id - ChatGeneralForm.CHAT_RUBICON) : this.id;
        try {
            await window.OVKAPI.call("messages.editChat", {
                "chat_id": chatId,
                "title": title
            });

            this.data.title = title;
            this.data.name = title;
            if (this.data.chat_settings) {
                this.data.chat_settings.title = title;
            }

            const conv = window.im.conversations._findConv(this.id);
            if (conv) {
                if (conv._conversation && conv._conversation.chat_settings) {
                    conv._conversation.chat_settings.title = title;
                }
                if (conv.peer) {
                    conv.peer.data.title = title;
                    conv.peer.data.name = title;
                }
                conv.name = title;
            }

            window.im.conversations.update();
            window.im.messenger.update();
            if (window.im.getTab("contact") && window.im.getTab("contact").render_class) {
                window.im.getTab("contact").render_class.update();
            }
        } catch (e) {
            fastError(String(e.message || e.error_msg || e));
            console.error("Failed to edit chat title", e);
        }
    }

    async updateAvatar(blob) {
        if (this.supposed_type != "chat") {
            return;
        }

        const chatId = this.id > ChatGeneralForm.CHAT_RUBICON ? (this.id - ChatGeneralForm.CHAT_RUBICON) : this.id;
        try {
            const v = await window.OVKAPI.call("photos.getChatUploadServer", {
                "chat_id": chatId
            });
            const upload_url = v.upload_url;
            const fd = new FormData();
            fd.append("photo", blob, "chat_avatar.jpg");

            const f = await fetch(upload_url, {
                method: "POST",
                body: fd
            });
            const j = await f.json();
            const photo = j.photo;
            const hash = j.hash;
            const v1 = await window.OVKAPI.call("messages.setChatPhoto", {
                "file": photo,
                "hash": hash,
                "chat_id": chatId,
            });

            if (v1 && (v1.chat || v1.response)) {
                const c = v1.chat || v1.response;
                if (c.photo_50) this.data.photo_50 = c.photo_50;
                if (c.photo_100) this.data.photo_100 = c.photo_100;
                if (c.photo_200) this.data.photo_200 = c.photo_200;
                if (c.photo_max) this.data.photo_max = c.photo_max;
            }

            const conv = window.im.conversations._findConv(this.id);
            if (conv && conv.peer) {
                if (this.data.photo_50) conv.peer.data.photo_50 = this.data.photo_50;
                if (this.data.photo_100) conv.peer.data.photo_100 = this.data.photo_100;
                if (this.data.photo_200) conv.peer.data.photo_200 = this.data.photo_200;
                if (this.data.photo_max) conv.peer.data.photo_max = this.data.photo_max;
            }

            window.im.conversations.update();
            window.im.messenger.update();
            if (window.im.getTab("contact") && window.im.getTab("contact").render_class) {
                window.im.getTab("contact").render_class.update();
            }

            return v1;
        } catch (e) {
            fastError(String(e.message || e.error_msg || e));
            console.error("Failed to update chat avatar", e);
        }
    }

    // blockness

    isClubMessagesBlocked() {
        if (window.im.state.getId() < 0) {
            return this.data.is_me_blocked == 1;
        }
        return this.data.is_messages_blocked == 1;
    }

    async toggleClubMessagesBlockness(event, action = true) {
        let state = action == "enable";
        // true - enable, false - forbid
        let r = null;
        const currentId = window.im.state.getId();
        const params = {};
        event.target.classList.add("lagged");
        if (currentId < 0) {
            params["group_id"] = Math.abs(currentId);
            params["owner_id"] = Math.abs(this.id);
            if (state) {
                r = await window.OVKAPI.call("groups.unban", params);
                this.data.is_me_blocked = 0;
            } else {
                r = await window.OVKAPI.call("groups.ban", params);
                this.data.is_me_blocked = 1;
            }
        } else {
            params["group_id"] = Math.abs(this.id);
            if (state) {
                r = await window.OVKAPI.call("messages.allowMessagesFromGroup", params);
                this.data.is_messages_blocked = 0;
            } else {
                r = await window.OVKAPI.call("messages.denyMessagesFromGroup", params);
                this.data.is_messages_blocked = 1;
            }
        }

        event.target.classList.remove("lagged");

        window.im.getTab("contact").render_class.update();
        window.im.messenger.update();
    }

    async checkMembers(offset = 0) {
        if (this.supposed_type != "chat") {
            return true;
        }

        this.members = new ChatMembers(this);
        await this.members.load(offset);
    }

    // Readness

    get in_read() {
        return this._in_read || (this.data ? this.data.in_read : 0) || 0;
    }
    set in_read(val) {
        this._in_read = Math.max(this._in_read || 0, val || 0);
    }

    get out_read() {
        return this._out_read || (this.data ? this.data.out_read : 0) || 0;
    }
    set out_read(val) {
        this._out_read = Math.max(this._out_read || 0, val || 0);
    }

    async read(startMessageId = 0) {
        const params = {
            "peer_id": this.id,
        };
        if (startMessageId > 0) {
            params["start_message_id"] = startMessageId;
        } else {
            try {
                const latestChunk = this._chunks.getLatestChunk();
                if (latestChunk && latestChunk.latest_message && latestChunk.latest_message.id) {
                    params["start_message_id"] = latestChunk.latest_message.id;
                }
            } catch (e) { }
        }
        if (window.im.state.getId() < 0) {
            params["group_id"] = Math.abs(window.im.state.getId());
        }

        try {
            await window.OVKAPI.call("messages.markAsRead", params);
            if (this._chunks) {
                const latestMsg = this._chunks.getLatestMessage();
                if (latestMsg) {
                    const msgId = (latestMsg.data && (latestMsg.data.local_id || latestMsg.data.id)) || latestMsg.id || 0;
                    this.in_read = msgId;
                }
                const currentUserId = window.openvk ? window.openvk.current_id : window.im?.state?.getId();
                this._chunks.getMessages().forEach(m => {
                    if (m.data && m.data.from_id != currentUserId) {
                        m.data.read_state = 1;
                    }
                });
                this._chunks._invalidateCache();
            }
            if (window.im?.conversations) {
                const conv = window.im.conversations._findConv(this.id);
                if (conv) {
                    conv.unread_count = 0;
                    if (conv._conversation) conv._conversation.unread_count = 0;
                    if (conv.peer) conv.peer.in_read = this.in_read;
                    if (typeof conv.getScrollPosition === 'function' && conv.getScrollPosition()) {
                        conv.getScrollPosition()._invalidateCache();
                    }
                }
                window.im.conversations.update();
            }
            if (window.im?.messenger) {
                window.im.messenger.update();
            }
            if (window.im?.fastChats) {
                window.im.fastChats.update();
            }
        } catch (e) {
            console.error("Failed to mark as read", e);
        }
    }
}

// ChatMessage

export class ChatMessage {
    static AUTHOR_NAME_HIDE_TIMEOUT = 600; // 60 * 10 = 10 minutes

    constructor(item = {}) {
        item = item || {};
        this.data = item;
        this.has_not_loaded_attachments = false;

        if (item.reply_message != null) {
            if (typeof item.reply_message.attachments == "string" && item.reply_message.attachments.length > 0) {
                const a = item.reply_message.attachments.split(",");
                const n = [];
                a.forEach(i => {
                    const _type = i.split('_')[0].replace(/[0-9]/g, '');
                    const f = {};
                    f['type'] = _type;
                    f[_type] = {};

                    n.push(f);
                })

                item.reply_message.attachments = n;
            }

            this.data.reply_message = new ChatMessage(item.reply_message);
        }

        if (item.fwd_messages && Array.isArray(item.fwd_messages)) {
            this.data.fwd_messages = item.fwd_messages.map(f => f instanceof ChatMessage ? f : new ChatMessage(f));
        } else if (item.forward_messages && Array.isArray(item.forward_messages)) {
            this.data.fwd_messages = item.forward_messages.map(f => f instanceof ChatMessage ? f : new ChatMessage(f));
        }
    }

    getFwdMessages() {
        return this.data.fwd_messages || [];
    }

    async hydrateFromEvent(msg) {
        const prevFwd = this.data.fwd_messages;
        this.data = msg.data;
        if ((!this.data.fwd_messages || this.data.fwd_messages.length === 0) && prevFwd && prevFwd.length > 0) {
            this.data.fwd_messages = prevFwd;
        }

        if (this.has_not_loaded_attachments === true) {
            this.has_not_loaded_attachments = false;
        }
    }

    _guessSender() {
        this.data.sender = window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(this.data.from_id);
        if (this.data.fwd_messages && Array.isArray(this.data.fwd_messages)) {
            this.data.fwd_messages.forEach(f => {
                if (f && typeof f._guessSender === 'function') f._guessSender();
            });
        }
    }
    doHideHead(another_msg) {
        let _time_eq = another_msg.data.date - this.data.date;
        return this.data.from_id == another_msg.data.from_id && _time_eq < ChatMessage.AUTHOR_NAME_HIDE_TIMEOUT && this.isAction() == false;
    }
    isMine() {
        const currentUserId = window.openvk ? window.openvk.current_id : window.im?.state?.getId();
        return Number(this.data.from_id) === Number(currentUserId);
    }
    getSentTime() { return new Date(this.data.date * 1000); }
    hasSender() { return this.data.from_id != null; }
    get sender() {
        if (!this.data.sender) {
            this._guessSender();
        }

        return this.data.sender;
    }
    get peer() {
        try {
            return window.im.conversations._findConv(this.data.peer_id).peer;
        } catch (e) {
            return window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(this.data.peer_id);
        }
    }
    getActionText() {
        if (!this.data.action) return "";
        const act = this.data.action;
        const type = act.type;
        const sender = this.sender;
        const gender = sender ? sender.getGender() : "neutral";

        switch (type) {
            case "chat_create": {
                const title = (act.text || "").trim();
                return title ? tr("event_chat_creation_" + gender, title) : (tr("event_chat_creation_no_title_" + gender) || tr("event_chat_create_impersonal"));
            }
            case "chat_title_update": {
                const title = (act.text || "").trim();
                return tr("event_chat_title_update_" + gender, title) || tr("event_chat_title_update_impersonal");
            }
            case "chat_photo_update":
                return tr("event_chat_photo_update_" + gender) || tr("event_chat_photo_update_impersonal");
            case "chat_photo_remove":
                return tr("event_chat_photo_remove_" + gender) || tr("event_chat_photo_remove_impersonal");
            case "chat_pin_message":
                return tr("event_chat_pin_message_" + gender) || tr("event_chat_pin_message_impersonal");
            case "chat_unpin_message":
                return tr("event_chat_unpin_message_" + gender) || tr("event_chat_unpin_message_impersonal");
            case "chat_invite_user": {
                const mid = act.member_id ?? this.data.action_mid;
                if (sender && mid == sender.id) {
                    return tr("event_chat_invite_user_self_" + gender) || tr("event_chat_invite_user_impersonal");
                }
                const targetProf = window.im?.cached_profiles?._findCachedProfileByIdEvenIfNotCached ? window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(mid) : window.im?.cached_profiles?._findCachedProfileById(mid);
                const targetName = targetProf ? targetProf.getName() : `id${mid}`;
                return tr("event_chat_invite_user_" + gender, targetName) || tr("event_chat_invite_user_impersonal");
            }
            case "chat_invite_user_by_link":
                return tr("event_chat_invite_user_by_link_" + gender) || tr("event_chat_invite_user_impersonal");
            case "chat_kick_user": {
                const mid = act.member_id ?? this.data.action_mid;
                if (sender && mid == sender.id) {
                    return tr("event_chat_kick_user_self_" + gender) || tr("event_chat_kick_user_impersonal");
                }
                const targetProf = window.im?.cached_profiles?._findCachedProfileByIdEvenIfNotCached ? window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(mid) : window.im?.cached_profiles?._findCachedProfileById(mid);
                const targetName = targetProf ? targetProf.getName() : `id${mid}`;
                return tr("event_chat_kick_user_" + gender, targetName) || tr("event_chat_kick_user_impersonal");
            }
            case "rating_up":
                return tr("event_chat_user_up_your_rating_" + gender, sender?.getName(), act.member_id) || tr("event_chat_rating_up_impersonal");
            case "coins_transfer":
                return tr("event_chat_user_added_voices_" + gender, sender?.getName(), act.member_id) || tr("event_coins_transfer_impersonal");
            default:
                return tr("event_" + type + "_impersonal") || this.data.text || "";
        }
    }
    getText(raw = false, conversation = false, with_attachments = false) {
        if (this.data.action != null) {
            const actionText = this.getActionText();
            if (conversation) {
                return raw ? actionText : escapeHtml(actionText);
            }
            return raw ? actionText : encode_emojis(nl2br(escapeHtml(actionText)));
        }

        let txt = "";
        if (raw) {
            txt = this.data.text;
        } else {
            txt = escapeHtml(this.data.text);
        }

        if (conversation) {
            txt = "";
            if (with_attachments) {
                if (this.data.attachments && this.data.attachments.length > 0) {
                    const c = this.data.attachments[0];

                    switch (c.type) {
                        case "photo":
                            txt += `<img class="conv_prev_img" src="${c.photo.photo_75}">`;

                            if (this.data.text.length == 0) {
                                txt += get_attachment_text(this.data.attachments[0]);
                            }

                            break;
                        default:
                            txt += get_attachment_text(this.data.attachments[0]);
                            break;
                    }

                    txt += " ";
                }

                txt += ovk_proc_strtr(escapeHtml(this.data.text), 100);
            } else {
                if (this.data.attachments && this.data.attachments.length > 0) {
                    txt = get_attachment_text(this.data.attachments[0]);
                }

                txt += escapeHtml(this.data.text);

                return txt;
            }
        } else {
            if (this.isSpecial("gift")) {
                const msg = this.data.attachments[0].gift.message;
                if (msg == "") {
                    txt = ("(" + tr("message_no_text") + ")").toLowerCase();
                }

                txt = msg;
            }
        }

        if (raw) {
            return txt;
        }

        return encode_emojis(nl2br(txt));
    }

    get reply() { return this.data.reply_message; }
    get global_id() { return this.data.global_id || this.data.id; }
    get id() { return this.data.id; }
    get conversation_message_id() { return this.data.conversation_message_id || this.data.id; }
    isAction() { return this.data.action != null; }
    isReply() { return this.data.reply_message != null; }
    isError() { return this.data.error_text != null; }
    isEdited() { return this.data.edited == 1 || this.data.edited == true; }
    isSending() { return Boolean(this.data?.is_sending || (this.id == null && !this.isError())); }
    isPinned() {
        if (this.id == null) return false;
        if (this.data.is_pinned == undefined) {
            try {
                const conv = window.im.conversations._findConv(this.data.peer_id);
                const pinnedId = conv ? conv.getPinnedMessageId() : null;
                let f = pinnedId != null && pinnedId == this.id;
                this.data.is_pinned = Number(f);
            } catch (e) {
                this.data.is_pinned = 0;
            }
        }

        return this.data.is_pinned == 1;
    }

    isDeleted(mode = 1) {
        // 0 - deleted by me via action, will not disappear but will leave placeholder text
        const is_deleted_by_me = this.data.deleted_by_me == 1;
        const is_deleted = this.data.deleted == 1;

        switch (mode) {
            default:
            case 0:
                return is_deleted && !is_deleted_by_me;
            case 1:
                return is_deleted;
        }
    }

    isSpecial(like) {
        if (like == null) {
            return this.isAction();
        }
        if (like == "sticker") {
            return this.data.is_sticker == 1;
        }
        if (like == "gift") {
            try {
                let is = false;
                this.data.attachments.forEach(item => {
                    if (item.type == "gift") {
                        is = true;
                    }
                })

                return is;
            } catch (e) {
                return false;
            }
        }
    }

    can(action, group) {
        if (this.isDeleted()) {
            return false;
        }

        switch (action) {
            case "reply":
                return true;
            case "pin":
                const peer = this.peer;
                if (peer.supposed_type == "chat") {
                    return peer.can("pin");
                }

                return peer.can("write");
            case "delete":
                return true;
            case "delete_for_all":
                const currentUserId = window.openvk ? window.openvk.current_id : window.im?.state?.getId();
                const isMine = this.data.from_id == currentUserId;
                const isChatAdmin = this.peer && typeof this.peer.isAdmin === 'function' ? this.peer.isAdmin() : false;
                return isMine || isChatAdmin;
            case "forward":
                return true;
            case "edit":
                if (this.data.can_edit != null) {
                    return this.data.can_edit === 1;
                }

                if (group != null) {
                    return false;
                }

                if (this.isAction() == true || this.isSpecial("sticker") == true) {
                    return false;
                }

                // return this.data.can_edit;
                return this.data.from_id === window.im.state.getId();
            case "report":
                return !this.isMine();
            case "viewers":
                if (this.isAction()) return false;
                if (!this.isMine()) return false;
                if (this.peer && this.peer.supposed_type === "chat") return true;
                const pId = this.data?.peer_id || this.peer?.id || window.im?.messenger?.currentChatId;
                if (pId > 2000000000 || (pId && String(pId).startsWith("2000"))) return true;
                const curPeer = window.im?.messenger?.getCurrentChat ? window.im.messenger.getCurrentChat()?.peer : null;
                return Boolean(curPeer && curPeer.supposed_type === "chat");
        }
    }

    setDeleted(by_me = false) {
        if (this.data._orig_text === undefined && this.data.text !== tr('message_is_deleted')) {
            this.data._orig_text = this.data.text;
            this.data._orig_attachments = this.data.attachments;
        }
        this.data.deleted = 1;
        if (by_me) {
            this.data.deleted_by_me = 1;
        }
        this.data.text = tr('message_is_deleted');
        this.data.attachments = [];
    }

    restore(origText = null, origAttachments = null) {
        this.data.deleted = 0;
        this.data.deleted_by_me = 0;
        this.data.text = origText !== null ? origText : (this.data._orig_text !== undefined ? this.data._orig_text : "");
        this.data.attachments = origAttachments !== null ? origAttachments : (this.data._orig_attachments !== undefined ? this.data._orig_attachments : []);
    }

    setText(text) {
        this.data.text = text;
    }
    shouldBeNotified() {
        if (this.data.from_id === window.im.state.getId()) {
            return false;
        }

        return !this.peer.isMuted();
    }
    get peer_id() { return this.data.peer_id; }
    get from_id() { return this.data.from_id; }
    getAttachments() {
        const _at = this.data.attachments;
        if (!_at) return [];
        return _at;
    }
    getStringAttachments() {
        const _at = this.data.attachments;
        if (_at.length == 0) return '';
    }
    getDate(mode = 0) {
        const conv_day = this.getConvDay();
        switch (mode) {
            case 0:
                return this.getSentTime().toLocaleTimeString(navigator.language, {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                });
            case 1:
                return month_day_string(this.getSentTime());
            case 2:
                const date = this.getSentTime();
                let is_today = date.toDateString() == new Date().toDateString();

                const diffMs = Date.now() - date;
                const diffHours = diffMs / (1000 * 60 * 60);
                const isLessThan6Hours = diffHours >= 0 && diffHours < 6;

                if (isLessThan6Hours) {
                    return this.getDate(0);
                }

                return conv_day;
        }
    }

    getConvDay(always_with_year = false) {
        const date = this.getSentTime();
        if (always_with_year == false && date.getFullYear() == new Date().getFullYear()) {
            return date.toLocaleDateString(navigator.language);
        } else {
            return date.toLocaleDateString(navigator.language, {
                month: '2-digit',
                day: '2-digit'
            })
        }
    }

    static async fromEvent(event, im = null) {
        const [, id, flags, peer, ts, subject, text, attachments, randomId] = event;
        let new_attachments = null;
        let reply_message = null;

        if (attachments['attach1']) {
            const temp_str = get_attachments_list_from_lp(attachments);
            new_attachments = await resolve_attachments(temp_str);
        }

        if (attachments['reply_to']) {
            const peer_obj = await window.im.conversations._findConvFromApi(peer);
            const reply_id = attachments['reply_to'];

            const __msg = await peer_obj.peer._chunks.findMessageByIdFromApi(reply_id);
            if (__msg != null) {
                reply_message = __msg;
            } else {
                reply_message = new ChatMessage({
                    'id': reply_id,
                    'text': '...'
                });
            }
        }

        let fwd_messages = null;
        if (attachments && (attachments['fwd'] || attachments['fwd_messages'])) {
            const fwdRaw = attachments['fwd'] || attachments['fwd_messages'];
            try {
                const res = await window.OVKAPI.call("messages.getById", {
                    message_ids: fwdRaw
                });
                if (res && res.items && res.items.length > 0) {
                    fwd_messages = res.items.map(item => new ChatMessage(item));
                }
            } catch (e) {
                console.error("Failed to load fwd messages for event:", e);
            }
        }

        let action = null;
        if (attachments && (attachments['source_act'] || attachments['act'])) {
            const actType = attachments['source_act'] || attachments['act'];
            const actMid = (attachments['source_mid'] || attachments['mid']) ? Number(attachments['source_mid'] || attachments['mid']) : null;
            const actText = attachments['source_text'] || attachments['source_old_text'] || attachments['text'] || "";
            action = {
                type: actType,
                member_id: actMid,
                text: actText
            };
            if (actMid && window.im?.cached_profiles?._findCachedProfileByIdEvenIfNotCached) {
                window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(actMid);
            }
        }

        const cmidFromLp = attachments && (attachments['conversation_message_id'] || attachments['cmid']) ? Number(attachments['conversation_message_id'] || attachments['cmid']) : 0;
        const msg = new ChatMessage({
            'id': id,
            'local_id': cmidFromLp,
            'conversation_message_id': cmidFromLp,
            'flags': flags,
            'from_id': attachments.from ? Number(attachments.from) : peer,
            'date': ts,
            'peer': peer,
            'peer_id': peer,
            'text': text,
            'attachments': new_attachments,
            'random_id': randomId,
            'reply_message': reply_message,
            'fwd_messages': fwd_messages,
            'action': action,
            'action_type': action ? action.type : null,
            'action_mid': action ? action.member_id : null,
            'action_text': action ? action.text : null,
        });
        msg._guessSender();

        // temp fix
        if (im && msg.peer_id == im.state.getOperator().id) {
            console.error("IM | WRONG PEER FROM EVENT!!!!!! USING ATTACHMENTS.FROM")
            msg.data.peer_id = Number(attachments.from);
        }

        return msg;
    }
    async setAttachmentsFromLP(data) {
        let new_attachments = null;
        if (data['attach1']) {
            const temp_str = get_attachments_list_from_lp(data);
            new_attachments = await resolve_attachments(temp_str);
        }

        this.data.attachments = new_attachments;

        if (data && (data['fwd'] || data['fwd_messages']) && (!this.data.fwd_messages || this.data.fwd_messages.length === 0)) {
            const fwdRaw = data['fwd'] || data['fwd_messages'];
            try {
                const res = await window.OVKAPI.call("messages.getById", {
                    message_ids: fwdRaw
                });
                if (res && res.items && res.items.length > 0) {
                    this.data.fwd_messages = res.items.map(item => new ChatMessage(item));
                }
            } catch (e) {
                console.error("Failed to load fwd messages for setAttachmentsFromLP:", e);
            }
        }

        if (data && (data['source_act'] || data['act'])) {
            const actType = data['source_act'] || data['act'];
            const actMid = (data['source_mid'] || data['mid']) ? Number(data['source_mid'] || data['mid']) : null;
            const actText = data['source_text'] || data['source_old_text'] || data['text'] || "";
            this.data.action = {
                type: actType,
                member_id: actMid,
                text: actText
            };
            this.data.action_type = actType;
            this.data.action_mid = actMid;
            this.data.action_text = actText;
            if (actMid && window.im?.cached_profiles?._findCachedProfileByIdEvenIfNotCached) {
                window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(actMid);
            }
        }
    }

    // if message has the exclamation mark
    async tryToResend() {
        let r = String(this.data.error_text);
        this.data.error_text = null;
        window.im.messenger.update();

        try {
            const resp = await window.OVKAPI.call('messages.send', this.data.resend_params);
            this.data.id = resp;
            console.info('IM | Resent message to ' + this.id);
            this.data.error_text = null;
            this.data.resend_params = null;
        } catch (e) {
            this.data.error_text = r;
            console.error('IM | STILL can not send message to ' + this.id, ': ', e);
        }

        window.im.messenger.update();
    }

    async edit(text, attachments = []) {
        let resp = null;
        try {
            const params = {
                "peer_id": this.peer_id,
                "message_id": this.id,
                "message": text,
                "keep_forward_messages": 1,
                "attachment": attachments.join(",")
            };
            const g = window.im.state.getId();
            if (g < 0) {
                params["group_id"] = Math.abs(g);
            }

            resp = await window.OVKAPI.call("messages.edit", params);
        } catch (e) {
            fastError(String(e));
            console.error(e);
            return;
        }

        this.data.text = text;
        this.data.edited = true;

        window.im.messenger.update();

        console.log("successfuly edited ", this, resp)
    }

    async togglePin(action) {
        let method = "pin";
        if (action == false) {
            method = "unpin";
        }

        const g = window.im.state.getId();
        try {
            const params = {
                "peer_id": this.peer_id,
                "message_id": this.id,
            };
            if (g < 0) {
                params["group_id"] = Math.abs(g);
            }
            let resp = await window.OVKAPI.call("messages." + method, params);
        } catch (e) {
            fastError(String(e));
            console.error(e);
            return;
        }

        this.data.is_pinned = Boolean(action);

        if (action) {
            window.im.messenger.getCurrentChat()._conversation.current_pinned_message = {
                "id": this.id,
            };
        }
    }

    isRead() {
        try {
            if (this.data && (this.data.read_state === 1 || this.data.read_state === true)) return true;
            const peerId = this.data ? (this.data.peer_id || this.peer_id) : (this.peer_id || 0);
            const conv = peerId ? window.im?.conversations?._findConv(peerId) : null;
            const peer = conv?.peer || this.peer;
            const outRead = peer?.out_read || conv?._conversation?.out_read || conv?.conversation?.out_read || 0;
            const inRead = peer?.in_read || conv?._conversation?.in_read || conv?.conversation?.in_read || 0;
            const currentUserId = window.openvk ? window.openvk.current_id : window.im?.state?.getId();
            const msgCmid = (this.data && (this.data.conversation_message_id || this.data.local_id)) || this.conversation_message_id || 0;
            const msgId = (this.data && this.data.id) || this.id || 0;
            const fromId = this.data ? this.data.from_id : (this.from_id || 0);

            if (fromId != currentUserId && inRead > 0 && ((msgCmid > 0 && msgCmid <= inRead) || (msgId > 0 && msgId <= inRead))) {
                return true;
            }
            if (fromId == currentUserId) {
                if (outRead > 0 && ((msgCmid > 0 && msgCmid <= outRead) || (msgId > 0 && msgId <= outRead))) {
                    return true;
                }
                if (peer && peer._chunks) {
                    const latest = peer._chunks.getLatestMessage();
                    if (latest && latest.data && latest.data.from_id != currentUserId && (latest.id > msgId || latest.getSentTime() > this.getSentTime())) {
                        return true;
                    }
                }
            }
            return false;
        } catch (e) {
            return false;
        }
    }
}
