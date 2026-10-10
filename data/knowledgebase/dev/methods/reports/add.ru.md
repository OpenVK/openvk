OpenVK-KB-Heading: reports.add

# reports.add

Отправляет жалобу модераторам платформы на нарушение правил пользователем, публикацией или другим объектом.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | **Обязательный параметр.** Идентификатор целевого объекта или пользователя, на которого подается жалоба. |
| `type` | string | **Обязательный параметр.** Тип объекта жалобы: `post`, `photo`, `video`, `group`, `comment`, `note`, `app`, `user`, `audio`. |
| `comment` | string | **Обязательный параметр.** Описание причины жалобы (не может быть пустым). |
| `reason` | integer | Числовой код причины жалобы. По умолчанию: `0`. |
| `report_source` | string | Источник жалобы (параметр совместимости). |

### Результат

Возвращает:
* `1` — жалоба успешно отправлена модераторам (или жалоба на самого себя / повторная жалоба пропущена);
* `0` — отправка заблокирована (пользователь заблокирован в системе поддержки).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `100` | `One of the parameters specified was missing or invalid: type should be ...` — Передан неверный или неподдерживаемый тип объекта `type`. |
| `100` | `One of the parameters specified was missing or invalid: Bad input` — Некорректный `owner_id` (`<= 0`). |
| `100` | `One of the parameters specified was missing or invalid: Comment can't be empty` — Передано пустое описание жалобы. |

### Пример запроса
```http
POST /method/reports.add HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=5&type=post&comment=Спам%20и%20реклама&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
