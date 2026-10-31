OpenVK-KB-Heading: newsfeed.search

# newsfeed.search

Осуществляет поиск по тексту записей на стене в глобальной ленте новостей OpenVK.

### Авторизация
Метод может вызываться как авторизованными пользователями, так и гостями (для гостей автоматически скрываются записи, помеченные как NSFW).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `q` | string | Поисковый запрос (подстрока для поиска в тексте записей). |
| `extended` | boolean | `1` (`true`) — возвращать информацию о профилях и сообществах авторов записей, `0` (`false`) — только записи. По умолчанию: `1`. |
| `count` | integer | Количество возвращаемых записей. По умолчанию: `30`. |
| `start_time` | integer | Начальный момент времени (Unix timestamp), начиная с которого созданы записи. По умолчанию: `0`. |
| `end_time` | integer | Конечный момент времени (Unix timestamp), до которого созданы записи. |
| `start_from` | string | Идентификатор курсора для постраничной навигации в формате `timestamp_id` (передается из `next_from` / `new_from`). |
| `fields` | string | Список дополнительных полей профилей и сообществ (например: `sex, bdate, screen_name, photo_50, photo_100`). |

### Результат

Возвращает объект со следующими полями:
* `count` (integer) — количество найденных записей в текущей выборке;
* `items` (array) — массив объектов записей со стены (`type: "post"`);
* `profiles` (array) — массив объектов профилей пользователей (при `extended=1`);
* `groups` (array) — массив объектов сообществ (при `extended=1`);
* `next_from` (string) — курсор для перехода к следующей странице результатов;
* `new_from` (string) — строковый курсор (для совместимости со старыми версиями API);
* `new_offset` (integer) — смещение выборки.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `100` | `One of the parameters specified was missing or invalid.` — Переданы некорректные параметры. |

### Пример запроса
```http
POST /method/newsfeed.search HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

q=OpenVK&count=5&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 12,
                "owner_id": 1,
                "from_id": 1,
                "date": 1699990000,
                "text": "Мы запустили новую версию OpenVK!",
                "type": "post",
                "source_id": 1,
                "comments": {
                    "count": 3,
                    "can_post": 1
                },
                "likes": {
                    "count": 8,
                    "user_likes": 1,
                    "can_like": 1
                }
            }
        ],
        "profiles": [
            {
                "id": 1,
                "first_name": "Администратор",
                "last_name": "",
                "screen_name": "admin",
                "photo_50": "/assets/packages/static/openvk/img/camera_50.png",
                "photo_100": "/assets/packages/static/openvk/img/camera_100.png"
            }
        ],
        "groups": [],
        "next_from": "1699990000_12",
        "new_from": "1699990000_12",
        "new_offset": 1
    }
}
```
