OpenVK-KB-Heading: messages.createFolder

# messages.createFolder

Creates a new custom chat folder to organize dialogues and conversations.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `name` | string | Folder display name. **Required.** |
| `type` | string | Folder type: `"user"` (custom), `"all"`, `"unread"`, `"channels"`. |
| `included_peer_ids` | string | Comma-separated list of destination IDs to include in the folder. |

### Result

Returns an object containing:
* `folder_id` (integer) — Identifier of the created folder.

### Example Request
```http
POST /method/messages.createFolder HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

name=Work&included_peer_ids=2000000001,2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "folder_id": 1
    }
}
```
