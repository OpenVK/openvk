OpenVK-KB-Heading: notes.createComment

# notes.createComment

Adds a new comment to a note.

### Authorization
This method requires user authorization (`access_token`). Executes a write action.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `note_id` | integer | **Required**. Note identifier. |
| `owner_id` | integer | **Required**. Note owner identifier. |
| `message` | string | **Required**. Comment text. |
| `attachments` | string | Attachments string (optional). |

### Result

In API version 5.0 and higher, returns the comment identifier (`integer`).

In legacy API versions (prior to version 5.0), returns an object `{"cid": <comment_id>}`.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — Note was not found, is deleted, or commenting is restricted. |
| `100` | `Required parameter 'message' missing.` — Required parameter `message` was not provided. |

### Request Example
```http
POST /method/notes.createComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

note_id=1&owner_id=1&message=Great%20post!&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 42
}
```
