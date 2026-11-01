OpenVK-KB-Heading: account.getBalance

# account.getBalance

Returns the current user's balance in votes (coins).

### Authorization
This method requires user authorization (`access_token`).

### Parameters
This method does not accept any parameters.

### Result
Returns an object containing the user's current balance:

| Field | Type | Description |
| --- | --- | --- |
| `votes` | integer | Number of votes (coins) on user balance. |

### Errors

| Code | Message | Description |
| --- | --- | --- |
| `-105` | `Commerce is disabled on this instance` | Commerce is disabled in server preferences. |

### Example Request
```http
POST /method/account.getBalance HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "votes": 100
    }
}
```
