OpenVK-KB-Heading: Comment Object

# Comment Object

The **Comment** object describes a comment on a wall post, photo, video, topic, or note. The structure of the returned fields depends on the API version passed in the `v` parameter.

---

## API version 5.0 and higher (v >= 5.0)

Modern API versions use the following fields:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Comment ID. |
| `from_id` | integer | Comment author user ID. |
| `date` | integer | Comment publication timestamp (Unix timestamp). |
| `text` | string | Comment text content. |
| `reply_to_user` | integer | *(Optional)* User ID replied to. |
| `reply_to_comment` | integer | *(Optional)* Comment ID replied to. |
| `attachments` | array | Array of media attachments (photos, audios, documents, etc.). |
| `parents_stack` | array | Array of parent comment IDs in thread hierarchy. |
| `likes` | object | *(Optional)* Likes information (`count`, `user_likes`, `can_like`). |

### Example Object (v >= 5.0)
```json
{
    "id": 10,
    "from_id": 1,
    "date": 1696680000,
    "text": "Great update!",
    "reply_to_comment": 5,
    "reply_to_user": 2,
    "attachments": [],
    "parents_stack": [5]
}
```

---

## Before API version 5.0 (v < 5.0)

In API versions 3.x and 4.x, legacy `cid` and `uid` fields are returned:

| Field | Type | Description |
| --- | --- | --- |
| `cid` | integer | Comment ID (equivalent to `id` in API v5.0+). |
| `uid` | integer | Author user ID (equivalent to `from_id`). |
| `date` | integer | Comment creation timestamp (Unix timestamp). |
| `message` / `text` | string | Comment text. |
| `reply_to_cid` | integer | *(Optional)* Parent comment ID. |
| `reply_to_uid` | integer | *(Optional)* Target user ID. |
| `attachments` | array | Attachments (in API v < 4.0 photos are embedded directly in the array). |

### Example Object (v < 5.0)
```json
{
    "cid": 10,
    "uid": 1,
    "date": 1696680000,
    "text": "Great update!",
    "reply_to_cid": 5
}
```
