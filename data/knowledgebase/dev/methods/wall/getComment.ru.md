OpenVK-KB-Heading: wall.getComment

# wall.getComment

Возвращает подробную информацию об отдельном комментарии к записи на стене.

### Авторизация
Для вызова этого метода необходим токен пользователя.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | **Обязательный параметр.** Идентификатор владельца стены. |
| `comment_id` | integer | **Обязательный параметр.** Идентификатор комментария. |
| `extended` | boolean / integer | `1` — возвращать профили и сообщества авторов (`profiles`, `groups`), `0` — только комментарий. По умолчанию: `0`. |
| `fields` | string | Список дополнительных полей профилей через запятую. |

### Результат

Возвращает объект с массивом `items`, содержащим запрошенный комментарий, и служебными флагами `can_post` и `show_reply_button`.

### Пример запроса
```http
POST /method/wall.getComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&comment_id=25&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "items": [
            {
                "id": 25,
                "from_id": 2,
                "date": 1775651000,
                "text": "Отличная новость!",
                "post_id": 10,
                "owner_id": 1,
                "likes": {
                    "count": 3,
                    "user_likes": 0,
                    "can_like": 1
                }
            }
        ],
        "can_post": true,
        "show_reply_button": true
    }
}
```
