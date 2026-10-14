OpenVK-KB-Heading: messages.getChatUsers

# messages.getChatUsers

Returns a list of IDs or user profiles of participants in a multi-user chat.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `chat_id` | integer | Chat ID (`1...N`). **Required.** |
| `fields` | string | Profile fields to return. If omitted, returns an array of integer user IDs. |
| `name_case` | string | Grammatical case. |

### Result

Returns an array of user IDs (integers) or array of user profile objects (when `fields` is specified).

### Example Request
```http
POST /method/messages.getChatUsers HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

chat_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": [1, 2, 3]
}
```
