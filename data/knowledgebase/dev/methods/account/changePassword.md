OpenVK-KB-Heading: account.changePassword

# account.changePassword

Changes the current user's password.

### Authorization
This method requires user authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `old_password` | string | Current user password. |
| `new_password` | string | New user password. |

### Result
Returns an object containing the new access token upon successful password update.