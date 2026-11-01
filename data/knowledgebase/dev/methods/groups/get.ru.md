OpenVK-KB-Heading: groups.get

# groups.get

Возвращает список сообществ текущего или указанного пользователя.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `user_id` | integer | Идентификатор пользователя, список сообществ которого необходимо получить. Если не указан или равен `0`, возвращаются сообщества текущего пользователя. По умолчанию: `0`. |
| `extended` | integer | `1` — возвращать расширенную информацию о сообществах, `0` — возвращать только идентификаторы (для API версии ниже 5.0). В API версии 5.0 и выше всегда возвращаются объекты сообществ. По умолчанию: `0`. |
| `filter` | string | Фильтр возвращаемых сообществ: `"groups"` — все сообщества, `"admin"` — сообщества, в которых пользователь является администратором (фильтр `"admin"` доступен только для текущего пользователя). По умолчанию: `"groups"`. |
| `fields` | string | Список дополнительных полей сообществ через запятую (например, `description,members_count,status,site`). |
| `offset` | integer | Смещение относительно начала списка сообществ. По умолчанию: `0`. |
| `count` | integer | Количество сообществ, которое необходимо вернуть. По умолчанию: `6`. |

### Результат

В API версии 5.0 и выше возвращает объект с полями:
* `count` (integer) — общее количество сообществ пользователя;
* `items` (array) — массив объектов сообществ [Group](/dev/models/group).

В API версии ниже 5.0:
* При `extended=0` возвращается массив формата `[count, gid1, gid2, ...]`;
* При `extended=1` возвращается массив объектов сообществ формата `[count, group1, group2, ...]`.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied: filter admin is available only for current user` — Фильтр `admin` запрошен для чужого профиля. |
| `15` | `Access denied` — Пользователь не найден или был удален. |
| `260` | `Access to the groups list is denied due to the user's privacy settings` — Пользователь скрыл список своих сообществ настройками приватности. |

### Пример запроса
```http
POST /method/groups.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&count=2&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 12,
        "items": [
            {
                "id": 1,
                "name": "Официальная группа OpenVK",
                "screen_name": "openvk",
                "is_closed": 0,
                "type": "group",
                "is_admin": 1,
                "is_member": 1,
                "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
                "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
                "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png"
            }
        ]
    }
}
```
