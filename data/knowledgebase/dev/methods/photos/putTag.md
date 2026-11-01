OpenVK-KB-Heading: photos.putTag

# photos.putTag

Adds a user tag to a photo.

> **Note:** In OpenVK, this method is a compatibility stub and always returns `1`.

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Photo owner ID. |
| `photo_id` | integer | Photo ID. |
| `pid` | integer | Alternative photo ID parameter. |
| `uid` | integer | ID of the user being tagged. |
| `x` | float | Percentage offset of the top-left tag corner (horizontal, 0.0 - 100.0). |
| `y` | float | Percentage offset of the top-left tag corner (vertical, 0.0 - 100.0). |
| `x2` | float | Percentage offset of the bottom-right tag corner (horizontal). Default: `100.0`. |
| `y2` | float | Percentage offset of the bottom-right tag corner (vertical). Default: `100.0`. |

### Result

Returns `1`.

### Example Request
```http
POST /method/photos.putTag HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&photo_id=1&uid=2&x=10.5&y=20.0&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
