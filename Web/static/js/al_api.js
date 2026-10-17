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
                            try {
                                if (xhr.response && xhr.response.byteLength > 0) {
                                    let resp = msgpack.decode(new Uint8Array(e.target.response));
                                    if (typeof resp.error !== "undefined") {
                                        rej(resp.error);
                                        return;
                                    } else if (typeof resp.result !== "undefined") {
                                        resolv(resp.result);
                                        return;
                                    }
                                }
                                if (xhr.status >= 200 && xhr.status < 300) {
                                    resolv(null);
                                } else {
                                    rej({
                                        "code": xhr.status || -1,
                                        "message": `HTTP Error ${xhr.status}`,
                                    });
                                }
                            } catch (e) {
                                rej({
                                    "code": xhr.status || -1,
                                    "message": xhr.status ? `HTTP Error ${xhr.status}` : `Network error`,
                                    "error": e
                                });
                            }
                        };

                        xhr.onerror = () => {
                            rej({
                                "code": -1,
                                "message": "Network connection error"
                            });
                        };

                        xhr.ontimeout = () => {
                            rej({
                                "code": -1,
                                "message": "Request timeout"
                            });
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

const _pageLoaded = () => {
    return new Promise((resolve) => {
        if (document.readyState === 'complete') {
            resolve();
        } else {
            window.addEventListener('load', () => resolve());
        }
    });
};

window.OVKAPI = new class {
    async call(method, params = {}, return_exception = false, version = null) {
        if(!method) {
            return
        }

        const api_version = version || params?.v || "5.86";

        const form_data = new FormData
        Object.entries(params || {}).forEach(fd => {
            if (fd[0] !== "v") {
                form_data.append(fd[0], fd[1])
            }
        })

        const __url_params = new URLSearchParams
        __url_params.append("v", api_version)

        if (!window.openvk) {
            await _pageLoaded();
        }

        if (window.openvk.current_id != 0) {
            __url_params.append("auth_mechanism", "roaming")
        }

        const url = `/method/${method}?${__url_params.toString()}`
        const res = await fetch(url, {
            method: "POST",
            body: form_data,
        })
        const json_response = await res.json()

        if(json_response.response || json_response.response == 0) {
            return json_response.response
        } else {
            if (return_exception == true) {
                return json_response;
            }

            const errObj = json_response.error || json_response;
            const msg = errObj.error_msg || json_response.error_msg || "API Error";
            const err = new Error(msg);
            err.error_code = Number(errObj.error_code || json_response.error_code || 0);
            err.error = errObj;
            throw err;
        }
    }
}
