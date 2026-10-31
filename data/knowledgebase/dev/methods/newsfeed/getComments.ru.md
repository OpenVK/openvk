OpenVK-KB-Heading: newsfeed.getComments

# newsfeed.getComments

Возвращает список записей на стене с последними комментариями, оставленными в отслеживаемых обсуждениях или на стенах, на которые подписан текущий пользователь, а также в записях, где пользователь сам оставлял комментарии.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `count` | integer | Количество возвращаемых записей (от 1 до 100). По умолчанию: `30`. |
| `filters` | string | Типы объектов (на данный момент поддерживается: `post`). По умолчанию: `post`. |
| `reposts` | string | Идентификаторы репостов для фильтрации. |
| `start_time` | integer | Начальный момент времени (Unix timestamp), начиная с которого оставлены комментарии. По умолчанию: `0`. |
| `end_time` | integer | Конечный момент времени (Unix timestamp), до которого оставлены комментарии. |
| `last_comments` | integer | Флаг включения последних комментариев к записям. По умолчанию: `1`. |
| `last_comments_count` | integer | Количество последних комментариев, возвращаемых для каждого объекта. По умолчанию: `1`. |
| `start_from` | string | Идентификатор курсора / смещения для постраничной навигации. |
| `fields` | string | Список дополнительных полей профилей и сообществ. |
| `offset` | integer | Смещение выборки (от 0 до 1000). По умолчанию: `0`. |

### Результат

Возвращает объект со следующими полями:
* `items` (array) — массив объектов записей на стене (`type: "post"`). Внутри каждого объекта поле `comments` содержит:
  * `count` (integer) — общее число комментариев к записи;
  * `can_post` (integer) — `1`, если текущий пользователь может оставить комментарий;
  * `list` (array) — массив объектов последних комментариев (`id`, `uid`, `text`, `date`).
* `profiles` (array) — массив объектов профилей авторов записей и комментариев;
* `groups` (array) — массив объектов сообществ;
* `next_from` (string) — курсор для следующей страницы;
* `new_from` (string) — строковое смещение.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/newsfeed.getComments HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

count=5&last_comments_count=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "items": [
            {
                "id": 15,
                "owner_id": 1,
                "from_id": 1,
                "date": 1699990000,
                "text": "Обсуждаем обновление платформы",
                "type": "post",
                "source_id": 1,
                "post_id": 15,
                "comments": {
                    "count": 5,
                    "can_post": 1,
                    "list": [
                        {
                            "id": 48,
                            "uid": 2,
                            "text": "Отличная новость!",
                            "date": 1699991200
                        }
                    ]
                },
                "likes": {
                    "count": 10,
                    "user_likes": 1,
                    "can_publish": 1
                }
            }
        ],
        "profiles": [
            {
                "id": 2,
                "uid": 2,
                "first_name": "Павел",
                "last_name": "Дуров",
                "screen_name": "durov",
                "photo": "/assets/packages/static/openvk/img/camera_50.png",
                "photo_50": "/assets/packages/static/openvk/img/camera_50.png",
                "photo_100": "/assets/packages/static/openvk/img/camera_100.png",
                "online": 1
            }
        ],
        "groups": [],
        "next_from": "5",
        "new_from": "5"
    }
}
```
