OpenVK-KB-Heading: photos.getChatUploadServer

# photos.getChatUploadServer

Returns the server upload URL for uploading a group chat cover image.

### Authorization
Requires user authorization (`access_token`) with the `photos` or `messages` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `chat_id` | integer | **Required.** Group chat ID (numeric ID without the 2000000000 offset). |
| `group_id` | integer | Community ID (if the chat belongs to a community). Default: `0`. |

### Result

Returns an object containing the `upload_url` field:
```json
{
    "response": {
        "upload_url": "https://openvk.instance/upload/photo/chat_hash...?info..."
    }
}
```

> **Note:** After performing a POST request to the upload URL, the server returns a `response` field with the uploaded chat cover metadata, which is then used by [messages.setChatPhoto](/dev/methods/messages/setChatPhoto).

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `100` | `Invalid chat_id` — Invalid chat ID (`chat_id <= 0`). |

### Example Request
```http
POST /method/photos.getChatUploadServer HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

chat_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "upload_url": "https://openvk.instance/upload/photo/chat_cover123?info..."
    }
}
```
