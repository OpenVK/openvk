OpenVK-KB-Heading: stickers.buy

# stickers.buy

Purchases or activates a sticker pack for the current user.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `stickerpack_id` | integer | **Required.** ID of the sticker pack to purchase. |

### Result

Returns an object containing:
| Field | Type | Description |
| --- | --- | --- |
| `success` | integer | Always `1` on successful purchase/activation. |
| `pack_id` | integer | ID of the purchased sticker pack. |

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Sticker pack not found` / `Sticker not available` — Sticker pack not found or unavailable for purchase. |
| `15` | `Cannot purchase this pack` — Insufficient coin balance or pack cannot be purchased. |

### Example Request
```http
POST /method/stickers.buy HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

stickerpack_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "success": 1,
        "pack_id": 1
    }
}
```
