OpenVK-KB-Heading: messages.getChatPreview

# messages.getChatPreview

Returns preview information for an invite link to a multi-user chat (title, cover photo, active member avatars).

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `link` | string | Full invite link or invite hash. **Required.** |
| `fields` | string | Profile fields to return. |

### Result

Returns an object containing:
* `preview` (object) — Chat preview object (`title`, `admin_id`, `members_count`, `photo_50`, `photo_100`, `photo_200`, `members`);
* `profiles` (array, optional) — Profiles of members;
* `groups` (array, optional) — Communities.

### Example Request
```http
POST /method/messages.getChatPreview HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

link=https://openvk.instance/join/abc12345&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
