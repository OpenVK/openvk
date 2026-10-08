OpenVK-KB-Heading: notifications.fetch

# notifications.fetch

Retrieves new notification events via the internal NotificationBroker based on the identifier of the last fetched event.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `last_id` | string | Identifier of the last received event (stream cursor). Default: `"0"`. |

### Result

Returns an object containing:
* `items` (array) — array of new notification objects;
* `profiles` (array) — array of user profiles;
* `groups` (array) — array of communities;
* `new_lastId` (string) — new last event ID (if new events arrived);
* `next_last_id` (string) — next cursor to use in polling.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `1981` | `Internal error during event processing` — Internal error during event processing. |

### Request Example
```http
POST /method/notifications.fetch HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

last_id=1700000000&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "items": [],
        "profiles": [],
        "groups": [],
        "next_last_id": "1700000000"
    }
}
```
