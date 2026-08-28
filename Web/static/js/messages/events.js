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
            const found = conv.peer._findMessageById(msgId);
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
                const found = _crs.peer._findMessageById(msgId);
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
                const found = _crs.peer._findMessageById(msgId);
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
        const localId = event[2];

        const _crs = await this.im.conversations._findConvFromApi(peerId);
        if (!_crs) return;

        if (_crs.peer) {
            _crs.peer.in_read = localId;
        }
        if (_crs._conversation) {
            _crs._conversation.in_read = localId;
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
            const msgId = (msg.data && (msg.data.local_id || msg.data.id)) || msg.id || 0;
            const fromId = msg.data ? msg.data.from_id : (msg.from_id || 0);
            if (msgId <= localId && fromId != currentUserId) {
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
        const localId = event[2];

        const _crs = await this.im.conversations._findConvFromApi(peerId);
        if (!_crs) return;

        if (_crs.peer) {
            _crs.peer.out_read = localId;
        }
        if (_crs._conversation) {
            _crs._conversation.out_read = localId;
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
            const msgId = (msg.data && (msg.data.local_id || msg.data.id)) || msg.id || 0;
            const fromId = msg.data ? msg.data.from_id : (msg.from_id || 0);
            if (msgId <= localId && fromId == currentUserId) {
                if (msg.data) msg.data.read_state = 1;
            }
        });

        if (this.im.messenger) {
            this.im.messenger.update();
        }
    }



    async NewMessageEvent(event) {
        const _msg = await ChatMessage.fromEvent(event, this.im);
        const _crs = await this.im.conversations._findConvFromApi(_msg.peer_id);

        if (!this.im.state.is_active && !_crs.peer.isMuted() && _msg.shouldBeNotified()) {
            triggerMessageNotification(_crs, _msg);
        }

        setTimeout(() => {
            try {
                const found = _crs.findMessageById(_msg.id);

                console.log(_crs, found)
                if (found == null) {
                    _crs.pushMessage(_msg);
                } else {
                    found.hydrateFromEvent(_msg);

                    if (this.im.state.is_active) {
                        this.im.messenger.update();
                        if (this.im.messenger.view.isAtEnd()) {
                            this.im.messenger.view._scrollToEnd();
                        }
                    }
                }

                if (this.im.state.is_active && this.im.messenger) {
                    const activeChat = this.im.messenger.getCurrentChat();
                    if (activeChat && activeChat.peer && activeChat.peer.id == _msg.peer_id) {
                        _crs.peer.read();
                    }
                }

                this.updateGlobalUnreadCounter();
            } catch (e) {
                console.error(e);
            }
        }, 100);
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

        const found = _crs.peer._findMessageById(msgId);
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
