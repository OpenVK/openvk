OpenVK-KB-Heading: places.checkin

# places.checkin

Creates a new location check-in.

> **Note (compatibility stub):** In the current version of OpenVK, this method is a compatibility stub for mobile clients and returns `1`.

### Authorization
This method requires user authorization (`access_token`). Executes a write action.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `place_id` | integer | Place identifier. Default: `0`. |
| `text` | string | Check-in comment text. |
| `lat` | float | Geographical latitude. Default: `0.0`. |
| `long` | float | Geographical longitude. Default: `0.0`. |
| `friends_only` | integer | Visible to friends only flag (`1` — friends only, `0` — all users). Default: `0`. |
| `services` | string | External services list for cross-posting. |

### Result

Returns `1`.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |

### Request Example
```http
POST /method/places.checkin HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

place_id=1&text=I%20am%20here&lat=55.7558&long=37.6173&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
