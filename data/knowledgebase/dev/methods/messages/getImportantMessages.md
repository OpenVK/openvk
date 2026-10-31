OpenVK-KB-Heading: messages.getImportantMessages

# messages.getImportantMessages

Returns a list of user messages marked as important.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `count` | integer | Number of messages to return (max `200`). Default: `20`. |
| `offset` | integer | Pagination offset. Default: `0`. |
| `start_message_id` | integer | Starting message ID. |
| `preview_length` | integer | Length of text preview. |
| `extended` | integer | `1` — return user and community profile objects. Default: `0`. |
| `fields` | string | Profile fields when `extended=1`. |

### Result

Returns an object containing:
* `messages` (object) — Object with `count` and `items` (array of **[Message](/dev/models/message)** objects);
* `profiles` (array, optional) — User profiles;
* `groups` (array, optional) — Community profiles;
* `conversations` (array, optional) — Conversation objects.

### Example Request
```http
POST /method/messages.getImportantMessages HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

count=20&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
