OpenVK-KB-Heading: Chat Object

# Chat Object

The **Chat** object describes a multi-user chat (conversation) in OpenVK API.

> **Important:** Starting from API version **5.80**, the unified **[conversation](/dev/models/conversation)** model (`messages.getConversations`, `messages.getConversationMembers`) is used instead of legacy methods (`messages.getDialogs`, `messages.getChat`, `messages.getChatUsers`). The `Chat` object is preserved for compatibility with clients using API versions prior to 5.80.

---

## API versions 5.0 — 5.79 (5.0 <= v < 5.80)

In API versions 5.x before 5.80, the chat object has the following schema (e.g. in the `chats` array when `extended=1` or in `messages.getChat`):

| Field | Type | Description |
| --- | --- | --- |
| `type` | string | Object type (always `"chat"`). |
| `id` | integer | Chat ID (number `1...N`). |
| `local_id` | integer | Local chat ID. |
| `title` | string | Chat title. |
| `description` | string | Chat description. |
| `admin_id` | integer | Main administrator user ID. |
| `owner_id` | integer | Chat owner user ID. |
| `admin_ids` | array | Array of all admin user IDs. |
| `members` / `users` | array | Array of chat member user IDs. |
| `members_count` | integer | Number of chat members. |
| `left` | integer | *(Optional)* `1` if current user left the chat. |
| `kicked` | integer | *(Optional)* `1` if current user was kicked from the chat. |
| `photo_50` | string | *(Optional)* Chat cover URL 50x50px. |
| `photo_100` | string | *(Optional)* Chat cover URL 100x100px. |
| `photo_200` | string | *(Optional)* Chat cover URL 200x200px. |
| `avatar_max` | string | *(Optional)* Chat cover URL in max resolution. |
| `photo_id` | integer | *(Optional)* Cover photo ID. |
| `push_settings` | object | Push notification settings (`sound`, `disabled_until`). |
| `acl` | object | Current user permissions in chat (`can_change_info`, `can_invite`, `can_promote_users`, etc.). |

### Example Object (5.0 <= v < 5.80)
```json
{
    "type": "chat",
    "id": 1,
    "local_id": 1,
    "title": "OpenVK Developers",
    "description": "Developer team workspace",
    "admin_id": 1,
    "owner_id": 1,
    "admin_ids": [1],
    "members": [1, 2, 3],
    "users": [1, 2, 3],
    "members_count": 3,
    "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
    "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
    "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png"
}
```

---

## Before API version 5.0 (v < 5.0)

In legacy versions (3.x, 4.x), fields `chat_id` and `users` are returned:

| Field | Type | Description |
| --- | --- | --- |
| `chat_id` | integer | Chat ID (equivalent to `id`). |
| `type` | string | Object type (`"chat"`). |
| `title` | string | Chat title. |
| `admin_id` | integer | Chat creator user ID. |
| `users` | array | Array of member user IDs. |

### Example Object (v < 5.0)
```json
{
    "chat_id": 1,
    "type": "chat",
    "title": "OpenVK Developers",
    "admin_id": 1,
    "users": [1, 2, 3]
}
```
