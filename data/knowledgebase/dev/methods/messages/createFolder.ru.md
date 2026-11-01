OpenVK-KB-Heading: messages.createFolder

# messages.createFolder

Создает новую пользовательскую папку для группировки диалогов и бесед.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `name` | string | Название папки. **Обязательный параметр.** |
| `type` | string | Тип папки: `"user"` (пользовательская), `"all"`, `"unread"`, `"channels"`. |
| `included_peer_ids` | string | Список идентификаторов диалогов/бесед через запятую, включаемых в папку. |

### Результат

Возвращает объект с полем:
* `folder_id` (integer) — идентификатор созданной папки.

### Пример запроса
```http
POST /method/messages.createFolder HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

name=Работа&included_peer_ids=2000000001,2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "folder_id": 1
    }
}
```
