OpenVK-KB-Heading: photos.delete

# photos.delete

Deletes one or more photos.

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | Photo owner ID. Default: current user ID. |
| `photo_id` | integer | Photo ID (for deleting a single photo). |
| `photos` | string | Comma-separated list of photo IDs in the format `owner_id_photo_id` (maximum 10). If provided, `photo_id` is ignored. |

### Result

Returns `1` upon successful deletion.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `-78` | `Photos count must not exceed limit` — Exceeded limit of 10 photos in `photos`. |

### Example Request
```http
POST /method/photos.delete HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&photo_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
