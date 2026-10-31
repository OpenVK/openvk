OpenVK-KB-Heading: notifications.getIgnoredSources

# notifications.getIgnoredSources

Returns the list of sources ignored from notifications.

> **Note (compatibility stub):** In the current version of OpenVK, this method is a compatibility stub and returns an empty list.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `offset` | integer | Items offset. Default: `0`. |
| `count` | integer | Number of items to return. Default: `0`. |
| `fields` | string | Additional profile and community fields. |

### Result

Returns an object containing:
* `count` (integer) — source count (always `0`);
* `items` (array) — empty array `[]`.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |

### Request Example
```http
POST /method/notifications.getIgnoredSources HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 0,
        "items": []
    }
}
```
