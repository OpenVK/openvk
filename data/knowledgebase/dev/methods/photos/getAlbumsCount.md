OpenVK-KB-Heading: photos.getAlbumsCount

# photos.getAlbumsCount

Returns the number of photo albums of a user or community.

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_id` | integer | ID of the user whose album count is requested. |
| `group_id` | integer | ID of the community whose album count is requested. |

> **Note:** If neither parameter is specified, the method returns the number of albums of the current user.

### Result

Returns an integer representing the number of available photo albums.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — Access to user or community albums is restricted by privacy settings. |

### Example Request
```http
POST /method/photos.getAlbumsCount HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 4
}
```
