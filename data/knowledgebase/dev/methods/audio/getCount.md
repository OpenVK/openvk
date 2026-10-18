OpenVK-KB-Heading: audio.getCount

# audio.getCount

Returns the total count of audio files in a user or community library.

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | Identifier of the audio owner (positive number for user, negative for community). **Required.** |
| `uploaded_only` | integer | `1` to count only audios uploaded directly by the authorized user (only applies when `owner_id` is current user), `0` otherwise. Default: `0`. |

### Result

Returns an integer representing the total number of audio tracks.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `15` | `Access denied` — Target user has restricted audio visibility. |
| `404` | `User not found` / `Group not found` |

### Request Example
```http
POST /method/audio.getCount HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 42
}
```
