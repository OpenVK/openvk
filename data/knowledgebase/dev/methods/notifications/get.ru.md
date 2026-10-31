OpenVK-KB-Heading: notifications.get

# notifications.get

Возвращает список уведомлений (ответов, упоминаний, отметок и приглашений) для текущего пользователя.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `count` | integer | Количество возвращаемых уведомлений (максимум 100). По умолчанию: `10`. |
| `offset` | integer | Смещение относительно начала списка. По умолчанию: `0`. |
| `start_from` | string | Идентификатор курсора для постраничной выборки. |
| `filters` | string | Список фильтров типов уведомлений, перечисленных через запятую. |
| `start_time` | integer | Начальный момент времени (Unix timestamp). По умолчанию: `0`. |
| `end_time` | integer | Конечный момент времени (Unix timestamp). |
| `archived` | integer | Флаг включения архивных/прочитанных уведомлений (`1` — включены, `0` — только непрочитанные). По умолчанию: `1`. |

### Результат

Возвращает объект со следующими полями:
* `items` (array) — массив объектов уведомлений;
* `profiles` (array) — массив профилей пользователей, являющихся авторами событий;
* `groups` (array) — массив сообществ;
* `last_viewed` (integer) — идентификатор (смещение) последнего просмотренного уведомления.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `125` | `Count is too big` — Передано значение `count` больше 100. |

### Пример запроса
```http
POST /method/notifications.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

count=5&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "items": [
            {
                "type": "wall_reply",
                "date": 1700002000,
                "feedback": {
                    "count": 1,
                    "items": [
                        {
                            "id": 10,
                            "from_id": 2,
                            "text": "Отличная запись!"
                        }
                    ]
                }
            }
        ],
        "profiles": [
            {
                "id": 2,
                "first_name": "Павел",
                "last_name": "Дуров",
                "photo_50": "/assets/packages/static/openvk/img/camera_50.png"
            }
        ],
        "groups": [],
        "last_viewed": 10
    }
}
```
