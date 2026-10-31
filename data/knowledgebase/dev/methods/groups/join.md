OpenVK-KB-Heading: groups.join

# groups.join

Joins a group or subscribes to a public page for the current user.

### Authorization
This method requires user authorization (`access_token`) with `groups` permissions.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | **Required parameter**. Community ID. |

### Result

Returns `1` upon successful joining or subscribing.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `9` | `Flood control: action too fast or too frequent.` — Rate limit exceeded for joining communities. |

### Request Example
```http
POST /method/groups.join HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
