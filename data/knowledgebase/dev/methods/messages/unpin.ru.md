OpenVK-KB-Heading: messages.unpin

# messages.unpin

Открепляет закрепленное сообщение в беседе или диалоге.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_id` | integer | Идентификатор беседы (`2000000000 + chat_id`) или диалога. **Обязательный параметр.** |
| `group_id` | integer | Идентификатор сообщества. |

### Результат

Возвращает `1` в случае успешного открепления.

### Пример запроса
```http
POST /method/messages.unpin HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
