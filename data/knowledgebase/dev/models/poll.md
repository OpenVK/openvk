OpenVK-KB-Heading: Poll Object

# Poll Object

The **Poll** object describes a voting poll in a wall post or message. The structure of the returned fields depends on the API version passed in the `v` parameter.

---

## API version 5.0 and higher (v >= 5.0)

API versions 5.0+ use the following fields:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Poll ID. |
| `owner_id` | integer | Poll owner ID. |
| `question` | string | Poll question text. |
| `votes` | integer | Total number of voters. |
| `answers` | array | Array of answer option objects (`id`, `text`, `votes`, `rate`). |
| `anonymous` | boolean | Whether the poll is anonymous. |
| `multiple` | boolean | Whether multiple choice is allowed. |
| `end_date` | integer | End date (Unix timestamp) or `0` if indefinite. |
| `closed` | boolean | Whether voting has ended. |
| `can_vote` | boolean | Whether current user can vote. |
| `can_share` | boolean | Whether sharing is allowed. |
| `answer_ids` | array | Array of answer IDs chosen by the current user. |
| `disable_unvote` | boolean | Whether revoting/unvoting is prohibited. |

### Answer Option Object (`answers[]`)
| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Answer option ID. |
| `text` | string | Option text. |
| `votes` | integer | Number of votes for this option. |
| `rate` | float | Percentage of votes (`0.0` to `100.0`). |

### Example Object (v >= 5.0)
```json
{
    "id": 1,
    "owner_id": 1,
    "question": "What is your favorite section in OpenVK?",
    "votes": 42,
    "anonymous": false,
    "multiple": false,
    "end_date": 0,
    "closed": false,
    "can_vote": true,
    "can_share": true,
    "answer_ids": [1],
    "answers": [
        {
            "id": 1,
            "text": "Messages",
            "votes": 25,
            "rate": 59.52
        },
        {
            "id": 2,
            "text": "Music",
            "votes": 17,
            "rate": 40.48
        }
    ]
}
```

---

## Before API version 5.0 (v < 5.0)

In API versions 3.x and 4.x, legacy `poll_id` is returned:

| Field | Type | Description |
| --- | --- | --- |
| `poll_id` / `id` | integer | Poll ID. |
| `owner_id` | integer | Owner ID. |
| `question` | string | Question text. |
| `votes` | integer | Total votes. |
| `answers` | array | List of answer options. |
| `answer_id` | integer | Selected answer ID. |

### Example Object (v < 5.0)
```json
{
    "poll_id": 1,
    "owner_id": 1,
    "question": "What is your favorite section in OpenVK?",
    "votes": 42,
    "answer_id": 1,
    "answers": [
        {
            "id": 1,
            "text": "Messages",
            "votes": 25,
            "rate": 59.52
        },
        {
            "id": 2,
            "text": "Music",
            "votes": 17,
            "rate": 40.48
        }
    ]
}
```
