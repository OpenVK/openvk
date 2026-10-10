OpenVK-KB-Heading: Post Object

# Post Object

The **Post** object describes a wall post on a user or community wall. The structure of the returned fields depends on the API version passed in the `v` parameter.

---

## API version 5.0 and higher (v >= 5.0)

API versions 5.0+ use the following fields:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Wall post ID. |
| `owner_id` | integer | Wall owner ID (positive for user, negative for community). |
| `from_id` | integer | Author ID who published the post. |
| `date` | integer | Publication timestamp (Unix timestamp). |
| `text` | string | Post text content. |
| `reply_owner_id` | integer | *(Optional)* Wall owner ID of the replied post. |
| `reply_post_id` | integer | *(Optional)* Post ID of the replied post. |
| `friends_only` | integer | `1` if post is visible to friends only. |
| `comments` | object | Comments info object (`count`, `can_post`). |
| `likes` | object | Likes info object (`count`, `user_likes`, `can_like`, `can_publish`). |
| `reposts` | object | Reposts info object (`count`, `user_reposted`). |
| `views` | object | Views info object (`count`). |
| `post_type` | string | Post type: `"post"`, `"copy"`, `"reply"`, `"postpone"`, `"suggest"`. |
| `attachments` | array | Media attachments array (photos, audios, videos, docs, polls, etc.). |
| `is_pinned` | integer | `1` if post is pinned. |
| `copy_history` | array | *(Optional)* Array of original source post objects if this post is a repost. |

### Example Object (v >= 5.0)
```json
{
    "id": 42,
    "owner_id": 1,
    "from_id": 1,
    "date": 1696680000,
    "text": "Welcome to OpenVK!",
    "post_type": "post",
    "comments": {
        "count": 5,
        "can_post": 1
    },
    "likes": {
        "count": 12,
        "user_likes": 0,
        "can_like": 1
    },
    "reposts": {
        "count": 3,
        "user_reposted": 0
    },
    "attachments": []
}
```

---

## Before API version 5.0 (v < 5.0)

In API versions 3.x and 4.x, legacy `to_id`, `media`, and `attachment` fields are used:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Wall post ID. |
| `to_id` | integer | Wall owner ID (equivalent to `owner_id` in API v5.0+). |
| `from_id` | integer | Post author ID. |
| `date` | integer | Publication timestamp (Unix timestamp). |
| `text` | string | Post text content. |
| `comments` | object | Comments info (`count`, `can_post`). |
| `likes` | object | Likes info (`count`, `user_likes`). |
| `reposts` | object | Reposts info (`count`). |
| `attachment` | object | *(Optional)* Single attachment in legacy format. |
| `attachments` | array | *(Optional)* List of attachments. |
| `copy_owner_id` | integer | Reposted post owner ID. |
| `copy_post_id` | integer | Reposted post ID. |

### Example Object (v < 5.0)
```json
{
    "id": 42,
    "to_id": 1,
    "from_id": 1,
    "date": 1696680000,
    "text": "Welcome to OpenVK!",
    "comments": {
        "count": 5
    },
    "likes": {
        "count": 12,
        "user_likes": 0
    },
    "reposts": {
        "count": 3
    }
}
```
