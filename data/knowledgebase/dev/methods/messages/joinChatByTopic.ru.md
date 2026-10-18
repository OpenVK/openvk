OpenVK-KB-Heading: messages.joinChatByTopic

# messages.joinChatByTopic

Присоединяет текущего пользователя к беседе, привязанной к теме обсуждений сообщества.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `group_id` | integer | Идентификатор сообщества (положительное число). **Обязательный параметр.** |
| `topic_id` | integer | Идентификатор темы обсуждения. **Обязательный параметр.** |

### Результат

Возвращает объект с полем:
* `chat_id` (integer) — идентификатор беседы (`1...N`).

### Пример запроса
```http
POST /method/messages.joinChatByTopic HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&topic_id=42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
