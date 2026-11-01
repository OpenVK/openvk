OpenVK-KB-Heading: places.getCheckins

# places.getCheckins

Returns location check-ins by geographic coordinates.

> **Note (compatibility stub):** In the current version of OpenVK, this method is a compatibility stub returning an empty list.

### Authorization
This method is public and does not require user authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `latitude` | float | Geographic latitude. Default: `0.0`. |
| `longitude` | float | Geographic longitude. Default: `0.0`. |
| `offset` | integer | Items offset. Default: `0`. |
| `count` | integer | Number of check-ins to return. Default: `20`. |

### Result

In API version 5.0 and higher, returns:
* `count` (integer) — check-in count (always `0`);
* `items` (array) — empty array `[]`.

In legacy API versions (prior to version 5.0), returns `[0]`.

### Request Example
```http
POST /method/places.getCheckins HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

latitude=55.7558&longitude=37.6173&count=10&v=5.138
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
