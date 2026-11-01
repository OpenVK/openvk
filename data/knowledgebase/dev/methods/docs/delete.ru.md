OpenVK-KB-Heading: docs.delete

# docs.delete

Удаляет документ из коллекции пользователя или сообщества.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `docs`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | Идентификатор владельца документа. **Обязательный параметр.** |
| `doc_id` | integer | Идентификатор документа. **Обязательный параметр.** |

### Результат

Возвращает `1` в случае успешного удаления.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `1150` | `Invalid document id` — Документ не найден или уже удален. |
| `1153` | `Access to document is denied` — Нет прав на удаление данного документа. |

### Пример запроса
```http
POST /method/docs.delete HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&doc_id=45&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
