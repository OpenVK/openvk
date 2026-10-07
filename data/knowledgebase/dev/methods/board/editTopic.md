OpenVK-KB-Heading: board.editTopic

# board.editTopic

Edits the title of a topic on a community discussion board.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | Community identifier. **Required.** |
| `topic_id` | integer | Topic identifier inside the community. **Required.** |
| `title` | string | New topic title (up to 127 characters). **Required.** |

### Result

Returns `1` on success, or `0` if the topic was not found, is deleted, or the user lacks edit permissions.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |

### Request Example
```http
POST /method/board.editTopic HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&topic_id=1&title=Updated+Community+Rules&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
