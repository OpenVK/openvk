OpenVK-KB-Heading: account.unban

# account.unban

Removes a user from the current user's blacklist.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | User ID to be removed from the blacklist. **Required.** |

### Result
Returns `1` on success (or if the user was not blacklisted).  
Returns `0` if the specified user does not exist.

### Example Request
```http
POST /method/account.unban HTTP/1.1
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
