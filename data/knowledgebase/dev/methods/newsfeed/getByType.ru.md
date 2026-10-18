OpenVK-KB-Heading: newsfeed.getByType

# newsfeed.getByType

Возвращает записи из ленты новостей указанного типа (`top` — популярные/глобальные записи, либо стандартная лента новостей пользователя).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `feed_type` | string | Тип ленты. Возможные значения: `"top"` (глобальная/популярная лента, делегирует вызов [newsfeed.getGlobal](/dev/methods/newsfeed/getGlobal)), любое другое значение — стандартная лента подписок (делегирует вызов [newsfeed.get](/dev/methods/newsfeed/get)). По умолчанию: `"top"`. |
| `fields` | string | Список дополнительных полей профилей и сообществ. |
| `start_from` | string / integer | Идентификатор курсора для постраничной навигации. |
| `start_time` | integer | Начальный момент времени (Unix timestamp). По умолчанию: `0`. |
| `end_time` | integer | Конечный момент времени (Unix timestamp). |
| `offset` | integer | Смещение выборки. По умолчанию: `0`. |
| `count` | integer | Количество возвращаемых записей. По умолчанию: `30`. |
| `extended` | boolean | `1` (`true`) — возвращать профили и сообщества, `0` (`false`) — только записи. По умолчанию: `0`. |
| `return_banned` | boolean | `1` (`true`) — включать скрытые источники в выдачу при `feed_type="top"`. По умолчанию: `0`. |

### Результат

Возвращает объект со списком новостей в формате соответствующего вызванного метода (`newsfeed.getGlobal` или `newsfeed.get`).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/newsfeed.getByType HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

feed_type=top&count=10&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "items": [
            {
                "id": 101,
                "owner_id": 1,
                "from_id": 1,
                "date": 1700000000,
                "text": "Популярная запись",
                "type": "post",
                "source_id": 1,
                "comments": {
                    "count": 5,
                    "can_post": 1
                },
                "likes": {
                    "count": 20,
                    "user_likes": 1,
                    "can_like": 1
                }
            }
        ],
        "profiles": [],
        "groups": [],
        "next_from": "1700000000_101"
    }
}
```
