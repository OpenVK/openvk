OpenVK-KB-Heading: board.closeTopic

# board.closeTopic

Closes a topic on a community discussion board, preventing new comments from being posted. Available only to community administrators.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | Community identifier. **Required.** |
| `topic_id` | integer | Topic identifier inside the community. **Required.** |

### Result

Returns `1` on success, or `0` if the topic was not found or the user lacks administrative permissions.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |

### Request Example
```http
POST /method/board.closeTopic HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&topic_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
