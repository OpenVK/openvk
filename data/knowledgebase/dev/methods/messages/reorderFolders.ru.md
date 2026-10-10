OpenVK-KB-Heading: messages.reorderFolders

# messages.reorderFolders

Изменяет порядок пользовательских папок диалогов.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `folder_ids` | string | Список идентификаторов папок в новом порядке через запятую. **Обязательный параметр.** |

### Результат

Возвращает `1` в случае успешного сохранения порядка.

### Пример запроса
```http
POST /method/messages.reorderFolders HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

folder_ids=2,1,3&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
