OpenVK-KB-Heading: audio.isLagtrain

# audio.isLagtrain

Special OpenVK API easter egg method that checks whether the audio track title contains "Lagtrain".

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `audio_id` | string | Audio track identifier (in format `owner_id_vid`, integer `id`, or `unique_id`). **Required.** |

### Result

Returns `1` if track title contains substring "Lagtrain", `0` otherwise.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `8` | `Invalid audio {id}` |
| `404` | `Audio not found` |

### Request Example
```http
POST /method/audio.isLagtrain HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

audio_id=1_42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
