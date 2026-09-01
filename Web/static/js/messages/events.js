//import { ChatMessage, ChatGeneralForm } from './components/messages.js';
const { ChatGeneralForm, ChatMessage } = await es6import_Im(import.meta.url, './components/messages.js');

export class EventHandler {
    constructor(im) {
        this.im = im;
        this.codes = {
            1: "ReplaceFlags",
            2: "SetFlags",
            3: "ResetFlags",
            4: "NewMessageEvent",
            5: "EditMessageEvent",
            6: "ReadIncomeBeforeEvent",
            7: "ReadOutcomeBeforeEvent",
            51: "ChatUpdateEvent",
            61: "TypingEvent",
        };
    }

    async handle(event) {
        if (!Array.isArray(event)) return;

        const method = this.codes[event[0]];
        console.log("lp event: ", event)
        console.log(this.im);
        if (!method) {
            console.info('unknown event,  ', event[0]);
        } else {
            await this[method](event);
        }
    }

    async ReplaceFlags(event) {
        const msgId = event[1]
        const peerId = event[3]
        const flags = event[2]

        // message is deleted
        if (flags == 128) {
            const conv = await this.im.conversations._findConv(peerId);
            console.log(conv);
            const found = conv.peer._chunks._findMessageById(msgId);
            console.log(found);

            if (found != null) {
                found.setDeleted(false);
                this.im.messenger.update();
            }
        }
    }

    updateGlobalUnreadCounter() {
        try {
            if (this.im && this.im.state && typeof this.im.state._updateCounter === 'function') {
                const count = this.im.state.getUnreadCounter();
                this.im.state._updateCounter(count);
            }
        } catch (e) {
            console.error("Error updating global unread counter:", e);
        }
    }

    async SetFlags(event) {
        const msgId = event[1];
        const flags = event[2];
        const peerId = event[3];

        if (flags & 1) { // FlagUnread
            const _crs = await this.im.conversations._findConvFromApi(peerId);
            if (_crs && _crs.peer) {
                const found = _crs.peer._chunks._findMessageById(msgId);
                if (found && found.data) {
                    found.data.read_state = 0;
                    if (this.im.messenger) this.im.messenger.update();
                }
            }
            this.updateGlobalUnreadCounter();
        }
    }

    async ResetFlags(event) {
        const msgId = event[1];
        const flags = event[2];
        const peerId = event[3];

        if (flags & 1) { // FlagUnread
            const _crs = await this.im.conversations._findConvFromApi(peerId);
            if (_crs && _crs.peer) {
                const found = _crs.peer._chunks._findMessageById(msgId);
                if (found && found.data) {
                    found.data.read_state = 1;
                    if (this.im.messenger) this.im.messenger.update();
                }
            }
            this.updateGlobalUnreadCounter();
        }
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
            const msgCmid = (msg.data && (msg.data.conversation_message_id || msg.data.local_id)) || msg.conversation_message_id || 0;
            const msgId = (msg.data && msg.data.id) || msg.id || 0;
            const fromId = msg.data ? msg.data.from_id : (msg.from_id || 0);
            if (((msgCmid > 0 && msgCmid <= localId) || (msgId > 0 && msgId <= localId)) && fromId != currentUserId) {
                if (msg.data) msg.data.read_state = 1;
            }
        });

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
            const msgCmid = (msg.data && (msg.data.conversation_message_id || msg.data.local_id)) || msg.conversation_message_id || 0;
            const msgId = (msg.data && msg.data.id) || msg.id || 0;
            const fromId = msg.data ? msg.data.from_id : (msg.from_id || 0);
            if (((msgCmid > 0 && msgCmid <= localId) || (msgId > 0 && msgId <= localId)) && fromId == currentUserId) {
                if (msg.data) msg.data.read_state = 1;
            }
        });

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



    async NewMessageEvent(event) {
        const _msg = await ChatMessage.fromEvent(event, this.im);
        const _crs = await this.im.conversations._findConvFromApi(_msg.peer_id);
        if (!_crs) return;

        const currentUserId = window.openvk ? window.openvk.current_id : this.im.state.getId();
        const isSelf = _msg.from_id == currentUserId;
        const activeChat = (this.im.messenger && typeof this.im.messenger.getCurrentChat === 'function') ? this.im.messenger.getCurrentChat() : null;
        const isActiveChatOpen = this.im.state.is_active && activeChat && activeChat.peer && activeChat.peer.id == _msg.peer_id;

        if (!isActiveChatOpen && !isSelf && !_crs.peer.isMuted()) {
            triggerMessageNotification(_crs, _msg);
        }

        setTimeout(() => {
            try {
                const found = _crs.findMessageById(_msg.id);

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
                    this.im.messenger.update();
                    if (this.im.messenger.view && this.im.messenger.view.isAtEnd()) {
                        this.im.messenger.view._scrollToEnd();
                    }
                }

                if (isActiveChatOpen) {
                    _crs.peer.read();
                }

                if (this.im.fastChats) {
                    this.im.fastChats.update();
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
        const _peerId = event[1];
        const _userIds = event[2];
        let userIds = null;

        if (Array.isArray(_userIds)) {
            userIds = _userIds;
        } else {
            userIds = String(_userIds).split(",");
        }

        const conv = await this.im.conversations._findConvFromApi(_peerId);

        if (conv != null) {
            await conv.setTyping(userIds);
        } else {
            console.error("IM | Event 61 | not found peer: ", _peerId, userIds)
        }
    }
}
