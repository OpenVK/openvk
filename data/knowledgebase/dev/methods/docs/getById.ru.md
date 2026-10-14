OpenVK-KB-Heading: docs.getById

# docs.getById

Возвращает информацию о документах по их идентификаторам.

Идентификаторы передаются в формате `owner_id_doc_id` или `owner_id_doc_id_access_key` (например, `1_10` или `1_10_4f8a1c9e2b3d4f5a6b`).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `docs` | string | Список идентификаторов документов через запятую (например, `1_1,1_2_abcdef1234`). **Обязательный параметр.** |
| `return_tags` | integer | `1` — возвращать теги для каждого документа, `0` — не возвращать. По умолчанию: `0`. |

### Результат

Возвращает массив объектов документов (`[doc1, doc2, ...]`).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `100` | `One of the parameters specified was missing or invalid: docs is undefined` — Не передан параметр `docs`. |

### Пример запроса
```http
POST /method/docs.getById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

docs=1_1,1_2_4f8a1c9e2b3d4f5a6b&return_tags=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": [
        {
            "id": 1,
            "owner_id": 1,
            "true_owner_id": 1,
            "title": "Archive.zip",
            "size": 5242880,
            "ext": "zip",
            "url": "https://openvk.instance/blob_a1/hash.zip",
            "date": 1609459200,
            "type": 2,
            "is_hidden": 0,
            "is_licensed": 0,
            "is_unsafe": 0,
            "folder_id": 0,
            "access_key": "4f8a1c9e2b3d4f5a6b",
            "can_manage": true,
            "tags": [
                "исходники"
            ]
        }
    ]
}
```
