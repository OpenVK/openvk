OpenVK-KB-Heading: audio.getBroadcastList

# audio.getBroadcastList

Returns a list of friends or communities broadcasting audio to their status.

### Authorization
Requires user authorization (`access_token`) with `audio` and `friends` permissions.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `filter` | string | Filter for returned entities: `"all"` (default), `"friends"`, `"groups"`. |
| `active` | integer | `1` to return only active broadcasts. Default: `0`. |
| `hash` | string | Stream authorization hash (optional). |

### Result

Returns an object with:
* `count` (integer) — number of items in list;
* `items` (array) — array of user or community objects, each with a `status_audio` property containing the currently broadcasted audio object.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `8` | `Invalid filter {filter}` |

### Request Example
```http
POST /method/audio.getBroadcastList HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

filter=friends&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 2,
                "first_name": "Nikolai",
                "last_name": "Durov",
                "status_audio": {
                    "id": 1,
                    "owner_id": 1,
                    "artist": "Rick Astley",
                    "title": "Never Gonna Give You Up",
                    "duration": 213,
                    "url": "https://openvk.instance/storage/01/hash.mp3"
                }
            }
        ]
    }
}
```
