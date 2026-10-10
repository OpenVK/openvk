OpenVK-KB-Heading: utils.resolveGuid

# utils.resolveGuid

Возвращает информацию о пользователе платформы по его уникальному идентификатору (GUID) учетной записи ядра Chandler.

### Авторизация
Этот метод не требует обязательной авторизации.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `guid` | string | **Обязательный параметр.** Глобальный идентификатор (GUID) пользователя в системе Chandler. |

### Результат

Возвращает стандартную структуру объекта пользователя (аналогично `users.get`).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `104` | `Not found` — пользователь с указанным GUID не найден. |

### Пример запроса
```http
POST /method/utils.resolveGuid HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

guid=018e6e5f-1234-789a-bcde-f0123456789a&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "id": 1,
        "first_name": "Павел",
        "last_name": "Дуров",
        "screen_name": "durov",
        "photo_50": "/assets/packages/static/openvk/img/camera_50.png",
        "photo_100": "/assets/packages/static/openvk/img/camera_100.png"
    }
}
```
