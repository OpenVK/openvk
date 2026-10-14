OpenVK-KB-Heading: messages.deleteChatPhoto

# messages.deleteChatPhoto

Удаляет обложку (фотографию) групповой беседы.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `chat_id` | integer | Идентификатор беседы (`1...N`). **Обязательный параметр.** |

### Результат

Возвращает объект, содержащий:
* `message_id` (integer) — идентификатор системного сообщения об удалении фото;
* `chat` (object) — обновленный объект беседы **[Chat](/dev/models/chat)**.

### Пример запроса
```http
POST /method/messages.deleteChatPhoto HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

chat_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
