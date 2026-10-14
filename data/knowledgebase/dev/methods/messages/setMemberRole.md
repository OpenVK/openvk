OpenVK-KB-Heading: messages.setMemberRole

# messages.setMemberRole

Changes the role of a participant in a multi-user chat (e.g., promote to administrator or demote to member).

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Destination ID (`2000000000 + chat_id`). **Required.** |
| `member_id` | integer | Identifier of the participant. **Required.** |
| `role` | string | Target role: `"admin"` or `"member"`. **Required.** |

### Result

Returns `1` on success.

### Example Request
```http
POST /method/messages.setMemberRole HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&member_id=2&role=admin&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
