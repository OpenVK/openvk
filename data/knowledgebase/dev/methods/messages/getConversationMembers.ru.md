OpenVK-KB-Heading: messages.getConversationMembers

# messages.getConversationMembers

Возвращает список участников, администраторов и права доступа в групповой беседе.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_id` | integer | Идентификатор беседы (`2000000000 + chat_id`). **Обязательный параметр.** |
| `extended` | integer | `1` — возвращать профили участников и сообществ. По умолчанию: `0`. |
| `fields` | string | Дополнительные поля профилей при `extended=1`. |
| `group_id` | integer | Идентификатор сообщества. |

### Результат

Возвращает объект, содержащий:
* `count` (integer) — количество участников беседы;
* `items` (array) — массив объектов участников:
  * `member_id` (integer) — идентификатор пользователя или сообщества;
  * `invited_by` (integer) — ID пользователя, пригласившего участника;
  * `join_date` (integer) — время вступления (Unix timestamp);
  * `is_admin` (boolean) — `true`, если участник является администратором;
  * `is_owner` (boolean) — `true`, если создатель беседы;
  * `can_kick` (boolean) — `true`, если текущий пользователь может исключить участника;
* `chat_restrictions` (object, опционально) — ограничения и права в чате;
* `profiles` (array, опционально) — профили пользователей (при `extended=1`);
* `groups` (array, опционально) — профили сообществ (при `extended=1`).

### Пример запроса
```http
POST /method/messages.getConversationMembers HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
