OpenVK-KB-Heading: messages.getDiff

# messages.getDiff

Возвращает дифференциальную синхронизацию состояния мессенджера и данные подключения LongPoll для официальных клиентов.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `ts` | integer | Временная метка начальной точки синхронизации. |
| `lp_version` | integer | Версия LongPoll. |
| `events_limit` | integer | Лимит событий. По умолчанию: `1000`. |
| `msgs_limit` | integer | Лимит сообщений. По умолчанию: `1000`. |

### Результат

Возвращает объект, содержащий:
* `server_time` (integer) — текущее серверное время (Unix timestamp);
* `server_version` (integer) — версия протокола сервера;
* `invalidate_all` (boolean) — флаг полной инвалидации кэша;
* `conversations_info` (array) — массив бесед с информацией о новых сообщениях;
* `profiles` (array) — профили пользователей;
* `groups` (array) — профили сообществ;
* `counters` (object) — счетчики непрочитанных сообщений;
* `credentials` (object) — данные LongPoll сервера (`server_lp`, `key`, `ts`).

### Пример запроса
```http
POST /method/messages.getDiff HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```
