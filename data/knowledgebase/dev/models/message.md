OpenVK-KB-Heading: Message Object

# Message Object

The **Message** object describes a direct message or a chat message.

> **Important:** In API version **5.80**, a major overhaul of the messaging system took place: message addressing was migrated to `peer_id` and `conversation_message_id`, `body` was replaced with `text`, and chat management was moved to the **[conversation](/dev/models/conversation)** model.

---

## API version 5.80 and higher (v >= 5.80)

Starting from API version 5.80, the modern unified message schema is used:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Global unique message ID. |
| `conversation_message_id` | integer | Sequential local message ID inside the specific conversation / chat. |
| `date` | integer | Message send timestamp (Unix timestamp). |
| `peer_id` | integer | Destination ID (user `id`, group `-id`, or chat `2000000000 + chat_id`). |
| `from_id` | integer | Author user or group ID. |
| `text` | string | Message text content (UTF-8). |
| `out` | integer | `1` — outgoing message sent by the current user, `0` — incoming. |
| `important` | boolean | Whether the message is marked as important. |
| `is_hidden` | boolean | Whether the message is hidden. |
| `attachments` | array | Media attachments array (photos, audios, videos, docs, stickers, etc.). |
| `fwd_messages` | array | Forwarded messages array (`Message` objects). |
| `reply_message` | object\|null | Quoted message replied to. |
| `action` | object\|null | Service chat action object (`type`, `member_id`, `text`). |

### Example Object (v >= 5.80)
```json
{
    "id": 1050,
    "conversation_message_id": 42,
    "date": 1696680000,
    "peer_id": 2000000001,
    "from_id": 1,
    "text": "Hello developers!",
    "out": 1,
    "important": false,
    "attachments": [],
    "fwd_messages": []
}
```

---

## API versions 5.0 — 5.79 (5.0 <= v < 5.80)

In versions prior to 5.80, chat metadata was embedded directly inside the message object:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Message ID. |
| `user_id` | integer | Interlocutor user ID (for 1-on-1 dialogues) or author ID. |
| `from_id` | integer | Message author ID. |
| `date` | integer | Send timestamp (Unix timestamp). |
| `read_state` | integer | `1` — read, `0` — unread. |
| `out` | integer | `1` — outgoing, `0` — incoming. |
| `title` | string | Dialogue title or chat title. |
| `body` | string | Message body. |
| `chat_id` | integer | Chat ID (number `1...N` without 2000000000 offset). |
| `chat_active` | array | Array of active chat member user IDs. |
| `users_count` | integer | Number of chat members. |
| `admin_id` | integer | Chat owner/creator user ID. |
| `photo_50` | string | Chat cover URL 50x50px. |
| `photo_100` | string | Chat cover URL 100x100px. |
| `photo_200` | string | Chat cover URL 200x200px. |
| `action` | string | Action type (`"chat_create"`, `"chat_title_update"`, `"chat_photo_update"`, `"chat_invite_user"`, `"chat_kick_user"`). |
| `action_mid` | integer | Target user ID. |
| `action_text` | string | Action text. |
| `attachments` | array | Media attachments. |
| `fwd_messages` | array | Forwarded messages. |
| `emoji` | integer | `1` if text contains emoji. |
| `deleted` | integer | `1` if deleted. |

### Example Object (5.0 <= v < 5.80)
```json
{
    "id": 1050,
    "user_id": 1,
    "from_id": 1,
    "date": 1696680000,
    "read_state": 1,
    "out": 1,
    "title": "OpenVK Developers",
    "body": "Hello developers!",
    "chat_id": 1,
    "chat_active": [1, 2, 3],
    "users_count": 3,
    "admin_id": 1,
    "attachments": []
}
```

---

## Before API version 5.0 (v < 5.0)

In legacy versions (3.x, 4.x), fields `mid` and `uid` are returned:

| Field | Type | Description |
| --- | --- | --- |
| `mid` | integer | Message ID (equivalent to `id`). |
| `uid` | integer | User ID (equivalent to `user_id`). |
| `date` | integer | Send timestamp (Unix timestamp). |
| `read_state` | integer | Read status (`0` or `1`). |
| `out` | integer | Outgoing status (`0` or `1`). |
| `title` | string | Dialogue title. |
| `body` | string | Message body. |
| `chat_id` | integer | Chat ID. |
| `attachments` | array | Attachments. |
| `fwd_messages` | array | Forwarded messages. |

### Example Object (v < 5.0)
```json
{
    "mid": 1050,
    "uid": 1,
    "date": 1696680000,
    "read_state": 1,
    "out": 1,
    "title": "OpenVK Developers",
    "body": "Hello developers!",
    "chat_id": 1,
    "attachments": []
}
```
