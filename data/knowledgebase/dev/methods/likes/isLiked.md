OpenVK-KB-Heading: likes.isLiked

# likes.isLiked

Checks whether the specified object is in the "Likes" list of the specified user.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_id` | integer | **Required parameter**. Identifier of the user to check. |
| `type` | string | **Required parameter**. Type of object: `"post"`, `"comment"`, `"photo"`, `"video"`, `"note"`. |
| `owner_id` | integer | **Required parameter**. Identifier of the user or community that owns the object. |
| `item_id` | integer | **Required parameter**. Object identifier. |

### Result

Returns an object containing:
* `liked` (integer) — `1` if the user liked the object (or has hidden likes in privacy settings), `0` otherwise;
* `copied` (integer) — `1` if the user reposted the object (or has hidden likes in privacy settings), `0` otherwise.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `15` | `Access denied` — Target user profile is not accessible to the current user. |
| `100` | `One of the parameters specified was missing or invalid: user not found` — User does not exist or has been deleted. |
| `100` | `One of the parameters specified was missing or invalid: incorrect type` — Invalid `type` parameter value. |
| `100` | `One of the parameters specified was missing or invalid: object not found` — Object does not exist or has been deleted. |
| `665` | `Access to postable denied` — Current user cannot access the target object. |

### Example Request
```http
POST /method/likes.isLiked HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&type=post&owner_id=1&item_id=42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "liked": 1,
        "copied": 0
    }
}
```
