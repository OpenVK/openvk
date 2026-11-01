OpenVK-KB-Heading: groups.unban

# groups.unban

Unbans a user and removes them from the community blacklist.

### Authorization
This method requires user authorization (`access_token`) with community administrator privileges.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | **Required parameter**. Community ID. |
| `owner_id` | integer | **Required parameter**. ID of the user to unban. |

### Result

Returns `1` upon successful unban.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `15` | `Access denied` — Current user is not an administrator of this community. |
| `15` | `Not found` — Target user was not found or has been deleted. |

### Request Example
```http
POST /method/groups.unban HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&owner_id=5&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
