OpenVK-KB-Heading: messages.getConversationMembers

# messages.getConversationMembers

Returns members, administrators, and permissions of a conversation/chat.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Conversation identifier (`2000000000 + chat_id`). **Required.** |
| `extended` | integer | `1` — return user and community profile objects. Default: `0`. |
| `fields` | string | Profile fields to return when `extended=1`. |
| `group_id` | integer | Community identifier. |

### Result

Returns an object containing:
* `count` (integer) — Total count of members in the conversation;
* `items` (array) — Array of member objects:
  * `member_id` (integer) — User or community ID;
  * `invited_by` (integer) — User ID who invited this member;
  * `join_date` (integer) — Unix timestamp when the member joined;
  * `is_admin` (boolean) — `true` if member is an administrator;
  * `is_owner` (boolean) — `true` if member is the chat creator;
  * `can_kick` (boolean) — `true` if current user can remove this member;
* `chat_restrictions` (object, optional) — Chat restrictions and ACL;
* `profiles` (array, optional) — User profile objects (when `extended=1`);
* `groups` (array, optional) — Community profile objects (when `extended=1`).

### Example Request
```http
POST /method/messages.getConversationMembers HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
