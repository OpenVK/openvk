OpenVK-KB-Heading: notes.addComment

# notes.addComment

Adds a comment to a note. This method is an alias for [notes.createComment](/dev/methods/notes/createComment) and accepts the comment body via either `message` or `text`.

### Authorization
This method requires user authorization (`access_token`). Executes a write action.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `note_id` | integer | **Required**. Note identifier. |
| `owner_id` | integer | **Required**. Note owner identifier. |
| `message` | string | Comment text (can also be passed as `text`). |
| `text` | string | Alternative parameter name for comment text. |
| `attachments` | string | Attachments string. |

### Result

In API version 5.0 and higher, returns the comment identifier (`integer`).

In legacy API versions (prior to version 5.0), returns an object `{"cid": <comment_id>}`.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — Note was not found, is deleted, or commenting is restricted. |
| `100` | `Required parameter 'message' missing.` — Comment text was not provided. |

### Request Example
```http
POST /method/notes.addComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

note_id=1&owner_id=1&text=Comment%20text&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 43
}
```
