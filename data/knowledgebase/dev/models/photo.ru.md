OpenVK-KB-Heading: Объект Photo

# Объект Photo

Объект **Photo** описывает фотографию в альбоме или прикреплении ВКонтакте / OpenVK. Структура возвращаемых полей зависит от версии API.

---

## Версия API 5.77 и выше (v >= 5.77)

Начиная с версии API 5.77, размеры изображений возвращаются в виде единого массива `sizes`:

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор фотографии. |
| `owner_id` | integer | Идентификатор владельца фотографии (пользователя или сообщества). |
| `album_id` | integer | Идентификатор альбома (`0` — со стены, `-3` — сохраненные). |
| `width` | integer | Ширина оригинала фотографии в пикселях. |
| `height` | integer | Высота оригинала фотографии в пикселях. |
| `text` | string | Текст описания фотографии. |
| `date` | integer | Дата добавления фотографии (Unix timestamp). |
| `access_key` | string | Специальный ключ доступа к фотографии. |
| `sizes` | array | Массив объектов с копиями фотографии разных размеров (`type`, `url`, `width`, `height`). |
| `orig_photo` | object | Объект с метаданными и ссылкой на исходное загруженное фото. |

### Пример объекта (v >= 5.77)
```json
{
    "id": 100,
    "owner_id": 1,
    "album_id": 0,
    "width": 1920,
    "height": 1080,
    "text": "Вид на закат",
    "date": 1696680000,
    "access_key": "abc123def456",
    "sizes": [
        {
            "type": "s",
            "url": "https://openvk.instance/photos/75/1_100.jpg",
            "width": 75,
            "height": 42
        },
        {
            "type": "m",
            "url": "https://openvk.instance/photos/130/1_100.jpg",
            "width": 130,
            "height": 73
        },
        {
            "type": "x",
            "url": "https://openvk.instance/photos/604/1_100.jpg",
            "width": 604,
            "height": 340
        }
    ]
}
```

---

## До версии API 5.77 (v < 5.77)

В версиях ниже 5.77 (включая API v3.x, v4.x и v5.0–v5.76) ссылки на копии изображений передаются отдельными строковыми полями:

| Поле | Тип | Описание |
| --- | --- | --- |
| `pid` / `id` | integer | Идентификатор фотографии. |
| `aid` / `album_id` | integer | Идентификатор альбома. |
| `owner_id` / `user_id` | integer | Идентификатор владельца фотографии. |
| `src_small` / `photo_75` | string | URL копии изображения 75x75px. |
| `src` / `photo_130` | string | URL копии изображения 130x130px. |
| `src_big` / `photo_604` | string | URL копии изображения 604x604px. |
| `src_xbig` / `photo_807` | string | URL копии изображения 807x807px. |
| `src_xxbig` / `photo_1280` | string | URL копии изображения 1280x1280px. |
| `src_xxxbig` / `photo_2560` | string | URL копии изображения 2560x2560px. |
| `src_original` | string | URL исходного изображения в максимальном качестве. |
| `created` / `date` | integer | Дата создания фото (Unix timestamp). |
| `text` | string | Описание фотографии. |

### Пример объекта (v < 5.77)
```json
{
    "pid": 100,
    "aid": 0,
    "owner_id": 1,
    "user_id": 1,
    "src_small": "https://openvk.instance/photos/75/1_100.jpg",
    "src": "https://openvk.instance/photos/130/1_100.jpg",
    "src_big": "https://openvk.instance/photos/604/1_100.jpg",
    "created": 1696680000,
    "text": "Вид на закат"
}
```
