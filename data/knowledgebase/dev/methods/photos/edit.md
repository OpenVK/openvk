OpenVK-KB-Heading: photos.edit

# photos.edit

Edits the caption (description) of a photo.

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** ID of the photo owner (positive for user, negative for community). |
| `photo_id` | integer | **Required.** Photo ID. |
| `caption` | string | New caption/description for the photo. |

### Result

Returns `1` on success.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `21` | `Access denied` — Photo not found, deleted, or user lacks permission to edit it. |

### Example Request
```http
POST /method/photos.edit HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&photo_id=1&caption=New%20photo%20caption&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
