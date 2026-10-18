OpenVK-KB-Heading: search.getHints

# search.getHints

Returns search hints and suggestions for fast navigation to users, friends, and communities.

> **Note:** In the current version of OpenVK, this method is a compatibility stub and returns an empty array.

### Authorization
Requires user authorization (`access_token`).

### Parameters
This method accepts no required parameters.

### Result

Returns an empty array `[]`.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |

### Example Request
```http
POST /method/search.getHints HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": []
}
```
