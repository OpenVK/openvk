OpenVK-KB-Heading: messages.deleteFolder

# messages.deleteFolder

Удаляет пользовательскую папку диалогов.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `folder_id` | integer | Идентификатор удаляемой папки. **Обязательный параметр.** |

### Результат

Возвращает `1` в случае успешного удаления.

### Пример запроса
```http
POST /method/messages.deleteFolder HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

folder_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
