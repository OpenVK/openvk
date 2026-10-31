OpenVK-KB-Heading: notes.get

# notes.get

Returns a list of notes for a specified user, taking into account privacy settings.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_id` | integer | **Required**. User identifier whose notes to fetch. |
| `note_ids` | string | Comma-separated list of specific note IDs. |
| `offset` | integer | Offset from the beginning of the list. Default: `0`. |
| `count` | integer | Number of notes to return. Default: `10`. |
| `sort` | integer | Sort order: `0` — ascending by creation date (ASC), `1` — descending by creation date (DESC). Default: `0`. |

### Result

In API version 5.0 and higher, returns an object with:
* `count` (integer) — total notes count;
* `items` (array) — array of note objects (`id`, `owner_id`, `title`, `text`, `date`, `comments`, `read_comments`, `view_url`).

In legacy API versions (prior to version 5.0), returns an array where the first element is the count (`integer`), followed by note objects.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — User was not found, is deleted, or access to notes is restricted by privacy settings. |

### Request Example
```http
POST /method/notes.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&count=5&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
                "owner_id": 1,
                "title": "My first note",
                "text": "Hello, world!",
                "date": 1700000000,
                "comments": 0,
                "read_comments": 0,
                "view_url": "/notes1_1"
            }
        ]
    }
}
```
