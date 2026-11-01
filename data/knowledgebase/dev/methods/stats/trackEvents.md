OpenVK-KB-Heading: stats.trackEvents

# stats.trackEvents

Submits analytics events from client applications and games.

> **Note:** In OpenVK, this method is a compatibility stub and always returns `1`.

### Authorization
Does not require authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `events` | string | JSON string or array of event objects for analytics. |

### Result

Returns `1`.

### Example Request
```http
POST /method/stats.trackEvents HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

events=[{"event_type":"app_open"}]&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
