OpenVK-KB-Heading: places.getCountryById

# places.getCountryById

Returns country information by numeric identifiers.

### Authorization
This method is public and does not require user authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `cids` | string / integer / array | Country identifiers (integer, array of integers, or comma-separated string). |

### Result

Returns an array of country objects containing:
* `id` (integer) — country identifier;
* `cid` (integer) — country identifier (for compatibility);
* `title` (string) — country name (e.g., `"Россия"`, `"Беларусь"`, `"Казахстан"`);
* `name` (string) — country name.

### Request Example
```http
POST /method/places.getCountryById HTTP/1.1
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
