OpenVK-KB-Heading: messages.search

# messages.search

Осуществляет поиск по тексту сообщений во всех переписках пользователя или внутри конкретной беседы.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `q` | string | Поисковый запрос. **Обязательный параметр.** |
| `peer_id` | integer | Идентификатор диалога/беседы для ограничения поиска. |
| `date` | integer | Фильтр по дате (Unix timestamp). |
| `preview_length` | integer | Длина фрагмента текста сообщения. |
| `offset` | integer | Смещение для постраничной пагинации. По умолчанию: `0`. |
| `count` | integer | Количество сообщений в ответе (максимум `100`). По умолчанию: `20`. |
| `extended` | integer | `1` — возвращать профили пользователей и сообществ. По умолчанию: `0`. |
| `fields` | string | Дополнительные поля профилей при `extended=1`. |

### Результат

Возвращает объект, содержащий:
* `count` (integer) — общее число найденных сообщений;
* `items` (array) — массив объектов **[Message](/dev/models/message)**;
* `profiles` (array, опционально) — профили пользователей;
* `groups` (array, опционально) — информация о сообществах.

### Пример запроса
```http
POST /method/messages.search HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

q=встреча&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
