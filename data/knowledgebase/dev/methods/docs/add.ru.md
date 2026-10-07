OpenVK-KB-Heading: docs.add

# docs.add

Копирует документ в коллекцию документов текущего авторизованного пользователя.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `docs`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | Идентификатор владельца документа. **Обязательный параметр.** |
| `doc_id` | integer | Идентификатор документа. **Обязательный параметр.** |
| `access_key` | string | Ключ доступа к документу (требуется для приватных файлов). |

### Результат

Возвращает строковый идентификатор созданной копии документа в формате `virtual_id_doc_id` (например, `"1_45"`).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Неверный ключ доступа. |
| `100` | `this document already added` — Документ уже добавлен в коллекцию текущего пользователя. |
| `1150` | `Invalid document id` — Документ не найден или удален. |

### Пример запроса
```http
POST /method/docs.add HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=2&doc_id=10&access_key=4f8a1c9e2b3d4f5a6b&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": "1_45"
}
```
