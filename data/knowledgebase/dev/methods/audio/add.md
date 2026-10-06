OpenVK-KB-Heading: audio.add

# audio.add

Copies an audio track to a user or community page.

### Authorization
This method requires `audio` access permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `audio_id` | integer | Audio track ID. Required. |
| `owner_id` | integer | ID of the user or community that owns the audio track. Required. |
| `group_id` | integer | Community ID to which the audio track should be added. If omitted, adds to the current user's profile. |

### Result
Returns the ID of the newly created audio entry.

### Example Response

```json
{
    "response": 456239020
}
```
