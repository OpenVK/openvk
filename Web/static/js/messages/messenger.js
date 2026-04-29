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

class Messenger {
    constructor(messages = []) {
        this.ko     = ko.applyBindings(new MessengerViewModel(messages));
        this.appEl  = document.querySelector(".messenger-app--messages");
        this.offset = 0;
        
        this._loadHistory();
        this._setupListener();
        this.appEl.scrollTop = this.appEl.scrollHeight;
    }
    
    _loadHistory() {
        let xhr = new XMLHttpRequest();
        xhr.open("GET", "/im/api/messages" + {$correspondent->getId()} + `/${ this.offset }.json`, false);
        xhr.send();
        
        let messages    = JSON.parse(xhr.responseText);
        let lastMessage = messages[messages.length - 1];
        if(typeof lastMessage !== "undefined") this.offset = lastMessage.uuid;
        
        this.prependMessages(messages);
    }
    
    _setupListener() {
        let listenLongpool = () => {
            let xhr = new XMLHttpRequest();
            xhr.open("GET", "/im12", true);
            xhr.onload = () => {
                let data = JSON.parse(xhr.responseText);
                data.forEach(event => {
                    event = event.event;
                    if(event.type !== "newMessage")
                        return;
                    else if(event.message.sender.id !== {$correspondent->getId()})
                        return;
                    else if(this.offset >= event.message.uuid)
                        return void(console.warn("Gay message recieved, skipping. [-WHeterosexual]"));
                    
                    this.addMessage(event.message);
                    this.offset = event.message.uuid;
                });
                
                listenLongpool();
            };
            xhr.send();
        };
        
        listenLongpool();
    }
    
    appendMessages(messages) {
        messages.forEach(m => window.messages.push(m));
    }
    
    prependMessages(messages) {
        messages.forEach(m => window.messages.unshift(m));
    }
    
    addMessage(message, scroll = true) {
        this.appendMessages([message]);
        
        if(scroll) {
            this.appEl.scrollTop = this.appEl.scrollHeight;
        }
    }
    
    _patchMessage(tempId, message) {
        for(let i = window.messages().length - 1; i > -1; i--) {
            let msg = window.messages()[i];
            if(typeof msg._tuid === "undefined")
                return;
            else if(msg._tuid !== tempId)
                return;
            
            window.messages.valueWillMutate();
            window.messages()[i] = message;
            window.messages.valueHasMutated();
        }
    }
    
    _newSelfMessage(content = "...") {
        return {
            "sender": {
                "link": {$thisUser->getURL()},
                "avatar": {$thisUser->getAvatarUrl()},
                "name": {$thisUser->getFirstName()}
            },
            "timing": {
                "sent": window.API.Service.getTime(),
                "edited": null
            },
            "text": content,
            "read": false,
            "attachments": [],
            "_tuid": Math.ceil(performance.now())
        };
    }
    
    _newReplyMessage(content = "...") {
        return {
            "sender": {
                "link": {$correspondent->getURL()},
                "avatar": {$correspondent->getAvatarUrl()},
                "name": {$correspondent->getFirstName()}
            },
            "timing": {
                "sent": window.API.Service.getTime(),
                "edited": null
            },
            "text": content,
            "read": true,
            "_tuid": Math.ceil(performance.now())
        };
    }
    
    newMessage(content) {
        let msg = this._newSelfMessage(content);
        msg.timing.sent = "<img src=\"/assets/packages/static/openvk/img/loading_mini.gif\">";
        this.addMessage(msg);
        
        return msg._tuid;
    }
    
    newReply(content) {
        let msg = this._newReplyMessage(content);
        this.addMessage(msg);
        
        return msg._tuid;
    }
    
    newReplies(replies) {
        replies.forEach(this.newReply);
    }
    
    sendMessage(content) {
        console.debug("New outcoming message. Pushing preview to local stack.");
        let tempId = this.newMessage(escapeHtml(content));
        
        let msgData = new FormData();
        msgData.set("content", content);
        msgData.set("hash", {$csrfToken});
        
        let that = this;
        let xhr  = new XMLHttpRequest();
        xhr.open("POST", "/im/api/messages" + {$correspondent->getId()} + "/create.json", true);
        xhr.onreadystatechange = (function() {
            if(this.readyState !== 4)
                return;
            else if(this.status !== 202) {
                console.error("Message was not sent.");
                that._patchMessage(tempId, {
                    sender: {
                        avatar: "/assets/packages/static/openvk/img/oof.apng",
                        id: -1,
                        link: "/support/manpages/messages/not-delivered.html",
                        name: tr("messages_error_1")
                    },
                    text: tr("messages_error_1_description"),
                    timing: {
                        edited: null,
                        sent: "???"
                    },
                    uuid: -4096
                });
                
                return;
            }
            
            console.debug("Message sent, updating view.");
            that._patchMessage(tempId, JSON.parse(xhr.responseText));
        });
        xhr.send(msgData);
        console.debug("Message sent, awaiting response.");
    }
}
