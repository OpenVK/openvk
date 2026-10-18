OpenVK-KB-Heading: audio.setBroadcast

# audio.setBroadcast

Broadcasts the currently playing audio track to the status of the authorized user or managed community.

### Authorization
Requires user authorization (`access_token`) with `audio` and `status` permissions.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `audio` | string | Audio track identifier in format `owner_id_audio_id` (e.g., `1_45`). Pass `0_0` to clear broadcast. **Required.** |
| `target_ids` | string | Comma-separated list of target identifiers (current user ID or negative community IDs `-gid`). **Required.** |

### Result

Returns an array of integer IDs where the broadcast was successfully updated (e.g., `[1, -12]`).

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `203` | `Insufficient rights to this group` |
| `404` | `Not Found` |
| `600` | `Can't listen on behalf of {id}` |

### Request Example
```http
POST /method/audio.setBroadcast HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

audio=1_45&target_ids=1,-12&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": [
        1,
        -12
    ]
}
```
