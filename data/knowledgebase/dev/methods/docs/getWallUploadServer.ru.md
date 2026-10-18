OpenVK-KB-Heading: docs.getWallUploadServer

# docs.getWallUploadServer

Возвращает адрес сервера для загрузки документов, прикрепляемых к записям на стене.

> **Примечание:** В текущей версии OpenVK метод возвращает заглушку (`0`).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `docs`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `group_id` | integer | Идентификатор сообщества (если документ загружается от имени сообщества). |

### Результат

В текущей версии возвращает `0`.

### Пример запроса
```http
POST /method/docs.getWallUploadServer HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 0
}
```
