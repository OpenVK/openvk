OpenVK-KB-Heading: pay.getIdByMarketingId

# pay.getIdByMarketingId

Проверяет цифровую подпись HMAC SHA-512/224 и преобразует маркетинговый идентификатор в числовой идентификатор.

### Авторизация
Метод является публичным и не требует обязательной авторизации.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `marketing_id` | string | **Обязательный параметр**. Маркетинговый идентификатор в формате `hexId_signature`. |

### Результат

Возвращает числовой идентификатор (`integer`).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `4` | `Invalid marketing id` — Некорректный формат или недействительная цифровая подпись маркетингового идентификатора. |

### Пример запроса
```http
POST /method/pay.getIdByMarketingId HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

marketing_id=2a_b64signature&v=5.138
```

### Пример ответа
```json
{
    "response": 42
}
```
