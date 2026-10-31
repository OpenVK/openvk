OpenVK-KB-Heading: Games and Apps in OpenVK

# Games and Apps in OpenVK

This section covers the development, configuration, and integration of embedded applications (IFrame apps, HTML5 games, and mini-apps) running inside the OpenVK platform.

---

## 1. Creating an Application

To create a new application:

1. Navigate to [Create Application](/editapp?act=create) (or go to **Apps** &rarr; **Manage**).
2. Fill in the required application settings:
   - **Title** — Displayed in the catalog and game window header.
   - **Description** — Overview of app features and mechanics.
   - **IFrame URL** — Direct HTTPS URL of your web application or game.
   - **Icon / Avatar** — Application cover image for the catalog.
   - **News Note** *(optional)* — Link to a note containing patch notes or updates.
3. Upon creation, your app receives a unique `app_id` and becomes accessible at `/app{id}`.

---

## 2. Integration Architecture (IFrame and postMessage)

Applications load inside a sandboxed `<iframe>` container on the `/app{id}` page.

Communication between the OpenVK host window and your IFrame application is established via standard browser `window.postMessage`.

### Sending Requests from the Application

To invoke platform features, send a message to `window.parent`:

```javascript
window.parent.postMessage({
    "@type": "VkApiRequest", // Request type
    "transaction": "tx_12345", // Unique transaction identifier
    "method": "users.get", // OpenVK API method
    "params": {
        "v": "5.80"
    }
}, "*");
```

### Handling Responses in the Application

```javascript
window.addEventListener("message", function(event) {
    var data = event.data;
    if (data.transaction === "tx_12345") {
        if (data.ok) {
            console.log("Success response:", data.response);
        } else {
            console.error("Error:", data.error);
        }
    }
});
```

---

## 3. Supported Request Types (`@type`)

### `VkApiRequest` — Invoking API Methods

Executes OpenVK API methods on behalf of the currently logged-in user.

If a method requires specific permissions (`friends`, `wall`, `messages`, `groups`, `likes`), OpenVK prompts the user with an authorization modal dialog.

```javascript
window.parent.postMessage({
    "@type": "VkApiRequest",
    "transaction": "req_users",
    "method": "friends.get",
    "params": {
        "count": 10
    }
}, "*");
```

### `UserInfoRequest` — Getting Current Player Data

Returns basic information about the user running the app:

```javascript
window.parent.postMessage({
    "@type": "UserInfoRequest",
    "transaction": "req_user_info"
}, "*");
```

Response:
```json
{
    "transaction": "req_user_info",
    "ok": true,
    "user": {
        "id": 1,
        "first_name": "Pavel",
        "last_name": "Durov"
    }
}
```

### `WallPostRequest` — Publishing Wall Posts

Requests user confirmation to publish a game-generated post to their personal wall:

```javascript
window.parent.postMessage({
    "@type": "WallPostRequest",
    "transaction": "req_wall_post",
    "text": "I scored 1000 points in OpenVK Runner!"
}, "*");
```

When approved by the user, the created post object is returned:
```json
{
    "transaction": "req_wall_post",
    "ok": true,
    "post": {
        "id": 42,
        "text": "I scored 1000 points in OpenVK Runner!"
    }
}
```

### `PaymentRequest` — In-App Purchases (Votes / Points)

Initiates points/votes deduction from user balance for in-game purchases:

```javascript
window.parent.postMessage({
    "@type": "PaymentRequest",
    "transaction": "order_987",
    "outSum": 10,
    "description": "100 Gold Coins"
}, "*");
```

After user approval and deduction, transaction signature is returned:
```json
{
    "transaction": "order_987",
    "ok": true,
    "outSum": 10,
    "description": "100 Gold Coins",
    "signature": "sign_hash_hex"
}
```

---

## 4. Application Permissions

| Scope | Description |
| --- | --- |
| `friends` | Access to user friends list. |
| `wall` | Publishing posts and reading user wall. |
| `messages` | Sending messages on behalf of the user. |
| `groups` | Access to user communities. |
| `likes` | Managing Like reactions. |