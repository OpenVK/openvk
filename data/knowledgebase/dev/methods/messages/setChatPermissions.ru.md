OpenVK-KB-Heading: messages.setChatPermissions

# messages.setChatPermissions

Настраивает права доступа и уровни привилегий для различных действий в групповой беседе.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_id` | integer | Идентификатор беседы (`2000000000 + chat_id`). **Обязательный параметр.** |
| `invite` | string | Кто может приглашать участников: `"all"` (все) или `"admin"` (только администраторы). |
| `change_info` | string | Кто может менять название и аватар: `"all"` или `"admin"`. |
| `change_pin` | string | Кто может закреплять сообщения: `"all"` или `"admin"`. |
| `use_mass_mentions` | string | Кто может использовать упоминания `@all` / `@online`: `"all"` или `"admin"`. |
| `see_invite_link` | string | Кто видит ссылку-приглашение: `"all"` или `"admin"`. |
| `change_admins` | string | Кто может назначать администраторов: `"all"` или `"admin"`. |

### Результат

Возвращает `1` в случае успешного сохранения настроек.

### Пример запроса
```http
POST /method/messages.setChatPermissions HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&invite=admin&change_pin=admin&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
