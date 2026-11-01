OpenVK-KB-Heading: queue.unsubscribe

# queue.unsubscribe

Unsubscribes the user from real-time event queues.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `queue_id` | string | Queue identifier. |
| `queue_ids` | string | Comma-separated list of queue identifiers. |

### Result

Returns `1`.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |

### Request Example
```http
POST /method/queue.unsubscribe HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

queue_id=im1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
