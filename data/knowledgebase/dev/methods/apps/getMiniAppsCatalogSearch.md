OpenVK-KB-Heading: apps.getMiniAppsCatalogSearch

# apps.getMiniAppsCatalogSearch

Searches the mini apps catalog.

> **Note (compatibility stub):** Mini apps catalog search is not implemented in OpenVK. The method returns an empty payload (`{"count": 0, "items": [], "apps": [], "profiles": [], "groups": []}`).

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `query` | string | Search query string. |
| `limit` | integer | Number of items to return. Default: `0`. |
| `start_from` | string | Offset token for pagination. |
| `fields` | string | Comma-separated list of additional profile or community fields. |

### Result
Returns an object containing search results:

| Field | Type | Description |
| --- | --- | --- |
| `count` | integer | Number of found mini apps (`0`). |
| `items` | array | Array of search result items (`[]`). |
| `apps` | array | Array of application objects (`[]`). |
| `profiles` | array | Array of user profiles (`[]`). |
| `groups` | array | Array of communities (`[]`). |

### Example Request
```http
POST /method/apps.getMiniAppsCatalogSearch HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

query=games&access_token=YOUR_ACCESS_TOKEN&v=5.138
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
