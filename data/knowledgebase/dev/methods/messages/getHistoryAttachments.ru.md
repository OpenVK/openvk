OpenVK-KB-Heading: messages.getHistoryAttachments

# messages.getHistoryAttachments

Возвращает медиавложения указанного типа из истории переписки в диалоге или беседе.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_id` | integer | Идентификатор диалога/беседы (`2000000000 + chat_id`). **Обязательный параметр.** |
| `media_type` | string | Тип возвращаемых вложений: `"photo"`, `"video"`, `"audio"`, `"doc"`, `"link"`, `"market"`, `"wall"`, `"share"`, `"graffiti"`. По умолчанию: `"photo"`. |
| `start_from` | string | Токен смещения для постраничной загрузки. |
| `count` | integer | Количество вложений в ответе (максимум `200`). По умолчанию: `30`. |
| `extended` | integer | `1` — возвращать профили пользователей и групп. По умолчанию: `0`. |
| `fields` | string | Дополнительные поля профилей. |

### Результат

Возвращает объект, содержащий:
* `items` (array) — массив объектов вложений;
* `next_from` (string, опционально) — токен для получения следующей страницы;
* `profiles` (array, опционально) — профили авторов вложений;
* `groups` (array, опционально) — информация о сообществах.

### Пример запроса
```http
POST /method/messages.getHistoryAttachments HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&media_type=photo&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
