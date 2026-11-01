OpenVK-KB-Heading: account.getProfileInfo

# account.getProfileInfo

Возвращает расширенную информацию о профиле текущего авторизованного пользователя для отображения или редактирования в настройках.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры
Параметры отсутствуют.

### Результат
Возвращает объект с подробными данными профиля:

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор пользователя. |
| `first_name` | string | Имя пользователя. |
| `last_name` | string | Фамилия пользователя. |
| `nickname` | string | Никнейм (псевдоним). |
| `maiden_name` | string | *(Заглушка совместимости)* Девичья фамилия (всегда пустая строка `""`). |
| `screen_name` | string | Короткий адрес страницы (например, `durov` или `id1`). |
| `sex` | integer | Пол: `1` — женский, `2` — мужской, `0` — не указан. |
| `status` | string | Текстовый статус профиля. |
| `bdate` | string | Дата рождения в формате `D.M.YYYY`. |
| `bdate_visibility` | integer | Видимость даты рождения: `1` — показывать полностью, `2` — только день и месяц, `0` — скрывать. |
| `home_town` | string | Родной город. |
| `country` | object | *(Заглушка совместимости)* Объект страны (захардкожен: `{"id": 1, "title": "Россия"}`). |
| `city` | object | *(Заглушка совместимости)* Объект города (захардкожен: `{"id": 1, "title": "—"}`). |
| `phone` | string | *(Заглушка совместимости)* Маскированный номер телефона (`"+420 ** *** 228"`). |
| `relation` | integer | Семейное положение: `0` — не указано, `1` — не женат/не замужем, `2` — есть друг/подруга, `3` — помолвлен/помолвлена, `4` — женат/замужем, `5` — всё сложно, `6` — в активном поиске, `7` — влюблен/влюблена, `8` — в гражданском браке. |
| `relation_partner` | null | *(Заглушка совместимости)* Партнер в отношениях (всегда `null`). |
| `is_verified` | boolean | Подтвержден ли профиль (наличие галочки верификации). |
| `verification_status` | string | Текстовый статус верификации (`"verified"` или `"unverified"`). |
| `can_create_stickers` | boolean | Разрешено ли пользователю создавать стикеры. |
| `is_service_account` | boolean | *(Заглушка совместимости)* Сервисный ли аккаунт (всегда `false`). |
| `photo_200` | string | Ссылка на квадратную фотографию профиля 200x200px. |
| `name_request` | null | *(Заглушка совместимости)* Статус заявки на смену имени (всегда `null`). |
| `audio_status` | object | Объект текущего транслируемого трека (если трансляция включена). |

### Пример запроса
```http
POST /method/account.getProfileInfo HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "id": 1,
        "first_name": "Павел",
        "last_name": "Дуров",
        "nickname": "",
        "maiden_name": "",
        "screen_name": "durov",
        "sex": 2,
        "status": "ВКонтакте",
        "bdate": "10.10.1984",
        "bdate_visibility": 1,
        "home_town": "Ленинград",
        "country": {
            "id": 1,
            "title": "Россия"
        },
        "city": {
            "id": 1,
            "title": "—"
        },
        "relation": 0,
        "relation_partner": null,
        "is_verified": true,
        "verification_status": "verified",
        "can_create_stickers": true,
        "is_service_account": false,
        "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png",
        "phone": "+420 ** *** 228",
        "name_request": null
    }
}
```
