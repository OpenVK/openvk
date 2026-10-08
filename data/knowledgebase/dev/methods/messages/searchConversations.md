OpenVK-KB-Heading: messages.searchConversations

# messages.searchConversations

Searches for conversations by title or participant name.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `q` | string | Search query string. **Required.** |
| `count` | integer | Number of conversations to return (max `200`). Default: `20`. |
| `extended` | integer | `1` — return profiles of participants and communities. Default: `0`. |
| `fields` | string | Profile fields when `extended=1`. |
| `group_id` | integer | Community identifier. |

### Result

Returns an object containing:
* `count` (integer) — Total count of matching conversations;
* `items` (array) — Array of **[Conversation](/dev/models/conversation)** objects;
* `profiles` (array, optional) — Profiles;
* `groups` (array, optional) — Communities.

### Example Request
```http
POST /method/messages.searchConversations HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

q=Developers&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
