OpenVK-KB-Heading: groups.getSettings

# groups.getSettings

Returns current settings and section access levels of a community.

### Authorization
This method requires user authorization (`access_token`) with community administrator privileges.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | **Required parameter**. Community ID. |

### Result

Returns an object containing community settings:

| Field | Type | Description |
| --- | --- | --- |
| `title` | string | Community title. |
| `description` | string | Community description text. |
| `address` | string | Short community address. |
| `wall` | integer | Wall mode (`0` — disabled, `1` — open, `2` — limited, `3` — closed). |
| `photos` | integer | Photos section state (`1` — enabled). |
| `video` | integer | Videos section state (`0` — disabled). |
| `audio` | integer | Member audio upload access (`1` — allowed, `0` — administrators only). |
| `docs` | integer | Documents section state (`1` — enabled). |
| `topics` | integer | Topic creation access (`1` — all members, `0` — administrators only). |
| `website` | string | Community website URL. |

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `15` | `Access denied` — The current user is not an administrator of this community. |

### Request Example
```http
POST /method/groups.getSettings HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "title": "Official OpenVK Group",
        "description": "Developers community",
        "address": "openvk",
        "wall": 1,
        "photos": 1,
        "video": 0,
        "audio": 1,
        "docs": 1,
        "topics": 1,
        "website": "https://openvk.instance"
    }
}
```
