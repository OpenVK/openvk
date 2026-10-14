OpenVK-KB-Heading: newsfeed.getRecommended

# newsfeed.getRecommended

Возвращает список рекомендуемых записей на стене для пользователя. В текущей реализации OpenVK является полным псевдонимом (алиасом) метода [newsfeed.getGlobal](/dev/methods/newsfeed/getGlobal).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `fields` | string | Список дополнительных полей профилей и сообществ (например: `sex, bdate, screen_name, photo_50, photo_100`). |
| `start_from` | string | Идентификатор курсора для постраничной навигации в формате `timestamp_id`. |
| `start_time` | integer | Начальный момент времени (Unix timestamp). По умолчанию: `0`. |
| `end_time` | integer | Конечный момент времени (Unix timestamp). |
| `offset` | integer | Смещение выборки. По умолчанию: `0`. |
| `count` | integer | Количество возвращаемых записей. По умолчанию: `30`. |
| `extended` | boolean | `1` (`true`) — возвращать профили и сообщества, `0` (`false`) — только записи. По умолчанию: `1`. |
| `rss` | boolean | `1` (`true`) — вернуть результат в формате RSS. По умолчанию: `0`. |
| `return_banned` | boolean | `1` (`true`) — включать скрытые источники, `0` (`false`) — исключать. По умолчанию: `0`. |

### Результат

Возвращает объект со следующими полями:
* `items` (array) — массив объектов записей со стены;
* `profiles` (array) — массив объектов профилей пользователей (при `extended=1`);
* `groups` (array) — массив объектов сообществ (при `extended=1`);
* `next_from` (string) — курсор для следующей страницы.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/newsfeed.getRecommended HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

count=10&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "items": [
            {
                "id": 50,
                "owner_id": -1,
                "from_id": -1,
                "date": 1700001000,
                "text": "Рекомендуемый пост сообщества",
                "type": "post",
                "source_id": -1,
                "comments": {
                    "count": 0,
                    "can_post": 1
                },
                "likes": {
                    "count": 12,
                    "user_likes": 0,
                    "can_like": 1
                }
            }
        ],
        "profiles": [],
        "groups": [
            {
                "id": 1,
                "name": "Официальная группа",
                "screen_name": "club1",
                "photo_50": "/assets/packages/static/openvk/img/community_50.png",
                "photo_100": "/assets/packages/static/openvk/img/community_100.png"
            }
        ],
        "next_from": "1700001000_50"
    }
}
```
