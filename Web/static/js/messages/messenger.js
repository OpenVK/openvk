function MessengerViewModel(initialMessages = []) {
    window.messages     = ko.observableArray(initialMessages);
    this.messages       = window.messages;
    this.messageContent = ko.observable("");
    
    this.sendMessage = model => {
        if(model.messageContent() === "") return false;
        
        window.Msg.sendMessage(model.messageContent());
        model.messageContent("");
    };
    this.loadHistory = _ => {
        window.Msg._loadHistory();
    };
    
    this.onMessagesScroll   = (model, e) => {
        if(e.target.scrollTop < 21)
            model.loadHistory();
    };
    this.onTextareaKeyPress = (model, e) => {
        if(e.which === 13) {
            if(!e.metaKey && !e.shiftKey) {
                let ta = u("textarea[name=message]").nodes[0];
                ta.blur(); //Fix update
                model.sendMessage(model);
                ta.focus();
                
                return false;
            }
        }
        
        return true;
    };
}

window.messenger = new (class {
    constructor() {}

    
})()
