OpenVK-KB-Heading: board.addTopic

# board.addTopic

Creates a new topic on a community discussion board.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | Community identifier. **Required.** |
| `title` | string | Topic title (up to 127 characters). **Required.** |
| `text` | string | Text of the first comment in the new topic. |
| `from_group` | boolean | `true` to create the topic on behalf of the community (if user is an admin), `false` on behalf of user. Default: `true`. |

### Result

Returns the integer identifier of the created topic inside the community (`topic_id`).

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `15` | `Access denied` |

### Request Example
```http
POST /method/board.addTopic HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&title=Release+Discussion&text=Share+your+thoughts!&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 6
}
```
