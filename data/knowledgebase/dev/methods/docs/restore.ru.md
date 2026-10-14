OpenVK-KB-Heading: docs.restore

# docs.restore

Восстанавливает ранее удаленный документ в коллекцию текущего пользователя.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `docs`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | Идентификатор владельца документа. **Обязательный параметр.** |
| `doc_id` | integer | Идентификатор документа. **Обязательный параметр.** |

### Результат

Возвращает идентификатор восстановленного документа в формате `virtual_id_doc_id`.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `1150` | `Invalid document id` — Документ не найден. |

### Пример запроса
```http
POST /method/docs.restore HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&doc_id=45&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": "1_45"
}
```
