OpenVK-KB-Heading: audio.beacon

# audio.beacon

Sends a playback beacon ping for an audio track. Increments track play counters (if the debounce window has elapsed) and updates the last played track for the user or community.

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `aid` | integer | Global integer ID of the audio file in database (`global_id`). **Required.** |
| `gid` | integer | Community ID if listening on behalf of a community (requires administrator rights). |

### Result

Returns `1` if the listen was successfully recorded and incremented, `0` if it was already recorded recently.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `201` | `Insufficient permissions to listen this audio` |
| `203` | `Insufficient rights to this group` |
| `404` | `Not Found` |

### Request Example
```http
POST /method/audio.beacon HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

aid=105&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
