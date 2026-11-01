OpenVK-KB-Heading: polls.create

# polls.create

Creates a new poll for the current user.

### Authorization
Requires user authorization (`access_token`) with the `wall` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `question` | string | **Required.** Question text. |
| `add_answers` | string | **Required.** JSON array of string answer options (e.g. `["Yes", "No"]`). |
| `disable_unvote` | boolean | `1` — forbid revoking votes, `0` — allow revoking votes. Default: `0`. |
| `is_anonymous` | boolean | `1` — anonymous poll, `0` — public poll. Default: `0`. |
| `is_multiple` | boolean | `1` — allow multiple choice, `0` — single choice only. Default: `0`. |
| `end_date` | integer | Poll closing time (Unix timestamp). Must be in the future, up to 365 days max. Default: `0` (unlimited). |

### Result

Returns the created poll object:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Poll ID. |
| `poll_id` | integer | Poll ID (for backwards compatibility). |
| `owner_id` | integer | Owner ID of the poll. |
| `author_id` | integer | Author ID of the poll. |
| `question` | string | Question text. |
| `votes` | integer | Total votes count (`0` initially). |
| `multiple` | boolean | Whether multiple choice is enabled. |
| `anonymous` | integer | `1` for anonymous poll, `0` for public. |
| `disable_unvote` | boolean | Whether revoking votes is disabled. |
| `closed` | boolean | Whether the poll has closed. |
| `end_date` | integer | Poll end timestamp or `0`. |
| `can_vote` | integer | `1` if the current user can vote. |
| `can_share` | integer | `1` if the poll can be shared. |
| `answer_ids` | array | Array of answer option IDs selected by the current user. |
| `answers` | array | Array of answer option objects. |

Each object in the `answers` array contains:
* `id` (integer) — answer option ID;
* `text` (string) — answer text;
* `votes` (integer) — number of votes;
* `rate` (float) — percentage of total votes.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `51` | `Too many options` — Exceeded maximum allowed answer options. |
| `62` | `Invalid options` — Invalid or empty `add_answers` JSON array. |
| `89` | `End date is too big` — `end_date` exceeds 1 year from now. |

### Example Request
```http
POST /method/polls.create HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

question=What%20is%20your%20favorite%20OS?&add_answers=["Linux","Windows","macOS"]&is_anonymous=0&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "id": 1,
        "poll_id": 1,
        "owner_id": 1,
        "author_id": 1,
        "question": "What is your favorite OS?",
        "votes": 0,
        "multiple": false,
        "anonymous": 0,
        "disable_unvote": false,
        "closed": false,
        "end_date": 0,
        "can_vote": 1,
        "can_share": 1,
        "can_edit": 0,
        "can_report": 0,
        "is_board": 0,
        "created": 0,
        "answer_ids": [],
        "answers": [
            {
                "id": 1,
                "text": "Linux",
                "votes": 0,
                "rate": 0
            },
            {
                "id": 2,
                "text": "Windows",
                "votes": 0,
                "rate": 0
            },
            {
                "id": 3,
                "text": "macOS",
                "votes": 0,
                "rate": 0
            }
        ]
    }
}
```
