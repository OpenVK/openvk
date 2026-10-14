OpenVK-KB-Heading: notifications.getSettings

# notifications.getSettings

Returns notification settings for the current user.

> **Note (compatibility stub):** In the current version of OpenVK, this method is a compatibility stub returning empty settings structures for VK client compatibility.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `device_id` | string | Device identifier. |
| `from` | string | Source origin. |
| `lang` | string | Language code. |

### Result

Returns an object with settings collections:
* `apps` (array) — empty array `[]`;
* `groups` (array) — empty array `[]`;
* `photos` (array) — empty array `[]`;
* `profiles` (array) — empty array `[]`;
* `items` (object) — empty object `{}`.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |

### Request Example
```http
POST /method/notifications.getSettings HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "apps": [],
        "groups": [],
        "photos": [],
        "profiles": [],
        "items": {}
    }
}
```
