OpenVK-KB-Heading: photos.getUserPhotos

# photos.getUserPhotos

Возвращает список всех фотографий пользователя в обратном хронологическом порядке. Является псевдонимом для метода [photos.getAll](/dev/methods/photos/getAll).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `user_id` | integer | Идентификатор пользователя. Если не указан, используется `owner_id`. |
| `owner_id` | integer | Идентификатор владельца (альтернатива `user_id`). |
| `extended` | boolean | `1` — возвращать дополнительные поля (`likes`, `comments`, `can_comment`, `can_repost`), `0` — нет. По умолчанию: `0`. |
| `offset` | integer | Смещение относительно начала списка. По умолчанию: `0`. |
| `count` | integer | Количество фотографий. По умолчанию: `100`. |
| `photo_sizes` | boolean | `1` — возвращать массив размеров `sizes`, `0` — нет. По умолчанию: `0`. |

### Результат

Возвращает список фотографий в формате, идентичном [photos.getAll](/dev/methods/photos/getAll).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Доступ к фотографиям пользователя ограничен приватностью. |

### Пример запроса
```http
POST /method/photos.getUserPhotos HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&count=20&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
                "pid": 1,
                "owner_id": 1,
                "user_id": 1,
                "album_id": 1,
                "aid": 1,
                "width": 800,
                "height": 600,
                "text": "Фото",
                "date": 1609459200,
                "access_key": "",
                "photo_75": "https://openvk.instance/photos/1_1_cropped/miniscule.jpeg",
                "photo_130": "https://openvk.instance/photos/1_1_cropped/tiny.jpeg",
                "photo_604": "https://openvk.instance/photos/1_1_cropped/normal.jpeg",
                "url": "https://openvk.instance/photos/1_1.jpeg"
            }
        ]
    }
}
```
