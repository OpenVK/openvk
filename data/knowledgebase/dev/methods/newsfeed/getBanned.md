OpenVK-KB-Heading: newsfeed.getBanned

# newsfeed.getBanned

Returns a list of users and communities hidden (ignored) by the current user from their newsfeed.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `extended` | boolean | `1` (`true`) — return full profile and group objects, `0` (`false`) — return numeric IDs only. Default: `0`. |
| `fields` | string | Comma-separated list of additional fields for profiles and groups (when `extended=1`). |
| `name_case` | string | Grammatical case for name declension (default: `nom`). |
| `merge` | boolean | When `extended=1` and `merge=1`, merges users and groups into a single `items` array with `count`. When `merge=0`, returns separated `groups` and `profiles` arrays. Default: `0`. |

### Result

Depending on parameters, returns:
* When `extended=0`:
  * `groups` (array) — array of negative IDs of hidden communities;
  * `members` (array) — array of positive IDs of hidden users.
* When `extended=1, merge=0`:
  * `groups` (array) — array of hidden community objects;
  * `profiles` (array) — array of hidden user profile objects.
* When `extended=1, merge=1`:
  * `count` (integer) — total count of hidden sources;
  * `items` (array) — merged array of user and community objects.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |

### Request Example
```http
POST /method/newsfeed.getBanned HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "groups": [],
        "profiles": [
            {
                "id": 5,
                "first_name": "Ivan",
                "last_name": "Ivanov",
                "screen_name": "ivan",
                "photo_50": "/assets/packages/static/openvk/img/camera_50.png"
            }
        ]
    }
}
```
