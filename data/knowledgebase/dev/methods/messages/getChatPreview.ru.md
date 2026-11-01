OpenVK-KB-Heading: messages.getChatPreview

# messages.getChatPreview

Возвращает информацию о групповой беседе для предпросмотра по ссылке-приглашению (название, обложка, количество и аватары участников).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `link` | string | Полная ссылка-приглашение или хеш ссылки. **Обязательный параметр.** |
| `fields` | string | Дополнительные поля профилей участников. |

### Результат

Возвращает объект со свойствами:
* `preview` (object) — объект предпросмотра беседы (`title`, `admin_id`, `members_count`, `photo_50`, `photo_100`, `photo_200`, `members`);
* `profiles` (array, опционально) — профили участников;
* `groups` (array, опционально) — сообщества.

### Пример запроса
```http
POST /method/messages.getChatPreview HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

link=https://openvk.instance/join/abc12345&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
