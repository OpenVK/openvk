OpenVK-KB-Heading: messages.getLongPollHistory

# messages.getLongPollHistory

Возвращает историю событий и обновления сообщений LongPoll, произошедшие с момента указанной временной метки (`ts`) или номера события (`pts`).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `ts` | integer | Временная метка последнего полученного события. |
| `pts` | integer | Номер последнего полученного события. |
| `preview_length` | integer | Длина текста сообщения для предпросмотра. |
| `extended` | integer | `1` — возвращать профили пользователей и сообществ. |
| `fields` | string | Дополнительные поля профилей. |
| `events_limit` | integer | Максимальное количество событий в ответе (максимум `1000`). По умолчанию: `1000`. |
| `msgs_limit` | integer | Максимальное количество сообщений в ответе (максимум `1000`). По умолчанию: `200`. |
| `max_msg_id` | integer | Максимальный ID сообщения для выборки. |
| `group_id` | integer | Идентификатор сообщества. |

### Результат

Возвращает объект, содержащий:
* `history` (array) — массив массивов событий (например, `[4, message_id, flags, peer_id, timestamp, text, attachments]`);
* `messages` (object) — объект со свойствами `count` и `items` (массив **[Message](/dev/models/message)**);
* `profiles` (array, опционально) — профили пользователей;
* `groups` (array, опционально) — профили сообществ;
* `new_pts` (integer) — новое значение `pts` для следующих запросов;
* `more` (boolean) — `true`, если доступны более поздние события.

### Пример запроса
```http
POST /method/messages.getLongPollHistory HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

ts=1696680000&pts=450&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
