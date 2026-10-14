OpenVK-KB-Heading: apps.getMiniAppsCatalogSearch

# apps.getMiniAppsCatalogSearch

Выполняет поиск по каталогу мини-приложений.

> **Примечание (заглушка совместимости):** Поиск по каталогу приложений в OpenVK не реализован. Метод возвращает пустую структуру ответа (`{"count": 0, "items": [], "apps": [], "profiles": [], "groups": []}`).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `query` | string | Поисковый запрос. |
| `limit` | integer | Количество элементов для возврата. По умолчанию: `0`. |
| `start_from` | string | Идентификатор смещения для постраничной навигации. |
| `fields` | string | Список дополнительных полей профилей или сообществ через запятую. |

### Результат
Возвращает объект с результатами поиска:

| Поле | Тип | Описание |
| --- | --- | --- |
| `count` | integer | Количество найденных приложений (`0`). |
| `items` | array | Список элементов поиска (`[]`). |
| `apps` | array | Список объектов приложений (`[]`). |
| `profiles` | array | Профили пользователей (`[]`). |
| `groups` | array | Сообщества (`[]`). |

### Пример запроса
```http
POST /method/apps.getMiniAppsCatalogSearch HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

query=игры&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 0,
        "items": [],
        "apps": [],
        "profiles": [],
        "groups": []
    }
}
```
