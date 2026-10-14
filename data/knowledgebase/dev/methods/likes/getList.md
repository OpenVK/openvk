OpenVK-KB-Heading: likes.getList

# likes.getList

Returns a list of IDs of users or extended user profile objects who liked the specified object (post, comment, photo, or video).

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `type` | string | **Required parameter**. Type of object: `"post"`, `"comment"`, `"photo"`, `"video"`. |
| `owner_id` | integer | **Required parameter**. Identifier of the user or community that owns the object. |
| `item_id` | integer | **Required parameter**. Object identifier. |
| `extended` | boolean | `1` (`true`) to return extended user profile objects (`id`, `first_name`, `last_name`, `photo_50`, `photo_100`), `0` (`false`) to return user IDs. Default: `0`. |
| `offset` | integer | Offset needed to return a specific subset of likers. Default: `0`. |
| `count` | integer | Number of users to return. Default: `10`. |
| `skip_own` | boolean | `1` (`true`) to exclude the current user from the returned list. Default: `0`. |

### Result

For API versions 5.0 and higher, returns an object containing:
* `count` (integer) — Total count of likes on the object;
* `items` (array) — Array of user identifiers (integers) or user profile objects (when `extended=1`).

For legacy API versions (prior to 5.0), returns an array where the first element is the total count of likes, followed by the user IDs or profile objects.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `58` | `Invalid type` — Invalid `type` parameter value. |
| `56` | `Invalid postable` — Object not found or deleted. |
| `665` | `Access to postable denied` — Current user cannot access the target object. |

### Example Request
```http
POST /method/likes.getList HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

type=post&owner_id=1&item_id=42&extended=0&count=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 2,
        "items": [
            1,
            2
        ]
    }
}
```
