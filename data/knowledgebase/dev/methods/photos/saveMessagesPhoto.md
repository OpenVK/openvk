OpenVK-KB-Heading: photos.saveMessagesPhoto

# photos.saveMessagesPhoto

Saves a photo for sending in a private message after uploading via [photos.getMessagesUploadServer](/dev/methods/photos/getMessagesUploadServer).

### Authorization
Requires user authorization (`access_token`) with the `photos` or `messages` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `photo` | string | **Required.** Upload metadata string received from the upload server. |
| `hash` | string | **Required.** Hash signature received from the upload server. |
| `server` | mixed | Upload server number (compatibility parameter). |

### Result

Returns an array containing the saved message photo object:

```json
{
    "response": [
        {
            "id": 8,
            "pid": 8,
            "owner_id": 1,
            "user_id": 1,
            "album_id": -3,
            "aid": -3,
            "width": 1024,
            "height": 768,
            "text": "",
            "date": 1609459200,
            "access_key": "a1b2c3d4e5f6",
            "photo_75": "https://openvk.instance/photos/1_8_cropped/miniscule.jpeg",
            "photo_130": "https://openvk.instance/photos/1_8_cropped/tiny.jpeg",
            "photo_604": "https://openvk.instance/photos/1_8_cropped/normal.jpeg",
            "url": "https://openvk.instance/photos/1_8.jpeg"
        }
    ]
}
```

> **Note:** To attach the saved photo to a message via [messages.send](/dev/methods/messages/send), format the attachment string as `photo<owner_id>_<id>` (or with access key: `photo<owner_id>_<id>_<access_key>`).

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `121` | `Incorrect hash` — Invalid upload hash signature. |
| `129` | `Invalid image file` — Error processing uploaded image file. |

### Example Request
```http
POST /method/photos.saveMessagesPhoto HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

photo=1|msg_img_1|0&hash=d8a1c9e...&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": [
        {
            "id": 8,
            "pid": 8,
            "owner_id": 1,
            "user_id": 1,
            "album_id": -3,
            "aid": -3,
            "width": 1024,
            "height": 768,
            "text": "",
            "date": 1609459200,
            "access_key": "a1b2c3d4e5f6",
            "photo_75": "https://openvk.instance/photos/1_8_cropped/miniscule.jpeg",
            "photo_130": "https://openvk.instance/photos/1_8_cropped/tiny.jpeg",
            "photo_604": "https://openvk.instance/photos/1_8_cropped/normal.jpeg",
            "url": "https://openvk.instance/photos/1_8.jpeg"
        }
    ]
}
```
