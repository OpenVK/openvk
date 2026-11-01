OpenVK-KB-Heading: notes.add

# notes.add

Creates a new note for the current user.

### Authorization
This method requires user authorization (`access_token`). Executes a write action.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `title` | string | **Required**. Note title. |
| `text` | string | Note content text. |

### Result

Returns the identifier of the created note (`integer`).

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `100` | `Required parameter 'title' missing.` — Required `title` parameter was not passed. |

### Request Example
```http
POST /method/notes.add HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

title=My%20first%20note&text=Hello%2C%20world!&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
