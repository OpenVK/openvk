import { ChatMessage, ChatGeneralForm } from './messages.js';

export class EventHandler {
  constructor() {
    this.codes = {
      1: null,
      2: null,
      4: this.NewMessageEvent,
    };
  }

  async handle(event) {
    if (!Array.isArray(event)) return;

    const method = this.codes[event[0]];
    if (!method) {
      console.info('неизвестный ивент,  ', event[0]);
    }

    await method(event);
  }

  async NewMessageEvent(event) {
    const _msg = ChatMessage.fromEvent(event);

    setTimeout(async () => {
      const _crs = await window.im.conversations._findConvFromApi(_msg.peer_id);
      const found = _crs.peer._findMessageById(_msg.id);
      if (found == null) {
        _crs.peer._pushNewMessage(_msg);
      } else {
        found.data = _msg.data;
        window.im.messenger.view._triggerUpdate();
      }
    }, 100);
  }
}
