import { ChatMessage, ChatGeneralForm } from './messages.js';

export class EventHandler {
    constructor() {
        this.codes = {
            1: this.ReplaceFlags,
            2: null,
            4: this.NewMessageEvent,
        };
    }

    async handle(event) {
        if (!Array.isArray(event)) return;

        const method = this.codes[event[0]];
        console.log("lp event: ", event)
        if (!method) {
            console.info('неизвестный ивент,  ', event[0]);
        }

        await method(event);
    }

    async ReplaceFlags(event) {
        const msgId = event[1]
        const peerId = event[3]
        const flags = event[2]

        // message is deleted
        if (flags == 128) {
            const conv = await window.im.conversations._findConvFromApi(peerId);
            console.log(conv);
            const found = conv.peer._findMessageById(msgId);
            console.log(found);

            if (found != null) {
                found.setDeleted(false);
                window.im.messenger.view._triggerUpdate();
            }
        }
    }

    async NewMessageEvent(event) {
        const _msg = await ChatMessage.fromEvent(event);
        console.log(_msg)
        const _crs = await window.im.conversations._findConvFromApi(_msg.peer_id);

        if (!window.im.is_active && !_crs.peer.is_muted && _msg.shouldBeNotified()) {
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

                    if (window.im.is_active) {
                        window.im.messenger.view._triggerUpdate();
                        window.im.messenger.view._scrollToEnd();
                    }
                }
            } catch (e) {
                console.error(e);
            }
        }, 100);
    }
}
