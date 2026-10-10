OpenVK-KB-Heading: audio.subscribeToQueue

# audio.subscribeToQueue

Returns the playback queue URL for receiving real-time playback events.

> **Note:** In the current OpenVK implementation, this method is a stub returning an empty URL string.

### Authorization
No authorization is strictly required.

### Parameters
None.

### Result

Returns an object with the field:
* `url` (string) — queue server URL (empty string `""` in current version).

### Request Example
```http
POST /method/audio.subscribeToQueue HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "url": ""
    }
}
```
