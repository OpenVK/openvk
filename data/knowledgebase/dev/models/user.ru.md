OpenVK-KB-Heading: Объект User

# Объект User

Объект **User** описывает профиль пользователя ВКонтакте / OpenVK. Структура возвращаемых полей зависит от версии API, переданной в параметре `v`.

---

## Версия API 5.0 и выше (v >= 5.0)

### Базовые поля

Следующие поля возвращаются базовыми методами получения пользователей (например, `users.get`):

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор пользователя. |
| `first_name` | string | Имя пользователя. |
| `last_name` | string | Фамилия пользователя. |
| `deactivated` | string\|false | Возвращается, если страница удалена или заблокирована (`"deleted"` или `"banned"`). |
| `is_closed` | boolean | Скрыт ли профиль настройками приватности (`true`, если профиль закрытый). |
| `can_access_closed` | integer | `1`, если текущий пользователь может просматривать закрытый профиль, иначе `0`. |

### Опциональные поля (`fields`)

Следующие поля возвращаются, если они были запрошены в параметре `fields`:

| Поле | Тип | Описание |
| --- | --- | --- |
| `screen_name` | string | Короткий адрес (домен) страницы пользователя (например `durov` или `id1`). |
| `sex` | integer | Пол: `1` — женский, `2` — мужской, `0` — не указан. |
| `verified` | integer | `1`, если страница верифицирована (подтверждена администрацией). |
| `status` | string | Текстовый статус страницы. |
| `nickname` | string | Никнейм (псевдоним) пользователя. |
| `photo_50` | string | Ссылка на аватар размером 50x50px. |
| `photo_100` | string | Ссылка на аватар размером 100x100px. |
| `photo_200` | string | Ссылка на аватар размером 200x200px. |
| `photo_max` | string | Ссылка на аватар в максимальном доступном разрешении. |
| `photo_base` | string | Ссылка на базовый размер аватара. |
| `photo_id` | string\|null | Идентификатор главной фотографии профиля (например `photo1_123`). |
| `reg_date` | integer | Время регистрации аккаунта (Unix timestamp). |
| `rating` | integer | Текущий рейтинг профиля в OpenVK. |
| `background` | array\|null | Настройки фонового изображения профиля OpenVK. |
| `is_dead` | boolean | Отметка мемориальной страницы (память об умершем). |
| `can_write_private_message` | integer | `1`, если пользователю разрешено отправлять личные сообщения. |
| `blacklisted_by_me` | integer | `1`, если пользователь добавлен в черный список текущим авторизованным пользователем. |
| `blacklisted` | integer | `1`, если текущий авторизованный пользователь находится в черном списке этого пользователя. |
| `games` | string | Любимые игры пользователя. |

### Пример объекта (v >= 5.0)
```json
{
    "id": 1,
    "first_name": "Павел",
    "last_name": "Дуров",
    "deactivated": false,
    "is_closed": false,
    "can_access_closed": 1,
    "screen_name": "durov",
    "sex": 2,
    "verified": 1,
    "status": "ВКонтакте",
    "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
    "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
    "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png"
}
```

---

## До версии API 5.0 (v < 5.0)

В ранних версиях протокола (3.x, 4.x) для совместимости со старыми клиентами возвращаются специфичные устаревшие поля:

| Поле | Тип | Описание |
| --- | --- | --- |
| `uid` | integer | Идентификатор пользователя (эквивалент `id` в API v5.0+). |
| `first_name` | string | Имя пользователя. |
| `last_name` | string | Фамилия пользователя. |
| `photo` | string | Ссылка на квадратную фотографию 50x50px. |
| `photo_rec` | string | Ссылка на квадратную фотографию 50x50px. |
| `photo_medium_rec` | string | Ссылка на квадратную фотографию 100x100px. |
| `photo_50` | string | Ссылка на аватар 50x50px. |
| `photo_100` | string | Ссылка на аватар 100x100px. |
| `screen_name` | string | Короткое имя страницы. |
| `sex` | integer | Пол: `1` — женский, `2` — мужской, `0` — не указан. |

### Пример объекта (v < 5.0)
```json
{
    "uid": 1,
    "first_name": "Павел",
    "last_name": "Дуров",
    "sex": 2,
    "photo": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
    "photo_rec": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
    "photo_medium_rec": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
    "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
    "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
    "screen_name": "durov"
}
```
