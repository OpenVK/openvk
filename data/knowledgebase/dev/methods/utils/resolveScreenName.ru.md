OpenVK-KB-Heading: utils.resolveScreenName

# utils.resolveScreenName

Определяет тип объекта (пользователь или сообщество) и его числовой идентификатор по короткому имени (screen_name) или псевдониму страницы.

### Авторизация
Этот метод не требует авторизации.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `screen_name` | string | **Обязательный параметр.** Короткое имя пользователя или сообщества (например, `durov`, `id1`, `club12`, `apiclub`). |

### Результат

Возвращает объект со следующими полями:

| Поле | Тип | Описание |
| --- | --- | --- |
| `object_id` | integer | Числовой идентификатор пользователя или сообщества. |
| `type` | string | Тип объекта: `"user"` (пользователь) или `"group"` (сообщество). |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `104` | `Not found` — объект с указанным коротким именем не найден. |

### Пример запроса
```http
POST /method/utils.resolveScreenName HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

screen_name=apiclub&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "object_id": 1,
        "type": "group"
    }
}
```
