OpenVK-KB-Heading: audio.editAlbum

# audio.editAlbum

Edits title and description of an existing playlist (album).

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `album_id` | integer | Identifier of the playlist to edit. **Required.** |
| `title` | string | New playlist title. |
| `description` | string | New playlist description. |

### Result

Returns `1` if changes were applied, or `0` if both parameters were empty.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `404` | `Album not found` |
| `600` | `Insufficient rights to this album` |

### Request Example
```http
POST /method/audio.editAlbum HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

album_id=42&title=Updated+Title&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
