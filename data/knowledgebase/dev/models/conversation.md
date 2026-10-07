OpenVK-KB-Heading: Conversation Object

# Conversation Object

The **Conversation** object describes a dialogue with a user, community, or a group multi-user chat in the modern OpenVK messaging architecture introduced in API version **5.80**.

Returned by methods `messages.getConversations`, `messages.getConversationsById`, `messages.searchConversations`.

---

## Conversation Object Structure (v >= 5.80)

| Field | Type | Description |
| --- | --- | --- |
| `peer` | object | Peer destination information (`id`, `type`, `local_id`). |
| `in_read` | integer | ID of the last read incoming message. |
| `out_read` | integer | ID of the last read outgoing message. |
| `unread_count` | integer | Number of unread messages in the conversation. |
| `important` | boolean | Whether the conversation is marked as important. |
| `unanswered` | boolean | Whether the conversation is marked as unanswered. |
| `push_settings` | object | Push notification settings (`sound`, `disabled_until`). |
| `can_write` | object | Information about ability to send messages (`allowed`, `reason`). |
| `chat_settings` | object\|null | *(For multi-user chats only)* Extended chat settings object. |

---

### `peer` Object
| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Destination ID: user `id`, community `-id`, or `2000000000 + chat_id` for chat. |
| `type` | string | Destination type: `"user"`, `"chat"`, `"group"`, `"email"`. |
| `local_id` | integer | Local ID (chat ID for chats, user/group ID for users/groups). |

---

### `chat_settings` Object (for chats)
| Field | Type | Description |
| --- | --- | --- |
| `title` | string | Chat title. |
| `members_count` | integer | Number of members in chat. |
| `state` | string | State of current user in chat: `"in"`, `"left"`, `"kicked"`. |
| `owner_id` | integer | Creator / owner user ID. |
| `admin_ids` | array | Array of administrator user IDs. |
| `active_ids` | array | Array of last active user IDs. |
| `photo` | object\|null | Chat cover URLs (`photo_50`, `photo_100`, `photo_200`). |
| `pinned_message` | object\|null | Pinned message object. |
| `acl` | object | Current user permissions in chat (`can_change_info`, `can_invite`, `can_change_pin`, etc.). |
| `permissions` | object | Chat permissions settings. |

---

### Example Object (v >= 5.80)
```json
{
    "peer": {
        "id": 2000000001,
        "type": "chat",
        "local_id": 1
    },
    "in_read": 1050,
    "out_read": 1050,
    "unread_count": 0,
    "important": false,
    "unanswered": false,
    "push_settings": {
        "sound": 1,
        "disabled_until": 0
    },
    "can_write": {
        "allowed": true
    },
    "chat_settings": {
        "title": "OpenVK Developers",
        "members_count": 3,
        "state": "in",
        "owner_id": 1,
        "admin_ids": [1],
        "active_ids": [1, 2, 3],
        "photo": {
            "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
            "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
            "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png"
        }
    }
}
```
