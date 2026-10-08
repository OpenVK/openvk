OpenVK-KB-Heading: messages.restore

# messages.restore

Восстанавливает удаленное ранее сообщение.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `message_id` | integer | Глобальный идентификатор восстанавливаемого сообщения. **Обязательный параметр.** |
| `group_id` | integer | Идентификатор сообщества (если вызов выполняется от имени группы). |

### Результат

Возвращает `1` в случае успешного восстановления.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `100` | `One of the parameters specified was missing or invalid: message_id is required` — Не указан `message_id`. |
| `910` | `Can't restore message: message has been permanently deleted` — Сообщение не может быть восстановлено (окончательно удалено). |

### Пример запроса
```http
POST /method/messages.restore HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

message_id=4512&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
