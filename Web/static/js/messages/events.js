//import { ChatMessage, ChatGeneralForm } from './components/messages.js';
const { ChatGeneralForm, ChatMessage } = await es6import_Im(import.meta.url, './components/messages.js');
const { imLog } = await es6import_Im(import.meta.url, './logger.js');

export class EventHandler {
    constructor(im) {
        this.im = im;
        this.codes = {
            0: "MsgDeleteEvent",
            1: "ReplaceFlags",
            2: "SetFlags",
            3: "ResetFlags",
            4: "NewMessageEvent",
            5: "EditMessageEvent",
            6: "ReadIncomeBeforeEvent",
            7: "ReadOutcomeBeforeEvent",
            8: "UserOnlineEvent",
            9: "UserOfflineEvent",
            10: "ChatResetFlagsEvent",
            11: "ChatReplaceFlagsEvent",
            12: "ChatSetFlagsEvent",
            13: "MassDeleteMessagesEvent",
            14: "MassRestoreMessagesEvent",
            51: "ChatUpdateEvent",
            52: "ChatUpdateEvent",
            61: "TypingEvent",
            62: "TypingEvent",
            63: "TypingEvent",
            64: "TypingEvent",
            80: "CounterUpdateEvent",
        };
        this._updateCounterTimeout = null;
    }

    async handle(event) {
        if (!Array.isArray(event)) return;

        const method = this.codes[event[0]];
        imLog("LP Event:", event, method || "unknown");
        if (!method) {
            imLog.info('unknown event,  ', event[0]);
        } else if (typeof this[method] === 'function') {
            await this[method](event);
        }
    }

    async MsgDeleteEvent(event) {
        const msgId = event[1];
        if (this.im && this.im.conversations && this.im.conversations.all_convs) {
            this.im.conversations.all_convs.forEach(conv => {
                if (conv.peer && conv.peer._chunks) {
                    const found = conv.peer._chunks._findMessageById(msgId);
                    if (found) {
                        found.setDeleted(true);
                        conv.peer._chunks._invalidateCache();
                    }
                }
            });
            this.im.conversations.update();
        }
        if (this.im.messenger) {
            this.im.messenger.update();
        }
        this.updateGlobalUnreadCounter();
    }

    async ReplaceFlags(event) {
        const msgId = event[1];
        const flags = event[2];
        const peerId = event[3];

        const _crs = await this.im.conversations._findConvFromApi(peerId);
        if (_crs && _crs.peer) {
            const found = _crs.peer._chunks._findMessageById(msgId);
            if (found != null) {
                if (flags & 128) {
                    found.setDeleted(false);
                } else if (found.isDeleted()) {
                    found.restore();
                }
                if (_crs.peer._chunks) _crs.peer._chunks._invalidateCache();
                this.im.messenger.update();
            }
        }
        this.updateGlobalUnreadCounter();
    }

    updateGlobalUnreadCounter(explicitCount = null) {
        if (typeof explicitCount === 'number' && !isNaN(explicitCount)) {
            if (this.im && this.im.state && typeof this.im.state._updateCounter === 'function') {
                this.im.state._updateCounter(explicitCount);
            }
        }

        if (this._updateCounterTimeout) {
            clearTimeout(this._updateCounterTimeout);
        }
        this._updateCounterTimeout = setTimeout(async () => {
            try {
                if (this.im && this.im.state && typeof this.im.state.fetchUnreadCounter === 'function') {
                    await this.im.state.fetchUnreadCounter();
                } else if (window.OVKAPI) {
                    const params = {};
                    if (this.im?.state?.group_id != null) {
                        params.group_id = Math.abs(this.im.state.group_id);
                    }
                    const res = await window.OVKAPI.call('messages.getUnreadConversations', params);
                    const count = (res && typeof res.count === 'number') ? res.count : (typeof res === 'number' ? res : 0);
                    if (this.im && this.im.state && typeof this.im.state._updateCounter === 'function') {
                        this.im.state._updateCounter(count);
                    }
                }
            } catch (e) {
                console.error("Error updating global unread counter:", e);
            }
        }, 50);
    }

    async SetFlags(event) {
        const msgId = event[1];
        const flags = event[2];
        const peerId = event[3];

        const _crs = await this.im.conversations._findConvFromApi(peerId);
        if (_crs && _crs.peer) {
            const found = _crs.peer._chunks._findMessageById(msgId);
            if (found) {
                if (flags & 128) {
                    found.setDeleted(false);
                }
                if (flags & 1) {
                    if (found.data) found.data.read_state = 0;
                    found.read_state = 0;
                }
                if (_crs.peer._chunks) _crs.peer._chunks._invalidateCache();
                if (typeof _crs.getScrollPosition === 'function' && _crs.getScrollPosition()) {
                    _crs.getScrollPosition()._invalidateCache();
                }
                if (this.im.messenger) this.im.messenger.update();
            }
        }

        if (flags & 1) {
            const markConvMsgUnread = (conv) => {
                if (!conv) return;
                const msg = conv._last_message || conv.last_message;
                if (!msg) return;
                const mId = Number(msg.data?.id || msg.id || 0);
                const mCmid = Number(msg.data?.conversation_message_id || msg.data?.local_id || msg.conversation_message_id || 0);
                if (mId === Number(msgId) || mCmid === Number(msgId)) {
                    if (msg.data) msg.data.read_state = 0;
                    msg.read_state = 0;
                }
            };
            if (_crs) markConvMsgUnread(_crs);
            if (this.im.conversations && Array.isArray(this.im.conversations.all_convs)) {
                this.im.conversations.all_convs.forEach(conv => {
                    if (conv?.peer?.id == peerId || conv?.id == peerId) {
                        markConvMsgUnread(conv);
                    }
                });
            }
            if (this.im.conversations) {
                this.im.conversations.update();
            }
        }
        this.updateGlobalUnreadCounter();
    }

    async ResetFlags(event) {
        const msgId = event[1];
        const flags = event[2];
        const peerId = event[3];

        const _crs = await this.im.conversations._findConvFromApi(peerId);
        if (_crs && _crs.peer) {
            const found = _crs.peer._chunks._findMessageById(msgId);
            if (found) {
                if (flags & 128) {
                    found.restore();
                }
                if (flags & 1) {
                    if (found.data) found.data.read_state = 1;
                    found.read_state = 1;
                }
                if (_crs.peer._chunks) _crs.peer._chunks._invalidateCache();
                if (typeof _crs.getScrollPosition === 'function' && _crs.getScrollPosition()) {
                    _crs.getScrollPosition()._invalidateCache();
                }
                if (this.im.messenger) this.im.messenger.update();
            }
        }

        if (flags & 1) {
            const currentUserId = window.openvk ? window.openvk.current_id : this.im.state.getId();
            const markConvMsgRead = (conv) => {
                if (!conv) return;
                const msg = conv._last_message || conv.last_message;
                if (!msg) return;
                const mId = Number(msg.data?.id || msg.id || 0);
                const mCmid = Number(msg.data?.conversation_message_id || msg.data?.local_id || msg.conversation_message_id || 0);
                const fromId = Number(msg.data ? (msg.data.from_id?.id || msg.data.from_id) : (msg.from_id || 0));
                const isMine = Boolean((msg.data && msg.data.out === 1) || msg.out === 1 || (fromId && currentUserId && fromId === Number(currentUserId)));

                if (mId === Number(msgId) || mCmid === Number(msgId) || (isMine && (mId <= Number(msgId) || mCmid <= Number(msgId)))) {
                    if (msg.data) msg.data.read_state = 1;
                    msg.read_state = 1;
                }
                if (isMine) {
                    if (conv.peer) conv.peer.out_read = Math.max(conv.peer.out_read || 0, Number(msgId));
                    if (conv._conversation) conv._conversation.out_read = Math.max(conv._conversation.out_read || 0, Number(msgId));
                }
            };

            if (_crs) {
                markConvMsgRead(_crs);
            }
            if (this.im.conversations && Array.isArray(this.im.conversations.all_convs)) {
                this.im.conversations.all_convs.forEach(conv => {
                    if (conv?.peer?.id == peerId || conv?.id == peerId) {
                        markConvMsgRead(conv);
                    }
                });
            }
            if (this.im.conversations) {
                this.im.conversations.update();
            }
        }
        this.updateGlobalUnreadCounter();
    }

    async ReadIncomeBeforeEvent(event) {
        const peerId = event[1];
        const localId = Number(event[2]);

        const _crs = await this.im.conversations._findConvFromApi(peerId);
        if (!_crs) return;

        if (_crs.peer) {
            _crs.peer.in_read = Math.max(_crs.peer.in_read || 0, localId);
        }
        if (_crs._conversation) {
            _crs._conversation.in_read = Math.max(_crs._conversation.in_read || 0, localId);
        }

        const getPeerMsgs = (peer) => {
            if (!peer) return [];
            if (peer._chunks && typeof peer._chunks.getMessages === 'function') {
                return peer._chunks.getMessages();
            }
            if (typeof peer.getLoadedMessages === 'function') {
                return peer.getLoadedMessages();
            }
            return [];
        };

        const currentUserId = window.openvk ? window.openvk.current_id : this.im.state.getId();
        getPeerMsgs(_crs.peer).forEach(msg => {
            const msgCmid = Number((msg.data && (msg.data.conversation_message_id || msg.data.local_id)) || msg.conversation_message_id || 0);
            const msgId = Number((msg.data && msg.data.id) || msg.id || 0);
            const fromId = Number(msg.data ? (msg.data.from_id?.id || msg.data.from_id) : (msg.from_id || 0));
            const isMine = Boolean((msg.data && msg.data.out === 1) || msg.out === 1 || (fromId && currentUserId && fromId === Number(currentUserId)));
            if (((msgCmid > 0 && msgCmid <= localId) || (msgId > 0 && msgId <= localId)) && !isMine) {
                if (msg.data) msg.data.read_state = 1;
                msg.read_state = 1;
            }
        });

        const markIncomeMsgRead = (msg) => {
            if (!msg) return;
            const msgCmid = Number((msg.data && (msg.data.conversation_message_id || msg.data.local_id)) || msg.conversation_message_id || 0);
            const msgId = Number((msg.data && msg.data.id) || msg.id || 0);
            const fromId = Number(msg.data ? (msg.data.from_id?.id || msg.data.from_id) : (msg.from_id || 0));
            const isMine = Boolean((msg.data && msg.data.out === 1) || msg.out === 1 || (fromId && currentUserId && fromId === Number(currentUserId)));
            if (((msgCmid > 0 && msgCmid <= localId) || (msgId > 0 && msgId <= localId)) && !isMine) {
                if (msg.data) msg.data.read_state = 1;
                msg.read_state = 1;
            }
        };
        markIncomeMsgRead(_crs._last_message);
        markIncomeMsgRead(_crs.last_message);

        if (this.im.conversations && Array.isArray(this.im.conversations.all_convs)) {
            this.im.conversations.all_convs.forEach(conv => {
                if (conv?.peer?.id == peerId || conv?.id == peerId) {
                    if (conv.peer) conv.peer.in_read = Math.max(conv.peer.in_read || 0, localId);
                    if (conv._conversation) conv._conversation.in_read = Math.max(conv._conversation.in_read || 0, localId);
                    markIncomeMsgRead(conv._last_message);
                    markIncomeMsgRead(conv.last_message);
                }
            });
        }

        if (_crs) {
            let newUnread = 0;
            if (_crs.peer && _crs.peer._chunks && typeof _crs.peer._chunks.isMessagesInited === 'function' && _crs.peer._chunks.isMessagesInited()) {
                newUnread = _crs.peer._chunks.getUnreadCount();
            }
            if (_crs._conversation) {
                _crs._conversation.unread_count = newUnread;
            }
            _crs.unread_count = newUnread;
        }

        if (_crs.peer && _crs.peer._chunks) {
            _crs.peer._chunks._invalidateCache();
        }
        if (typeof _crs.getScrollPosition === 'function' && _crs.getScrollPosition()) {
            _crs.getScrollPosition()._invalidateCache();
        }

        this.updateGlobalUnreadCounter();

        if (this.im.messenger) {
            this.im.messenger.update();
        }
        if (this.im.conversations) {
            this.im.conversations.update();
        }
        if (this.im.fastChats) {
            this.im.fastChats.update();
        }
    }


    async ReadOutcomeBeforeEvent(event) {
        const peerId = event[1];
        const localId = Number(event[2]);

        const _crs = await this.im.conversations._findConvFromApi(peerId);
        if (!_crs) return;

        if (_crs.peer) {
            _crs.peer.out_read = Math.max(_crs.peer.out_read || 0, localId);
        }
        if (_crs._conversation) {
            _crs._conversation.out_read = Math.max(_crs._conversation.out_read || 0, localId);
        }

        const getPeerMsgs = (peer) => {
            if (!peer) return [];
            if (peer._chunks && typeof peer._chunks.getMessages === 'function') {
                return peer._chunks.getMessages();
            }
            if (typeof peer.getLoadedMessages === 'function') {
                return peer.getLoadedMessages();
            }
            return [];
        };

        const currentUserId = window.openvk ? window.openvk.current_id : this.im.state.getId();
        getPeerMsgs(_crs.peer).forEach(msg => {
            const msgCmid = Number((msg.data && (msg.data.conversation_message_id || msg.data.local_id)) || msg.conversation_message_id || 0);
            const msgId = Number((msg.data && msg.data.id) || msg.id || 0);
            const fromId = Number(msg.data ? (msg.data.from_id?.id || msg.data.from_id) : (msg.from_id || 0));
            const isMine = Boolean((msg.data && msg.data.out === 1) || msg.out === 1 || (fromId && currentUserId && fromId === Number(currentUserId)));
            if (((msgCmid > 0 && msgCmid <= localId) || (msgId > 0 && msgId <= localId)) && isMine) {
                if (msg.data) msg.data.read_state = 1;
                msg.read_state = 1;
            }
        });

        const markMsgReadIfMatches = (msg) => {
            if (!msg) return;
            const msgCmid = Number((msg.data && (msg.data.conversation_message_id || msg.data.local_id)) || msg.conversation_message_id || 0);
            const msgId = Number((msg.data && msg.data.id) || msg.id || 0);
            const fromId = Number(msg.data ? (msg.data.from_id?.id || msg.data.from_id) : (msg.from_id || 0));
            const isMine = Boolean((msg.data && msg.data.out === 1) || msg.out === 1 || (fromId && currentUserId && fromId === Number(currentUserId)));
            if (((msgCmid > 0 && msgCmid <= localId) || (msgId > 0 && msgId <= localId)) && isMine) {
                if (msg.data) msg.data.read_state = 1;
                msg.read_state = 1;
            }
        };

        markMsgReadIfMatches(_crs._last_message);
        markMsgReadIfMatches(_crs.last_message);

        if (this.im.conversations && Array.isArray(this.im.conversations.all_convs)) {
            this.im.conversations.all_convs.forEach(conv => {
                if (conv?.peer?.id == peerId || conv?.id == peerId) {
                    if (conv.peer) conv.peer.out_read = Math.max(conv.peer.out_read || 0, localId);
                    if (conv._conversation) conv._conversation.out_read = Math.max(conv._conversation.out_read || 0, localId);
                    markMsgReadIfMatches(conv._last_message);
                    markMsgReadIfMatches(conv.last_message);
                }
            });
        }

        if (_crs.peer && _crs.peer._chunks) {
            _crs.peer._chunks._invalidateCache();
        }
        if (typeof _crs.getScrollPosition === 'function' && _crs.getScrollPosition()) {
            _crs.getScrollPosition()._invalidateCache();
        }

        if (this.im.messenger) {
            this.im.messenger.update();
        }
        if (this.im.conversations) {
            this.im.conversations.update();
        }
        if (this.im.fastChats) {
            this.im.fastChats.update();
        }
        this.updateGlobalUnreadCounter();
    }



    async NewMessageEvent(event) {
        const _msg = await ChatMessage.fromEvent(event, this.im);

        if (this.im && this.im.fastChats) {
            this.im.fastChats.onNewMessage(_msg);
        }

        const _crs = await this.im.conversations._findConvFromApi(_msg.peer_id);
        if (!_crs) return;

        const currentUserId = window.openvk ? window.openvk.current_id : this.im.state.getId();
        const isSelf = _msg.from_id == currentUserId || (typeof _msg.isMine === 'function' ? _msg.isMine() : false);
        if (isSelf) {
            _msg.out = 1;
            if (_msg.data) _msg.data.out = 1;
            const isSaved = _crs.peer && typeof _crs.peer.isSavedMessages === 'function' && _crs.peer.isSavedMessages();
            _msg.read_state = isSaved ? 1 : 0;
            if (_msg.data) _msg.data.read_state = isSaved ? 1 : 0;
        }
        const activeChat = (this.im.messenger && typeof this.im.messenger.getCurrentChat === 'function') ? this.im.messenger.getCurrentChat() : null;
        const isActiveChatOpen = this.im.state.is_active && activeChat && activeChat.peer && activeChat.peer.id == _msg.peer_id;

        if (!isActiveChatOpen && !isSelf && !_crs.peer.isMuted()) {
            triggerMessageNotification(_crs, _msg);
        }

        setTimeout(() => {
            try {
                const found = _crs.findMessageById(_msg.id, _msg.random_id || _msg.data?.random_id);

                if (found == null) {
                    _crs.pushMessage(_msg);
                } else {
                    found.hydrateFromEvent(_msg);
                }

                _crs._last_message = _msg;
                _crs.last_message = _msg;

                if (!isSelf && !isActiveChatOpen) {
                    _crs.unread_count = (_crs.unread_count || 0) + 1;
                    if (_crs._conversation) {
                        _crs._conversation.unread_count = _crs.unread_count;
                    }
                }

                if (this.im.conversations && this.im.conversations.all_convs) {
                    const idx = this.im.conversations.all_convs.indexOf(_crs);
                    if (idx > 0) {
                        this.im.conversations.all_convs.splice(idx, 1);
                        this.im.conversations.all_convs.unshift(_crs);
                    } else if (idx === -1) {
                        this.im.conversations.all_convs.unshift(_crs);
                    }
                    this.im.conversations.update();
                }

                if (this.im.state.is_active) {
                    const wasAtEnd = this.im.messenger.view ? this.im.messenger.view.isAtEnd() : false;
                    this.im.messenger.update();
                    if (wasAtEnd && this.im.messenger.view) {
                        this.im.messenger.view._scrollToEnd();
                    }
                }

                if (isActiveChatOpen) {
                    const wasAtEnd = this.im.messenger.view ? this.im.messenger.view.isAtEnd() : false;
                    const isWindowFocused = (typeof document === 'undefined' || !document.hidden) && (typeof document.hasFocus !== 'function' || document.hasFocus());
                    if (wasAtEnd && isWindowFocused) {
                        _crs.peer.read();
                    }
                }

                this.updateGlobalUnreadCounter();
            } catch (e) {
                console.error(e);
            }
        }, 50);
    }

    async EditMessageEvent(event) {
        const msgId = event[1];
        const flags = event[2];
        const peerId = event[3];
        const editTime = event[5];
        const text = event[5];
        const attachments = event[6];
        const idk = event[7];

        if (this.im && this.im.fastChats) {
            this.im.fastChats.onEditMessage(peerId, msgId, text);
        }

        const _crs = await this.im.conversations._findConvFromApi(peerId);
        if (!_crs) {
            return;
        }

        const found = _crs.peer._chunks._findMessageById(msgId);
        if (!found) {
            return;
        }

        found.setText(text);
        await found.setAttachmentsFromLP(attachments);
        found.data.edited = true;

        this.im.messenger.update();
    }

    async ChatUpdateEvent(event) {
        const _type = event[1];
        const peer_id = event[2];

        const _crs = await this.im.conversations._findConvFromApi(peer_id, true);
        if (this.im.conversations) {
            this.im.conversations.update();
        }
        if (this.im.messenger) {
            this.im.messenger.update();
        }
    }


    async TypingEvent(event) {
        const code = Number(event[0]);
        let peerId = null;
        let userIds = [];
        let variant = "writing";
        let flags = 1;

        switch (code) {
            case 61: {
                // [61, $user_id, $flags]
                const userId = Number(event[1]);
                flags = Number(event[2] ?? 1);
                peerId = userId;
                userIds = [userId];
                if (flags === 2) {
                    variant = "audiomessage";
                }
                break;
            }
            case 62: {
                // [62, $user_id, $chat_id, $flags?]
                const userId = Number(event[1]);
                const chatId = Number(event[2]);
                flags = Number(event[3] ?? 1);
                const CHAT_RUBICON = ChatGeneralForm.CHAT_RUBICON || 2000000000;
                peerId = chatId < CHAT_RUBICON ? CHAT_RUBICON + chatId : chatId;
                userIds = [userId];
                if (flags === 2) {
                    variant = "audiomessage";
                }
                break;
            }
            case 63: {
                // [63, $user_ids, $peer_id, $total_count, $ts]
                const rawUsers = event[1];
                peerId = Number(event[2]);
                userIds = Array.isArray(rawUsers) ? rawUsers.map(Number) : [Number(rawUsers)];
                variant = "writing";
                break;
            }
            case 64: {
                // [64, $user_ids, $peer_id, $total_count, $ts]
                const rawUsers = event[1];
                peerId = Number(event[2]);
                userIds = Array.isArray(rawUsers) ? rawUsers.map(Number) : [Number(rawUsers)];
                variant = "audiomessage";
                break;
            }
            default: {
                peerId = Number(event[1]);
                userIds = Array.isArray(event[2]) ? event[2].map(Number) : [Number(event[2])];
                break;
            }
        }

        const conv = await this.im.conversations._findConvFromApi(peerId);

        if (conv != null) {
            if (flags === 0) {
                if (conv.current_activity) {
                    userIds.forEach(uid => delete conv.current_activity[uid]);
                    if (this.im && this.im.messenger) {
                        this.im.messenger.update();
                    }
                }
            } else {
                await conv.setTyping(userIds, variant);
            }
        } else {
            console.error(`IM | Event ${code} | not found peer: `, peerId, userIds);
        }
    }

    async UserOnlineEvent(event) {
        const userId = Math.abs(event[1]);
        const now = Math.floor(Date.now() / 1000);

        if (this.im.fastChats) {
            this.im.fastChats.setUserOnline(userId, true);
        }

        if (window.im?.cached_profiles) {
            const prof = window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached
                ? window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(userId)
                : window.im.cached_profiles._findCachedProfileById(userId);
            if (prof) {
                prof.online = 1;
                if (prof.data) {
                    prof.data.online = 1;
                    if (!prof.data.last_seen) prof.data.last_seen = {};
                    prof.data.last_seen.time = now;
                }
            }
            if (Array.isArray(window.im.cached_profiles.cached_profiles)) {
                for (const p of window.im.cached_profiles.cached_profiles) {
                    if (p && Number(p.id) === userId) {
                        p.online = 1;
                        if (p.data) {
                            p.data.online = 1;
                            if (!p.data.last_seen) p.data.last_seen = {};
                            p.data.last_seen.time = now;
                        }
                    }
                }
            }
        }

        if (this.im.conversations) {
            if (Array.isArray(this.im.conversations.all_convs)) {
                for (const conv of this.im.conversations.all_convs) {
                    if (conv && conv.peer && Number(conv.peer.id) === userId) {
                        conv.peer.online = 1;
                        if (conv.peer.data) {
                            conv.peer.data.online = 1;
                            if (!conv.peer.data.last_seen) conv.peer.data.last_seen = {};
                            conv.peer.data.last_seen.time = now;
                        }
                    }
                }
            }
            const conv = this.im.conversations._findConv(userId);
            if (conv && conv.peer) {
                conv.peer.online = 1;
                if (conv.peer.data) {
                    conv.peer.data.online = 1;
                    if (!conv.peer.data.last_seen) conv.peer.data.last_seen = {};
                    conv.peer.data.last_seen.time = now;
                }
            }
            if (typeof this.im.conversations.update === 'function') {
                this.im.conversations.update();
            }
        }

        if (this.im.messenger) {
            if (Array.isArray(this.im.messenger.opened_tabs)) {
                for (const tab of this.im.messenger.opened_tabs) {
                    if (tab && tab.peer && Number(tab.peer.id) === userId) {
                        tab.peer.online = 1;
                        if (tab.peer.data) {
                            tab.peer.data.online = 1;
                            if (!tab.peer.data.last_seen) tab.peer.data.last_seen = {};
                            tab.peer.data.last_seen.time = now;
                        }
                    }
                }
            }
            const curChat = this.im.messenger.getCurrentChat();
            if (curChat && curChat.peer && Number(curChat.peer.id) === userId) {
                curChat.peer.online = 1;
                if (curChat.peer.data) {
                    curChat.peer.data.online = 1;
                    if (!curChat.peer.data.last_seen) curChat.peer.data.last_seen = {};
                    curChat.peer.data.last_seen.time = now;
                }
            }
            this.im.messenger.update();
            const win = this.im.messenger.getWindow();
            if (win && typeof win.update === 'function') {
                win.update();
            }
        }

        if (window.im && typeof window.im.updateTabs === 'function') {
            window.im.updateTabs();
        }
    }

    async UserOfflineEvent(event) {
        const userId = Math.abs(event[1]);
        const time = Number(event[4] || event[3] || Math.floor(Date.now() / 1000));

        if (this.im.fastChats) {
            this.im.fastChats.setUserOnline(userId, false);
        }

        if (window.im?.cached_profiles) {
            const prof = window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached
                ? window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(userId)
                : window.im.cached_profiles._findCachedProfileById(userId);
            if (prof) {
                prof.online = 0;
                if (prof.data) {
                    prof.data.online = 0;
                    prof.data.last_seen = { time: time };
                }
            }
            if (Array.isArray(window.im.cached_profiles.cached_profiles)) {
                for (const p of window.im.cached_profiles.cached_profiles) {
                    if (p && Number(p.id) === userId) {
                        p.online = 0;
                        if (p.data) {
                            p.data.online = 0;
                            p.data.last_seen = { time: time };
                        }
                    }
                }
            }
        }

        if (this.im.conversations) {
            if (Array.isArray(this.im.conversations.all_convs)) {
                for (const conv of this.im.conversations.all_convs) {
                    if (conv && conv.peer && Number(conv.peer.id) === userId) {
                        conv.peer.online = 0;
                        if (conv.peer.data) {
                            conv.peer.data.online = 0;
                            conv.peer.data.last_seen = { time: time };
                        }
                    }
                }
            }
            const conv = this.im.conversations._findConv(userId);
            if (conv && conv.peer) {
                conv.peer.online = 0;
                if (conv.peer.data) {
                    conv.peer.data.online = 0;
                    conv.peer.data.last_seen = { time: time };
                }
            }
            if (typeof this.im.conversations.update === 'function') {
                this.im.conversations.update();
            }
        }

        if (this.im.messenger) {
            if (Array.isArray(this.im.messenger.opened_tabs)) {
                for (const tab of this.im.messenger.opened_tabs) {
                    if (tab && tab.peer && Number(tab.peer.id) === userId) {
                        tab.peer.online = 0;
                        if (tab.peer.data) {
                            tab.peer.data.online = 0;
                            tab.peer.data.last_seen = { time: time };
                        }
                    }
                }
            }
            const curChat = this.im.messenger.getCurrentChat();
            if (curChat && curChat.peer && Number(curChat.peer.id) === userId) {
                curChat.peer.online = 0;
                if (curChat.peer.data) {
                    curChat.peer.data.online = 0;
                    curChat.peer.data.last_seen = { time: time };
                }
            }
            this.im.messenger.update();
            const win = this.im.messenger.getWindow();
            if (win && typeof win.update === 'function') {
                win.update();
            }
        }

        if (window.im && typeof window.im.updateTabs === 'function') {
            window.im.updateTabs();
        }
    }

    async ChatResetFlagsEvent(event) {
        const peerId = event[1];
        const mask = event[2];
        const conv = await this.im.conversations._findConvFromApi(peerId);
        if (conv && this.im.conversations) {
            this.im.conversations.update();
        }
        this.updateGlobalUnreadCounter();
    }

    async ChatReplaceFlagsEvent(event) {
        const peerId = event[1];
        const flags = event[2];
        const conv = await this.im.conversations._findConvFromApi(peerId);
        if (conv && this.im.conversations) {
            this.im.conversations.update();
        }
        this.updateGlobalUnreadCounter();
    }

    async ChatSetFlagsEvent(event) {
        const peerId = event[1];
        const mask = event[2];
        const conv = await this.im.conversations._findConvFromApi(peerId);
        if (conv && this.im.conversations) {
            this.im.conversations.update();
        }
        this.updateGlobalUnreadCounter();
    }

    async MassDeleteMessagesEvent(event) {
        const peerId = event[1];
        const localId = Number(event[2]);
        const _crs = await this.im.conversations._findConvFromApi(peerId);
        if (_crs && _crs.peer && _crs.peer._chunks) {
            _crs.peer._chunks.getMessages().forEach(msg => {
                const msgCmid = (msg.data && (msg.data.conversation_message_id || msg.data.local_id)) || msg.conversation_message_id || 0;
                const msgId = (msg.data && msg.data.id) || msg.id || 0;
                if ((msgCmid > 0 && msgCmid <= localId) || (msgId > 0 && msgId <= localId)) {
                    msg.setDeleted(true);
                }
            });
            _crs.peer._chunks._invalidateCache();
        }
        if (this.im.messenger) this.im.messenger.update();
        if (this.im.conversations) this.im.conversations.update();
        this.updateGlobalUnreadCounter();
    }

    async MassRestoreMessagesEvent(event) {
        const peerId = event[1];
        const localId = Number(event[2]);
        const _crs = await this.im.conversations._findConvFromApi(peerId);
        if (_crs && _crs.peer && _crs.peer._chunks) {
            _crs.peer._chunks.getMessages().forEach(msg => {
                const msgCmid = (msg.data && (msg.data.conversation_message_id || msg.data.local_id)) || msg.conversation_message_id || 0;
                const msgId = (msg.data && msg.data.id) || msg.id || 0;
                if ((msgCmid > 0 && msgCmid <= localId) || (msgId > 0 && msgId <= localId)) {
                    msg.restore();
                }
            });
            _crs.peer._chunks._invalidateCache();
        }
        if (this.im.messenger) this.im.messenger.update();
        if (this.im.conversations) this.im.conversations.update();
        this.updateGlobalUnreadCounter();
    }

    async CounterUpdateEvent(event) {
        const count = typeof event[1] === 'number' ? event[1] : Number(event[1]);
        if (!isNaN(count)) {
            this.updateGlobalUnreadCounter(count);
        } else {
            this.updateGlobalUnreadCounter();
        }
    }
}
