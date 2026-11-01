OpenVK-KB-Heading: account.getCounters

# account.getCounters

Returns counters of unread messages, notifications, and incoming friend requests.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `filter` | string | Comma-separated list of counter keys to return (`friends`, `notifications`, `messages`). If omitted, all counters are returned. |

### Result
Returns an object containing the requested counters:

| Field | Type | Description |
| --- | --- | --- |
| `friends` | integer | Number of incoming friend requests. |
| `notifications` | integer | Number of unviewed notifications. |
| `messages` | integer | Number of conversations with unread messages. |

### Example Request
```http
POST /method/account.getCounters HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

filter=messages,notifications&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "notifications": 3,
        "messages": 5
    }
}
```
