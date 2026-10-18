OpenVK-KB-Heading: utils.resolveAttachments

# utils.resolveAttachments

Преобразует строку с перечислением медиавложений в массив стандартных структур вложений VK API.

### Авторизация
Для вызова этого метода необходим токен пользователя.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `attachments` | string | **Обязательный параметр.** Строка со списком вложений через запятую в формате `<тип><владелец>_<id>` (например, `photo1_23,video-5_10,audio1_45`). |
| `allow_type` | integer | Фильтр поддерживаемых типов: `0` — все типы (`photo`, `video`, `doc`, `audio`, `wall`, `sticker`, `gift`), `1` — с поддержкой заметок (`photo`, `video`, `note`, `audio`, `sticker`, `gift`). По умолчанию: `0`. |

### Результат

Возвращает массив объектов вложений. Для каждого вложения возвращается объект с полем `type` и соответствующим объектом данных (например, `photo`, `video`, `audio`). Если вложение недоступно или скрыто настройками приватности, возвращается объект с типом `"unknown"`.

### Пример запроса
```http
POST /method/utils.resolveAttachments HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

attachments=photo1_12,audio1_10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": [
        {
            "type": "photo",
            "photo": {
                "id": 12,
                "owner_id": 1,
                "album_id": -6,
                "text": "Красивый закат",
                "date": 1775650000,
                "sizes": [
                    {
                        "type": "m",
                        "url": "https://openvk.instance/photos/1_12_m.jpg",
                        "width": 320,
                        "height": 240
                    }
                ]
            }
        },
        {
            "type": "audio",
            "audio": {
                "id": 10,
                "owner_id": 1,
                "artist": "Даниил Мысливец",
                "title": "Данилкинс вами недоволен!",
                "duration": 285,
                "url": "https://openvk.instance/audio/1_10.mp3"
            }
        }
    ]
}
```
