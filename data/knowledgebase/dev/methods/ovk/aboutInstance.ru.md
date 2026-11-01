OpenVK-KB-Heading: ovk.aboutInstance

# ovk.aboutInstance

Возвращает подробные сведения о текущем инстансе OpenVK: статистику (число пользователей, сообществ, записей на стенах), список администраторов сервера, популярные сообщества и полезные ссылки.

### Авторизация
Метод является публичным и не требует обязательной авторизации пользователя.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `fields` | string | Список возвращаемых секций информации, перечисленных через запятую. Возможные значения: `statistics`, `administrators`, `popular_groups`, `links`. По умолчанию: `statistics,administrators,popular_groups,links`. |
| `admin_fields` | string | Список дополнительных полей профилей администраторов инстанса (например, `photo_50, photo_100, screen_name`). |
| `group_fields` | string | Список дополнительных полей для популярных сообществ. |

### Результат

Возвращает объект с запрошенными секциями:
* `statistics` (object) — статистика инстанса:
  * `users_count` (integer) — общее количество зарегистрированных пользователей;
  * `online_users_count` (integer) — количество пользователей онлайн;
  * `active_users_count` (integer) — количество активных пользователей;
  * `groups_count` (integer) — общее количество сообществ;
  * `wall_posts_count` (integer) — общее количество записей на стенах.
* `administrators` (object) — список администраторов инстанса:
  * `count` (integer) — количество администраторов;
  * `items` (array) — массив объектов профилей администраторов.
* `popular_groups` (object) — популярные сообщества инстанса:
  * `count` (integer) — количество сообществ;
  * `items` (array) — массив объектов сообществ.
* `links` (object) — официальные ссылки инстанса:
  * `count` (integer) — количество ссылок;
  * `items` (array) — массив ссылок.

### Пример запроса
```http
POST /method/ovk.aboutInstance HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

fields=statistics,links&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "statistics": {
            "users_count": 1250,
            "online_users_count": 42,
            "active_users_count": 380,
            "groups_count": 65,
            "wall_posts_count": 4820
        },
        "links": {
            "count": 1,
            "items": [
                {
                    "title": "Исходный код",
                    "url": "https://github.com/openvk/openvk"
                }
            ]
        }
    }
}
```
