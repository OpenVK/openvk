OpenVK-KB-Heading: execute

# execute

A versatile method that allows running custom algorithms and executing a sequence of API method calls in a single HTTP request to the server.

The algorithm is passed as code written in **VKScript** (a sandboxed, safe subset of JavaScript / ECMAScript executed in PHP memory).

---

## 1. Calling the Method

Send an HTTP POST or GET request to the `/method/execute` endpoint:

```http
POST /method/execute HTTP/1.1
Host: openvk.instance
Authorization: Bearer a1b2c3d4e5f6...
Content-Type: application/x-www-form-urlencoded

v=5.80&code=var user = API.users.get()[0]; var friends = API.friends.get({"count": 5}); return {"user": user, "friends": friends};
```

JSON request payloads are also supported:

```json
POST /method/execute
{
    "v": "5.80",
    "code": "var u = API.users.get(); return u;"
}
```

---

## 2. Parameters

| Parameter | Type | Required | Description |
| --- | --- | --- | --- |
| `code` | string | **Yes** (if procedure not set) | VKScript source code. |
| `procedure` | string | No | Stored procedure name (also accessible via `/method/execute.<procedure_name>`). |
| `func_v` | integer | No | Stored procedure version number. |
| `client_id` | integer | No | Client ID to select platform-specific procedures. |
| `client_name` | string | Нет | Client tag (e.g. `vk_android`, `kate_mobile`). |
| `v` | string | **Yes** | API version. |

---

## 3. Script Arguments (`Args`)

Any additional parameters passed to `execute` (except reserved system parameters `code`, `access_token`, `v`, `callback`, `auth_mechanism`) are accessible inside the script via the global `Args` object:

```http
POST /method/execute HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

v=5.80&user_id=1&post_count=3&code=var posts = API.wall.get({"owner_id": Args.user_id, "count": Args.post_count}); return posts;
```

---

## 4. VKScript Language Features

### API Method Calls (`API.<method>`)
Invoke any OpenVK API method via the global `API` object:

```javascript
var me = API.users.get({"fields": "photo_200,counters"});
var myAudios = API.audio.get({"count": 10});
return {
    "profile": me[0],
    "tracks": myAudios.items
};
```

> **Error Resilience:** If an API method fails (e.g. private profile), it evaluates to `false` without crashing the script. The error is appended to the `execute_errors` response array.

---

### Projection Operator (`@`)
Extract properties from an array of objects (similar to E4X / Groovy spread operator):

```javascript
var friends = API.friends.get({"fields": "first_name,last_name"});
var firstNames = friends.items@.first_name; // Array of string names
var photoUrls = friends.items@["photo_50"];  // Dynamic property lookup
return firstNames;
```

---

### Merging and Concatenation (`+`)
The `+` operator supports array concatenation and shallow object merging:

```javascript
var list = [1, 2] + [3, 4];         // Result: [1, 2, 3, 4]
var merged = {"a": 1} + {"b": 2};   // Result: {"a": 1, "b": 2}
```

---

### Syntax and Control Structures
* **Variables:** `var a = 1, b = "hello";`
* **Conditionals:** `if (cond) { ... } else if (...) { ... } else { ... }`, ternary operator `cond ? x : y`
* **Loops:** `for (var i = 0; i < n; i++)`, `for (var key in obj)`, `while (cond)`, `do { ... } while (cond)`
* **Jumps:** `break`, `continue`, `return result;`
* **Operators:** `+`, `-`, `*`, `/`, `%`, `==`, `!=`, `===`, `!==`, `<`, `>`, `<=`, `>=`, `&&`, `||`, `!`, `typeof`, `delete obj.prop`, `prop in obj`
* **Bitwise:** `&`, `|`, `^`, `~`, `<<`, `>>`, `>>>`

---

### Built-in Objects and Functions
* **`Math`:** `min`, `max`, `floor`, `ceil`, `round`, `abs`, `trunc`, `pow`, `sqrt`, `random`, `sin`, `cos`, `tan`, `log`, `PI`, `E`.
* **`JSON`:** `JSON.parse(str)`, `JSON.stringify(obj)`.
* **`Array`:** `push`, `pop`, `shift`, `unshift`, `slice`, `splice`, `indexOf`, `lastIndexOf`, `reverse`, `join`, `concat`, `includes`, `sort`.
* **`String`:** `split`, `slice`, `substr`, `substring`, `indexOf`, `lastIndexOf`, `includes`, `startsWith`, `endsWith`, `toLowerCase`, `toUpperCase`, `trim`, `charAt`, `charCodeAt`, `replace`, `repeat`.
* **Global functions:** `parseInt`, `parseFloat`, `isNaN`, `isFinite`, `encodeURIComponent`, `decodeURIComponent`, `escape`, `unescape`, `String()`, `Number()`, `Boolean()`.

---

## 5. Execution Limits

To protect server performance, the following limits are enforced:
* **Max 25 API calls** per `execute` run (`MAX_API_CALLS = 25`).
* **Max 5,000,000 operations** per execution.

---

## 6. Response with Errors Example (`execute_errors`)

```json
{
    "response": {
        "user": {
            "id": 1,
            "first_name": "Pavel"
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
