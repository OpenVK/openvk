OpenVK-KB-Heading: messages.report

# messages.report

Отправляет жалобу на сообщение модераторам сайта (спам, оскорбление или нарушение правил).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_id` | integer | Идентификатор диалога/беседы, где расположено сообщение. **Обязательный параметр.** |
| `message_id` | integer | Идентификатор сообщения, на которое подается жалоба. **Обязательный параметр.** |
| `type` | string | Тип нарушения (например, `"spam"`). По умолчанию: `"spam"`. |
| `comment` | string | Дополнительный комментарий к жалобе. |

### Результат

Возвращает `1` в случае успешной отправки жалобы.

### Пример запроса
```http
POST /method/messages.report HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&message_id=4512&type=spam&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
