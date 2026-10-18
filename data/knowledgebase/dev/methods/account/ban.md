OpenVK-KB-Heading: account.ban

# account.ban

Adds a user to the current user's blacklist.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | User ID to be added to the blacklist. **Required.** |

### Result
Returns `1` on success (or if the user is already blacklisted).  
Returns `0` if the specified user was not found or has been deleted.

### Errors

| Code | Message | Description |
| --- | --- | --- |
| `15` | `Access denied: cannot blacklist yourself` | Attempt to blacklist oneself. |
| `-7856` | `Blacklist limit exceeded` | Blacklist limit exceeded. |

### Example Request
```http
POST /method/account.ban HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
