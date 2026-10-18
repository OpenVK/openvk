OpenVK-KB-Heading: gifts.getCategories

# gifts.getCategories

Возвращает список категорий каталога подарков платформы.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `extended` | boolean | `1` — возвращать локализованные названия и описания категорий для всех поддерживаемых языков платформы, `0` — возвращать только базовые данные. По умолчанию: `0`. |
| `page` | integer | Номер страницы каталога категорий. По умолчанию: `1`. |

### Результат

Возвращает массив объектов категорий подарков. Каждый объект содержит:
| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор категории. |
| `name` | string | Название категории. |
| `description` | string | Описание категории. |
| `thumbnail` | string | URL изображения-миниатюры категории. |
| `localizations` | object | Объект с локализациями названия и описания по кодам языков (при `extended=1`). |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `-105` | `Commerce is disabled on this instance` — Система коммерции/голосов отключена на данном инстансе. |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/gifts.getCategories HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

extended=1&page=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": [
        {
            "id": 1,
            "name": "День рождения",
            "description": "Подарки ко дню рождения",
            "thumbnail": "https://openvk.instance/assets/packages/static/openvk/img/gift_cats/1.png",
            "localizations": {
                "ru": {
                    "name": "День рождения",
                    "desc": "Подарки ко дню рождения"
                },
                "en": {
                    "name": "Birthday",
                    "desc": "Birthday gifts"
                }
            }
        }
    ]
}
```
