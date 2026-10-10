OpenVK-KB-Heading: groups.ban

# groups.ban

Adds a user to the community blacklist (bans a user in a community).

### Authorization
This method requires user authorization (`access_token`) with community administrator privileges.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | **Required parameter**. Community ID. |
| `owner_id` | integer | **Required parameter**. ID of the user to be banned. |
| `end_date` | integer | Ban expiration date (Unix timestamp). `0` or omitted for a permanent ban. Default: `0`. |
| `reason` | integer | Ban reason code (for compatibility). Default: `0`. |
| `comment` | string | Administrator comment regarding the ban. Default: empty string. |
| `comment_visible` | boolean | Whether the ban comment is visible to the banned user. Default: `true`. |

### Result

Returns `1` upon successful ban.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `15` | `Access denied` — Current user is not an administrator of this community. |
| `15` | `Access denied: cannot ban yourself` — Attempted to ban oneself. |
| `15` | `Not found` — Target user was not found or has been deleted. |

### Request Example
```http
POST /method/groups.ban HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&owner_id=5&comment=Spam&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
