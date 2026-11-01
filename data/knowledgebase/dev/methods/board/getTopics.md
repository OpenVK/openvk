OpenVK-KB-Heading: board.getTopics

# board.getTopics

Returns a list of topics on a community discussion board with options for topic ID filtering, preview snippets of first and last comments, and user permissions check.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | Community identifier (positive number). **Required.** |
| `topic_ids` | string | Comma-separated list of topic IDs (e.g. `1,2,5`). If passed, only returns the requested topics. |
| `offset` | integer | Offset needed to return a specific subset of topics. Default: `0`. |
| `count` | integer | Number of topics to return (from `1` to `100`). Default: `10`. |
| `extended` | boolean | `true` to return user and group profile objects. Default: `false`. |
| `preview` | integer | `1` to include `first_comment` and `last_comment` text snippets, `0` otherwise. Default: `0`. |
| `preview_length` | integer | Maximum character length for preview snippets. Default: `90`. |

### Result

For API versions 5.0 and higher, returns an object containing:
* `count` (integer) — total count of topics in the community;
* `items` (array) — array of topic objects;
* `can_add_topics` (integer) — `1` if current user can create topics, `0` otherwise;
* `default_order` (integer) — default sort order (`1` — by last update);
* `topics` (array) — array of topic objects (for backward compatibility).

Each topic object includes:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Topic identifier inside the community (virtual ID). |
| `tid` | integer | Alias for `id`. |
| `title` | string | Topic title (or linked chat title). |
| `created` | integer | Creation time (Unix timestamp). |
| `created_by` | integer | Author identifier (positive for user, negative for group). |
| `updated` | integer | Last comment / update time (Unix timestamp). |
| `updated_by` | integer | Author identifier of the last comment. |
| `is_closed` | integer | `1` if closed, `0` if open. |
| `is_fixed` | integer | `1` if pinned to the top, `0` otherwise. |
| `comments` | integer | Total comment count in topic. |
| `type` | string | Topic type: `"topic"` (regular discussion) or `"chat"` (linked group conversation). |
| `first_comment` | string | Text snippet of first comment (when `preview=1`). |
| `last_comment` | string | Text snippet of last comment (when `preview=1`). |

### Error Codes

| Code | Description |
| --- | --- |
| `4` | `Invalid count` |
| `5` | `User authorization failed: no access_token passed.` |
| `15` | `Access denied` |

### Request Example
```http
POST /method/board.getTopics HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&preview=1&count=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 5,
        "items": [
            {
                "id": 1,
                "tid": 1,
                "title": "Community Guidelines",
                "created": 1609459200,
                "created_by": 1,
                "updated": 1612137600,
                "updated_by": 1,
                "is_closed": 1,
                "is_fixed": 1,
                "comments": 1,
                "type": "topic",
                "first_comment": "Welcome! Please read the rules...",
                "last_comment": "Welcome! Please read the rules..."
            }
        ],
        "can_add_topics": 1,
        "default_order": 1
    }
}
```
