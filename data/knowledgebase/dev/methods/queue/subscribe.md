OpenVK-KB-Heading: queue.subscribe

# queue.subscribe

Subscribes the authorized user to one or more real-time event queues and returns queue server URLs, secret access keys, and the current timestamp.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `queue_id` | string | Single queue identifier. |
| `queue_ids` | string | Comma-separated list of queue identifiers. |
| `ts` | integer | Starting event timestamp. Default: `0` (current timestamp). |

### Result

Returns an object containing connection credentials:
* `base_url` (string) — base URL of the queue endpoint (`/queue`);
* `queues` (array) — array of queue configurations:
  * `queue_id` (string) — queue identifier;
  * `id` (string) — queue identifier;
  * `name` (string) — queue name;
  * `base_url` (string) — queue server URL;
  * `key` (string) — secret access key for event stream;
  * `ts` (string) — string timestamp;
  * `timestamp` (integer) — numeric timestamp;
  * `wait` (integer) — wait timeout in seconds (25);
  * `events` (array) — initial events array.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |

### Request Example
```http
POST /method/queue.subscribe HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

queue_id=im1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "base_url": "https://openvk.instance/queue",
        "queues": [
            {
                "queue_id": "im1",
                "id": "im1",
                "name": "im1",
                "base_url": "https://openvk.instance/queue",
                "key": "a1b2c3d4e5f67890a1b2c3d4e5f67890",
                "ts": "1700000000",
                "timestamp": 1700000000,
                "wait": 25,
                "events": []
            }
        ]
    }
}
```
