OpenVK-KB-Heading: audio.getLyrics

# audio.getLyrics

Returns lyrics for an audio track by its identifier.

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `lyrics_id` | integer | Identifier of the lyrics / audio track. **Required.** |

### Result

Returns an object with fields:
* `lyrics_id` (integer) — identifier of the lyrics;
* `text` (string) — track lyrics with normalized newline characters (`\n`).

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `201` | `Access denied to lyrics` |
| `404` | `Not found` — Audio not found or lyrics are missing. |

### Request Example
```http
POST /method/audio.getLyrics HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

lyrics_id=105&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "lyrics_id": 105,
        "text": "We're no strangers to love\nYou know the rules and so do I\nA full commitment's what I'm thinking of\nYou wouldn't get this from any other guy\n\nI just wanna tell you how I'm feeling\nGotta make you understand\n\nNever gonna give you up\nNever gonna let you down\nNever gonna run around and desert you"
    }
}
```
