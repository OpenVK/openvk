OpenVK-KB-Heading: store.deactivateProduct

# store.deactivateProduct

Deactivates a product (sticker pack) and hides it from quick access in the sticker keyboard.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `product_id` | integer | **Required.** Product (sticker pack) ID to deactivate. |
| `type` | string | Product type. Default: `stickers`. |

### Result

Returns an object containing:
| Field | Type | Description |
| --- | --- | --- |
| `success` | integer | Always `1` on successful deactivation. |

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Product not found` — Product with specified ID was not found. |

### Example Request
```http
POST /method/store.deactivateProduct HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

product_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "success": 1
    }
}
```
