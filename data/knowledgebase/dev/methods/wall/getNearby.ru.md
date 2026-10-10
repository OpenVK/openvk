OpenVK-KB-Heading: wall.getNearby

# wall.getNearby

Возвращает список соседних записей с геометками, опубликованных поблизости от указанной записи на стене.

### Авторизация
Для вызова этого метода необходим токен пользователя.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | **Обязательный параметр.** Идентификатор владельца стены (положительное число — пользователь, отрицательное — сообщество). |
| `post_id` | integer | **Обязательный параметр.** Идентификатор исходной записи с геометкой. |

### Результат

Возвращает массив объектов записей, опубликованных вблизи указанных координат:

| Поле | Тип | Описание |
| --- | --- | --- |
| `message` | string | Текст записи или превью. |
| `url` | string | Относительный URL записи. |
| `created` | string | Время создания в формате HTML/строки. |
| `owner` | object | Информация об авторе записи (`domain`, `photo_50`, `name`, `verified`). |
| `geo` | object | Географические данные точки. |
| `distance` | number | Расстояние от исходной записи в метрах/километрах. |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `15` | `Access denied` — запись закрыта настройками приватности. |
| `100` | `One of the parameters specified was missing or invalid: post_id is undefined` — запись не найдена или удалена. |
| `-97` | `Post doesn't contains geo` — в исходной записи отсутствует информация о местоположении (геометка). |

### Пример запроса
```http
POST /method/wall.getNearby HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&post_id=42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": [
        {
            "message": "Прогулка в парке",
            "url": "/wall1_43",
            "created": "только что",
            "owner": {
                "domain": "durov",
                "photo_50": "/assets/packages/static/openvk/img/camera_50.png",
                "name": "Павел Дуров",
                "verified": true
            },
            "geo": {
                "type": "point",
                "coordinates": "59.9343 30.3351"
            },
            "distance": 120.5
        }
    ]
}
```
