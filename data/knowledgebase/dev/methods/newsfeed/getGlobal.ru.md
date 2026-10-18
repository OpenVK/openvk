OpenVK-KB-Heading: newsfeed.getGlobal

# newsfeed.getGlobal

Возвращает глобальную ленту всех публичных записей со стен платформы OpenVK с учетом настроек приватности, скрытия из глобальной ленты и NSFW-фильтрации. Метод также поддерживает выгрузку ленты в формате RSS 2.0.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `fields` | string | Список дополнительных полей профилей и сообществ (например: `sex, bdate, screen_name, photo_50, photo_100`). |
| `start_from` | string | Курсор для постраничной навигации в формате `timestamp_id` (значение `next_from` предыдущего ответа). |
| `start_time` | integer | Начальный момент времени (Unix timestamp), начиная с которого необходимо получить новости. По умолчанию: `0`. |
| `end_time` | integer | Конечный момент времени (Unix timestamp), до которого необходимо получить новости. |
| `offset` | integer | Смещение выборки. По умолчанию: `0`. |
| `count` | integer | Количество возвращаемых записей. По умолчанию: `30`. |
| `extended` | boolean | `1` (`true`) — возвращать массивы `profiles` и `groups` с информацией об авторах, `0` (`false`) — только записи. По умолчанию: `1`. |
| `rss` | boolean | `1` (`true`) — сформировать и вернуть RSS 2.0 XML-канал, `0` (`false`) — вернуть стандартный JSON-ответ API. По умолчанию: `0`. |
| `return_banned` | boolean | `1` (`true`) — включать записи от скрытых (игнорируемых) источников, `0` (`false`) — исключать скрытые источники. По умолчанию: `0`. |
| `with_alien_wall_posts` | boolean | `1` (`true`) — возвращать записи сторонних пользователей на стенах, `0` (`false`) — только публикации владельцев стен. По умолчанию: `0`. |

### Результат

Возвращает объект со списком записей:
* `items` (array) — массив объектов записей стены (`type: "post"`);
* `profiles` (array) — массив объектов профилей пользователей (при `extended=1`);
* `groups` (array) — массив объектов сообществ (при `extended=1`);
* `next_from` (string) — курсор для следующей страницы.

Если передан флаг `rss=1`, метод возвращает объект RSS-канала.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/newsfeed.getGlobal HTTP/1.1
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
                "id": 204,
                "owner_id": 1,
                "from_id": 1,
                "date": 1700005000,
                "text": "Новость для всех пользователей платформы",
                "type": "post",
                "source_id": 1,
                "comments": {
                    "count": 2,
                    "can_post": 1
                },
                "likes": {
                    "count": 10,
                    "user_likes": 0,
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
        "next_from": "1700005000_204"
    }
}
```
