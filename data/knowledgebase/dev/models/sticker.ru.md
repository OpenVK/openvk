OpenVK-KB-Heading: Объект Sticker

# Объект Sticker

Объект **Sticker** описывает графический стикер или набор стикеров (стикерпак) ВКонтакте / OpenVK.

---

## Объект Sticker

| Поле | Тип | Описание |
| --- | --- | --- |
| `sticker_id` | integer | Идентификатор стикера. |
| `product_id` | integer | Идентификатор набора стикеров, к которому принадлежит стикер. |
| `images` | array | Массив объектов с изображениями стикера без фона (`url`, `width`, `height`). |
| `images_with_background` | array | Массив объектов с изображениями стикера с фоном (`url`, `width`, `height`). |
| `animation_url` | string\|null | *(Опционально)* URL анимации стикера (для анимированных стикеров). |

### Пример объекта Sticker
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

## Объект Sticker Pack

Описывает полный набор стикеров:

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор набора. |
| `title` | string | Название набора стикеров. |
| `author` | string | Автор стикерпака. |
| `description` | string | Описание набора. |
| `stickers` | array | Массив объектов `Sticker`, входящих в набор. |
| `icon` | object | Объект с иконками набора стикеров (`base_url`, `512`, `256`, `128`, `64`). |
| `free` | boolean | Бесплатен ли набор для пользователей. |
