OpenVK-KB-Heading: messages.getFolders

# messages.getFolders

Возвращает список папок диалогов, настроенных текущим пользователем.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `with_peers` | integer | `1` — включать список бесед в ответ. По умолчанию: `0`. |
| `fields` | string | Дополнительные поля профилей. |

### Результат

Возвращает объект, содержащий:
* `count` (integer) — количество папок;
* `items` (array) — массив объектов папок (`id`, `name`, `type`, `included_peer_ids`, `order`).

### Пример запроса
```http
POST /method/messages.getFolders HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```
