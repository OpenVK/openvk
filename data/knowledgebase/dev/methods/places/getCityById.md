OpenVK-KB-Heading: places.getCityById

# places.getCityById

Returns information about cities by their identifiers.

### Authorization
This method is public and does not require user authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `cids` | string / integer / array | City identifiers (integer, array of integers, or comma-separated string). |

### Result

Returns an array of city objects containing:
* `id` (integer) — city identifier;
* `cid` (integer) — city identifier (for compatibility);
* `title` (string) — city name;
* `name` (string) — city name.

### Request Example
```http
POST /method/places.getCityById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

cids=1,2&v=5.138
```

### Response Example
```json
{
    "response": [
        {
            "id": 1,
            "cid": 1,
            "title": "Moscow",
            "name": "Moscow"
        }
    ]
}
```
