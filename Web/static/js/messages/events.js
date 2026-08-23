import { ChatMessage, ChatGeneralForm } from './components/messages.js';

export class EventHandler {
    constructor(im) {
        this.im = im;
        this.codes = {
            1: "ReplaceFlags",
            2: null,
            4: "NewMessageEvent",
            5: "EditMessageEvent",
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

    async NewMessageEvent(event) {
        const _msg = await ChatMessage.fromEvent(event, this.im);
        const _crs = await this.im.conversations._findConvFromApi(_msg.peer_id);

        if (!this.im.state.is_active && !_crs.peer.is_muted && _msg.shouldBeNotified()) {
            triggerMessageNotification(_crs, _msg);
        }

        setTimeout(() => {
            try {
                const found = _crs.peer._findMessageById(_msg.id);

                console.log(_crs, found)
                if (found == null) {
                    _crs.peer._pushNewMessage(_msg);
                } else {
                    found.hydrateFromEvent(_msg);

                    if (this.im.state.is_active) {
                        this.im.messenger.update();
                        if (this.im.messenger.view.isAtEnd()) {
                            this.im.messenger.view._scrollToEnd();
                        }
                    }
                }
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
