class EventHandler {
    constructor() {
        this.codes = {
            1: null, // MsgReplaceFlagsEvent
            2: null,
            4: this.NewMessageEvent
        };
    }

    async handle(event) {
        if (!Array.isArray(event)) return;

        const method = this.codes[event[0]];
        if (!method) {
            console.info('неизвестный ивент,  ', event[0]);
        };

        await method(event);
        /*switch (code) {
            case 1: { 
                const messageId = event[1];
                const flags = event[2];
                const peerId = event[3];
                console.log("Replace flags. MsgID:", messageId, "Flags:", flags, "Peer:", peerId);
                break;
            }

            case 2: { // MsgSetFlagsEvent
                const messageId = event[1];
                const mask = event[2];
                const peerId = event[3];
                console.log("Set flags (mask):", mask, "MsgID:", messageId);
                break;
            }

            case 3: { // MsgResetFlagsEvent
                const messageId = event[1];
                const mask = event[2];
                const peerId = event[3];
                console.log("Reset flags (mask):", mask, "для сообщения:", messageId);
                break;
            }

            case 4: { // NewMessageEvent
                this._messageSent(event)

                break;
            }

            case 5: { // UpdateMessageEvent
                const [, id, mask, peer, ts, text, attachments] = event;
                console.log(`Message ${id} update: ${text}`);
                break;
            }

            case 6: { // ReadIncomeBeforeEvent
                const peerId = event[1];
                const localId = event[2];
                console.log(`Incomes in chat ${peerId} checked as read until ID ${localId}`);
                break;
            }

            case 7: { // ReadOutcomeBeforeEvent
                const peerId = event[1];
                const localId = event[2];
                console.log(`Outcomes in chat ${peerId} checked as read until ID ${localId}`);
                break;
            }

            case 8: { // GotOnlineEvent
                const userId = event[1];
                const extra = event[2];
                const timestamp = event[3];
                console.log(`User ${userId} online (From: ${extra & 0xFF})`);
                break;
            }

            case 9: { // GotOfflineEvent
                const userId = event[1];
                const flags = event[2];
                console.log(`User ${userId} offline. Reason: ${flags === 1 ? 'timeout' : 'logout'}`);
                break;
            }

            case 10: { // ChatResetFlagsEvent
                const peerId = event[1];
                const mask = event[2];
                break;
            }

            case 11: { // ChatReplaceFlagsEvent
                const peerId = event[1];
                const flags = event[2];
                break;
            }

            case 12: { // ChatSetFlagsEvent
                const peerId = event[1];
                const mask = event[2];
                break;
            }

            case 13: { // MassDeleteMessagesEvent
                const peerId = event[1];
                const localId = event[2];
                console.log(`Mass delete messages ${peerId} until ${localId}`);
                break;
            }

            case 51: { // ChatSomethingChangedEvent
                const chatId = event[1];
                const self = event[2];
                console.log(`Something happened in chat ${chatId}${self ? " and its triggered by me" : ""}`);
                break;
            }

            case 61: { // IsDMTypingEvent
                const userId = event[1];
                const flags = event[2]; // 1 - пишет, 2 - аудио
                console.log(`${userId} is ${flags == 1 ? "typing" : "recording voice message"} in DMs`);
                break;
            }

            case 62: { // IsChatTypingEvent
                const userId = event[1];
                const chatId = event[2];
                const flags = event[3];
                console.log(`${userId} is ${flags == 1 ? "typing" : "recording voice message"} in chat ${chatId}`);
                break;
            }

            case 70: { // MakingACallEvent
                const userId = event[1];
                const callId = event[2];
                console.log(`Received incoming call ${callId} from ${userId}`);
                break;
            }

            case 80: { // CounterUpdateEvent
                const count = event[1];
                console.log("Unreads counter:", count);
                break;
            }

            case 114: { // NotificationSetEvent
                const peerId = event[1];
                const sound = event[2];
                const disabledUntil = event[3];
                console.log(`Notification settings updated for peer ${peerId}: sound is ${sound}`);
                break;
            }

            default:
                console.log("unknown event", code, event);
        }*/
    }

    async NewMessageEvent(event) {
        const _msg = ChatMessage.fromEvent(event);

        // finding conversation
        const _crs = await window.im.conversations._findConvFromApi(_msg.peer_id);
        const found = _crs.peer._findMessageById(_msg);
        if (found) {
            _crs.peer._pushNewMessage(_msg);
        } else {
            found.data = _msg.data;
        }
    }
}
