OpenVK-KB-Heading: store.buy

# store.buy

Purchases a product (sticker pack) in the store using coins from the current user's balance.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `product_id` | integer | Product (sticker pack) ID. |
| `stickerpack_id` | integer | Alternative pack ID parameter (for backwards compatibility). |

> **Note:** At least one of `product_id` or `stickerpack_id` must be passed.

### Result

Returns an object containing:
| Field | Type | Description |
| --- | --- | --- |
| `success` | integer | Always `1` on successful purchase/activation. |
| `product_id` | integer | ID of the purchased product. |
| `pack_id` | integer | ID of the purchased sticker pack. |

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Sticker pack not found` / `Sticker not available` — Product not found or unavailable for purchase. |
| `15` | `Cannot purchase this pack` — Insufficient coin balance or purchase not allowed. |

### Example Request
```http
POST /method/store.buy HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

product_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "success": 1,
        "product_id": 1,
        "pack_id": 1
    }
}
```
