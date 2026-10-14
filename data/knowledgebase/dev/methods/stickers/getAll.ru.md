OpenVK-KB-Heading: stickers.getAll

# stickers.getAll

Возвращает список всех доступных в каталоге стикерпаков.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `count` | integer | Количество возвращаемых стикерпаков. По умолчанию: `50`. |
| `offset` | integer | Смещение относительно начала каталога. По умолчанию: `0`. |

### Результат

Возвращает объект с полями:
* `count` (integer) — общее количество стикерпаков в каталоге;
* `items` (array) — массив объектов стикерпаков.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/stickers.getAll HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

count=20&offset=0&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 5,
        "items": [
            {
                "id": 1,
                "name": "Котик Персик",
                "title": "Котик Персик",
                "description": "Милый рыжий кот",
                "slug": "peach",
                "price": 0,
                "end_time": 0,
                "purchased": 1,
                "photo_128": "https://openvk.instance/images/stickers/1/128b.png",
                "photo_256": "https://openvk.instance/images/stickers/1/256b.png",
                "is_animated": false,
                "animation_url": null,
                "stickers_count": 24,
                "sticker_ids": [1, 2, 3]
            }
        ]
    }
}
```
