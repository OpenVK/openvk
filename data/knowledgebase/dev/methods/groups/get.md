OpenVK-KB-Heading: groups.get

# groups.get

Returns a list of communities for the current or specified user.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_id` | integer | User ID whose communities are to be returned. If not specified or `0`, current user communities are returned. Default: `0`. |
| `extended` | integer | `1` to return extended community objects, `0` to return integer IDs only (for API versions below 5.0). In API 5.0+, full community objects are always returned. Default: `0`. |
| `filter` | string | Community filter: `"groups"` — all communities, `"admin"` — communities where the user is an administrator (filter `"admin"` is available only for the current user). Default: `"groups"`. |
| `fields` | string | Comma-separated list of additional community fields (e.g. `description,members_count,status,site`). |
| `offset` | integer | Offset needed to return a specific subset of communities. Default: `0`. |
| `count` | integer | Number of communities to return. Default: `6`. |

### Result

In API version 5.0 and higher, returns an object containing:
* `count` (integer) — Total number of user communities;
* `items` (array) — Array of [Group](/dev/models/group) objects.

In API versions below 5.0:
* When `extended=0`, returns an array formatted as `[count, gid1, gid2, ...]`;
* When `extended=1`, returns an array formatted as `[count, group1, group2, ...]`.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `15` | `Access denied: filter admin is available only for current user` — `admin` filter was requested for another user's profile. |
| `15` | `Access denied` — User was not found or has been deleted. |
| `260` | `Access to the groups list is denied due to the user's privacy settings` — User's community list is private. |

### Request Example
```http
POST /method/groups.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&count=2&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 12,
        "items": [
            {
                "id": 1,
                "name": "Official OpenVK Group",
                "screen_name": "openvk",
                "is_closed": 0,
                "type": "group",
                "is_admin": 1,
                "is_member": 1,
                "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
                "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
                "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png"
            }
        ]
    }
}
```
