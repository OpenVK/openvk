OpenVK-KB-Heading: groups.leave

# groups.leave

Позволяет текущему пользователю покинуть группу или отписаться от публичной страницы.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `groups`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `group_id` | integer | **Обязательный параметр**. Идентификатор сообщества. |

### Результат

Возвращает `1` в случае успешного выхода или отписки.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/groups.leave HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
