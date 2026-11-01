OpenVK-KB-Heading: messages.joinChatByInviteLink

# messages.joinChatByInviteLink

Присоединяет текущего пользователя к беседе по ссылке-приглашению.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `link` | string | Полная ссылка-приглашение или хеш приглашения. **Обязательный параметр.** |

### Результат

Возвращает объект с полем:
* `chat_id` (integer) — идентификатор беседы (`1...N`).

### Пример запроса
```http
POST /method/messages.joinChatByInviteLink HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

link=https://openvk.instance/join/abc12345&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
