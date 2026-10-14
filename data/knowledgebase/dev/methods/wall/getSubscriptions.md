OpenVK-KB-Heading: wall.getSubscriptions

# wall.getSubscriptions

Returns a list of communities and users whose wall updates the current user is subscribed to.

### Authorization
Requires user authorization token.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `offset` | integer | Offset needed to return a specific subset of subscriptions. Default: `0`. |
| `count` | integer | Number of subscriptions to return. Default: `20`. |
| `extended` | integer | `1` — return user and group objects, `0` — identifiers only. Default: `0`. |
| `fields` | string | Comma-separated list of profile and group fields. |

### Result

Returns an object with the following fields:

| Field | Type | Description |
| --- | --- | --- |
| `count` | integer | Total number of subscriptions. |
| `items` | array | Array of subscription objects or IDs. |

### Example Request
```http
POST /method/wall.getSubscriptions HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 0,
        "items": []
    }
}
```
