OpenVK-KB-Heading: photos.saveOwnerPhoto

# photos.saveOwnerPhoto

Saves the main profile or community photo (avatar) after uploading via the URL obtained from [photos.getOwnerPhotoUploadServer](/dev/methods/photos/getOwnerPhotoUploadServer).

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `photo` | string | **Required.** Upload information string received from the upload server. |
| `hash` | string | **Required.** Hash signature received from the upload server. |

### Result

Returns an object containing:
| Field | Type | Description |
| --- | --- | --- |
| `photo_hash` | null | Reserved field. |
| `photo_src` | string | URL of the saved and updated avatar. |

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `10` | `Invalid image` — Image file not found in temporary storage. |
| `121` | `Incorrect hash` — Invalid upload hash signature. |
| `129` | `Invalid image file` — Error processing image file. |

### Example Request
```http
POST /method/photos.saveOwnerPhoto HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

photo=1|a1b2c3|0&hash=d8a1c9e...&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "photo_hash": null,
        "photo_src": "https://openvk.instance/photos/1_1.jpeg"
    }
}
```
