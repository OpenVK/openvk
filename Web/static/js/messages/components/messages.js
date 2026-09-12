import { MessageChunk, Chunks, DayChunk } from "./partition.js";
import { getAppLocale, getTimeFormatOptions, formatTime, formatDate } from "./common.js";
import { imLog } from "../logger.js";

export class Draft {
    constructor() {
        this.text = null;
        this.attachments_html = [];
        this.scroll = null;
        this.editMsg = null;
        this.forwarded_msg = null;
        this.anchorMsgId = null;
        this.anchorRelTop = null;
        this.isAtEnd = false;
    }

    static fromPage(page) {
        const d = new Draft();
        if (!page) return d;

        d.text = typeof page.getCurrentText === 'function' ? page.getCurrentText() : "";
        d.attachments_html = typeof page.getCurrentAttachments === 'function' ? page.getCurrentAttachments() : [];
        d.scroll = typeof page.getScroll === 'function' ? page.getScroll() : 0;
        d.isAtEnd = typeof page.isAtEnd === 'function' ? page.isAtEnd(100) : false;

        const anchorMsg = typeof page.getFirstVisibleMessageElement === 'function'
            ? page.getFirstVisibleMessageElement()
            : null;
        if (anchorMsg) {
            d.anchorMsgId = anchorMsg.getAttribute('data-msg-id') || anchorMsg.dataset?.msgId || null;
            const container = typeof page.getMessagesContainer === 'function' ? page.getMessagesContainer() : null;
            if (container) {
                const cRect = container.getBoundingClientRect();
                const mRect = anchorMsg.getBoundingClientRect();
                d.anchorRelTop = mRect.top - cRect.top;
            }
        }

        d.editMsg = window.im?.messenger?.editMsg || null;
        d.forwarded_msg = window.im?.messenger?.forwarded_msg || null;

        return d;
    }

    loadToPage(page) {
        if (!page) return;
        imLog("Draft | applying", this, "to", page);
        if (this.text != null) {
            if (window.im?.messenger) {
                window.im.messenger.currentDraft = this.text;
            }
            if (typeof page.setCurrentText === 'function') {
                page.setCurrentText(this.text);
            } else if (page.container) {
                const ce = page.container.querySelector(".small-textarea.content-editable");
                if (ce && ce._contentEditable && typeof ce._contentEditable.setText === 'function') {
                    ce._contentEditable.setText(this.text);
                } else {
                    const ta = page.container.querySelector(".messenger-app--input---messagebox textarea, .small-textarea");
                    if (ta) ta.value = this.text;
                }
            }
        }
        if (page.container) {
            if (this.attachments_html && this.attachments_html[0] != null) {
                const h = page.container.querySelector(".post-horizontal");
                if (h) h.innerHTML = this.attachments_html[0];
            }
            if (this.attachments_html && this.attachments_html[1] != null) {
                const v = page.container.querySelector(".post-vertical");
                if (v) v.innerHTML = this.attachments_html[1];
            }
        }
        if (window.im?.messenger) {
            if (this.editMsg) {
                imLog("Draft | editMsg:", this.editMsg);
                window.im.messenger.editMsg = this.editMsg;
            } else {
                window.im.messenger.editMsg = null;
            }
            if (this.forwarded_msg) {
                window.im.messenger.setForwarded(this.forwarded_msg);
            }
        }

        this.loadScroll(page);
    }

    loadScroll(page) {
        if (!page) return;
        if (this.isAtEnd) {
            page._scrollToEnd();
            return;
        }

        const container = typeof page.getMessagesContainer === 'function' ? page.getMessagesContainer() : null;
        if (this.anchorMsgId && container) {
            const anchorEl = container.querySelector(`.messenger-app--messages---message[data-msg-id="${this.anchorMsgId}"]`);
            if (anchorEl) {
                const cRect = container.getBoundingClientRect();
                const mRect = anchorEl.getBoundingClientRect();
                const currentRelTop = mRect.top - cRect.top;
                const targetRelTop = this.anchorRelTop != null ? this.anchorRelTop : 20;
                container.scrollTop += (currentRelTop - targetRelTop);
                return;
            }
        }

        if (this.scroll != null) {
            page._scrollTo(this.scroll);
        } else {
            imLog("loadScroll default to end:", this);
            page._scrollToEnd();
        }
    }
}

class ChatMembers {
    constructor(link) {
        this.link = link;
        this.items = [];
        this.total_count = 0;
        this.peer_id = link ? link.id : null;
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
            if (v.chat_settings && this.link) {
                this.link.data = this.link.data || {};
                this.link.data.chat_settings = {
                    ...(this.link.data.chat_settings || {}),
                    ...v.chat_settings
                };
                if (v.chat_settings.acl) {
                    this.link.data.acl = v.chat_settings.acl;
                }
                if (v.chat_settings.permissions) {
                    this.link.data.permissions = v.chat_settings.permissions;
                }
                if (v.chat_settings.owner_id) {
                    this.link.data.owner_id = v.chat_settings.owner_id;
                }
                if (v.chat_settings.admin_ids) {
                    this.link.data.admin_ids = v.chat_settings.admin_ids;
                }
            }
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
            this.failed = true;
            this.items = [];
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
    static BASE_FIELDS = 'photo_50,photo_100,photo_200,photo_max,last_seen,online,photo_id,status,sex,can_write_private_message,can_invite,followers_count,is_messages_blocked,screen_name,domain';
    static SAVED_MESSAGES_AVATAR = "/assets/packages/static/openvk/img/im/saved_messages.png";
    static CHAT_NO_AVATAR = "/assets/packages/static/openvk/img/im/chat_meaningless.jpg";

    constructor(item) {
        this.data = item || {};
        this._chunks = new Chunks(this);
        this.pinned_message_chunks = [];

        this._messages_inited = false;
        this.members = null;
        if (this.data.online !== undefined) {
            this._online = this.data.online;
        }
    }

    get online() {
        if (this._online !== undefined) return this._online;
        return this.data ? this.data.online : undefined;
    }

    set online(val) {
        this._online = val;
        if (this.data) {
            this.data.online = val;
        }
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

    getAcl() {
        if (this.data.acl && typeof this.data.acl === 'object') return this.data.acl;
        if (this.data.chat_settings?.acl && typeof this.data.chat_settings.acl === 'object') return this.data.chat_settings.acl;
        if (this.data._full_conversation?.chat_settings?.acl && typeof this.data._full_conversation.chat_settings.acl === 'object') return this.data._full_conversation.chat_settings.acl;
        return null;
    }

    getPermissions() {
        if (this.data.permissions && typeof this.data.permissions === 'object') return this.data.permissions;
        if (this.data.chat_settings?.permissions && typeof this.data.chat_settings.permissions === 'object') return this.data.chat_settings.permissions;
        if (this.data._full_conversation?.chat_settings?.permissions && typeof this.data._full_conversation.chat_settings.permissions === 'object') return this.data._full_conversation.chat_settings.permissions;
        return null;
    }

    isOwner() {
        if (this.supposed_type !== "chat") return false;
        const currentUserId = window.openvk ? window.openvk.current_id : window.im?.state?.getId();
        const ownerId = this.data.owner_id || this.data.chat_settings?.owner_id || this.data._full_conversation?.chat_settings?.owner_id || this.data.admin_id || this.data.chat_settings?.admin_id;
        return Number(ownerId) === Number(currentUserId);
    }

    isAdmin() {
        if (this.supposed_type !== "chat") {
            return false;
        }
        const currentUserId = window.openvk ? window.openvk.current_id : window.im?.state?.getId();
        if (this.isOwner()) return true;
        const acl = this.getAcl();
        if (acl && acl.can_moderate) return true;
        if (this.data.admin_id === currentUserId) return true;
        if (this.data.chat_settings) {
            if (this.data.chat_settings.admin_id === currentUserId) return true;
            if (this.data.chat_settings.is_admin) return true;
            if (Array.isArray(this.data.chat_settings.admin_ids) && this.data.chat_settings.admin_ids.map(Number).includes(Number(currentUserId))) return true;
        }
        if (Array.isArray(this.data.admin_ids) && this.data.admin_ids.map(Number).includes(Number(currentUserId))) return true;
        return false;
    }

    can(thing, relatively_current_group = null) { // unified function
        const acl = this.getAcl();
        switch (thing) {
            case "write": {
                if (this.data.deactivated) return false;
                if (this.supposed_type === 'club' && typeof this.isClubMessagesBlocked === 'function' && this.isClubMessagesBlocked()) {
                    return false;
                }
                if (this.data.can_message === false) {
                    return false;
                }
                if (this.supposed_type === 'chat' && this.isILeft()) {
                    return false;
                }
                if (this.data.can_write !== undefined && this.data.can_write !== null) {
                    if (typeof this.data.can_write === 'object') {
                        return !!this.data.can_write.allowed;
                    } else if (typeof this.data.can_write === 'boolean') {
                        return this.data.can_write;
                    } else if (typeof this.data.can_write === 'number') {
                        return this.data.can_write === 1;
                    }
                }
                if (this.data.can_write_private_message !== undefined && this.data.can_write_private_message !== null) {
                    return Number(this.data.can_write_private_message) === 1;
                }
                return true;
            }
            case "update_title":
            case "update_avatar":
            case "change_info":
                if (this.supposed_type !== "chat" || this.isILeft()) return false;
                if (acl && acl.can_change_info !== undefined) return !!acl.can_change_info;
                return this.isAdmin();
            case "invite_new":
            case "invite":
                if (this.supposed_type !== "chat" || this.isILeft()) return false;
                if (acl && acl.can_invite !== undefined) return !!acl.can_invite;
                return (this.data.can_invite ?? 1) === 1;
            case "leave_chat":
                return this.supposed_type === "chat" && !this.isKicked() && !this.isILeft();
            case "return_to_chat":
                return this.supposed_type === "chat" && this.isILeft() && !this.isKicked();
            case "view_invite_links":
            case "see_invite_link":
                if (this.supposed_type !== "chat" || this.isILeft()) return false;
                if (acl && acl.can_see_invite_link !== undefined) return !!acl.can_see_invite_link;
                return this.isAdmin();
            case "change_invite_link":
            case "regenerate_link":
                if (this.supposed_type !== "chat" || this.isILeft()) return false;
                if (acl && acl.can_change_invite_link !== undefined) return !!acl.can_change_invite_link;
                return this.isOwner();
            case "pin":
            case "unpin":
            case "change_pin":
                if (this.supposed_type !== "chat" || this.isILeft()) return false;
                if (acl && acl.can_change_pin !== undefined) return !!acl.can_change_pin;
                return this.isAdmin();
            case "change_admins":
            case "promote_users":
                if (this.supposed_type !== "chat" || this.isILeft()) return false;
                if (acl && acl.can_promote_users !== undefined) return !!acl.can_promote_users;
                return this.isOwner();
            case "mass_mentions":
            case "use_mass_mentions":
                if (this.supposed_type !== "chat" || this.isILeft()) return false;
                if (acl && acl.can_use_mass_mentions !== undefined) return !!acl.can_use_mass_mentions;
                return true;
            case "call":
                if (this.supposed_type !== "chat" || this.isILeft()) return false;
                if (acl && acl.can_call !== undefined) return !!acl.can_call;
                return true;
            case "moderate":
                if (this.supposed_type !== "chat" || this.isILeft()) return false;
                if (acl && acl.can_moderate !== undefined) return !!acl.can_moderate;
                return this.isAdmin();
        }

        return true;
    }

    isILeft() {
        if (this.supposed_type !== 'chat') return false;
        if (this.data.left === 1 || this.data.left === true || this.data.kicked === 1 || this.data.kicked === true) return true;
        if (this.data.chat_settings?.state === 'left' || this.data.chat_settings?.state === 'kicked') return true;
        if (this.data.state === 'left' || this.data.state === 'kicked') return true;
        if (this.data._full_conversation?.chat_settings?.state === 'left' || this.data._full_conversation?.chat_settings?.state === 'kicked') return true;
        if (this.data.can_write && typeof this.data.can_write === 'object' && this.data.can_write.allowed === false) {
            if ([915, 916, 917].includes(Number(this.data.can_write.reason))) return true;
        }
        if (this.data._full_conversation?.can_write && typeof this.data._full_conversation.can_write === 'object' && this.data._full_conversation.can_write.allowed === false) {
            if ([915, 916, 917].includes(Number(this.data._full_conversation.can_write.reason))) return true;
        }
        return false;
    }

    isKicked() {
        if (this.supposed_type !== 'chat') return false;
        if (this.data.kicked === 1 || this.data.kicked === true) return true;
        if (this.data.chat_settings?.state === 'kicked') return true;
        if (this.data.state === 'kicked') return true;
        if (this.data._full_conversation?.chat_settings?.state === 'kicked') return true;
        if (this.data.can_write && typeof this.data.can_write === 'object' && Number(this.data.can_write.reason) === 915) return true;
        if (this.data._full_conversation?.can_write && typeof this.data._full_conversation.can_write === 'object' && Number(this.data._full_conversation.can_write.reason) === 915) return true;
        return false;
    }

    getCantWriteInfo() {
        if (this.can("write")) {
            return { allowed: true, text: "" };
        }

        let reason = 0;
        if (this.data.can_write && typeof this.data.can_write === 'object' && this.data.can_write.reason) {
            reason = Number(this.data.can_write.reason);
        }

        if (this.supposed_type === 'chat') {
            if (reason === 915 || this.isKicked()) {
                return { allowed: false, reason: 915, text: tr("cannot_write_chat_kicked") };
            }
            if (reason === 916 || this.isILeft()) {
                return { allowed: false, reason: 916, text: tr("cannot_write_chat_left") };
            }
            if (reason === 917) {
                return { allowed: false, reason: 917, text: tr("cannot_write_chat_readonly") };
            }
            return { allowed: false, reason: reason || 917, text: tr("cannot_write_chat_readonly") };
        }

        if (this.supposed_type === 'club') {
            if (reason === 18) {
                return { allowed: false, reason: 18, text: tr("cannot_write_user_deleted") };
            }
            return { allowed: false, reason: 902, text: tr("cannot_write_group_disabled") };
        }

        if (reason === 18 || this.data.deactivated) {
            if (this.data.deactivated === 'banned') {
                return { allowed: false, reason: 18, text: tr("cannot_write_user_banned") };
            }
            return { allowed: false, reason: 18, text: tr("cannot_write_user_deactivated") };
        }

        if (reason === 900) {
            return { allowed: false, reason: 900, text: tr("cannot_write_blacklist") };
        }

        if (reason === 901) {
            return { allowed: false, reason: 901, text: tr("messages_blocked") };
        }

        if (this.data.can_write_private_message === 0) {
            return { allowed: false, reason: 901, text: tr("messages_blocked") };
        }

        return { allowed: false, reason: reason || 901, text: tr("cannot_write_default") };
    }

    get has_custom_avatar() {
        if (this.supposed_type !== 'chat') return true;
        if (this.isILeft()) return false;
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
        if (this.supposed_type === 'chat' && this.isILeft()) {
            return [];
        }
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

        if (this.supposed_type === 'chat' && this.isILeft()) {
            return ChatGeneralForm.CHAT_NO_AVATAR;
        }

        let ava = null;
        switch (size) {
            case "min":
                ava = this.data.photo_50 || this.data.photo_100;
                break;
            case "mid":
                ava = this.data.photo_100 || this.data.photo_50;
                break;
            case "big":
                ava = this.data.photo_200 || this.data.photo_100;
                break;
            case "max":
                ava = this.data.photo_max || this.data.photo_200;
                break;
        }

        if (!ava && this.supposed_type == "chat") {
            return ChatGeneralForm.CHAT_NO_AVATAR;
        }

        return ava ?? '/assets/packages/static/openvk/img/camera_50.png';
    }
    hasAvatar() {
        if (this.supposed_type === 'chat' && this.isILeft()) {
            return false;
        }
        return this.data.photo_200 != null && this.data.photo_200 !== "" && !this.data.photo_200.includes("/assets/packages/static/openvk/img/");
    }
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
        if (this.supposed_type === 'club') {
            return tr("followers", this.data.followers_count || 0);
        }

        if (this.supposed_type === 'chat') {
            return tr("members_count", this.data.members_count || (this.members?.length || 0));
        }

        if (this.isOnline()) {
            return tr("online");
        }

        let lastSeen = this.data?.last_seen;
        if (!lastSeen?.time && window.im?.cached_profiles) {
            const cached = window.im.cached_profiles._findCachedProfileById(this.id);
            if (cached?.data?.last_seen) lastSeen = cached.data.last_seen;
            else if (cached?.last_seen) lastSeen = cached.last_seen;
        }

        if (!lastSeen || !lastSeen.time) {
            return tr("im_was_online_unkown_" + this.getGender()).toLowerCase();
        }

        const time = lastSeen.time;
        const date = new Date(time * 1000);
        const today = new Date();
        const sameMonth = date.getMonth() === today.getMonth();
        const timeStr = formatTime(date, false);
        const dayStr = formatDate(date, {
            month: '2-digit',
            day: '2-digit'
        });

        if (sameMonth && date.getDate() === today.getDate()) {
            return tr("im_was_online_today_" + this.getGender(), timeStr).toLowerCase();
        }

        if (sameMonth && date.getDate() === today.getDate() - 1) {
            return tr("im_was_online_yesterday_" + this.getGender(), timeStr).toLowerCase();
        }

        return tr("im_was_online_other_" + this.getGender(), timeStr, dayStr).toLowerCase();
    }

    isOnline() {
        if (this.supposed_type !== "user") return false;
        if (this.online === 1 || this.data?.online === 1) return true;
        if (this.online === 0 || this.data?.online === 0) return false;
        const lastSeenTime = this.data?.last_seen?.time;
        if (lastSeenTime) {
            const now = Math.floor(Date.now() / 1000);
            if (now - lastSeenTime <= 300) return true;
        }
        return false;
    }

    getOfflineBarString() {
        if (this.supposed_type !== "user" || this.isSavedMessages()) {
            return null;
        }

        if (this.isOnline()) {
            return null;
        }

        const name = this.data?.first_name || this.getName(false, true) || this.getName() || "";
        const gender = this.getGender();

        let lastSeen = this.data?.last_seen;
        if (!lastSeen?.time && window.im?.cached_profiles) {
            const cached = window.im.cached_profiles._findCachedProfileById(this.id);
            if (cached?.data?.last_seen) lastSeen = cached.data.last_seen;
            else if (cached?.last_seen) lastSeen = cached.last_seen;
        }

        if (!lastSeen || !lastSeen.time) {
            const res = tr("im_write_bar_offline_unknown_" + gender, name);
            return res.startsWith("@") ? `${name} был в сети недавно` : res;
        }

        const time = lastSeen.time;
        const date = new Date(time * 1000);
        const today = new Date();
        const sameMonth = date.getMonth() === today.getMonth();
        const sameYear = date.getFullYear() === today.getFullYear();
        const timeStr = formatTime(date, false);
        const dayStr = formatDate(date, {
            month: '2-digit',
            day: '2-digit'
        });

        if (sameYear && sameMonth && date.getDate() === today.getDate()) {
            const res = tr("im_write_bar_offline_today_" + gender, name, timeStr);
            return res.startsWith("@") ? `${name} был в сети сегодня в ${timeStr}` : res;
        }

        if (sameYear && sameMonth && date.getDate() === today.getDate() - 1) {
            const res = tr("im_write_bar_offline_yesterday_" + gender, name, timeStr);
            return res.startsWith("@") ? `${name} был в сети вчера в ${timeStr}` : res;
        }

        const res = tr("im_write_bar_offline_other_" + gender, name, timeStr, dayStr);
        return res.startsWith("@") ? `${name} был в сети ${dayStr} в ${timeStr}` : res;
    }

    isMuted() {
        const isMuteAll = (localStorage.getItem("tw.im.mute_all") || "0") === "1";
        if (isMuteAll) return true;

        const pushSettings = this.data?.push_settings || this.data?._full_conversation?.push_settings || this.data?.chat_settings?.push_settings;
        if (pushSettings) {
            if (pushSettings.disabled_until === -1 || pushSettings.disabled_forever) {
                return true;
            }
            if (pushSettings.disabled_until > 0) {
                return pushSettings.disabled_until > Math.floor(Date.now() / 1000);
            }
            if (pushSettings.no_sound === true || pushSettings.sound === 0 || pushSettings.sound === false) {
                return true;
            }
        }
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
            if (conv.can_write) peerData.can_write = conv.can_write;
            if (conv.push_settings) peerData.push_settings = conv.push_settings;
            if (conv.pinned_message) peerData.pinned_message = conv.pinned_message;
            if (chatSettings.pinned_message) peerData.pinned_message = chatSettings.pinned_message;
            if (chatSettings.state === 'kicked' || chatData.kicked === 1 || conv.can_write?.reason === 915) {
                peerData.kicked = 1;
                peerData.left = 0;
            } else if (chatSettings.state === 'left' || chatData.left === 1 || conv.can_write?.reason === 916) {
                peerData.left = 1;
                peerData.kicked = 0;
            }
            peerData._full_conversation = conv;
            return peerData;
        } else {
            if (id > 0) {
                const __ = await window.OVKAPI.call('users.get', { 'user_ids': id, 'fields': ChatGeneralForm.BASE_FIELDS });
                if (!__ || !__[0] || (__[0].first_name == "DELETED" && __[0].deactivated == "deleted")) {
                    return null;
                }
                const peerData = __[0];
                try {
                    const convRes = await window.OVKAPI.call('messages.getConversationsById', { 'peer_ids': id });
                    if (convRes && convRes.items && convRes.items.length > 0) {
                        const conv = convRes.items[0].conversation || {};
                        if (conv.push_settings) peerData.push_settings = conv.push_settings;
                        if (conv.can_write) peerData.can_write = conv.can_write;
                        peerData._full_conversation = conv;
                    }
                } catch (e) {
                    console.error("resolveById user getConversationsById error:", e);
                }
                return peerData;
            } else {
                const __ = await window.OVKAPI.call('groups.getById', { 'group_ids': Math.abs(id), 'fields': ChatGeneralForm.BASE_FIELDS });
                if (!__ || !__[0] || __[0].type == 'undefined') {
                    return null;
                }
                const peerData = __[0];
                try {
                    const convRes = await window.OVKAPI.call('messages.getConversationsById', { 'peer_ids': id });
                    if (convRes && convRes.items && convRes.items.length > 0) {
                        const conv = convRes.items[0].conversation || {};
                        if (conv.push_settings) peerData.push_settings = conv.push_settings;
                        if (conv.can_write) peerData.can_write = conv.can_write;
                        peerData._full_conversation = conv;
                    }
                } catch (e) {
                    console.error("resolveById group getConversationsById error:", e);
                }
                return peerData;
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
        const rawText = (msg && typeof msg.getText === 'function') ? (msg.getText(true) || '') : '';
        const cleanText = rawText.replace(/[\s\u200b\ufeff\u00a0]/g, '');
        const hasAttachments = attachments != null && attachments.length > 0;
        const hasForward = forward_msgs != null && forward_msgs.length > 0;
        const hasReply = reply_to != null;

        if (!cleanText && !hasAttachments && !hasForward && !hasReply) {
            return;
        }

        const trimmedText = cleanText ? rawText.replace(/^[\s\u200b\ufeff\u00a0\u200c\u200d]+|[\s\u200b\ufeff\u00a0\u200c\u200d]+$/g, '') : '';
        if (msg && typeof msg.setText === 'function') {
            msg.setText(trimmedText);
        }

        const isSaved = typeof this.isSavedMessages === 'function' ? this.isSavedMessages() : false;
        msg.data = msg.data || {};
        msg.data.out = 1;
        msg.out = 1;
        msg.data.read_state = isSaved ? 1 : 0;
        msg.read_state = msg.data.read_state;
        msg.peer = this;
        msg.peer_id = this.id;

        this._chunks.pushNewMessage(msg);
        if (push_callback) {
            push_callback();
        }

        const conv = window.im?.conversations ? window.im.conversations._findConv(this.id) : null;
        if (conv) {
            conv.last_message = msg;
            conv._last_message = msg;
            if (window.im?.conversations?.all_convs) {
                const all = window.im.conversations.all_convs;
                const idx = all.indexOf(conv);
                if (idx > 0) {
                    all.splice(idx, 1);
                    all.unshift(conv);
                }
            }
        }
        if (window.im?.conversations) {
            window.im.conversations.update();
        }

        const datas = {
            'peer_id': this.id,
            'message': trimmedText,
            //'attachment': msg.getStringAttachments(), не помню что это
        };

        if (window.im.usage_type == "group") {
            datas["group_id"] = Math.abs(window.im.state.getOperator().id);
        }

        if (reply_to != null) {
            datas['reply_to'] = reply_to.id;
        }

        if (msg.data && msg.data.random_id) {
            datas['random_id'] = msg.data.random_id;
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
            imLog.info('IM | Maybe message send interrupted, so does not sending. ', this.id, msg);
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
            msg.id = msg.data.id;
            msg.conversation_message_id = msg.data.conversation_message_id;
            msg.data.is_sending = false;
            msg.data.out = 1;
            msg.out = 1;
            msg.data.read_state = isSaved ? 1 : 0;
            msg.read_state = msg.data.read_state;

            if (conv) {
                conv.last_message = msg;
                conv._last_message = msg;
            }

            imLog('Sent message to ' + this.id, resp);
            if (this._chunks) {
                this._chunks._invalidateCache();
            }
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
            let d = String(e?.message || e?.error_msg || e);
            if (d.startsWith("Error: Broker failure")) {
                d = d.replace("Error: Broker failure: ", "");
            }

            let errCode = Number(e?.error_code || e?.error?.error_code || 0);
            if (!errCode) {
                if (d.includes("900") || d.toLowerCase().includes("blacklist")) errCode = 900;
                else if (d.includes("901") || d.toLowerCase().includes("privacy")) errCode = 901;
                else if (d.includes("902")) errCode = 902;
                else if (d.includes("18") || d.toLowerCase().includes("deleted") || d.toLowerCase().includes("banned")) errCode = 18;
                else if (d.includes("915") || d.toLowerCase().includes("kicked")) errCode = 915;
                else if (d.includes("916") || d.toLowerCase().includes("left")) errCode = 916;
                else if (d.includes("917")) errCode = 917;
            }

            if ([18, 900, 901, 902, 915, 916, 917].includes(errCode)) {
                this.data.can_write = { allowed: false, reason: errCode };
                if (conv && conv._conversation) {
                    conv._conversation.can_write = { allowed: false, reason: errCode };
                }
                if (window.im?.fastChats) {
                    const fc = window.im.fastChats.openedChats?.find(c => Number(c.peerId) === Number(this.id));
                    if (fc) {
                        fc.canWrite = false;
                        fc.cantWriteReason = errCode;
                        fc.cantWriteText = window.im.fastChats.getCantWriteText(fc);
                        window.im.fastChats.render();
                    }
                }
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
            if (window.im?.conversations) {
                window.im.conversations.update();
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
            throw e;
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

        if (window.im?.getTab("contact")?.render_class) {
            window.im.getTab("contact").render_class.update();
        }
        if (window.im?.messenger) {
            window.im.messenger.update();
        }
    }

    async checkMembers(offset = 0) {
        if (this.supposed_type != "chat" || this.isILeft()) {
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
            let newlyReadCount = 0;
            if (this._chunks) {
                const latestMsg = this._chunks.getLatestMessage();
                const latestMsgId = latestMsg ? ((latestMsg.data && (latestMsg.data.local_id || latestMsg.data.id)) || latestMsg.id || 0) : 0;
                const targetReadId = startMessageId > 0 ? Number(startMessageId) : Number(latestMsgId);

                if (targetReadId > 0) {
                    this.in_read = Math.max(this.in_read || 0, targetReadId);
                }

                const currentUserId = window.openvk ? window.openvk.current_id : window.im?.state?.getId();
                this._chunks.getMessages().forEach(m => {
                    const mId = Number(m.id || m.conversation_message_id || 0);
                    if (m.data && m.data.from_id != currentUserId) {
                        if (!targetReadId || (mId > 0 && mId <= targetReadId)) {
                            if (m.data.read_state === 0) {
                                m.data.read_state = 1;
                                newlyReadCount++;
                            }
                        }
                    }
                });
                this._chunks._invalidateCache();
            }
            if (window.im?.conversations) {
                const conv = window.im.conversations._findConv(this.id);
                if (conv) {
                    let remainingUnreadInChunks = 0;
                    if (this._chunks) {
                        const currentUserId = window.openvk ? window.openvk.current_id : window.im?.state?.getId();
                        this._chunks.getMessages().forEach(m => {
                            if (m.data && m.data.from_id != currentUserId && m.data.read_state === 0) {
                                remainingUnreadInChunks++;
                            }
                        });
                    }

                    if (startMessageId > 0 && remainingUnreadInChunks > 0) {
                        conv.unread_count = remainingUnreadInChunks;
                        if (conv._conversation) conv._conversation.unread_count = remainingUnreadInChunks;
                    } else if (startMessageId > 0 && conv.unread_count > newlyReadCount) {
                        conv.unread_count = Math.max(0, conv.unread_count - newlyReadCount);
                        if (conv._conversation) conv._conversation.unread_count = conv.unread_count;
                    } else {
                        conv.unread_count = 0;
                        if (conv._conversation) conv._conversation.unread_count = 0;
                        this._firstUnreadMsgId = null;
                    }

                    if (conv.unread_count === 0) {
                        this._firstUnreadMsgId = null;
                        if (conv._last_message && !conv._last_message.isMine()) {
                            if (conv._last_message.data) conv._last_message.data.read_state = 1;
                            conv._last_message.read_state = 1;
                        }
                    }

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
            if (window.im?.event_handler && typeof window.im.event_handler.updateGlobalUnreadCounter === 'function') {
                window.im.event_handler.updateGlobalUnreadCounter();
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
        if (item instanceof ChatMessage) {
            item = item.data;
        }
        item = item || {};
        this.data = item;
        this.has_not_loaded_attachments = false;

        if (typeof item.attachments === "string" && item.attachments.length > 0) {
            const a = item.attachments.split(",");
            const n = [];
            a.forEach(i => {
                const _type = i.split('_')[0].replace(/[0-9]/g, '');
                if (_type) {
                    const f = { type: _type };
                    f[_type] = {};
                    n.push(f);
                }
            });
            item.attachments = n;
        }

        if (!this.data.peer_id) {
            if (this.data.chat_id) {
                this.data.peer_id = 2000000000 + Number(this.data.chat_id);
            } else if (window.im?.messenger?.currentChatId) {
                this.data.peer_id = Number(window.im.messenger.currentChatId);
            }
        }

        if (item.reply_message != null) {
            if (item.reply_message instanceof ChatMessage) {
                this.data.reply_message = item.reply_message;
            } else {
                if (typeof item.reply_message.attachments == "string" && item.reply_message.attachments.length > 0) {
                    const a = item.reply_message.attachments.split(",");
                    const n = [];
                    a.forEach(i => {
                        const _type = i.split('_')[0].replace(/[0-9]/g, '');
                        const f = {};
                        f['type'] = _type;
                        f[_type] = {};

                        n.push(f);
                    });

                    item.reply_message.attachments = n;
                }

                this.data.reply_message = new ChatMessage(item.reply_message);
            }
        }

        let rawFwd = item.fwd_messages || item.forward_messages;
        if (rawFwd) {
            if (!Array.isArray(rawFwd) && typeof rawFwd === 'object') {
                rawFwd = Object.values(rawFwd);
            }
            if (Array.isArray(rawFwd)) {
                this.data.fwd_messages = rawFwd.map(f => f instanceof ChatMessage ? f : new ChatMessage(f));
            }
        }
    }

    getFwdMessages() {
        return this.data.fwd_messages || [];
    }

    getFwdCount() {
        if (!this.data) return 0;
        const fwd = this.getFwdMessages();
        if (Array.isArray(fwd) && fwd.length > 0) return fwd.length;
        if (this.data.forward_messages) {
            const fwds = Array.isArray(this.data.forward_messages) ? this.data.forward_messages : (typeof this.data.forward_messages === 'object' ? Object.values(this.data.forward_messages) : []);
            if (fwds.length > 0) return fwds.length;
        }
        if (this.data.fwd_messages) {
            const fwds = Array.isArray(this.data.fwd_messages) ? this.data.fwd_messages : (typeof this.data.fwd_messages === 'object' ? Object.values(this.data.fwd_messages) : []);
            if (fwds.length > 0) return fwds.length;
        }
        const fwdRaw = this.data.fwd || this.data.attachments?.fwd || this.data.attachments?.fwd_messages;
        if (typeof fwdRaw === 'string' && fwdRaw.trim().length > 0) {
            return fwdRaw.split(',').filter(Boolean).length || 1;
        }
        if (this.data.attachments) {
            let atts = this.data.attachments;
            if (!Array.isArray(atts) && typeof atts === 'object') {
                atts = Object.values(atts);
            }
            if (Array.isArray(atts)) {
                const fwdAtt = atts.find(a => a && (a.type === 'fwd' || a.type === 'fwd_messages' || a.type === 'forward' || a.type === 'forward_messages'));
                if (fwdAtt) {
                    if (typeof fwdAtt.count === 'number' && fwdAtt.count > 0) return fwdAtt.count;
                    if (Array.isArray(fwdAtt.items) && fwdAtt.items.length > 0) return fwdAtt.items.length;
                    if (Array.isArray(fwdAtt.fwd_messages) && fwdAtt.fwd_messages.length > 0) return fwdAtt.fwd_messages.length;
                    return 1;
                }
            }
        }
        if (this.data.has_fwd_messages) {
            return 1;
        }
        return 0;
    }

    async hydrateFromEvent(msg) {
        const prevFwd = this.data.fwd_messages;
        const prevAttachments = this.data.attachments;
        this.data = msg.data;
        if ((!this.data.fwd_messages || this.data.fwd_messages.length === 0) && prevFwd && prevFwd.length > 0) {
            this.data.fwd_messages = prevFwd;
        }
        if ((!this.data.attachments || this.data.attachments.length === 0) && prevAttachments && prevAttachments.length > 0) {
            this.data.attachments = prevAttachments;
        } else if (this.data.attachments && prevAttachments) {
            this.data.attachments.forEach(att => {
                if (att && att.type === 'sticker' && (!att.sticker?.photo_128 || !att.sticker?.images?.length)) {
                    const prevStk = prevAttachments.find(p => p && p.type === 'sticker')?.sticker;
                    if (prevStk && (prevStk.id === att.sticker?.id || prevStk.sticker_id === att.sticker?.sticker_id)) {
                        att.sticker = { ...prevStk, ...att.sticker };
                    }
                }
            });
        }

        if (this.data.attachments && Array.isArray(this.data.attachments)) {
            this.data.attachments.forEach(att => {
                if (att && att.type === 'sticker' && (!att.sticker?.photo_128 || !att.sticker?.images?.length)) {
                    const localStk = typeof window.findStickerData === 'function' ? window.findStickerData(att.sticker?.id || att.sticker?.sticker_id) : null;
                    if (localStk) {
                        att.sticker = { ...localStk, ...att.sticker };
                    }
                }
            });
        }

        if (this.has_not_loaded_attachments === true) {
            this.has_not_loaded_attachments = false;
        }
    }

    _guessSender() {
        this.data.sender = window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(this.data.from_id);
        if (this.data.reply_message && typeof this.data.reply_message._guessSender === 'function') {
            this.data.reply_message._guessSender();
        }
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
        const fromId = Number(this.data ? (this.data.from_id?.id || this.data.from_id) : (this.from_id || 0));
        const isSelf = fromId != null && !isNaN(fromId) && Number(currentUserId) != null && fromId === Number(currentUserId);
        return Boolean((this.data && this.data.out === 1) || this.out === 1 || isSelf);
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
        if (this._peer) return this._peer;
        try {
            return window.im.conversations._findConv(this.data.peer_id).peer;
        } catch (e) {
            return window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(this.data.peer_id);
        }
    }
    set peer(val) {
        this._peer = val;
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
                return title ? tr("event_chat_creation_" + gender, title) : tr("event_chat_creation_no_title_" + gender);
            }
            case "chat_title_update": {
                const title = (act.text || "").trim();
                return tr("event_chat_title_update_" + gender, title);
            }
            case "chat_photo_update":
                return tr("event_chat_photo_update_" + gender);
            case "chat_photo_remove":
                return tr("event_chat_photo_remove_" + gender);
            case "chat_pin_message":
                return tr("event_chat_pin_message_" + gender);
            case "chat_unpin_message":
                return tr("event_chat_unpin_message_" + gender);
            case "chat_invite_user": {
                const mid = act.member_id ?? this.data.action_mid;
                if (sender && mid == sender.id) {
                    return tr("event_chat_invite_user_self_" + gender);
                }
                const targetProf = window.im?.cached_profiles?._findCachedProfileByIdEvenIfNotCached ? window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(mid) : window.im?.cached_profiles?._findCachedProfileById(mid);
                const targetName = targetProf ? targetProf.getName() : `id${mid}`;
                return tr("event_chat_invite_user_" + gender, targetName);
            }
            case "chat_invite_user_by_link":
                return tr("event_chat_invite_user_by_link_" + gender);
            case "chat_kick_user": {
                const mid = act.member_id ?? this.data.action_mid;
                if (sender && mid == sender.id) {
                    return tr("event_chat_kick_user_self_" + gender);
                }
                const targetProf = window.im?.cached_profiles?._findCachedProfileByIdEvenIfNotCached ? window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(mid) : window.im?.cached_profiles?._findCachedProfileById(mid);
                const targetName = targetProf ? targetProf.getName() : `id${mid}`;
                return tr("event_chat_kick_user_" + gender, targetName);
            }
            case "chat_moderator_add": {
                const mid = act.member_id ?? this.data.action_mid;
                const targetProf = window.im?.cached_profiles?._findCachedProfileByIdEvenIfNotCached ? window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(mid) : window.im?.cached_profiles?._findCachedProfileById(mid);
                const targetName = targetProf ? targetProf.getName() : `id${mid}`;
                return tr("event_chat_moderator_add_" + gender, targetName);
            }
            case "chat_moderator_remove": {
                const mid = act.member_id ?? this.data.action_mid;
                const targetProf = window.im?.cached_profiles?._findCachedProfileByIdEvenIfNotCached ? window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(mid) : window.im?.cached_profiles?._findCachedProfileById(mid);
                const targetName = targetProf ? targetProf.getName() : `id${mid}`;
                return tr("event_chat_moderator_remove_" + gender, targetName);
            }
            case "rating_up":
                return tr("event_chat_user_up_your_rating_" + gender, sender?.getName(), act.member_id);
            case "coins_transfer":
                return tr("event_chat_user_added_voices_" + gender, sender?.getName(), act.member_id);
            default:
                return tr("event_" + type + "_impersonal");
        }
    }
    getText(raw = false, conversation = false, with_attachments = false) {
        const baseText = this.data.text ?? this.data.body ?? "";
        if (this.data.action != null) {
            let actionText = this.getActionText() || "";
            if (conversation) {
                if (!actionText) return "";
                const sender = this.sender;
                const senderName = sender?.getName ? (sender.getName(false, true) || sender.getName()) : (this.data?.from_id ? "id" + this.data.from_id : "");

                if (senderName && actionText.startsWith(senderName)) {
                    actionText = actionText.slice(senderName.length).trim();
                }

                let formattedAction = `<span class="im-action-msg">${escapeHtml(actionText)}</span>`;
                let rawAction = actionText;

                formattedAction = formattedAction.replace(/[\r\n]+/g, ' ').replace(/\s{2,}/g, ' ').trim();
                rawAction = rawAction.replace(/[\r\n]+/g, ' ').replace(/\s{2,}/g, ' ').trim();
                return raw ? rawAction : formattedAction;
            }
            return raw ? actionText : encode_emojis(nl2br(escapeHtml(actionText)));
        }

        let txt = "";
        let cleanBaseText = baseText;
        if (conversation) {
            cleanBaseText = baseText
                .replace(/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/g, '$1')
                .replace(/\[([a-zA-Z0-9_]+)(?:\|([^\]]*))?\]/g, (match, target, title) => {
                    if (title !== undefined && title.trim().length > 0) {
                        return title.trim();
                    }
                    if (target.toLowerCase() === 'all' || target.toLowerCase() === 'online') {
                        return '@' + target.toLowerCase();
                    }
                    return target;
                })
                .replace(/[@*]([a-zA-Z0-9_]+)\s*\(([^)]+)\)/g, '$2')
                .replace(/[\r\n]+/g, ' ')
                .replace(/\s{2,}/g, ' ')
                .trim();
        }

        if (raw) {
            txt = cleanBaseText;
        } else {
            txt = escapeHtml(cleanBaseText);
        }

        if (conversation) {
            txt = "";
            let allAtts = this.data.attachments;
            if (allAtts && !Array.isArray(allAtts)) {
                allAtts = typeof allAtts === 'object' ? Object.values(allAtts) : [allAtts];
            }
            const visualAttachments = (allAtts || []).filter(a => a && a.type !== 'link' && a.type !== 'share' && a.type !== 'fwd' && a.type !== 'fwd_messages' && a.type !== 'forward' && a.type !== 'forward_messages');
            const fwdCount = (typeof this.getFwdCount === 'function') ? this.getFwdCount() : 0;
            const fwdText = fwdCount > 0 ? (typeof tr === 'function' ? tr('forwarded_messages_noun', fwdCount) : `Пересланные сообщения (${fwdCount})`) : "";
            const fwdHtml = fwdText ? `<span class="conv_prev_attachment_text">${escapeHtml(fwdText)}</span>` : "";

            if (with_attachments) {
                if (visualAttachments.length > 0) {
                    const c = visualAttachments[0];

                    switch (c.type) {
                        case "photo":
                            const photoSrc = c.photo?.photo_75 || c.photo?.photo_130 || c.photo?.link || "";
                            if (photoSrc) {
                                txt += `<img class="conv_prev_img" src="${photoSrc}">`;
                            }
                            if (!cleanBaseText || cleanBaseText.length === 0) {
                                txt += get_attachment_text(c);
                            }
                            break;
                        case "sticker":
                            const stickerSrc = c.sticker?.photo_64 || c.sticker?.photo_128 || c.sticker?.images?.[0]?.url || "";
                            if (stickerSrc) {
                                txt += `<img class="conv_prev_img" src="${stickerSrc}">`;
                            }
                            if (!cleanBaseText || cleanBaseText.length === 0) {
                                txt += get_attachment_text(c);
                            }
                            break;
                        default:
                            txt += get_attachment_text(c);
                            break;
                    }

                    if (fwdHtml) {
                        txt += (txt ? " " : "") + fwdHtml;
                    }
                    if (cleanBaseText) {
                        txt += " " + ovk_proc_strtr(escapeHtml(cleanBaseText), 100);
                    }
                } else {
                    if (fwdHtml) {
                        txt += fwdHtml;
                        if (cleanBaseText) {
                            txt += " " + ovk_proc_strtr(escapeHtml(cleanBaseText), 100);
                        }
                    } else if (cleanBaseText) {
                        txt += ovk_proc_strtr(escapeHtml(cleanBaseText), 100);
                    } else {
                        txt = typeof tr === "function" && tr("message_no_text") ? "(" + tr("message_no_text").toLowerCase() + ")" : "...";
                    }
                }
            } else {
                let attachTxt = "";
                if (visualAttachments.length > 0) {
                    attachTxt = get_attachment_text(visualAttachments[0]);
                }
                if (fwdHtml) {
                    attachTxt = attachTxt ? (attachTxt + " " + fwdHtml) : fwdHtml;
                }

                if (cleanBaseText) {
                    txt = attachTxt ? (attachTxt + " " + escapeHtml(cleanBaseText)) : escapeHtml(cleanBaseText);
                } else {
                    txt = attachTxt || ("(" + tr("message_no_text").toLowerCase() + ")");
                }

                txt = txt.replace(/<br\s*\/?>/gi, ' ').replace(/[\r\n]+/g, ' ').replace(/\s{2,}/g, ' ').trim();
                return raw ? txt.replace(/<[^>]*>/g, '').trim() : encode_emojis(txt);
            }
        } else {
            if (this.isSpecial("gift")) {
                const msg = this.data.attachments?.[0]?.gift?.message;
                if (!msg) {
                    txt = "(" + tr("message_no_text").toLowerCase() + ")";
                } else {
                    txt = msg;
                }
            }
        }

        if (raw) {
            return conversation ? txt.replace(/<[^>]*>/g, '').trim() : txt;
        }

        if (conversation) {
            let formattedTxt = txt
                .replace(/<br\s*\/?>/gi, ' ')
                .replace(/[\r\n]+/g, ' ')
                .replace(/\s{2,}/g, ' ')
                .trim();
            return encode_emojis(formattedTxt);
        }

        // Format VK mentions: [id123|Name], [club123|Name], [all|Всем], [online|Онлайн], [slug|Name], @slug (Name), *slug (Name)
        let formattedTxt = txt;
        formattedTxt = formattedTxt.replace(/\[([a-zA-Z0-9_]+)(?:\|([^\]]*))?\]/gi, (match, target, title) => {
            const lowerTarget = target.toLowerCase();
            const display = (title && title.trim()) ? title.trim() : target;
            if (lowerTarget === "all" || lowerTarget === "online") {
                return `<b class="mention mention-mass">${display.startsWith('@') ? display : '@' + display}</b>`;
            }
            return `<a href="/${lowerTarget}" class="mention chat-link">${display}</a>`;
        });
        formattedTxt = formattedTxt.replace(/[@*]([a-zA-Z0-9_]+)\s*\(([^)]+)\)/g, (match, target, title) => {
            const lowerTarget = target.toLowerCase();
            const display = (title && title.trim()) ? title.trim() : target;
            if (lowerTarget === "all" || lowerTarget === "online") {
                return `<b class="mention mention-mass">${display.startsWith('@') ? display : '@' + display}</b>`;
            }
            return `<a href="/${lowerTarget}" class="mention chat-link">${display}</a>`;
        });
        formattedTxt = formattedTxt.replace(/(^|[\s\(\[\{<]|&gt;)([@*])([a-zA-Z0-9_]+)\b/gi, (match, prefix, symbol, target) => {
            const lowerTarget = target.toLowerCase();
            if (lowerTarget === "all" || lowerTarget === "online") {
                return `${prefix}<b class="mention mention-mass">@${lowerTarget}</b>`;
            }
            return `${prefix}<a href="/${lowerTarget}" class="mention chat-link">@${lowerTarget}</a>`;
        });

        // Format markdown links [title](url) and plain URLs
        formattedTxt = formattedTxt.replace(/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/g, (match, title, url) => {
            return `<a href="${url}" target="_blank" rel="noopener noreferrer" class="chat-link">${title}</a>`;
        });
        formattedTxt = formattedTxt.replace(/(^|[\s\(\[\{<]|&gt;)(https?:\/\/[^\s<>"'\]\)]+)/g, (match, prefix, url) => {
            return `${prefix}<a href="${url}" target="_blank" rel="noopener noreferrer" class="chat-link">${url}</a>`;
        });

        return encode_emojis(nl2br(formattedTxt));
    }

    get reply() { return this.data ? this.data.reply_message : undefined; }
    set reply(val) { if (this.data) this.data.reply_message = val; }
    get global_id() { return this.data ? (this.data.global_id || this.data.id) : undefined; }
    set global_id(val) { if (this.data) this.data.global_id = val; }
    get id() { return this.data ? this.data.id : undefined; }
    set id(val) { if (this.data) this.data.id = val; }
    get conversation_message_id() { return this.data ? (this.data.conversation_message_id || this.data.id) : undefined; }
    set conversation_message_id(val) { if (this.data) this.data.conversation_message_id = val; }
    get is_sending() { return this.isSending(); }
    set is_sending(val) { if (this.data) this.data.is_sending = Boolean(val); }
    isAction() { return this.data.action != null; }
    isReply() { return Boolean(this.data.reply_message || this.data.reply_to); }
    isError() { return this.data.error_text != null; }
    isEdited() { return this.data.edited == 1 || this.data.edited == true; }
    isSending() { return Boolean(this.data?.is_sending || (this.id == null && !this.isError())); }
    isImportant() {
        if (!this.data) return Boolean(this.important || (this.flags & 8));
        return Boolean(this.data.important || this.important || (this.data.flags & 8) || (this.flags & 8));
    }

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
            if (this.data.is_sticker == 1) return true;
            if (this.data.attachments && Array.isArray(this.data.attachments)) {
                return this.data.attachments.some(a => a && a.type === 'sticker');
            }
            return false;
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
        if (action === "restore") {
            return Boolean(this.isDeleted() && this.isMine());
        }

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
                if (this.isAction() == true || this.isSpecial("sticker") == true) {
                    return false;
                }

                if (this.data.can_edit != null) {
                    return Boolean(this.data.can_edit);
                }

                if (group != null) {
                    return false;
                }

                return this.isMine();
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
    set peer_id(val) {
        if (!this.data) this.data = {};
        this.data.peer_id = val;
    }
    get from_id() { return this.data.from_id; }
    getAttachments(includeLinks = false) {
        let _at = this.data.attachments;
        if (!_at) return [];
        if (!Array.isArray(_at)) {
            _at = typeof _at === 'object' ? Object.values(_at) : [_at];
        }
        if (includeLinks) return _at;
        return _at.filter(a => a && a.type !== 'link' && a.type !== 'share');
    }
    getStringAttachments() {
        const _at = this.data.attachments;
        if (_at.length == 0) return '';
    }
    getDate(mode = 0) {
        const conv_day = this.getConvDay();
        switch (mode) {
            case 0:
                return formatTime(this.getSentTime(), true);
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
            return formatDate(date);
        } else {
            return formatDate(date, {
                month: '2-digit',
                day: '2-digit'
            });
        }
    }

    static async fromEvent(event, im = null) {
        const [, id, flags, peer, ts, subject, text, attachments, randomId] = event;
        let new_attachments = null;
        let reply_message = null;

        if (attachments && (attachments['attach1'] || attachments['attach1_type'])) {
            const temp_str = get_attachments_list_from_lp(attachments);
            if (temp_str.length > 0) {
                try {
                    new_attachments = await resolve_attachments(temp_str);
                } catch (e) {
                    console.error("resolve_attachments error:", e);
                }
            }

            if (attachments['attach1_type'] === 'sticker' && (!new_attachments || new_attachments.length === 0)) {
                const sId = parseInt(attachments['attach1']);
                const localStk = typeof window.findStickerData === 'function' ? window.findStickerData(sId) : null;
                if (localStk) {
                    new_attachments = [{
                        type: 'sticker',
                        sticker: localStk
                    }];
                }
            } else if (new_attachments && Array.isArray(new_attachments)) {
                new_attachments.forEach(att => {
                    if (att && att.type === 'sticker') {
                        if (att.sticker && (att.sticker.id || att.sticker.sticker_id)) {
                            window._stickersCache = window._stickersCache || new Map();
                            window._stickersCache.set(Number(att.sticker.id || att.sticker.sticker_id), att.sticker);
                        }
                        if (!att.sticker?.photo_128 || !att.sticker?.images?.length) {
                            const localStk = typeof window.findStickerData === 'function' ? window.findStickerData(att.sticker?.id || att.sticker?.sticker_id) : null;
                            if (localStk) {
                                att.sticker = { ...localStk, ...att.sticker };
                            }
                        }
                    }
                });
            }
        }

        if (attachments['reply_to'] || attachments['reply']) {
            const reply_id = attachments['reply_to'] || attachments['reply'];
            try {
                const peer_obj = await window.im.conversations._findConvFromApi(peer);
                let __msg = peer_obj?.peer?._chunks ? await peer_obj.peer._chunks.findMessageByIdFromApi(reply_id) : null;
                if (!__msg && peer_obj?.peer?._chunks) {
                    __msg = peer_obj.peer._chunks._findMessageById(reply_id);
                }

                if (__msg != null) {
                    reply_message = __msg;
                } else {
                    let res = null;
                    // First try getByConversationMessageId since LongPoll reply_to delivers the conversation-local cmid
                    try {
                        res = await window.OVKAPI.call("messages.getByConversationMessageId", {
                            peer_id: peer,
                            conversation_message_ids: reply_id,
                            extended: 1
                        });
                    } catch (e) {
                        res = null;
                    }

                    // If not found, fallback to messages.getById with global ID
                    if (!res || !res.items || res.items.length === 0) {
                        try {
                            res = await window.OVKAPI.call("messages.getById", {
                                message_ids: reply_id,
                                extended: 1
                            });
                        } catch (e) {
                            res = null;
                        }
                    }

                    if (res && res.items && res.items.length > 0) {
                        if (res.profiles || res.groups) {
                            window.im.cached_profiles._moveToProfileCache(res.profiles, res.groups);
                        }
                        const item = res.items[0];
                        const author = window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(item.from_id);
                        const ChatGeneralForm = getChatGeneralForm();
                        item.sender = author ? new ChatGeneralForm(author) : null;
                        reply_message = new ChatMessage(item);
                    } else {
                        reply_message = new ChatMessage({
                            'id': reply_id,
                            'conversation_message_id': reply_id,
                            'text': '...'
                        });
                    }
                }
            } catch (e) {
                console.error("Failed to load reply message for event:", e);
                reply_message = new ChatMessage({
                    'id': reply_id,
                    'conversation_message_id': reply_id,
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
        const curUid = window.openvk ? window.openvk.current_id : (im ? im.state.getId() : 0);
        const fromId = attachments && attachments.from ? Number(attachments.from) : ((flags & 2) ? curUid : peer);
        const isMentionedFromLp = Boolean(attachments && (attachments['mention'] == 1 || attachments['mention'] === true || attachments['is_mentioned']));
        const isStickerMsg = (new_attachments && new_attachments.some(a => a && a.type === 'sticker')) ? 1 : 0;
        const isOut = Boolean(flags & 2);
        const isUnread = Boolean(flags & 1);

        const msg = new ChatMessage({
            'id': id,
            'local_id': cmidFromLp,
            'conversation_message_id': cmidFromLp,
            'flags': flags,
            'out': isOut ? 1 : 0,
            'read_state': isUnread ? 0 : 1,
            'from_id': fromId,
            'date': ts,
            'peer': peer,
            'peer_id': peer,
            'text': text,
            'attachments': new_attachments,
            'mention': isMentionedFromLp,
            'is_mentioned': isMentionedFromLp,
            'extra_attachments': attachments,
            'is_sticker': isStickerMsg,
            'random_id': randomId,
            'reply_message': reply_message,
            'fwd': attachments ? (attachments['fwd'] || attachments['fwd_messages'] || null) : null,
            'fwd_messages': fwd_messages,
            'action': action,
            'action_type': action ? action.type : null,
            'action_mid': action ? action.member_id : null,
            'action_text': action ? action.text : null,
        });
        msg.mention = isMentionedFromLp;
        msg.is_mentioned = isMentionedFromLp;
        msg.out = isOut ? 1 : 0;
        msg.read_state = isUnread ? 0 : 1;
        if (msg.data) {
            msg.data.mention = isMentionedFromLp;
            msg.data.is_mentioned = isMentionedFromLp;
            msg.data.out = isOut ? 1 : 0;
            msg.data.read_state = isUnread ? 0 : 1;
        }
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
        if (data && (data['attach1'] || data['attach1_type'])) {
            const temp_str = get_attachments_list_from_lp(data);
            if (temp_str.length > 0) {
                try {
                    new_attachments = await resolve_attachments(temp_str);
                } catch (e) {
                    console.error("resolve_attachments error:", e);
                }
            }

            if (data['attach1_type'] === 'sticker' && (!new_attachments || new_attachments.length === 0)) {
                const sId = parseInt(data['attach1']);
                const localStk = typeof window.findStickerData === 'function' ? window.findStickerData(sId) : null;
                if (localStk) {
                    new_attachments = [{
                        type: 'sticker',
                        sticker: localStk
                    }];
                }
            } else if (new_attachments && Array.isArray(new_attachments)) {
                new_attachments.forEach(att => {
                    if (att && att.type === 'sticker') {
                        if (att.sticker && (att.sticker.id || att.sticker.sticker_id)) {
                            window._stickersCache = window._stickersCache || new Map();
                            window._stickersCache.set(Number(att.sticker.id || att.sticker.sticker_id), att.sticker);
                        }
                        if (!att.sticker?.photo_128 || !att.sticker?.images?.length) {
                            const localStk = typeof window.findStickerData === 'function' ? window.findStickerData(att.sticker?.id || att.sticker?.sticker_id) : null;
                            if (localStk) {
                                att.sticker = { ...localStk, ...att.sticker };
                            }
                        }
                    }
                });
            }
        }

        if (new_attachments) {
            this.data.attachments = new_attachments;
        }

        if (data && (data['fwd'] || data['fwd_messages'])) {
            this.data.fwd = data['fwd'] || data['fwd_messages'];
        }

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
            imLog.info('IM | Resent message to ' + this.id);
            this.data.error_text = null;
            this.data.resend_params = null;
        } catch (e) {
            this.data.error_text = r;
            let d = String(e?.message || e?.error_msg || e);
            let errCode = Number(e?.error_code || e?.error?.error_code || 0);
            if (!errCode) {
                if (d.includes("900") || d.toLowerCase().includes("blacklist")) errCode = 900;
                else if (d.includes("901") || d.toLowerCase().includes("privacy")) errCode = 901;
                else if (d.includes("902")) errCode = 902;
                else if (d.includes("18") || d.toLowerCase().includes("deleted") || d.toLowerCase().includes("banned")) errCode = 18;
                else if (d.includes("915") || d.toLowerCase().includes("kicked")) errCode = 915;
                else if (d.includes("916") || d.toLowerCase().includes("left")) errCode = 916;
                else if (d.includes("917")) errCode = 917;
            }
            if ([18, 900, 901, 902, 915, 916, 917].includes(errCode)) {
                if (this.peer && this.peer.data) {
                    this.peer.data.can_write = { allowed: false, reason: errCode };
                }
                const conv = window.im?.conversations?._findConv(this.peer_id);
                if (conv) {
                    if (conv._conversation) conv._conversation.can_write = { allowed: false, reason: errCode };
                    if (conv.peer && conv.peer.data) conv.peer.data.can_write = { allowed: false, reason: errCode };
                }
            }
            console.error('IM | STILL can not send message to ' + this.id, ': ', e);
        }

        window.im.messenger.update();
    }

    async edit(text, attachments = []) {
        const rawText = text || '';
        const cleanText = rawText.replace(/[\s\u200b\ufeff\u00a0]/g, '');
        const hasAttachments = attachments && attachments.length > 0;

        if (!cleanText && !hasAttachments) {
            return;
        }
        const textToSend = cleanText ? rawText : '';

        let resp = null;
        try {
            const params = {
                "peer_id": this.peer_id,
                "message_id": this.id,
                "message": textToSend,
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

        this.data.text = textToSend;
        this.data.edited = true;

        window.im.messenger.update();

        imLog("successfully edited", this, resp);
    }

    async togglePin(action) {
        let method = "pin";
        if (action == false) {
            method = "unpin";
        }

        const g = window.im?.state?.getId?.() || 0;
        let resp = null;
        try {
            const params = {
                "peer_id": this.peer_id,
                "message_id": this.id,
            };
            if (g < 0) {
                params["group_id"] = Math.abs(g);
            }
            resp = await window.OVKAPI.call("messages." + method, params);
        } catch (e) {
            fastError(String(e));
            console.error(e);
            return;
        }

        this.data.is_pinned = Boolean(action);

        const curChat = window.im?.messenger?.getCurrentChat?.() || window.im?.conversations?._findConv?.(this.peer_id);
        if (curChat && typeof curChat.setPinnedMessage === 'function') {
            if (action) {
                const pinnedData = (resp && resp.response) ? resp.response : (resp && !resp.error ? resp : this.data);
                curChat.setPinnedMessage(pinnedData);
            } else {
                curChat.setPinnedMessage(null);
            }
        }
        if (window.im?.messenger) window.im.messenger.update();
        if (window.im?.conversations) window.im.conversations.update();
    }

    isRead(conv = null) {
        try {
            if (this.read_state === 1 || this.read_state === true || this.data?.read_state === 1 || this.data?.read_state === true) return true;
            const currentChat = window.im?.messenger?.getCurrentChat();
            const currentChatId = currentChat?.peer?.id || (window.im?.messenger?.currentChatId ? Number(window.im.messenger.currentChatId) : 0);
            const peerId = (this.data && (this.data.peer_id || (this.data.chat_id ? 2000000000 + Number(this.data.chat_id) : 0)))
                || this.peer_id
                || (this.peer?.id)
                || currentChatId
                || 0;
            conv = conv || (peerId ? window.im?.conversations?._findConv(peerId) : currentChat);
            const peer = conv?.peer || this.peer || currentChat?.peer;
            if (peer && typeof peer.isSavedMessages === 'function' && peer.isSavedMessages()) {
                return true;
            }
            const outRead = Number(peer?.out_read || conv?._conversation?.out_read || conv?.conversation?.out_read || 0);
            const inRead = Number(peer?.in_read || conv?._conversation?.in_read || conv?.conversation?.in_read || 0);
            const currentUserId = window.openvk ? window.openvk.current_id : window.im?.state?.getId();
            const msgCmid = Number((this.data && (this.data.conversation_message_id || this.data.local_id)) || this.conversation_message_id || 0);
            const msgId = Number((this.data && this.data.id) || this.id || 0);
            const fromId = Number(this.data ? (this.data.from_id?.id || this.data.from_id) : (this.from_id || 0));
            const isMine = Boolean((this.data && this.data.out === 1) || this.out === 1 || (fromId && currentUserId && fromId === Number(currentUserId)));

            if (!isMine) {
                if (inRead > 0 && ((msgCmid > 0 && msgCmid <= inRead) || (msgId > 0 && msgId <= inRead))) {
                    return true;
                }
                const rawUnread = conv?._conversation?.unread_count ?? conv?._unread_count;
                if (rawUnread === 0) {
                    return true;
                }
            } else {
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
            if (this.read_state !== undefined) {
                return Boolean(this.read_state);
            }
            return Boolean(this.data?.read_state);
        } catch (e) {
            return false;
        }
    }
}
