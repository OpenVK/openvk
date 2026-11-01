OpenVK-KB-Heading: groups.getById

# groups.getById

Returns detailed information about communities by their IDs or short addresses (`screen_name`).

### Authorization
This method can be called with or without user authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_ids` | string | Comma-separated list of community IDs or short names (e.g. `1,2,apiclub`). |
| `group_id` | string | Community ID or short name (used when `group_ids` is not specified). |
| `fields` | string | Comma-separated list of additional community fields (e.g. `description,members_count,status,site,can_post`). |
| `offset` | integer | Offset needed to return a specific subset of communities. Default: `0`. |
| `count` | integer | Number of communities to return (maximum `500`). Default: `500`. |

### Result

Returns an array of [Group](/dev/models/group) objects. If a community was deleted or does not exist, a stub object with `"name": "DELETED"` is returned.

### Possible Errors

| Code | Description |
| --- | --- |
| `100` | `One of the parameters specified was missing or invalid: group_ids is undefined` — Neither `group_ids` nor `group_id` was passed. |

### Request Example
```http
POST /method/groups.getById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_ids=1,apiclub&fields=description,members_count&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": [
        {
            "id": 1,
            "name": "Official OpenVK Group",
            "screen_name": "openvk",
            "is_closed": 0,
            "type": "group",
            "is_admin": 1,
            "is_member": 1,
            "description": "OpenVK developers and users community",
            "members_count": 150,
            "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
            "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
            "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png"
        }
    ]
}
```
