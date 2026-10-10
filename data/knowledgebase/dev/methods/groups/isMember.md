OpenVK-KB-Heading: groups.isMember

# groups.isMember

Checks whether a user is a member/follower of the specified community.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | **Required parameter**. Community ID. |
| `user_id` | integer | Target user ID. If not specified or `0`, the current user is checked. Default: `0`. |
| `extended` | integer | `1` to return extended membership details object, `0` to return integer status (`1` or `0`). Default: `0`. |

### Result

* When `extended=0`, returns `1` if the user is a member, or `0` otherwise.
* When `extended=1`, returns an object containing:
  * `member` (integer) — `1` if the user is a member, `0` otherwise;
  * `request` (integer) — `0`;
  * `invitation` (integer) — `0`;
  * `can_invite` (integer) — `0`;
  * `can_recall` (integer) — `0`.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `15` | `Access denied` — Access to view this community is restricted. |
| `15` | `Not found` — Specified user was not found or has been deleted. |

### Request Example
```http
POST /method/groups.isMember HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&user_id=2&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "member": 1,
        "request": 0,
        "invitation": 0,
        "can_invite": 0,
        "can_recall": 0
    }
}
```
