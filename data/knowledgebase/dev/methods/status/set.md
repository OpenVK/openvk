OpenVK-KB-Heading: status.set

# status.set

Sets a new text status for the current user or updates the description of a managed community.

### Authorization
Requires user authorization (`access_token`) with the `status` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `text` | string | **Required.** New status text or community description. |
| `group_id` | integer | Community ID if updating group description. Default: `0` (current user status). |

### Result

Returns `1` on success.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — User lacks administrative permission to modify the community's description. |

### Example Request
```http
POST /method/status.set HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

text=Coding%20right%20now&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
