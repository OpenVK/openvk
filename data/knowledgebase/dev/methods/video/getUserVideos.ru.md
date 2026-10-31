OpenVK-KB-Heading: video.getUserVideos

# video.getUserVideos

Возвращает список видеозаписей указанного пользователя.

> **Примечание:** Этот метод является удобным псевдонимом для вызова [video.get](/dev/methods/video/get) с фильтрацией по идентификатору пользователя.

### Авторизация
Этот метод не требует обязательной авторизации при просмотре открытых видеозаписей.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `user_id` | integer | Идентификатор пользователя, видеозаписи которого необходимо получить. По умолчанию: ID текущего пользователя. |
| `offset` | integer | Смещение относительно начала списка. По умолчанию: `0`. |
| `count` | integer | Количество возвращаемых записей. По умолчанию: `30`. |
| `extended` | integer | `1` — возвращать профили и группы, `0` — только видеозаписи. По умолчанию: `0`. |

### Результат

Возвращает объект с полями `count` (общее число видеозаписей) и `items` (массив объектов видеозаписей).

### Пример запроса
```http
POST /method/video.getUserVideos HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&count=2&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
                "owner_id": 1,
                "title": "Мое видео",
                "description": "Описание ролика",
                "duration": 300,
                "photo_320": "https://openvk.instance/videos/thumb_1_1.jpg",
                "date": 1775650000,
                "views": 42,
                "comments": 1,
                "player": "https://openvk.instance/video_ext.php?oid=1&id=1"
            }
        ]
    }
}
```
