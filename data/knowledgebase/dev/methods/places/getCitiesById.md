OpenVK-KB-Heading: places.getCitiesById

# places.getCitiesById

Returns information about cities by their identifiers. This method is a direct alias for [places.getCityById](/dev/methods/places/getCityById).

### Authorization
This method is public and does not require user authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `cids` | string / integer / array | City identifiers (integer, array of integers, or comma-separated string). |

### Result

Returns an array of city objects (`id`, `cid`, `title`, `name`).

### Request Example
```http
POST /method/places.getCitiesById HTTP/1.1
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
