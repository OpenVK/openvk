OpenVK-KB-Heading: account.getMulti

# account.getMulti

Возвращает информацию о текущем мультиаккаунте.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `fields` | string | Список дополнительных полей профиля. |

### Результат
Возвращает объект с данными сессии:

| Поле | Тип | Описание |
| --- | --- | --- |
| `count` | integer | Количество доступных аккаунтов (`1`). |
| `items` | object | Объект данных текущего пользователя (`photo_50`, `photo_100`, `photo_base`, `has_photo`). |

### Пример запроса
```http
POST /method/account.getMulti HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "items": {
            "id": 1,
            "first_name": "Павел",
            "last_name": "Дуров",
            "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
            "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png"
        }
    }
}
```
