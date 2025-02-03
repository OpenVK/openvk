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

        const form_data = new FormData
        Object.entries(params).forEach(fd => {
            form_data.append(fd[0], fd[1])
        })

        const __url_params = new URLSearchParams
        __url_params.append("v", "5.200")
        if(window.openvk.current_id != 0) {
            __url_params.append("auth_mechanism", "roaming")
        }

        const url = `/method/${method}?${__url_params.toString()}`
        const res = await fetch(url, {
            method: "POST",
            body: form_data,
        })
        const json_response = await res.json()

        if(json_response.response) {
            return json_response.response
        } else {
            throw new Error(json_response.error_msg)
        }
    }
}
