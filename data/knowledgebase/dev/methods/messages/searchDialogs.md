OpenVK-KB-Heading: messages.searchDialogs

# messages.searchDialogs

Searches dialogues by interlocutor name or chat title (legacy method).

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `q` | string | Search query string. **Required.** |
| `limit` | integer | Maximum number of results to return. Default: `20`. |
| `fields` | string | Profile fields to return. |

### Result

Returns an array of user and chat objects matching the query.

### Example Request
```http
POST /method/messages.searchDialogs HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

q=Pavel&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
