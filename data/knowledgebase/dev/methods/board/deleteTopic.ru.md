OpenVK-KB-Heading: board.deleteTopic

# board.deleteTopic

Удаляет тему в обсуждениях сообщества. Доступно только администраторам сообщества.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `group_id` | integer | Идентификатор сообщества. **Обязательный параметр.** |
| `topic_id` | integer | Идентификатор темы внутри сообщества. **Обязательный параметр.** |

### Результат

Возвращает `1` в случае успешного удаления темы, либо `0`, если тема не найдена, уже удалена или у пользователя нет прав администратора сообщества.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/board.deleteTopic HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&topic_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
