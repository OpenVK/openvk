OpenVK-KB-Heading: Group Object

# Group Object

The **Group** object describes a community (group, public page, or event) in VK / OpenVK. The structure of the returned fields depends on the API version passed in the `v` parameter.

---

## API version 5.0 and higher (v >= 5.0)

### Basic Fields

The following fields are returned by basic methods that fetch communities (e.g. `groups.get`, `groups.getById`):

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Community ID. |
| `name` | string | Community title. |
| `screen_name` | string | Short community domain / slug (e.g. `apiclub` or `club1`). |
| `is_closed` | integer | Privacy level: `0` — open, `1` — closed, `2` — private. |
| `type` | string | Community type: `"group"`, `"page"`, or `"event"`. |
| `is_member` | integer | `1` if current user is a member/subscriber, `0` otherwise. |
| `is_admin` | integer | `1` if current user is a manager/admin, `0` otherwise. |
| `deactivated` | string\|null | Returned if community is blocked or deleted (`"banned"` or `"deleted"`). |
| `can_access_closed` | integer | `1` if current user can view closed community content, `0` otherwise. |
| `can_message` | boolean | Whether current user is allowed to send messages to the community. |

### Optional Fields (`fields`)

The following fields are returned if requested in the `fields` parameter:

| Field | Type | Description |
| --- | --- | --- |
| `verified` | integer | `1` if verified by administration, `0` otherwise. |
| `site` | string | Community website address. |
| `status` | string | Community status text. |
| `description` | string | Full community text description. |
| `members_count` | integer | Number of community members. |
| `photo_50` | string | URL of community avatar 50x50px. |
| `photo_100` | string | URL of community avatar 100x100px. |
| `photo_200` | string | URL of community avatar 200x200px. |
| `photo_max` | string | URL of community avatar in max available resolution. |
| `photo_id` | integer\|null | ID of main community avatar photo. |
| `counters` | object | Section counters object (`photos`, `albums`, `topics`, `videos`, `docs`). |
| `contacts` | array | List of community contacts (array of objects with `user_id`, `desc`). |
| `can_post` | boolean | Whether current user can publish posts on the wall. |
| `can_suggest` | boolean | Whether user can suggest news (for public pages). |
| `start_date` | integer | Event start date (Unix timestamp, events only). |
| `finish_date` | integer | Event finish date (Unix timestamp, events only). |

### Example Object (v >= 5.0)
```json
{
    "id": 1,
    "name": "OpenVK Team",
    "screen_name": "openvk",
    "is_closed": 0,
    "type": "group",
    "is_member": 1,
    "is_admin": 0,
    "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/community_50.png",
    "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/community_100.png",
    "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/community_200.png",
    "members_count": 1420
}
```

---

## Before API version 5.0 (v < 5.0)

In API versions 3.x and 4.x, the following legacy field names are returned:

| Field | Type | Description |
| --- | --- | --- |
| `gid` | integer | Community ID (equivalent to `id` in API v5.0+). |
| `name` | string | Community title. |
| `screen_name` | string | Short community domain. |
| `is_closed` | integer | Privacy level (`0`, `1`, `2`). |
| `type` | string | Community type (`"group"`, `"page"`, `"event"`). |
| `photo` | string | Avatar URL 50x50px. |
| `photo_medium` | string | Avatar URL 100x100px. |
| `photo_big` | string | Avatar URL 200x200px. |
| `is_member` | integer | Current user membership flag. |
| `is_admin` | integer | Current user administrator flag. |

### Example Object (v < 5.0)
```json
{
    "gid": 1,
    "name": "OpenVK Team",
    "screen_name": "openvk",
    "is_closed": 0,
    "type": "group",
    "is_member": 1,
    "is_admin": 0,
    "photo": "https://openvk.instance/assets/packages/static/openvk/img/community_50.png",
    "photo_medium": "https://openvk.instance/assets/packages/static/openvk/img/community_100.png",
    "photo_big": "https://openvk.instance/assets/packages/static/openvk/img/community_200.png"
}
```
