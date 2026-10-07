OpenVK-KB-Heading: account.get

# account.get

Возвращает информацию о пользователях или текущем аккаунте в формате базовой структуры профиля.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `user_ids` | string | Список идентификаторов пользователей через запятую. Если параметр не указан, возвращается профиль текущего пользователя. |
| `fields` | string | Список дополнительных полей профиля. |

### Результат
Возвращает массив объектов пользователей:

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор пользователя. |
| `first_name` | string | Имя. |
| `last_name` | string | Фамилия. |
| `screen_name` | string | Короткое имя страницы. |
| `photo_50` | string | Ссылка на аватар 50x50px. |
| `photo_100` | string | Ссылка на аватар 100x100px. |
| `photo_base` | string | Ссылка на исходный аватар. |
| `has_photo` | integer | `1`, если у пользователя загружен аватар. |
| `verified` | integer | `1`, если аккаунт верифицирован. |
| `sex` | integer | Пол: `1` — женский, `2` — мужской, `0` — не указан. |

### Пример запроса
```http
POST /method/account.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": [
        {
            "id": 1,
            "first_name": "Павел",
            "last_name": "Дуров",
            "screen_name": "durov",
            "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
            "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
            "photo_base": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png",
            "has_photo": 1,
            "verified": 1,
            "sex": 2
        }
    ]
}
```
