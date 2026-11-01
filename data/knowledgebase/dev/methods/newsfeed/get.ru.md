OpenVK-KB-Heading: newsfeed.get

# newsfeed.get

Возвращает список записей на стене, фотографий и видеозаписей из ленты новостей текущего пользователя, сформированной на основе его подписок на пользователей и сообщества.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `filters` | string | Типы объектов для выборки, перечисленные через запятую. Возможные значения: `post` (записи на стене), `photo` (фотографии), `video` (видеозаписи). По умолчанию: `post`. |
| `fields` | string | Список дополнительных полей профилей и сообществ, которые необходимо вернуть (например: `sex, bdate, screen_name, photo_50, photo_100`). |
| `start_from` | string | Идентификатор курсора для постраничной навигации в формате `timestamp_id` (например, `1700000000_12345`). Значение передается из поля `next_from` предыдущего ответа. |
| `start_time` | integer | Начальный момент времени (в формате Unix timestamp), начиная с которого необходимо получить новости. По умолчанию: `0`. |
| `end_time` | integer | Конечный момент времени (в формате Unix timestamp), до которого необходимо получить новости. |
| `offset` | integer | Смещение относительно начала выборки (число от 0 до 1000). По умолчанию: `0`. |
| `count` | integer | Количество возвращаемых новостей (от 1 до 100). По умолчанию: `30`. |
| `extended` | boolean | `1` (`true`) — возвращать дополнительные массивы `profiles` и `groups` с информацией об авторах записей, `0` (`false`) — не возвращать. По умолчанию: `1`. |
| `with_alien_wall_posts` | boolean | `1` (`true`) — возвращать записи сторонних пользователей на стенах подписок, `0` (`false`) — только записи от имени владельцев стен. По умолчанию: `0`. |
| `forGodSakePleaseDoNotReportAboutMyOnlineActivity` | integer | Специальный параметр. Если передана `1`, сервер не будет обновлять статус онлайн пользователя (на версиях API старше 5.63). По умолчанию: `0`. |

### Результат

В API версии 5.0 и выше возвращает объект со следующими полями:
* `items` (array) — массив объектов новостей. В зависимости от типа:
  * Записи на стене (`type: "post"`) — стандартные объекты записей стены с полями `id`, `owner_id`, `source_id`, `date`, `text`, `attachments`, `comments`, `likes`, `reposts`.
  * Фотографии (`type: "photo"`) — сгруппированные по источнику и дню фотографии с полями `source_id`, `date`, `photos: { count, items }`.
  * Видеозаписи (`type: "video"`) — объекты видеозаписей с полем `source_id`.
* `profiles` (array) — массив объектов профилей пользователей (при `extended=1`);
* `groups` (array) — массив объектов сообществ (при `extended=1`);
* `next_from` (string) — курсор для получения следующей страницы новостей (передается в параметр `start_from`).

В устаревших версиях API (до версии 5.0) возвращает также дополнительные поля:
* `new_from` (string) — идентификатор для получения следующей порции новостей;
* `new_offset` (integer) — новое смещение;
* Для элементов `items` дублируются поля `post_id` и `source_id`, для профилей `uid`, для сообществ `gid`.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/newsfeed.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

filters=post,photo&count=10&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "items": [
            {
                "id": 105,
                "owner_id": 1,
                "source_id": 1,
                "from_id": 1,
                "date": 1700000000,
                "text": "Привет, OpenVK!",
                "type": "post",
                "comments": {
                    "count": 0,
                    "can_post": 1
                },
                "likes": {
                    "count": 5,
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
        "next_from": "1700000000_105"
    }
}
```
