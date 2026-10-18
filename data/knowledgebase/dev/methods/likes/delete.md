OpenVK-KB-Heading: likes.delete

# likes.delete

Removes a "Like" reaction from the specified object (post, comment, photo, video, or note).

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `type` | string | **Required parameter**. Type of object: `"post"`, `"comment"`, `"photo"`, `"video"`, `"note"`. |
| `owner_id` | integer | **Required parameter**. Identifier of the user or community that owns the object. |
| `item_id` | integer | **Required parameter**. Object identifier. |

### Result

Returns an object containing:
* `likes` (integer) — Total count of likes on the object after removing the reaction.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `2` | `Access to postable denied` — User does not have permission to view the object. |
| `100` | `One of the parameters specified was missing or invalid: incorrect type` — Invalid `type` parameter value. |
| `100` | `One of the parameters specified was missing or invalid: object not found` — Object does not exist or has been deleted. |

### Example Request
```http
POST /method/likes.delete HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

type=post&owner_id=1&item_id=42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "likes": 14
    }
}
```
