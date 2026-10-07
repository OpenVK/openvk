OpenVK-KB-Heading: account.getToggles

# account.getToggles

Returns feature toggles and active A/B test experiments for client applications.

> **Note (compatibility stub):** Returns a fixed list of disabled feature toggles (`core_common_websocket`, `core_common_websocket_api`, `core_common_websocket_compress`, `core_common_websocket_rate_lmt`, `queue_new_subscribe`) to keep official clients from initiating unsupported protocol handshakes.

### Authorization
This method requires user authorization (`access_token`).

### Parameters
This method does not accept any parameters.

### Result
Returns an object containing feature toggles and experiments:

| Field | Type | Description |
| --- | --- | --- |
| `toggles` | array | Array of feature flag objects (`name`, `enabled`, `value`). |
| `version` | integer | Toggle configuration schema version (`1`). |
| `ab_tests` | array | Array of active A/B experiments (`[]`). |

### Example Request
```http
POST /method/account.getToggles HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "toggles": [
            {
                "name": "core_common_websocket",
                "enabled": false,
                "value": null
            },
            {
                "name": "core_common_websocket_api",
                "enabled": false,
                "value": null
            },
            {
                "name": "core_common_websocket_compress",
                "enabled": false,
                "value": null
            },
            {
                "name": "core_common_websocket_rate_lmt",
                "enabled": false,
                "value": null
            },
            {
                "name": "queue_new_subscribe",
                "enabled": false,
                "value": null
            }
        ],
        "version": 1,
        "ab_tests": []
    }
}
```
