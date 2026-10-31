OpenVK-KB-Heading: groups.search

# groups.search

Осуществляет поиск по сообществам платформы по ключевым словам.

### Авторизация
Метод может вызываться как с авторизацией пользователя, так и без нее.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `q` | string | **Обязательный параметр**. Строка поискового запроса. |
| `offset` | integer | Смещение относительно начала списка найденных сообществ. По умолчанию: `0`. |
| `count` | integer | Количество возвращаемых сообществ (значение не должно превышать `100`). По умолчанию: `100`. |
| `fields` | string | Список дополнительных полей сообществ через запятую (например, `description,members_count,status,site`). По умолчанию: `"screen_name,is_admin,is_member,is_advertiser,photo_50,photo_100,photo_200"`. |

### Результат

В API версии 5.0 и выше возвращает объект с полями:
* `count` (integer) — общее количество найденных сообществ;
* `items` (array) — массив объектов сообществ [Group](/dev/models/group).

В API версии ниже 5.0 возвращается массив формата `[count, group1, group2, ...]`.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `100` | `One of the parameters specified was missing or invalid: count should be less or equal to 100` — Значение параметра `count` превышает 100. |

### Пример запроса
```http
POST /method/groups.search HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

q=новости&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 5,
                "name": "Новости OpenVK",
                "screen_name": "news",
                "is_closed": 0,
                "type": "page",
                "is_admin": 0,
                "is_member": 1,
                "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
                "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
                "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png"
            }
        ]
    }
}
```
