OpenVK-KB-Heading: pay.getIdByMarketingId

# pay.getIdByMarketingId

Verifies the HMAC SHA-512/224 signature and converts a marketing identifier into a numeric ID.

### Authorization
This method is public and does not require user authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `marketing_id` | string | **Required**. Marketing identifier in `hexId_signature` format. |

### Result

Returns the decoded numeric identifier (`integer`).

### Errors

| Code | Description |
| --- | --- |
| `4` | `Invalid marketing id` — Invalid marketing ID format or signature verification failed. |

### Request Example
```http
POST /method/pay.getIdByMarketingId HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

marketing_id=2a_b64signature&v=5.138
```

### Response Example
```json
{
    "response": 42
}
```
