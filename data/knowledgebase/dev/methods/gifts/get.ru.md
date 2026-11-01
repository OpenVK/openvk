OpenVK-KB-Heading: gifts.get

# gifts.get

Возвращает список подарков, полученных пользователем.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `user_id` | integer | Идентификатор пользователя, чьи полученные подарки необходимо вернуть. Если не указан, используется ID текущего пользователя. По умолчанию: `0`. |
| `count` | integer | Количество подарков, которое необходимо вернуть. По умолчанию: `10`. |
| `offset` | integer | Смещение относительно начала списка подарков. По умолчанию: `0`. |

### Результат

В API версии 5.0 и выше возвращает объект с полями:
* `count` (integer) — общее количество полученных подарков;
* `items` (array) — массив объектов полученных подарков.

В API версии ниже 5.0 возвращается массив формата `[count, gift1, gift2, ...]`.

Каждый объект подарка содержит:
| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор записи о подарке. |
| `from_id` | integer | Идентификатор отправителя подарка (`0`, если подарок отправлен анонимно). |
| `message` | string | Текст прикрепленного к подарку сообщения. |
| `date` | integer | Дата и время отправки подарка (Unix timestamp). |
| `privacy` | integer | Уровень приватности: `0` — публичный подарок, `1` — анонимный отправитель. |
| `gift` | object | Объект с информацией о самом подарке: `id` (integer), `thumb_256` (string), `thumb_96` (string), `thumb_48` (string). |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Доступ к подаркам пользователя ограничен настройками приватности или пользователь заблокирован. |

### Пример запроса
```http
POST /method/gifts.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&count=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 5,
        "items": [
            {
                "id": 12,
                "from_id": 2,
                "message": "С днем рождения!",
                "date": 1609459200,
                "privacy": 0,
                "gift": {
                    "id": 101,
                    "thumb_256": "https://openvk.instance/assets/packages/static/openvk/img/gifts/101.png",
                    "thumb_96": "https://openvk.instance/assets/packages/static/openvk/img/gifts/101.png",
                    "thumb_48": "https://openvk.instance/assets/packages/static/openvk/img/gifts/101.png"
                }
            }
        ]
    }
}
```
