OpenVK-KB-Heading: gifts.getGiftsInCategory

# gifts.getGiftsInCategory

Returns gifts available in a specific catalog category.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `id` | integer | **Required parameter**. Gift catalog category ID. |
| `page` | integer | Page number within the category. Default: `1`. |

### Result

Returns an array of gift objects. Each object contains:
| Field | Type | Description |
| --- | --- | --- |
| `name` | string | Gift name. |
| `image` | string | Relative path to the gift image. |
| `usages_left` | integer | Remaining free usage quota for the current user. |
| `price` | integer | Gift price in votes (coins). |
| `is_free` | boolean | `true` if the gift is free, `false` otherwise. |

### Possible Errors

| Code | Description |
| --- | --- |
| `-105` | `Commerce is disabled on this instance` — Commerce / votes system is disabled on this instance. |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `15` | `Category not found` — Category with the specified ID was not found. |

### Request Example
```http
POST /method/gifts.getGiftsInCategory HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

id=1&page=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": [
        {
            "name": "Cake",
            "image": "/assets/packages/static/openvk/img/gifts/101.png",
            "usages_left": 0,
            "price": 1,
            "is_free": false
        }
    ]
}
```
