OpenVK-KB-Heading: newsfeed.getBanned

# newsfeed.getBanned

Возвращает список пользователей и сообществ, чьи публикации были скрыты (игнорируются) текущим пользователем из ленты новостей.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `extended` | boolean | `1` (`true`) — возвращать полные объекты профилей и сообществ, `0` (`false`) — возвращать только числовые идентификаторы. По умолчанию: `0`. |
| `fields` | string | Список дополнительных полей профилей и сообществ (при `extended=1`). |
| `name_case` | string | Падеж для склонения имени и фамилии пользователя (по умолчанию: `nom`). |
| `merge` | boolean | При `extended=1` и `merge=1` объединяет результаты в единый массив `items` со счетчиком `count`. При `merge=0` возвращает раздельные массивы `groups` и `profiles`. По умолчанию: `0`. |

### Результат

В зависимости от параметров возвращает:
* При `extended=0`:
  * `groups` (array) — массив отрицательных идентификаторов скрытых сообществ;
  * `members` (array) — массив положительных идентификаторов скрытых пользователей.
* При `extended=1, merge=0`:
  * `groups` (array) — массив объектов скрытых сообществ;
  * `profiles` (array) — массив объектов скрытых пользователей.
* При `extended=1, merge=1`:
  * `count` (integer) — общее количество скрытых источников;
  * `items` (array) — объединенный массив объектов пользователей и сообществ.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/newsfeed.getBanned HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "groups": [],
        "profiles": [
            {
                "id": 5,
                "first_name": "Иван",
                "last_name": "Иванов",
                "screen_name": "ivan",
                "photo_50": "/assets/packages/static/openvk/img/camera_50.png"
            }
        ]
    }
}
```
