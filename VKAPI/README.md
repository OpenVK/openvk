# VK API Compatability layer for OpenVK

This directory contains VK API handlers, structures and relared
exceptions. It is still a work-in-progress functionality.  
**Note**: requests to API are routed through
openvk.Web.Presenters.VKAPIPresenter, this dir contains only handlers.

[Documentation for API clients](https://openvk.org/dev)

## Implementing API methods

VK API methods have names like this: `example.test`. To implement a
method like this you will need to create a class `Example` in the
Handlers subdirectory. This class **must** extend VKAPIHandler and be
final.  
Next step is to create test method. It **must** have a type hint that is
not void. Everything else is fine, the return value of method will be
authomatically converted to JSON and sent back to client.

### Parameters

Method arguments are parameters. To declare a parameter just create an
argument with the same name. You should also provide correct type hints
for them. Type conversion is done automatically if possible. If not
possible error №1 will be returned.  
If parameter is not passed by client then router will pass default value
to argument. If there is no default value but argument accepts NULL then
NULL will be passed. If NULL is not acceptable, default value is
undefined and parameter is not passed, API will return missing parameter
error to client.

### Returning errors

To return an error, call fail method like this: `$this->fail(5,
"error")` (first argument is error code and second is error message).
You can also throw the exception manually: `throw new
APIErrorException("error", 5)` (class:
openvk.VKAPI.Exceptions.APIErrorException).  
If you throw any exception that does not inherit APIErrorException then
API will return error №1 (unknown error) to client.

### Refering to user

To get user use `getUser` method: `$this->getUser()`. Keep in mind it
will return NULL if user is undefined (no access\_token passed or it is
invalid/expired or roaming authentification failed).  
If you need to check whether user is defined use `userAuthorized`. This
method returns true if user is present and false if not.  
If your method can’t work without user context call `requireUser` and it
will automatically return unauthorized error.

### Working with data

You can use OpenVK models for that. However, **do not** return them
(either you will leak data or JSON conversion will fail). It is better
to create a response object and return it. It is also a good idea to
define a structure in Structures subdirectory.

## The `execute` method (VKScript)

OpenVK provides full support for the VK API `execute` method and an isolated VKScript language interpreter.

### How to Call `execute`

Send a request to `/method/execute` passing your VKScript in the `code` parameter:
```http
POST /method/execute HTTP/1.1
Host: openvk.local
Authorization: Bearer <access_token>
Content-Type: application/x-www-form-urlencoded

v=5.131&code=var u = API.users.get(); return {"user": u[0], "time": API.utils.getServerTime()};
```

You can also send requests with `Content-Type: application/json`:
```json
POST /method/execute
{
    "code": "return API.users.get();",
    "v": "5.131"
}
```

### Script Arguments (`Args`)

Any additional query parameter or JSON body property passed to `/method/execute` (except reserved system keys like `code`, `access_token`, `v`, `callback`, `auth_mechanism`, `requestPort`) is accessible inside the script via the global `Args` object:

```javascript
// Example request with custom parameters: code=...&user_id=1&count=10
var userId = Args.user_id;
var count = Args.count;

var wall = API.wall.get({"owner_id": userId, "count": count});
return wall;
```

---

### VKScript Language Reference

VKScript is a safe, sandboxed subset of ECMAScript / ActionScript executed entirely in PHP memory.

#### 1. Calling API Methods
Call any available VK API method via the global `API` object:
```javascript
var profile = API.users.get({"fields": "photo_max,counters"});
var wall = API.wall.get({"count": 5});
```
- **Error resilience:** If an API method fails (e.g. private profile), it evaluates to `false` without crashing the script. The error is recorded into the `execute_errors` response array.

#### 2. Projection Operator (`@`)
Extract properties or array elements in bulk (like the E4X / Groovy spread operator):
```javascript
var friends = API.friends.get({"fields": "first_name,last_name"});
var names = friends.items@.first_name;      // Array of first names
var photoUrls = friends.items@["photo_50"]; // Dynamic property lookup
```

#### 3. Array and Object Merging (`+`)
The `+` operator supports VK-specific list concatenation and shallow object merging:
```javascript
var list = [1, 2] + [3, 4];            // [1, 2, 3, 4]
var merged = {"a": 1} + {"b": 2};      // {"a": 1, "b": 2}
```

#### 4. Control Structures & Syntax
- **Variables:** `var a = 1, b = "hello";` (function/global script scope).
- **Conditionals:** `if (cond) { ... } else if (other) { ... } else { ... }`, ternary operator `cond ? a : b`.
- **Loops:** `for (var i = 0; i < 10; i++)`, `for (var key in obj)`, `while (cond)`, `do { ... } while (cond)`.
- **Jumps:** `break`, `continue`, `return <expr>;`.
- **Operators:**
  - Arithmetic: `+`, `-`, `*`, `/`, `%`
  - Bitwise: `&`, `|`, `^`, `~`, `<<`, `>>`, `>>>`
  - Assignment: `=`, `+=`, `-=`, `*=`, `/=`, `%=`, `&=`, `|=`, `^=`, `<<=`, `>>=`, `>>>=`
  - Increment / Decrement: `++a`, `a++`, `--a`, `a--`
  - Comparison: `==`, `!=`, `===`, `!==`, `<`, `>`, `<=`, `>=`
  - Operators: `typeof`, `delete obj.prop`, `prop in obj`

#### 5. Built-in Objects & Methods
- **`Math`:** `min`, `max`, `floor`, `ceil`, `round`, `abs`, `trunc`, `pow`, `sqrt`, `random`, `sin`, `cos`, `tan`, `atan2`, `log`, `exp`, `PI`, `E`.
- **`JSON`:** `JSON.parse(str)`, `JSON.stringify(val)`.
- **`Array`:** `push`, `pop`, `shift`, `unshift`, `slice`, `splice`, `indexOf`, `lastIndexOf`, `reverse`, `join`, `concat`, `includes`, `sort`.
- **`String`:** `split`, `slice`, `substr`, `substring`, `indexOf`, `lastIndexOf`, `includes`, `startsWith`, `endsWith`, `toLowerCase`, `toUpperCase`, `trim`, `charAt`, `charCodeAt`, `replace`, `repeat`, `padStart`, `padEnd`.
- **Global Functions:** `parseInt`, `parseFloat`, `isNaN`, `isFinite`, `encodeURIComponent`, `decodeURIComponent`, `escape`, `unescape`, `String()`, `Number()`, `Boolean()`.

#### 6. Execution Limits & Safety
To protect the server against denial-of-service and infinite loops:
- **`MAX_API_CALLS`:** Maximum **25** API calls per script.
- **`MAX_OPERATIONS`:** Maximum **5,000,000** operations per execution.

---

### Stored Procedures (Client-specific scripts)

Official VK mobile applications (VK for Android, iPhone, Windows Phone) and third-party clients (Kate Mobile, VK4ME, etc.) frequently rely on pre-written stored procedures invoked via `/method/execute.<procedure_name>` or `/method/execute?procedure=<procedure_name>`.

#### 1. File Structure and Locations
Stored procedures are standard `.vks` files (VKScript) located under `VKAPI/Procedures/`:
- **Client-specific procedures:** `VKAPI/Procedures/{client_id}/{procedure}.vks`
  - Example: `VKAPI/Procedures/2274003/getFullProfileNewWithGifts.vks` for official VK Android (`client_id = 2274003`).
- **Versioned procedures:** `VKAPI/Procedures/{client_id}/{procedure}.v{func_v}.vks`
  - If the client passes a `func_v` parameter (e.g. `func_v=2`), OpenVK checks for `{procedure}.v2.vks` first before falling back to `{procedure}.vks`.
- **Global fallback procedures:** `VKAPI/Procedures/{procedure}.vks`
  - Procedures in the root of `VKAPI/Procedures/` are available to any client as a fallback if no client-specific override exists.

#### 2. How OpenVK Resolves Client Procedures
When a request arrives at `/method/execute.<procedure>` or `/method/execute?procedure=<name>`:
1. **Default resolution via Token:** OpenVK identifies the client platform and application ID from the user's `access_token` (`token.client_id` or `token.platform`).
2. **Explicit Override:** A client can explicitly target procedures of another client by passing `client_id` or `client_name`:
   - `client_id=2274003` (numeric App ID from `data/clients.xml` or custom app).
   - `client_name=vk_android` (matches tags or platform families like `vk_android`, `vk_iphone`, `kate_mobile`, etc.).
   *These parameters can be supplied via query strings, `x-www-form-urlencoded` POST bodies, or JSON payloads.*

#### 3. Calling Procedures Across Clients
- **Direct HTTP Request:**
  ```http
  POST /method/execute.getFullProfileNewWithGifts HTTP/1.1
  Host: openvk.local
  Content-Type: application/x-www-form-urlencoded

  access_token=<token_from_any_client>&client_id=2274003&id=1&v=5.131
  ```
- **Nested invocation inside another VKScript:**
  ```javascript
  var res = API.execute.getFullProfileNewWithGifts({
      "client_id": 2274003,
      "id": Args.user_id
  });
  return res;
  ```

#### 4. Handling Unauthenticated / Guest Requests
- Stored procedures can be invoked without an `access_token` if `client_id` or `client_name` is provided (or if the procedure is global).
- The procedure runs in guest mode with `$identity = null`.
- Public methods and stubs return valid data normally.
- Any internal method requiring authentication (e.g., `account.getCounters`, `messages.*`) evaluates to `false` without crashing the procedure, and appends an error (`error_code: 5`, `"User authorization failed: no access_token passed."`) to the `execute_errors` array. The procedure continues executing and safely returns fallback values.

Have a lot of fun <sup></sup>


