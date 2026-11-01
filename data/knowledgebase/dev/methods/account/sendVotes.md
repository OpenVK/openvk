OpenVK-KB-Heading: account.sendVotes

# account.sendVotes

Transfers votes (coins) from the current user account balance to another user.

### Authorization
This method requires user authorization (`access_token`). Rate limits for write actions apply.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `receiver` | integer | Recipient user ID. **Required.** |
| `value` | integer | Number of votes to transfer (at least `1`). **Required.** |
| `message` | string | Attached message text (max 255 characters). |

### Result
Returns an object containing the sender's updated balance:

| Field | Type | Description |
| --- | --- | --- |
| `votes` | integer | Updated votes balance of the current user. |

### Errors

| Code | Message | Description |
| --- | --- | --- |
| `-105` | `Commerce is disabled on this instance` | Commerce and vote transfers are disabled on this instance. |
| `-248` | `Invalid receiver id` / `Invalid value` | Invalid recipient ID or transfer amount is less than 1. |
| `-249` | `Message is too long` | Attached message exceeds 255 characters. |
| `-250` | `Invalid receiver` | Recipient user not found, deleted, or inaccessible. |
| `-251` | `Can't transfer votes to yourself` | Cannot transfer votes to one's own account. |
| `-252` | `Not enough votes` | Insufficient votes balance to complete the transfer. |

### Example Request
```http
POST /method/account.sendVotes HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

receiver=2&value=10&message=Thank+you!&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "votes": 90
    }
}
```
