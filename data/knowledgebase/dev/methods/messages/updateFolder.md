OpenVK-KB-Heading: messages.updateFolder

# messages.updateFolder

Updates chat folder name and modifies its included dialogues.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `folder_id` | integer | Folder identifier. **Required.** |
| `name` | string | New folder name. |
| `add_included_peer_ids` | string | Comma-separated list of peer IDs to add to folder. |
| `remove_included_peer_ids` | string | Comma-separated list of peer IDs to remove from folder. |

### Result

Returns `1` on success.

### Example Request
```http
POST /method/messages.updateFolder HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

folder_id=1&name=Archive&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
