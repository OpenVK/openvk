OpenVK-KB-Heading: photos.getTags

# photos.getTags

Returns a list of user tags on a photo.

> **Note:** In the current version of OpenVK, photo tagging is not implemented; this method always returns an empty array.

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | Photo owner ID. Default: `0`. |
| `photo_id` | integer | Photo ID. |
| `pid` | integer | Alternative photo ID parameter. |

### Result

Returns an empty array `[]`.

### Example Request
```http
POST /method/photos.getTags HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&photo_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": []
}
```
