OpenVK-KB-Heading: groups.edit

# groups.edit

Edits community information and main settings.

### Authorization
This method requires user authorization (`access_token`) with community administrator privileges.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | **Required parameter**. Community ID. |
| `title` | string | Community title. |
| `description` | string | Community description text. |
| `screen_name` | string | Short community address (e.g. `my_club`). |
| `website` | string | Community website URL. |
| `wall` | integer | Wall mode (`0` — disabled, `1` — open, `2` — limited, `3` — closed, `-1` — unchanged). Default: `-1`. |
| `topics` | integer | Discussions topic creation access: `1` — all members can create topics, `0` — administrators only. |
| `adminlist` | integer | `1` to display community administrators block, `0` to hide. |
| `topicsAboveWall` | integer | `1` to display discussions block above the wall, `0` for default order. |
| `hideFromGlobalFeed` | integer | `1` to hide community posts from the global feed, `0` to display. |
| `audio` | integer | `1` to allow all members to upload audios, `0` for administrators only. |

### Result

Returns `1` upon successful save.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `15` | `Access denied` — The current user is not an administrator of this community. |
| `50` | `Invalid wall value` — An invalid wall mode value was provided. |
| `103` | `Invalid screen_name` — The provided short address is already taken or invalid. |

### Request Example
```http
POST /method/groups.edit HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&title=Updated+Title&wall=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
