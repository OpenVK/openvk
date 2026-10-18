OpenVK-KB-Heading: messages.editChat

# messages.editChat

Изменяет название групповой беседы.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `chat_id` | integer | Идентификатор беседы (`1...N`). **Обязательный параметр.** |
| `title` | string | Новое название беседы. **Обязательный параметр.** |

### Результат

Возвращает `1` в случае успешного изменения.

### Пример запроса
```http
POST /method/messages.editChat HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

chat_id=1&title=Команда+OpenVK&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
