OpenVK-KB-Heading: gifts.get

# gifts.get

Returns a list of gifts received by a user.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_id` | integer | ID of the user whose received gifts are to be returned. If not specified, the current user ID is used. Default: `0`. |
| `count` | integer | Number of gifts to return. Default: `10`. |
| `offset` | integer | Offset needed to return a specific subset of gifts. Default: `0`. |

### Result

In API version 5.0 and higher, returns an object containing:
* `count` (integer) — Total number of received gifts;
* `items` (array) — Array of gift items.

In API versions below 5.0, returns an array formatted as `[count, gift1, gift2, ...]`.

Each gift object contains:
| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Gift record ID. |
| `from_id` | integer | Sender user ID (`0` if the gift was sent anonymously). |
| `message` | string | Attached message text. |
| `date` | integer | Date and time the gift was sent (Unix timestamp). |
| `privacy` | integer | Privacy level: `0` — public gift, `1` — anonymous sender. |
| `gift` | object | Gift graphic details: `id` (integer), `thumb_256` (string), `thumb_96` (string), `thumb_48` (string). |

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `15` | `Access denied` — Access to user gifts is restricted by privacy settings or user is banned. |

### Request Example
```http
POST /method/gifts.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&count=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 5,
        "items": [
            {
                "id": 12,
                "from_id": 2,
                "message": "Happy Birthday!",
                "date": 1609459200,
                "privacy": 0,
                "gift": {
                    "id": 101,
                    "thumb_256": "https://openvk.instance/assets/packages/static/openvk/img/gifts/101.png",
                    "thumb_96": "https://openvk.instance/assets/packages/static/openvk/img/gifts/101.png",
                    "thumb_48": "https://openvk.instance/assets/packages/static/openvk/img/gifts/101.png"
                }
            }
        ]
    }
}
```
