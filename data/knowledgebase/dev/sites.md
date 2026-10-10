OpenVK-KB-Heading: Websites and OAuth

# Websites and OAuth

OpenVK supports user authentication for external websites and web applications via the **OAuth 2.0** protocol.

Using OAuth, you can implement OpenVK sign-in on your website, fetch user profile data, and perform authorized actions via the API.

---

## Authorization Workflow (OAuth 2.0 Web Flow)

### 1. Redirecting User to Authorization Page

To request user authorization, redirect the user's browser to the `/authorize` endpoint:

```http
GET /authorize?client_name=MyWebsite&redirect_uri=https://example.com/oauth_callback&response_type=php HTTP/1.1
Host: openvk.instance
```

#### `/authorize` Request Parameters
| Parameter | Type | Required | Description |
| --- | --- | --- | --- |
| `client_name` | string | **Yes** | Name of your website or service (shown to the user in the confirmation prompt). |
| `client_id` | integer | No | Client ID from the OpenVK client registry (if specified, `client_name` is optional). |
| `redirect_uri` | string | **Yes** | HTTPS/HTTP URL on your website where the user will be redirected with the token. |
| `response_type` | string | No | Token return format: `"php"` (in query parameters, default) or `"token"` (in URL hash). |
| `revoke` | integer | No | `1` — force revocation of older tokens and issue a new one. |
| `prefers_postMessage` | integer | No | `1` — for popup windows. Sends the token via `window.opener.postMessage`. |
| `accepts_stale` | integer | No | `1` — reuse an existing active token for this `client_name` if already issued. |

---

### 2. User Confirmation

The user sees a permission prompt: "Application **MyWebsite** is requesting access to your account".

Upon clicking "Allow", the OpenVK server redirects the user to the specified `redirect_uri`.

---

### 3. Receiving the Access Token

#### With `response_type=php` (Server-side handling)
OpenVK redirects the user with token parameters in the URL query string:
```
https://example.com/oauth_callback?access_token=a1b2c3d4e5f6...&expires_in=0&user_id=1
```

Your backend extracts `access_token` and `user_id` from the request parameters and stores it for API usage.

#### With `response_type=token` (Client-side / SPA handling)
OpenVK redirects the user with token parameters in the URL hash fragment (after `#`):
```
https://example.com/oauth_callback#access_token=a1b2c3d4e5f6...&expires_in=0&user_id=1
```

Your client-side JavaScript reads the token from `window.location.hash`:
```javascript
const hash = window.location.hash.substring(1);
const params = new URLSearchParams(hash);
const accessToken = params.get("access_token");
const userId = params.get("user_id");
```

---

### 4. Popup Authorization (Popup + postMessage)

When opening the authorization page in a popup window (`window.open`), specify `prefers_postMessage=1`:

```javascript
// Open authorization popup
const authWindow = window.open(
    "https://openvk.instance/authorize?client_name=MyWebsite&redirect_uri=https://example.com/callback&response_type=php&prefers_postMessage=1",
    "openvk_auth",
    "width=600,height=450"
);

// Listen for token via postMessage
window.addEventListener("message", function(event) {
    if (event.origin === "https://openvk.instance" || event.origin === "https://example.com") {
        if (event.data && event.data.access_token) {
            console.log("Access token:", event.data.access_token);
            console.log("User ID:", event.data.user_id);
            if (authWindow) authWindow.close();
        }
    }
});
```

---

## Calling API Methods on Behalf of User

After obtaining an `access_token`, your website can request profile info and perform other actions via the OpenVK API:

```http
GET /method/users.get?user_ids=1&fields=photo_200,sex,bdate&v=5.80 HTTP/1.1
Host: openvk.instance
Authorization: Bearer a1b2c3d4e5f6...
```