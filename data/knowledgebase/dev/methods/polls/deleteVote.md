OpenVK-KB-Heading: polls.deleteVote

# polls.deleteVote

Revokes the current user's vote in a poll.

### Authorization
Requires user authorization (`access_token`) with the `wall` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `poll_id` | integer | **Required.** Poll ID. |
| `owner_id` | integer | Poll owner ID. Default: `0`. |
| `answer_id` | integer | Answer option ID (compatibility parameter). |

### Result

Returns `1` upon successful vote revocation.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied: Poll is locked or isn't revotable` — Poll has ended, is locked, or revoking votes is disabled for this poll. |
| `251` | `Invalid poll id` — Poll not found. |

### Example Request
```http
POST /method/polls.deleteVote HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

poll_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
