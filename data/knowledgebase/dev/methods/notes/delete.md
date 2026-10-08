OpenVK-KB-Heading: notes.delete

# notes.delete

Deletes a note belonging to the current user.

### Authorization
This method requires user authorization (`access_token`). Executes a write action.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `note_id` | integer | **Required**. Note identifier. |

### Result

Returns `1` on successful deletion.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — Note was not found, is deleted, or does not belong to the current user. |

### Request Example
```http
POST /method/notes.delete HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

note_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
