OpenVK-KB-Heading: store.activateProduct

# store.activateProduct

Activates a product (sticker pack) and adds it to quick access in the sticker keyboard.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `product_id` | integer | **Required.** Product (sticker pack) ID to activate. |
| `type` | string | Product type. Default: `stickers`. |

### Result

Returns an object containing:
| Field | Type | Description |
| --- | --- | --- |
| `success` | integer | Always `1` on successful activation. |

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Product not found` — Product with specified ID was not found. |

### Example Request
```http
POST /method/store.activateProduct HTTP/1.1
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
