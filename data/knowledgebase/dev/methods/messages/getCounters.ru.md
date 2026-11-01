OpenVK-KB-Heading: messages.getCounters

# messages.getCounters

Возвращает счетчики непрочитанных и неотвеченных сообщений, а также статистику по папкам диалогов.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `filter` | integer | Флаг фильтрации. По умолчанию: `0`. |

### Результат

Возвращает объект, содержащий:
* `messages` (integer) — общее количество непрочитанных сообщений;
* `messages_unread_unmuted` (integer) — количество непрочитанных сообщений в чатах с включенными уведомлениями;
* `message_requests` (integer) — количество запросов на переписку;
* `important` (integer) — количество непрочитанных важных сообщений;
* `unanswered` (integer) — количество неотвеченных диалогов;
* `messages_folders` (array) — статистика по папкам диалогов.

### Пример запроса
```http
POST /method/messages.getCounters HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```
