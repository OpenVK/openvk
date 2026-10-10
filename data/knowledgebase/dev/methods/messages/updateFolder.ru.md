OpenVK-KB-Heading: messages.updateFolder

# messages.updateFolder

Обновляет название папки диалогов и список входящих в нее бесед.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `folder_id` | integer | Идентификатор папки. **Обязательный параметр.** |
| `name` | string | Новое название папки. |
| `add_included_peer_ids` | string | Список идентификаторов бесед для добавления в папку через запятую. |
| `remove_included_peer_ids` | string | Список идентификаторов бесед для удаления из папки через запятую. |

### Результат

Возвращает `1` в случае успешного обновления.

### Пример запроса
```http
POST /method/messages.updateFolder HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

folder_id=1&name=Архив&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
