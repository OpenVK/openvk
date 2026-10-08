OpenVK-KB-Heading: polls.getById

# polls.getById

Returns detailed information about a poll by its ID.

### Authorization
Does not strictly require authorization, but passing an `access_token` returns current user's voting state (`answer_ids`, `can_vote`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `poll_id` | integer | **Required.** Poll ID. |
| `extended` | boolean | `1` — return creator profile in `profiles` array, `0` — do not return. Default: `0`. |
| `fields` | string | Comma-separated list of profile fields (when `extended=1`). Default: `sex,screen_name,photo_50,photo_100,online_info,online`. |

### Result

Returns the poll object:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Poll ID. |
| `poll_id` | integer | Poll ID (for backwards compatibility). |
| `owner_id` | integer | Owner ID of the poll. |
| `author_id` | integer | Author ID of the poll. |
| `question` | string | Poll question. |
| `votes` | integer | Total votes count. |
| `multiple` | boolean | Whether multiple choice is enabled. |
| `anonymous` | integer | `1` for anonymous poll, `0` for public. |
| `disable_unvote` | boolean | Whether unvoting is disabled. |
| `closed` | boolean | Whether the poll has closed. |
| `end_date` | integer | Poll end timestamp or `0`. |
| `can_vote` | integer | `1` if current user can vote. |
| `can_share` | integer | `1` if the poll can be shared. |
| `answer_ids` | array | Array of answer option IDs selected by the current user. |
| `answers` | array | Array of answer option objects. |
| `profiles` | array | Array of author profiles (when `extended=1`). |

### Possible Errors

| Code | Description |
| --- | --- |
| `100` | `One of the parameters specified was missing or invalid: poll_id is incorrect` — Poll with specified ID was not found. |

### Example Request
```http
POST /method/polls.getById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

poll_id=1&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
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
        "votes": 10,
        "multiple": false,
        "anonymous": 0,
        "disable_unvote": false,
        "closed": false,
        "end_date": 0,
        "can_vote": 0,
        "can_share": 1,
        "can_edit": 0,
        "can_report": 0,
        "is_board": 0,
        "created": 0,
        "answer_ids": [1],
        "answers": [
            {
                "id": 1,
                "text": "Linux",
                "votes": 7,
                "rate": 70
            },
            {
                "id": 2,
                "text": "Windows",
                "votes": 2,
                "rate": 20
            },
            {
                "id": 3,
                "text": "macOS",
                "votes": 1,
                "rate": 10
            }
        ],
        "profiles": [
            {
                "id": 1,
                "first_name": "Pavel",
                "last_name": "Durov"
            }
        ]
    }
}
```
