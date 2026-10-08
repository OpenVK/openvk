OpenVK-KB-Heading: ovk.test

# ovk.test

Diagnostic method to test API connection, authorization state, and protocol version.

### Authorization
This method is public, but dynamically returns authorization status if an `access_token` is provided.

### Parameters
Takes no mandatory parameters. An optional `auth_mechanism` parameter may be passed (default `access_token`).

### Result

Returns an object with the following fields:
* `authorized` (boolean) — `true` if authorized with valid user credentials, `false` otherwise;
* `auth_with` (string) — authorization mechanism used;
* `version` (string / float) — declared VKAPI protocol version.

### Request Example
```http
POST /method/ovk.test HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "authorized": true,
        "auth_with": "access_token",
        "version": "5.138"
    }
}
```
