OpenVK-KB-Heading: wall.getById

# wall.getById

Возвращает информацию о записях на стене по их идентификаторам.

### Авторизация
Этот метод не требует обязательной авторизации при просмотре открытых записей.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `posts` | string | **Обязательный параметр.** Идентификаторы записей через запятую в формате `<owner_id>_<post_id>` (например, `1_10,-5_42`). |
| `extended` | integer | `1` — возвращать информацию о пользователях и сообществах (`profiles`, `groups`), `0` — только записи. По умолчанию: `0`. |
| `fields` | string | Список дополнительных полей профилей и сообществ через запятую (при `extended=1`). |

### Результат

В версиях API 5.x при `extended=0` возвращает массив объектов записей. При `extended=1` возвращает объект:

| Поле | Тип | Описание |
| --- | --- | --- |
| `items` | array | Массив запрошенных объектов записей. |
| `profiles` | array | Профили авторов записей и вложений. |
| `groups` | array | Сообщества авторов записей и вложений. |

### Пример запроса
```http
POST /method/wall.getById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

posts=1_10&extended=1&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "items": [
            {
                "id": 10,
                "from_id": 1,
                "owner_id": 1,
                "date": 1775650000,
                "text": "Добро пожаловать на мою страницу!",
                "comments": {
                    "count": 2,
                    "can_post": 1
                },
                "likes": {
                    "count": 5,
                    "user_likes": 0,
                    "can_like": 1
                },
                "reposts": {
                    "count": 1,
                    "user_reposted": 0
                }
            }
        ],
        "profiles": [
            {
                "id": 1,
                "first_name": "Павел",
                "last_name": "Дуров",
                "photo_50": "/assets/packages/static/openvk/img/camera_50.png"
            }
        ],
        "groups": []
    }
}
```
