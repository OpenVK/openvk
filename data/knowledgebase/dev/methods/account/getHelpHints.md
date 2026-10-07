OpenVK-KB-Heading: account.getHelpHints

# account.getHelpHints

Returns contextual help hints for a specified section or application.

> **Note (compatibility stub):** Context help hints are not implemented in OpenVK. The method returns an empty payload (`{"hints": [], "items": [], "count": 0}`).

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `section` | string | Help section identifier. |
| `app_id` | string | Application ID. |
| `fields` | string | Additional fields list. |

### Result
Returns an object containing hint arrays:

| Field | Type | Description |
| --- | --- | --- |
| `hints` | array | Array of hint texts (`[]`). |
| `items` | array | Array of hint items (`[]`). |
| `count` | integer | Number of hints (`0`). |

### Example Request
```http
POST /method/account.getHelpHints HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

section=general&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "hints": [],
        "items": [],
        "count": 0
    }
}
```
