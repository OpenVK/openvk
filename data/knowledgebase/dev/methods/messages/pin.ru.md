OpenVK-KB-Heading: messages.pin

# messages.pin

Закрепляет сообщение в беседе или диалоге.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_id` | integer | Идентификатор беседы (`2000000000 + chat_id`) или диалога. **Обязательный параметр.** |
| `message_id` | integer | Глобальный идентификатор закрепляемого сообщения. |
| `conversation_message_id` | integer | Локальный номер сообщения в беседе. |

### Результат

Возвращает объект закрепленного сообщения **[Message](/dev/models/message)**.

### Пример запроса
```http
POST /method/messages.pin HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&conversation_message_id=42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
