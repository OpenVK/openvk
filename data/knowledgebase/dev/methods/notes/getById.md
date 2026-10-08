OpenVK-KB-Heading: notes.getById

# notes.getById

Returns detailed information about a note by its identifier and owner identifier.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `note_id` | integer | **Required**. Note identifier. |
| `owner_id` | integer | **Required**. Note owner identifier. |
| `need_wiki` | boolean | Whether to return wiki markup. Default: `false`. |

### Result

Returns a note object with the following fields:
* `id` (integer) — note identifier;
* `owner_id` (integer) — owner identifier;
* `title` (string) — note title;
* `text` (string) — note text;
* `date` (integer) — creation date (Unix timestamp);
* `comments` (integer) — total comments count;
* `read_comments` (integer) — read comments count;
* `view_url` (string) — relative URL of the note page.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — Note was not found, is deleted, or access is restricted by privacy settings. |

### Request Example
```http
POST /method/notes.getById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

note_id=1&owner_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "id": 1,
        "owner_id": 1,
        "title": "My first note",
        "text": "Hello, world!",
        "date": 1700000000,
        "comments": 0,
        "read_comments": 0,
        "view_url": "/notes1_1"
    }
}
```
