OpenVK-KB-Heading: messages.searchConversations

# messages.searchConversations

Осуществляет поиск по беседам, диалогам и контактам пользователя.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `q` | string | Поисковый запрос (текст заголовка или имя собеседника). **Обязательный параметр.** |
| `count` | integer | Количество бесед в ответе (максимум `200`). По умолчанию: `20`. |
| `extended` | integer | `1` — возвращать профили пользователей и сообществ. По умолчанию: `0`. |
| `fields` | string | Дополнительные поля профилей при `extended=1`. |
| `group_id` | integer | Идентификатор сообщества. |

### Результат

Возвращает объект, содержащий:
* `count` (integer) — количество найденных бесед;
* `items` (array) — массив объектов **[Conversation](/dev/models/conversation)**;
* `profiles` (array, опционально) — профили пользователей;
* `groups` (array, опционально) — профили сообществ.

### Пример запроса
```http
POST /method/messages.searchConversations HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

q=Разработчики&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
