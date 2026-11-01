OpenVK-KB-Heading: utils.resolveOffset

# utils.resolveOffset

Calculates exact pagination offset (`offset`) required to jump directly to the list page containing a target object (wall post, photo in album, or video).

### Authorization
Requires user authorization token.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Identifier of the object owner (positive for user, negative for group). |
| `id` | integer | **Required.** Identifier of the target object (virtual ID of the post, photo, or video). |
| `id2` | integer | Secondary container identifier (e.g., album collection ID for `photos.get`). |
| `method` | string | Target API method to calculate offset for: `"wall.get"`, `"photos.get"`, or `"video.get"`. Default: `"wall.get"`. |
| `perPage` | integer | Number of items per page. Default: `10`. |
| `rev` | boolean | Sorting order (for photo albums): `1` — reverse, `0` — direct. Default: `0`. |

### Result

Returns an `integer` — calculated `offset` value rounded to page boundaries.

### Possible Errors

| Code | Description |
| --- | --- |
| `100` | `One of the parameters specified was missing or invalid` — invalid `perPage` or object not found. |
| `-5` | `Unknown entity` — unsupported API method provided in `method`. |
| `-9` | `Missing relation` — photo relation not found in the specified album. |

### Example Request
```http
POST /method/utils.resolveOffset HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&id=42&method=wall.get&perPage=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 40
}
```
