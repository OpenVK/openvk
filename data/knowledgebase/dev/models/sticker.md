OpenVK-KB-Heading: Sticker Object

# Sticker Object

The **Sticker** object describes a graphic sticker or sticker pack in VK / OpenVK.

---

## Sticker Object

| Field | Type | Description |
| --- | --- | --- |
| `sticker_id` | integer | Sticker ID. |
| `product_id` | integer | Sticker pack ID the sticker belongs to. |
| `images` | array | Array of sticker image objects without background (`url`, `width`, `height`). |
| `images_with_background` | array | Array of sticker image objects with background (`url`, `width`, `height`). |
| `animation_url` | string\|null | *(Optional)* Sticker animation URL (for animated stickers). |

### Example Sticker Object
```json
{
    "sticker_id": 1,
    "product_id": 1,
    "images": [
        {
            "url": "https://openvk.instance/images/stickers/1/64b.png",
            "width": 64,
            "height": 64
        },
        {
            "url": "https://openvk.instance/images/stickers/1/128b.png",
            "width": 128,
            "height": 128
        },
        {
            "url": "https://openvk.instance/images/stickers/1/256b.png",
            "width": 256,
            "height": 256
        },
        {
            "url": "https://openvk.instance/images/stickers/1/352b.png",
            "width": 352,
            "height": 352
        },
        {
            "url": "https://openvk.instance/images/stickers/1/512.png",
            "width": 512,
            "height": 512
        }
    ],
    "images_with_background": []
}
```

---

## Sticker Pack Object

Describes the complete sticker pack:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Pack ID. |
| `title` | string | Pack title. |
| `author` | string | Pack author. |
| `description` | string | Pack description. |
| `stickers` | array | Array of `Sticker` objects included in the pack. |
| `icon` | object | Icon URLs object (`base_url`, `512`, `256`, `128`, `64`). |
| `free` | boolean | Whether the pack is free. |
