OpenVK-KB-Heading: gifts.getGiftsInCategory

# gifts.getGiftsInCategory

Возвращает список подарков, доступных в выбранной категории каталога.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `id` | integer | **Обязательный параметр**. Идентификатор категории каталога подарков. |
| `page` | integer | Номер страницы списка подарков в категории. По умолчанию: `1`. |

### Результат

Возвращает массив объектов подарков. Каждый объект содержит:
| Поле | Тип | Описание |
| --- | --- | --- |
| `name` | string | Название подарка. |
| `image` | string | Относительный путь к изображению подарка. |
| `usages_left` | integer | Количество доступных бесплатных отправок подарка для текущего пользователя. |
| `price` | integer | Стоимость подарка в голосах (монетах). |
| `is_free` | boolean | `true`, если подарок является бесплатным, иначе `false`. |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `-105` | `Commerce is disabled on this instance` — Система коммерции/голосов отключена на данном инстансе. |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Category not found` — Категория подарков с указанным идентификатором не найдена. |

### Пример запроса
```http
POST /method/gifts.getGiftsInCategory HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

id=1&page=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": [
        {
            "name": "Торт",
            "image": "/assets/packages/static/openvk/img/gifts/101.png",
            "usages_left": 0,
            "price": 1,
            "is_free": false
        }
    ]
}
```
