OpenVK-KB-Heading: messages.getImportantMessages

# messages.getImportantMessages

Возвращает список сообщений пользователя, помеченных как важные (избранные).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `count` | integer | Количество сообщений в ответе (максимум `200`). По умолчанию: `20`. |
| `offset` | integer | Смещение для пагинации. По умолчанию: `0`. |
| `start_message_id` | integer | Идентификатор сообщения, начиная с которого возвращаются сообщения. |
| `preview_length` | integer | Длина фрагмента текста. |
| `extended` | integer | `1` — возвращать профили пользователей и сообществ. По умолчанию: `0`. |
| `fields` | string | Дополнительные поля профилей при `extended=1`. |

### Результат

Возвращает объект, содержащий:
* `messages` (object) — объект со свойствами `count` и `items` (массив **[Message](/dev/models/message)**);
* `profiles` (array, опционально) — профили пользователей;
* `groups` (array, опционально) — информация о сообществах;
* `conversations` (array, опционально) — объекты бесед.

### Пример запроса
```http
POST /method/messages.getImportantMessages HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

count=20&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
