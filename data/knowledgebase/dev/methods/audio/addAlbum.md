OpenVK-KB-Heading: audio.addAlbum

# audio.addAlbum

Creates a new playlist (album) for the user or community.

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `title` | string | Playlist title. **Required.** |
| `description` | string | Playlist description. |
| `group_id` | integer | Community ID if creating for a community (requires administrator rights). Default: `0` (creates for current user). |

### Result

Returns the integer identifier of the created playlist (`album_id`).

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `404` | `Invalid group_id` |
| `600` | `Insufficient rights to this group` |

### Request Example
```http
POST /method/audio.addAlbum HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

title=New+Playlist&description=Playlist+description&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 42
}
```
