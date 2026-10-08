OpenVK-KB-Heading: messages.search

# messages.search

Searches for messages by text query string across all user conversations or in a specific dialogue.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `q` | string | Search query string. **Required.** |
| `peer_id` | integer | Destination ID to limit search to a specific conversation. |
| `date` | integer | Date filter (Unix timestamp). |
| `preview_length` | integer | Length of snippet text. |
| `offset` | integer | Offset for pagination. Default: `0`. |
| `count` | integer | Number of messages to return (max `100`). Default: `20`. |
| `extended` | integer | `1` — return user and community profile objects. Default: `0`. |
| `fields` | string | Profile fields when `extended=1`. |

### Result

Returns an object containing:
* `count` (integer) — Total number of found messages;
* `items` (array) — Array of **[Message](/dev/models/message)** objects;
* `profiles` (array, optional) — User profile objects;
* `groups` (array, optional) — Community profile objects.

### Example Request
```http
POST /method/messages.search HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

q=meeting&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
