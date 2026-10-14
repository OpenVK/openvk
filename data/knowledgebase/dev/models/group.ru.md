OpenVK-KB-Heading: Объект Group

# Объект Group

Объект **Group** описывает сообщество (группу, публичную страницу или мероприятие) ВКонтакте / OpenVK. Структура возвращаемых полей зависит от версии API, переданной в параметре `v`.

---

## Версия API 5.0 и выше (v >= 5.0)

### Базовые поля

Следующие поля возвращаются базовыми методами получения сообществ (например, `groups.get`, `groups.getById`):

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор сообщества. |
| `name` | string | Название сообщества. |
| `screen_name` | string | Короткий адрес (домен) страницы сообщества (например `apiclub` или `club1`). |
| `is_closed` | integer | Закрытость сообщества: `0` — открытое, `1` — закрытое, `2` — частное. |
| `type` | string | Тип сообщества: `"group"` — группа, `"page"` — публичная страница, `"event"` — мероприятие. |
| `is_member` | integer | `1`, если текущий пользователь является участником сообщества, иначе `0`. |
| `is_admin` | integer | `1`, если текущий пользователь является руководителем / администратором сообщества, иначе `0`. |
| `deactivated` | string\|null | Возвращается, если сообщество заблокировано или удалено (`"banned"` или `"deleted"`). |
| `can_access_closed` | integer | `1`, если текущий пользователь имеет доступ к контенту сообщества, иначе `0`. |
| `can_message` | boolean | Разрешено ли текущему пользователю отправлять сообщения сообществу. |

### Опциональные поля (`fields`)

Следующие поля возвращаются, если они были запрошены в параметре `fields`:

| Поле | Тип | Описание |
| --- | --- | --- |
| `verified` | integer | `1`, если сообщество верифицировано администрацией, иначе `0`. |
| `site` | string | Адрес веб-сайта, указанного в описании сообщества. |
| `status` | string | Текстовый статус страницы сообщества. |
| `description` | string | Полное текстовое описание сообщества. |
| `members_count` | integer | Количество участников сообщества. |
| `photo_50` | string | URL аватара сообщества размером 50x50px. |
| `photo_100` | string | URL аватара сообщества размером 100x100px. |
| `photo_200` | string | URL аватара сообщества размером 200x200px. |
| `photo_max` | string | URL аватара сообщества в исходном / максимальном разрешении. |
| `photo_id` | integer\|null | Идентификатор главной фотографии аватара. |
| `counters` | object | Объект со счетчиками разделов сообщества (`photos`, `albums`, `topics`, `videos`, `docs`). |
| `contacts` | array | Список контактов сообщества (массив объектов с полями `user_id`, `desc`). |
| `can_post` | boolean | Разрешено ли текущему пользователю публиковать записи на стене. |
| `can_suggest` | boolean | Разрешено ли предлагать новости (для публичных страниц). |
| `start_date` | integer | Дата начала мероприятия (Unix timestamp, только для событий). |
| `finish_date` | integer | Дата окончания мероприятия (Unix timestamp, только для событий). |

### Пример объекта (v >= 5.0)
```json
{
    "id": 1,
    "name": "Команда OpenVK",
    "screen_name": "openvk",
    "is_closed": 0,
    "type": "group",
    "is_member": 1,
    "is_admin": 0,
    "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/community_50.png",
    "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/community_100.png",
    "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/community_200.png",
    "members_count": 1420
}
```

---

## До версии API 5.0 (v < 5.0)

В версиях API 3.x и 4.x для обратной совместимости со старыми клиентами возвращаются следующие поля:

| Поле | Тип | Описание |
| --- | --- | --- |
| `gid` | integer | Идентификатор сообщества (эквивалент `id` в API v5.0+). |
| `name` | string | Название сообщества. |
| `screen_name` | string | Короткое имя страницы. |
| `is_closed` | integer | Статус закрытости (`0`, `1`, `2`). |
| `type` | string | Тип (`"group"`, `"page"`, `"event"`). |
| `photo` | string | URL аватара размером 50x50px. |
| `photo_medium` | string | URL аватара размером 100x100px. |
| `photo_big` | string | URL аватара размером 200x200px. |
| `is_member` | integer | Флаг членства текущего пользователя. |
| `is_admin` | integer | Флаг прав администратора у текущего пользователя. |

### Пример объекта (v < 5.0)
```json
{
    "gid": 1,
    "name": "Команда OpenVK",
    "screen_name": "openvk",
    "is_closed": 0,
    "type": "group",
    "is_member": 1,
    "is_admin": 0,
    "photo": "https://openvk.instance/assets/packages/static/openvk/img/community_50.png",
    "photo_medium": "https://openvk.instance/assets/packages/static/openvk/img/community_100.png",
    "photo_big": "https://openvk.instance/assets/packages/static/openvk/img/community_200.png"
}
```
