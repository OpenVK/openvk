OpenVK-KB-Heading: pay.verifyOrder

# pay.verifyOrder

Verifies the cryptographic HMAC Whirlpool signature and order parameters of an application payment. Can only be invoked by the owner of the application.

### Authorization
This method requires user authorization (`access_token`). The user must be the owner of the application.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `app_id` | integer | **Required**. Application identifier. |
| `amount` | float | **Required**. Order amount. |
| `signature` | string | **Required**. Order signature string formatted as `time,signature`. |

### Result

Returns `true` upon successful signature and order verification.

### Errors

| Code | Description |
| --- | --- |
| `4` | `Invalid order` — Invalid order cryptographic signature. |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access error` — Current user is not the owner of the specified application. |
| `26` | `No app found with this id` — No application exists with the given `app_id`. |

### Request Example
```http
POST /method/pay.verifyOrder HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

app_id=1&amount=100.50&signature=1700000000%2Cwhirlpool_hash&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": true
}
```
