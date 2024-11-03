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

window.OVKAPI = new class {
    async call(method, params) {
        if(!method) {
            return
        }

        const url = `/method/${method}?auth_mechanism=roaming&${new URLSearchParams(params).toString()}`
        const res = await fetch(url)
        const json_response = await res.json()

        if(json_response.response) {
            return json_response.response
        } else {
            throw new Exception(json_response.error_msg)
        }
    }
}
