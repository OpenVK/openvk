OpenVK-KB-Heading: audio.add

# audio.add

Copies an audio track to the authorized user's or community's collection.

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `audio_id` | integer | Virtual ID of the audio file in the owner's library. **Required.** |
| `owner_id` | integer | Identifier of the audio file owner. **Required.** |
| `group_id` | integer | Community identifier if adding to a group collection (authorized user must have administrative rights). |
| `album_id` | integer | *(Not implemented)* Passing this parameter raises error 10. |

### Result

Returns a string identifier of the added audio track in format `owner_id_virtual_id` (e.g., `"1_45"`).

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `10` | `album_id not implemented` |
| `201` | `Access denied to audio(owner={owner_id}, vid={audio_id})` |
| `203` | `Insufficient rights to this group` |
| `300` | `Album is full` |
| `404` | `Not found` / `Invalid group_id` |

### Request Example
```http
POST /method/audio.add HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

audio_id=1&owner_id=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": "1_45"
}
```
