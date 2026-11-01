OpenVK-KB-Heading: messages.getConversationsById

# messages.getConversationsById

Возвращает информацию о беседах по их идентификаторам назначения (`peer_ids`).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_ids` | string | Список идентификаторов назначения через запятую (например, `1,2000000001`). **Обязательный параметр.** |
| `extended` | integer | `1` — возвращать расширенные профили участников. По умолчанию: `0`. |
| `fields` | string | Дополнительные поля профилей при `extended=1`. |
| `group_id` | integer | Идентификатор сообщества. |

### Результат

Возвращает объект, содержащий:
* `count` (integer) — количество возвращенных бесед;
* `items` (array) — массив объектов **[Conversation](/dev/models/conversation)**;
* `profiles` (array, опционально) — профили пользователей;
* `groups` (array, опционально) — профили сообществ.

### Пример запроса
```http
POST /method/messages.getConversationsById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_ids=2000000001&extended=0&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
