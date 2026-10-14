OpenVK-KB-Heading: Topic Object

# Topic Object

The **Topic** object describes a discussion thread in a community board. The structure of the returned fields depends on the API version passed in the `v` parameter.

---

## API version 5.0 and higher (v >= 5.0)

API versions 5.0+ use the following fields:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Topic ID. |
| `title` | string | Topic title. |
| `created` | integer | Topic creation timestamp (Unix timestamp). |
| `created_by` | integer | Creator user/group ID. |
| `updated` | integer | Last message timestamp (Unix timestamp). |
| `updated_by` | integer | Last message author user/group ID. |
| `is_closed` | integer | `1` if topic is closed, `0` otherwise. |
| `is_fixed` | integer | `1` if topic is pinned, `0` otherwise. |
| `comments` | integer | Number of comments in the topic. |
| `first_comment` | string\|null | *(Optional)* First comment text preview. |
| `last_comment` | string\|null | *(Optional)* Last comment text preview. |

### Example Object (v >= 5.0)
```json
{
    "id": 5,
    "title": "Community Rules",
    "created": 1696680000,
    "created_by": 1,
    "updated": 1696683600,
    "updated_by": 1,
    "is_closed": 1,
    "is_fixed": 1,
    "comments": 1
}
```

---

## Before API version 5.0 (v < 5.0)

In API versions 3.x and 4.x, legacy `tid` is returned:

| Field | Type | Description |
| --- | --- | --- |
| `tid` / `id` | integer | Topic ID (equivalent to `id` in API v5.0+). |
| `title` | string | Topic title. |
| `created` | integer | Creation timestamp (Unix timestamp). |
| `created_by` | integer | Author ID. |
| `updated` | integer | Last update timestamp. |
| `updated_by` | integer | Last reply author ID. |
| `is_closed` | integer | Closed flag. |
| `is_fixed` | integer | Pinned flag. |
| `comments` | integer | Comments count. |

### Example Object (v < 5.0)
```json
{
    "tid": 5,
    "title": "Community Rules",
    "created": 1696680000,
    "created_by": 1,
    "updated": 1696683600,
    "updated_by": 1,
    "is_closed": 1,
    "is_fixed": 1,
    "comments": 1
}
```
