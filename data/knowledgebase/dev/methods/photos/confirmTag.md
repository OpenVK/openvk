OpenVK-KB-Heading: photos.confirmTag

# photos.confirmTag

Confirms a user tag on a photo.

> **Note:** In OpenVK, this method is a compatibility stub and always returns `1`.

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Photo owner ID. |
| `tag_id` | integer | **Required.** Tag ID to confirm. |
| `photo_id` | integer | Photo ID. |
| `pid` | integer | Alternative photo ID parameter. |

### Result

Returns `1`.

### Example Request
```http
POST /method/photos.confirmTag HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&photo_id=1&tag_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
