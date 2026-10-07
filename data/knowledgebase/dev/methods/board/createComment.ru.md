OpenVK-KB-Heading: board.createComment

# board.createComment

Добавляет новый комментарий (сообщение или стикер) в тему обсуждения.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `group_id` | integer | Идентификатор сообщества. **Обязательный параметр.** |
| `topic_id` | integer | Идентификатор темы обсуждения. **Обязательный параметр.** |
| `message` | string | Текст комментария. Обязателен, если не переданы `attachments` или `sticker_id`. |
| `from_group` | boolean | `true` — отправить комментарий от имени сообщества (доступно администраторам сообщества), `false` — от имени текущего пользователя. По умолчанию: `true`. |
| `sticker_id` | integer | Идентификатор стикера (если отправляется стикер). При отправке стикера текст сообщения очищается. |
| `attachments` | string | Список прикреплений через запятую (например, `photo1_42`). |

### Результат

Возвращает числовой идентификатор созданного комментария (`comment_id`).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Тема не найдена, удалена или закрыта для комментирования. |
| `100` | `Required parameter 'message' missing.` / `Sticker not found` / `Sticker is not available for you` — Не передан текст комментария либо недоступен стикер. |

### Пример запроса
```http
POST /method/board.createComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&topic_id=1&message=Отличная+новость!&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 102
}
```
