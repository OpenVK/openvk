OpenVK-KB-Heading: friends.getMutual

# friends.getMutual

Returns a list of mutual friends between the current (or specified) user and other users.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `source_uid` | integer | ID of the user whose mutual friends are searched. If not specified, current user ID is used. Default: `0`. |
| `target_uid` | integer | Target user ID to find mutual friends with (used when `target_uids` is not passed). |
| `target_uids` | string | Comma-separated list of target user IDs to find mutual friends with (e.g. `2,3,4`). |
| `order` | string | Sort order: `"random"` for random order, otherwise ascending by `id`. Default: empty string. |
| `count` | integer | Number of mutual friends to return. Default: all. |
| `offset` | integer | Offset needed to return a specific subset of mutual friends. Default: `0`. |
| `need_common_count` | boolean | `1` to return the total number of mutual friends in `common_count`, `0` otherwise. Default: `0`. |

### Result

* If a single `target_uid` was specified, returns an object with fields:
  * `target_uid` (integer) — Target user ID;
  * `common_friends` (array) — Array of integer mutual friend IDs;
  * `common_count` (integer, optional) — Total mutual friends count (when `need_common_count=1`).
* If `target_uids` list was specified, returns an array of such objects for each target user.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `30` | `This profile is private` — Target user's profile or friends list is private. |
| `100` | `User was deleted or banned` — Source user was deleted or banned. |
| `100` | `One of the parameters specified was missing or invalid: target_uid is undefined` — Neither `target_uid` nor `target_uids` was specified. |
| `100` | `One of the parameters specified was missing or invalid: target_uids[N] not integer` — An invalid user ID was passed in `target_uids`. |

### Request Example
```http
POST /method/friends.getMutual HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

target_uid=2&need_common_count=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "target_uid": 2,
        "common_friends": [
            3,
            7,
            12
        ],
        "common_count": 3
    }
}
```
