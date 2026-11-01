OpenVK-KB-Heading: messages.setChatPhoto

# messages.setChatPhoto

Устанавливает обложку (главную фотографию) групповой беседы.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `file` | string | Строка, возвращенная сервером загрузки фотографий. **Обязательный параметр.** |

### Результат

Возвращает объект, содержащий:
* `message_id` (integer) — идентификатор созданного системного сообщения о смене обложки;
* `chat` (object) — обновленный объект беседы **[Chat](/dev/models/chat)**.

### Пример запроса
```http
POST /method/messages.setChatPhoto HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

file=eyJhbGciOiJIUzI1NiJ9...&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
