window.API = new Proxy(Object.create(null), {
    get(apiObj, name, recv) {
        if(name === "Types")
            return apiObj.Types;
        
        return new Proxy(new window.String(name), {
            get(classSymbol, method, recv) {
                return ((...args) => {
                    return new Promise((resolv, rej) => {
                        let xhr = new XMLHttpRequest();
                        xhr.open("POST", "/rpc", true);
                        xhr.responseType = "arraybuffer";

                        xhr.onload = e => {
                            let resp = msgpack.decode(new Uint8Array(e.target.response));
                            if(typeof resp.error !== "undefined")
                                rej(resp.error);
                            else
                                resolv(resp.result);
                        };

                        xhr.send(msgpack.encode({
                            "brpc": 1,
                            "method": `${classSymbol.toString()}.${method}`,
                            "params": args
                        }));
                    });
                })
            }
        });
    }
});

window.API.Types = {};
window.API.Types.Message = (class Message {
    
});