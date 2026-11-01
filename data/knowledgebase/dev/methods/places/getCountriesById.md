OpenVK-KB-Heading: places.getCountriesById

# places.getCountriesById

Returns country information by identifiers. This method is a direct alias for [places.getCountryById](/dev/methods/places/getCountryById).

### Authorization
This method is public and does not require user authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `cids` | string / integer / array | Country identifiers (integer, array of integers, or comma-separated string). |

### Result

Returns an array of country objects (`id`, `cid`, `title`, `name`).

### Request Example
```http
POST /method/places.getCountriesById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

cids=1,3&v=5.138
```

### Response Example
```json
{
    "response": [
        {
            "id": 1,
            "cid": 1,
            "title": "Россия",
            "name": "Россия"
        },
        {
            "id": 3,
            "cid": 3,
            "title": "Беларусь",
            "name": "Беларусь"
        }
    ]
}
```
