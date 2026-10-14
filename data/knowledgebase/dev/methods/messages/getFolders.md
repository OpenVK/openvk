OpenVK-KB-Heading: messages.getFolders

# messages.getFolders

Returns the list of chat folders configured by the current user.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `with_peers` | integer | `1` — include peer list in response. Default: `0`. |
| `fields` | string | Profile fields. |

### Result

Returns an object containing:
* `count` (integer) — Total count of folders;
* `items` (array) — Array of folder objects (`id`, `name`, `type`, `included_peer_ids`, `order`).

### Example Request
```http
POST /method/messages.getFolders HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```
