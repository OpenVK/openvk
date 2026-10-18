OpenVK-KB-Heading: messages.removeChatUser

# messages.removeChatUser

Исключает пользователя из групповой беседы или позволяет текущему пользователю покинуть беседу.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `chat_id` | integer | Идентификатор беседы (`1...N`). |
| `peer_id` | integer | Идентификатор назначения (`2000000000 + chat_id`). |
| `user_id` | integer | Идентификатор исключаемого пользователя или ID текущего пользователя (чтобы выйти из беседы). **Обязательный параметр.** |

### Результат

Возвращает `1` в случае успешного выполнения.

### Пример запроса
```http
POST /method/messages.removeChatUser HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

chat_id=1&user_id=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
