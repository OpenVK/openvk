OpenVK-KB-Heading: execute

# execute

Универсальный метод, который позволяет запускать алгоритмы и выполнять последовательность вызовов других методов API за один HTTP-запрос к серверу.

Алгоритм передается в виде кода на языке **VKScript** (безопасное подмножество JavaScript / ECMAScript, исполняемое в изолированной песочнице на стороне сервера).

---

## 1. Вызов метода

Выполните HTTP POST или GET запрос к эндпоинту `/method/execute`:

```http
POST /method/execute HTTP/1.1
Host: openvk.instance
Authorization: Bearer a1b2c3d4e5f6...
Content-Type: application/x-www-form-urlencoded

v=5.80&code=var user = API.users.get()[0]; var friends = API.friends.get({"count": 5}); return {"user": user, "friends": friends};
```

Также поддерживаются запросы с `Content-Type: application/json`:

```json
POST /method/execute
{
    "v": "5.80",
    "code": "var u = API.users.get(); return u;"
}
```

---

## 2. Параметры метода

| Параметр | Тип | Обязательный | Описание |
| --- | --- | --- | --- |
| `code` | string | **Да** (если не указана процедура) | Исходный код скрипта на языке VKScript. |
| `procedure` | string | Нет | Имя хранимой процедуры (также доступно через `/method/execute.<procedure_name>`). |
| `func_v` | integer | Нет | Номер версии хранимой процедуры. |
| `client_id` | integer | Нет | Идентификатор приложения для вызова платформо-зависимых процедур. |
| `client_name` | string | Нет | Тег приложения (например `vk_android`, `kate_mobile`). |
| `v` | string | **Да** | Версия API. |

---

## 3. Передача аргументов в скрипт (`Args`)

Любые дополнительные параметры, переданные в запросе к `execute` (кроме служебных `code`, `access_token`, `v`, `callback`, `auth_mechanism`), становятся доступны внутри скрипта через глобальный объект `Args`:

```http
POST /method/execute HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

v=5.80&user_id=1&post_count=3&code=var posts = API.wall.get({"owner_id": Args.user_id, "count": Args.post_count}); return posts;
```

---

## 4. Возможности языка VKScript

### Вызовы методов API (`API.<method>`)
Вызов любого метода OpenVK API осуществляется через глобальный объект `API`:

```javascript
var me = API.users.get({"fields": "photo_200,counters"});
var myAudios = API.audio.get({"count": 10});
return {
    "profile": me[0],
    "tracks": myAudios.items
};
```

> **Устойчивость к ошибкам:** Если метод внутри скрипта завершился с ошибкой (например, приватный профиль или нет прав), вызов возвращает `false`, не прерывая выполнение всего скрипта. Ошибка добавляется в специальный массив `execute_errors` в ответе.

---

### Оператор проекции (`@`)
Позволяет быстро извлечь поле из массива объектов (аналог E4X / Groovy spread):

```javascript
var friends = API.friends.get({"fields": "first_name,last_name"});
var firstNames = friends.items@.first_name; // Массив строк с именами
var photoUrls = friends.items@["photo_50"];  // Динамическое обращение по ключу
return firstNames;
```

---

### Сложение и объединение (`+`)
Оператор `+` поддерживает склеивание массивов и поверхностное слияние объектов:

```javascript
var list = [1, 2] + [3, 4];         // Результат: [1, 2, 3, 4]
var merged = {"a": 1} + {"b": 2};   // Результат: {"a": 1, "b": 2}
```

---

### Синтаксис и управляющие конструкции
* **Переменные:** `var a = 1, b = "текст";`
* **Условия:** `if (условие) { ... } else if (...) { ... } else { ... }`, тернарный оператор `cond ? x : y`
* **Циклы:** `for (var i = 0; i < n; i++)`, `for (var key in obj)`, `while (условие)`, `do { ... } while (условие)`
* **Прерывания:** `break`, `continue`, `return результат;`
* **Операторы:** `+`, `-`, `*`, `/`, `%`, `==`, `!=`, `===`, `!==`, `<`, `>`, `<=`, `>=`, `&&`, `||`, `!`, `typeof`, `delete obj.prop`, `prop in obj`
* **Битовые операции:** `&`, `|`, `^`, `~`, `<<`, `>>`, `>>>`

---

### Встроенные объекты и функции
* **`Math`:** `min`, `max`, `floor`, `ceil`, `round`, `abs`, `trunc`, `pow`, `sqrt`, `random`, `sin`, `cos`, `tan`, `log`, `PI`, `E`.
* **`JSON`:** `JSON.parse(str)`, `JSON.stringify(obj)`.
* **Массивы (`Array`):** `push`, `pop`, `shift`, `unshift`, `slice`, `splice`, `indexOf`, `lastIndexOf`, `reverse`, `join`, `concat`, `includes`, `sort`.
* **Строки (`String`):** `split`, `slice`, `substr`, `substring`, `indexOf`, `lastIndexOf`, `includes`, `startsWith`, `endsWith`, `toLowerCase`, `toUpperCase`, `trim`, `charAt`, `charCodeAt`, `replace`, `repeat`.
* **Глобальные функции:** `parseInt`, `parseFloat`, `isNaN`, `isFinite`, `encodeURIComponent`, `decodeURIComponent`, `escape`, `unescape`, `String()`, `Number()`, `Boolean()`.

---

## 5. Ограничения выполнения

Для предотвращения зависаний и защиты сервера установлены лимиты:
* **Не более 25 вызовов API** за один запуск `execute` (`MAX_API_CALLS = 25`).
* **Не более 5 000 000 операций** на выполнение скрипта.

---

## 6. Пример ответа с ошибками (`execute_errors`)

Если один из методов вернул ошибку, выполнение скрипта продолжается, а ошибки возвращаются в поле `execute_errors`:

```json
{
    "response": {
        "user": {
            "id": 1,
            "first_name": "Павел"
        },
        "private_data": false
    },
    "execute_errors": [
        {
            "method": "users.get",
            "error_code": 30,
            "error_msg": "This profile is private"
        }
    ]
}
```
