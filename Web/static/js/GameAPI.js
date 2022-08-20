const VKAPI = Object.create(null);

VKAPI._makeRequest = function(type, params) {
    return new Promise((succ, fail) => {
        let uuid    = crypto.randomUUID();
        let request = params;
        request["@type"]    = type;
        request.transaction = uuid;

        let listener = e => {
            if (e.source !== window.parent)
                return;

            if (e.data.transaction !== uuid)
                return;

            let resp = e.data;
            let ok = resp.ok;
            delete resp.transaction;
            delete resp.ok;

            (ok ? succ : fail)(resp);
            window.removeEventListener("message", listener);
        };

        let origin = document.referrer.split("/").slice(0, 3).join("/");
        window.addEventListener("message", listener);
        window.parent.postMessage(request, origin);
    });
}

VKAPI.getUser = function() {
    return VKAPI._makeRequest("UserInfoRequest", {});
}

VKAPI.makePost = function(text) {
    return VKAPI._makeRequest("WallPostRequest", {
        text: text
    });
}

VKAPI.execute = function(method, params) {
    return VKAPI._makeRequest("VkApiRequest", {
        method: method,
        params: params
    });
}

VKAPI.buy = function(price, item) {
    return VKAPI._makeRequest("PaymentRequest", {
        outSum: price,
        description: item
    });
}