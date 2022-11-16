const perms = {
    friends: [tr("appjs_act_friends"), tr("appjs_act_friends_desc")],
    wall: [tr("appjs_act_wall"), tr("appjs_act_wall_desc")],
    messages: [tr("appjs_act_messages"), tr("appjs_act_messages_desc")],
    groups: [tr("appjs_act_groups"), tr("appjs_act_groups_desc")],
    likes: [tr("appjs_act_likes"), tr("appjs_act_likes_desc")]
}

function escapeHtml(unsafe)
{
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&apos;");
}

function toQueryString(obj, prefix) {
    var str = [],
        p;
    for (p in obj) {
        if (obj.hasOwnProperty(p)) {
            var k = prefix ? prefix + "[" + p + "]" : p,
                v = obj[p];
            str.push((v !== null && typeof v === "object") ?
                serialize(v, k) :
                encodeURIComponent(k) + "=" + encodeURIComponent(v));
        }
    }
    return str.join("&");
}

function handleWallPostRequest(event) {
    let mBoxContent = `
        <b>${tr("app")} <i>${window.appTitle}</i> ${tr("appjs_wall_post_desc")}:</b><br/>
        <p style="padding: 8px; border: 1px solid gray;">${escapeHtml(event.data.text)}</p>
    `;

    MessageBox(tr("appjs_wall_post"), mBoxContent, [tr("appjs_act_allow"), tr("appjs_act_disallow")], [
        async () => {
            let id = await API.Wall.newStatus(event.data.text);
            event.source.postMessage({
                transaction: event.data.transaction,
                ok: true,
                post: {
                    id: id,
                    text: event.data.text
                }
            }, '*');
        },
        () => {
            event.source.postMessage({
                transaction: event.data.transaction,
                ok: false,
                error: "User cancelled action"
            }, '*');
        }
    ]);
}

async function handleVkApiRequest(event) {
    let method = event.data.method;
    if(!/^[a-z]+\.[a-z0-9]+$/.test(method)) {
        event.source.postMessage({
            transaction: event.data.transaction,
            ok: false,
            error: "API Method name is invalid"
        }, '*');
        return;
    }

    let domain = method.split(".")[0];
    if(domain === "newsfeed")
        domain = "wall";

    if(!window.appPerms.includes(domain)) {
        if(typeof perms[domain] === "undefined") {
            event.source.postMessage({
                transaction: event.data.transaction,
                ok: false,
                error: "This API method is not supported"
            }, '*');
            return;
        }

        let dInfo   = perms[domain];
        let allowed = false;
        await (new Promise(r => {
            MessageBox(
                tr("appjs_act_request"),
                `<p>${tr("app")} <b>${window.appTitle}</b> ${tr("appjs_act_requests")} <b>${dInfo[0]}</b>. ${tr("appjs_act_can")} <b>${dInfo[1]}</b>.`,
                [tr("appjs_act_allow"), tr("appjs_act_disallow")],
                [
                    () => {
                        API.Apps.updatePermission(window.appId, domain, "yes").then(() => {
                            window.appPerms.push(domain);
                            allowed = true;
                            r();
                        });
                    },
                    () => {
                        r();
                    }
                ]
            )
        }));

        if(!allowed) {
            event.source.postMessage({
                transaction: event.data.transaction,
                ok: false,
                error: "No permission to use this method"
            }, '*');
            return;
        }
    }

    let params      = toQueryString(event.data.params);
    let apiResponse = await (await fetch("/method/" + method + "?auth_mechanism=roaming&" + params)).json();
    if(typeof apiResponse.error_code !== "undefined") {
        event.source.postMessage({
            transaction: event.data.transaction,
            ok: false,
            error: apiResponse.error_code + ": " + apiResponse.error_msg
        }, '*');
        return;
    }

    event.source.postMessage({
        transaction: event.data.transaction,
        ok: true,
        response: apiResponse.response
    }, '*');
}

function handlePayment(event) {
    let payload = event.data;
    if(payload.outSum < 0) {
        event.source.postMessage({
            transaction: payload.transaction,
            ok: false,
            error: "negative sum"
        }, '*');
        
        return;
    }

    MessageBox(
        tr("appjs_payment"),
        `
            <p>${tr("appjs_payment_intro")} <b>${window.appTitle}</b>.<br/>${tr("appjs_order_items")}: <b>${payload.description}</b></p>
            <p>${tr("appjs_payment_total")}: <big><b>${payload.outSum}</b></big> ${tr("points_count")}.
        `,
        [tr("appjs_payment_confirm"), tr("cancel")],
        [
            async () => {
                let sign;
                try {
                    sign = await API.Apps.pay(window.appId, payload.outSum);
                } catch(e) {
                    MessageBox(tr("error"), tr("appjs_err_funds"), ["OK"], [Function.noop]);

                    event.source.postMessage({
                        transaction: payload.transaction,
                        ok: false,
                        error: "Payment error[" + e.code + "]: " + e.message
                    }, '*');

                    return;
                }

                event.source.postMessage({
                    transaction: payload.transaction,
                    ok: true,
                    outSum: payload.outSum,
                    description: payload.description,
                    signature: sign
                }, '*');
            },
            () => {
                event.source.postMessage({
                    transaction: payload.transaction,
                    ok: false,
                    error: "User cancelled payment"
                }, '*');
            }
        ]
    )
}

async function onNewMessage(event) {
    if(event.source !== appFrame.contentWindow)
        return;

    let payload = event.data;
    switch(payload["@type"]) {
        case "VkApiRequest":
            handleVkApiRequest(event);
            break;

        case "WallPostRequest":
            handleWallPostRequest(event);
            break;

        case "PaymentRequest":
            handlePayment(event);
            break;

        case "UserInfoRequest":
            event.source.postMessage({
                transaction: payload.transaction,
                ok: true,
                user: await API.Apps.getUserInfo()
            }, '*');
            break;

        default:
            event.source.postMessage({
                transaction: payload.transaction,
                ok: false,
                error: "Unknown query type"
            }, '*');
    }
}

window.addEventListener("message", onNewMessage);