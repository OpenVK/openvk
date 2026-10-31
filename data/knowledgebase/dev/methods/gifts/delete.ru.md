OpenVK-KB-Heading: gifts.delete

# gifts.delete

Удаляет отправленный пользователем подарок.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `gift_id` | integer | **Обязательный параметр**. Идентификатор записи отправленного подарка. |

### Результат

Возвращает `1` в случае успешного удаления.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `-105` | `Commerce is disabled on this instance` — Система коммерции/голосов отключена на данном инстансе. |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Invalid gift` — Отправленный подарок с указанным идентификатором не найден. |

### Пример запроса
```http
POST /method/gifts.delete HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

gift_id=12&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
