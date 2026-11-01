OpenVK-KB-Heading: account.getBanned

# account.getBanned

Возвращает список пользователей, добавленных текущим пользователем в черный список.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `offset` | integer | Смещение, необходимое для выборки определенного подмножества черного списка. По умолчанию: `0`. |
| `count` | integer | Количество пользователей, информацию о которых необходимо вернуть (максимум `100`). По умолчанию: `100`. |
| `fields` | string | Список дополнительных полей профилей заблокированных пользователей через запятую (например: `photo_50`, `sex`, `screen_name`). |

### Результат
Возвращает объект со списком заблокированных пользователей:

| Поле | Тип | Описание |
| --- | --- | --- |
| `count` | integer | Общее количество пользователей в черном списке. |
| `items` | array | Массив объектов профилей заблокированных пользователей. |

### Пример запроса
```http
POST /method/account.getBanned HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

offset=0&count=20&fields=photo_50,screen_name&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 2,
                "first_name": "Иван",
                "last_name": "Иванов",
                "screen_name": "id2",
                "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png"
            }
        ]
    }
}
```
