OpenVK-KB-Heading: apps.getMiniAppsCatalog

# apps.getMiniAppsCatalog

Returns the mini apps catalog for mobile and web clients.

> **Note (compatibility stub):** Mini apps catalog is not implemented in OpenVK. The method returns an empty catalog object (`{"count": 0, "items": [], "apps": [], "profiles": [], "groups": []}`).

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `limit` | integer | Number of items to return. Default: `0`. |
| `start_from` | string | Offset token for pagination. |
| `ref` | string | Referrer tracking string. |
| `section_id` | integer | Catalog section ID. |
| `fields` | string | Comma-separated list of additional profile or community fields. |

### Result
Returns an object containing catalog data:

| Field | Type | Description |
| --- | --- | --- |
| `count` | integer | Total number of mini apps (`0`). |
| `items` | array | Array of catalog items (`[]`). |
| `apps` | array | Array of application objects (`[]`). |
| `profiles` | array | Array of user profiles (`[]`). |
| `groups` | array | Array of communities (`[]`). |

### Example Request
```http
POST /method/apps.getMiniAppsCatalog HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

limit=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 0,
        "items": [],
        "apps": [],
        "profiles": [],
        "groups": []
    }
}
```
