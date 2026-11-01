OpenVK-KB-Heading: messages.getConversationsById

# messages.getConversationsById

Returns conversation objects by their destination IDs (`peer_ids`).

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_ids` | string | Comma-separated list of peer IDs (e.g. `1,2000000001`). **Required.** |
| `extended` | integer | `1` — return user and community profile objects. Default: `0`. |
| `fields` | string | Profile fields to return when `extended=1`. |
| `group_id` | integer | Community identifier. |

### Result

Returns an object containing:
* `count` (integer) — Total count of conversations;
* `items` (array) — Array of **[Conversation](/dev/models/conversation)** objects;
* `profiles` (array, optional) — Profiles (when `extended=1`);
* `groups` (array, optional) — Communities (when `extended=1`).

### Example Request
```http
POST /method/messages.getConversationsById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_ids=2000000001&extended=0&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
