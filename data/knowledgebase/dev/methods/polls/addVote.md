OpenVK-KB-Heading: polls.addVote

# polls.addVote

Casts the current user's vote for one or more answer options in a poll.

### Authorization
Requires user authorization (`access_token`) with the `wall` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `poll_id` | integer | **Required.** Poll ID. |
| `answer_ids` | string | Comma-separated IDs of selected answer options (for multiple-choice polls). |
| `answer_id` | string | ID of selected answer option (for single-choice polls). |
| `owner_id` | integer | Poll owner ID. Default: `0`. |

> **Note:** At least one of `answer_ids` or `answer_id` must be passed.

### Result

Returns:
* `1` — vote successfully recorded;
* `0` — user has already voted or the poll is locked/ended.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `100` | `Required parameter 'answer_ids' or 'answer_id' is missing.` — Missing answer ID parameter. |
| `251` | `Invalid poll id` — Poll not found. |

### Example Request
```http
POST /method/polls.addVote HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

poll_id=1&answer_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
