OpenVK-KB-Heading: messages.setChatPermissions

# messages.setChatPermissions

Configures permission settings and ACL for actions inside a multi-user chat.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Destination ID (`2000000000 + chat_id`). **Required.** |
| `invite` | string | Who can invite members: `"all"` or `"admin"`. |
| `change_info` | string | Who can edit title and description: `"all"` or `"admin"`. |
| `change_pin` | string | Who can pin messages: `"all"` or `"admin"`. |
| `use_mass_mentions` | string | Who can use `@all` / `@online`: `"all"` or `"admin"`. |
| `see_invite_link` | string | Who can view invite link: `"all"` or `"admin"`. |
| `change_admins` | string | Who can manage administrators: `"all"` or `"admin"`. |

### Result

Returns `1` on success.

### Example Request
```http
POST /method/messages.setChatPermissions HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&invite=admin&change_pin=admin&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
