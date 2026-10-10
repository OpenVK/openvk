OpenVK-KB-Heading: Объект Album

# Объект Album

Объект **Album** описывает фотоальбом пользователя или сообщества ВКонтакте / OpenVK. Структура возвращаемых полей зависит от версии API, переданной в параметре `v`.

---

## Версия API 5.0 и выше (v >= 5.0)

В версиях API 5.0+ используются следующие поля:

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор фотоальбома. |
| `thumb_id` | integer | Идентификатор фотографии-обложки альбома. |
| `owner_id` | integer | Идентификатор владельца альбома. |
| `title` | string | Название фотоальбома. |
| `description` | string | Описание фотоальбома. |
| `created` | integer | Дата создания альбома (Unix timestamp). |
| `updated` | integer | Дата последнего обновления альбома (Unix timestamp). |
| `size` | integer | Количество фотографий в альбоме. |
| `thumb_src` | string | URL обложки альбома. |
| `can_upload` | integer | `1`, если текущий пользователь может загружать фотографии в альбом. |
| `sizes` | array | *(Опционально)* Копии обложки альбома разных размеров (при передаче `need_covers=1, photo_sizes=1`). |

### Пример объекта (v >= 5.0)
```json
{
    "id": 1,
    "thumb_id": 100,
    "owner_id": 1,
    "title": "Летние фотографии",
    "description": "Поездка на море",
    "created": 1696680000,
    "updated": 1696683600,
    "size": 15,
    "thumb_src": "https://openvk.instance/photos/130/1_100.jpg",
    "can_upload": 1
}
```

---

## До версии API 5.0 (v < 5.0)

В версиях API 3.x и 4.x возвращается поле `aid`:

| Поле | Тип | Описание |
| --- | --- | --- |
| `aid` / `id` | integer | Идентификатор альбома (эквивалент `id` в API v5.0+). |
| `thumb_id` | integer | Идентификатор обложки. |
| `owner_id` | integer | Идентификатор владельца. |
| `title` | string | Название альбома. |
| `description` | string | Описание альбома. |
| `created` | integer | Дата создания. |
| `updated` | integer | Дата обновления. |
| `size` | integer | Число фото. |
| `thumb_src` | string | URL обложки. |

### Пример объекта (v < 5.0)
```json
{
    "aid": 1,
    "thumb_id": 100,
    "owner_id": 1,
    "title": "Летние фотографии",
    "description": "Поездка на море",
    "created": 1696680000,
    "updated": 1696683600,
    "size": 15,
    "thumb_src": "https://openvk.instance/photos/130/1_100.jpg"
}
```
