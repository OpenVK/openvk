OpenVK-KB-Heading: notes.getComments

# notes.getComments

Returns a list of comments on a note.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `note_id` | integer | **Required**. Note identifier. |
| `owner_id` | integer | **Required**. Note owner identifier. |
| `sort` | integer | Sort order. Default: `1`. |
| `offset` | integer | Offset from the beginning of the comment list. Default: `0`. |
| `count` | integer | Number of comments to return. Default: `100`. |

### Result

In API version 5.0 and higher, returns an object containing:
* `count` (integer) — total comments count;
* `items` (array) — array of comment objects (`id`, `uid` / `from_id`, `date`, `text`, `reply_to_uid`, `reply_to_cid`).

In legacy API versions (prior to version 5.0), returns an array with `count` as first element followed by comment objects.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — Note was not found, is deleted, or access is restricted. |

### Request Example
```http
POST /method/notes.getComments HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

note_id=1&owner_id=1&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
                "uid": 2,
                "date": 1700001000,
                "text": "Great note!"
            }
        ]
    }
}
```
