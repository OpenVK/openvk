OpenVK-KB-Heading: messages.getInviteLink

# messages.getInviteLink

Генерирует или возвращает существующую ссылку-приглашение для вступления в беседу.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_id` | integer | Идентификатор беседы (`2000000000 + chat_id`). **Обязательный параметр.** |
| `reset` | integer | `1` — сгенерировать новую ссылку (аннулировав старую), `0` — вернуть текущую. По умолчанию: `0`. |

### Результат

Возвращает объект с полем:
* `link` (string) — URL ссылки-приглашения (например, `https://openvk.instance/join/abc123...`).

### Пример запроса
```http
POST /method/messages.getInviteLink HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&reset=0&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
