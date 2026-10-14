OpenVK-KB-Heading: audio.delete

# audio.delete

Deletes an audio track from a user or community collection.

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `audio_id` | integer | Virtual ID of the audio file. **Required.** |
| `owner_id` | integer | Identifier of the audio owner. **Required.** |
| `group_id` | integer | Community identifier if deleting from a group page (requires administrative permissions). |

### Result

Returns `1` on successful deletion.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `203` | `Insufficient rights to this group` |
| `404` | `Not found` / `Invalid group_id` |

### Request Example
```http
POST /method/audio.delete HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

audio_id=45&owner_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
