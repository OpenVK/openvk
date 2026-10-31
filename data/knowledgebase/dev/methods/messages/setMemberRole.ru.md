OpenVK-KB-Heading: messages.setMemberRole

# messages.setMemberRole

Назначает или снимает роль участника в групповой беседе (например, назначает администратором или возвращает обычным участником).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_id` | integer | Идентификатор беседы (`2000000000 + chat_id`). **Обязательный параметр.** |
| `member_id` | integer | Идентификатор участника беседы. **Обязательный параметр.** |
| `role` | string | Назначаемая роль: `"admin"` (администратор) или `"member"` (участник). **Обязательный параметр.** |

### Результат

Возвращает `1` в случае успешного изменения роли.

### Пример запроса
```http
POST /method/messages.setMemberRole HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&member_id=2&role=admin&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
