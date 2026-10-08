OpenVK-KB-Heading: messages.delete

# messages.delete

Удаляет сообщения для текущего пользователя или для всех участников переписки (беседы).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `message_ids` | string | Список глобальных идентификаторов сообщений через запятую. |
| `peer_id` | integer | Идентификатор назначения (при использовании `conversation_message_ids`). |
| `conversation_message_ids` | string | Список локальных номеров сообщений в беседе через запятую. |
| `delete_for_all` | integer | `1` — удалить сообщение для всех собеседников/участников беседы, `0` — удалить только у себя. По умолчанию: `0`. |
| `spam` | integer | `1` — пометить сообщение как спам. По умолчанию: `0`. |

### Результат

Возвращает объект, где ключами являются идентификаторы сообщений, а значениями — статус удаления (`1` — успешно, `0` — ошибка):
```json
{
    "response": {
        "4512": 1
    }
}
```

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `100` | `One of the parameters specified was missing or invalid: message_ids required` — Не указаны идентификаторы сообщений. |
| `924` | `Can't delete message for everyone` — Истекло время для удаления сообщения у всех или недостаточно прав в беседе. |

### Пример запроса
```http
POST /method/messages.delete HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

message_ids=4512&delete_for_all=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "4512": 1
    }
}
```
