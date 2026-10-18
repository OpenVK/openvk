OpenVK-KB-Heading: notes.edit

# notes.edit

Edits an existing note of the current user.

### Authorization
This method requires user authorization (`access_token`). Executes a write action.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `note_id` | integer / string | **Required**. Note identifier. |
| `title` | string | New title of the note. |
| `text` | string | New content text of the note. |
| `privacy` | integer | View privacy setting (legacy parameter). |
| `comment_privacy` | integer | Comment privacy setting (legacy parameter). |
| `privacy_view` | string | View privacy setting string. |
| `privacy_comment` | string | Comment privacy setting string. |

### Result

Returns `1` upon successful edit.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — Note was not found, is deleted, or does not belong to the current user. |

### Request Example
```http
POST /method/notes.edit HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

note_id=1&title=Updated%20Title&text=Updated%20Text&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
