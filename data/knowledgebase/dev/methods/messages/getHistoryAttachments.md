OpenVK-KB-Heading: messages.getHistoryAttachments

# messages.getHistoryAttachments

Returns media attachments of the specified type from dialogue or chat history.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Destination ID (`2000000000 + chat_id` or user ID). **Required.** |
| `media_type` | string | Attachment type: `"photo"`, `"video"`, `"audio"`, `"doc"`, `"link"`, `"market"`, `"wall"`, `"share"`, `"graffiti"`. Default: `"photo"`. |
| `start_from` | string | Offset token to return subsequent batch. |
| `count` | integer | Number of attachments to return (max `200`). Default: `30`. |
| `extended` | integer | `1` — return extended profile objects. Default: `0`. |
| `fields` | string | Profile fields. |

### Result

Returns an object containing:
* `items` (array) — Array of attachment objects;
* `next_from` (string, optional) — Offset token for pagination;
* `profiles` (array, optional) — User profiles;
* `groups` (array, optional) — Community profiles.

### Example Request
```http
POST /method/messages.getHistoryAttachments HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&media_type=photo&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
