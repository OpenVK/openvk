OpenVK-KB-Heading: messages.markAsImportant

# messages.markAsImportant

Помечает сообщения как важные (избранные) или снимает отметку.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `message_ids` | string | Список идентификаторов сообщений через запятую. **Обязательный параметр.** |
| `important` | integer | `1` — пометить как важное, `0` — снять отметку. По умолчанию: `1`. |

### Результат

Возвращает массив идентификаторов сообщений, статус которых был изменен.

### Пример запроса
```http
POST /method/messages.markAsImportant HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

message_ids=4512&important=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
