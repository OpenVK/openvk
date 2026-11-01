OpenVK-KB-Heading: messages.edit

# messages.edit

Редактирует текст или вложения отправленного ранее сообщения.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_id` | integer | Идентификатор назначения (диалога или беседы), где расположено сообщение. |
| `message_id` | integer | Глобальный идентификатор редактируемого сообщения. |
| `conversation_message_id` | integer | Локальный номер сообщения в беседе (при передаче `peer_id`). |
| `message` | string | Новый текст сообщения. |
| `attachment` | string | Новый список вложений через запятую в формате `<тип><владелец>_<id>`. |
| `keep_forward_messages` | integer | `1` — сохранять прикрепленные пересланные сообщения, `0` — удалить их. |
| `keep_snippets` | integer | `1` — сохранять прикрепленные сниппеты ссылок. |

### Результат

Возвращает `1` в случае успешного изменения.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `100` | `One of the parameters specified was missing or invalid: message not found` — Сообщение не найдено. |
| `909` | `Can't edit message` — Истек допустимый срок редактирования или сообщение отправлено другим пользователем. |

### Пример запроса
```http
POST /method/messages.edit HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&message_id=4512&message=Обновленный+текст&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
