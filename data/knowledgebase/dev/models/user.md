OpenVK-KB-Heading: User Object

# User Object

The **User** object describes a user profile in OpenVK. The exact field structure depends on the API version passed in the `v` parameter.

---

## API Version 5.0 and Higher (v >= 5.0)

### Core Fields

These basic fields are returned by standard profile fetching methods (such as `users.get`):

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | User ID. |
| `first_name` | string | User's first name. |
| `last_name` | string | User's last name. |
| `deactivated` | string\|false | Returned if the page is deactivated (`"deleted"` or `"banned"`). |
| `is_closed` | boolean | Whether the profile is closed by privacy settings (`true` / `false`). |
| `can_access_closed` | integer | `1` if the current user can view this closed profile, `0` otherwise. |

### Optional Fields (`fields`)

The following fields are included when requested in the `fields` query parameter:

| Field | Type | Description |
| --- | --- | --- |
| `screen_name` | string | Short profile domain address (e.g. `durov` or `id1`). |
| `sex` | integer | Sex: `1` for female, `2` for male, `0` if unspecified. |
| `verified` | integer | `1` if the profile is verified with a checkmark badge. |
| `status` | string | Profile status text. |
| `nickname` | string | User's nickname. |
| `photo_50` | string | 50x50px square avatar URL. |
| `photo_100` | string | 100x100px avatar URL. |
| `photo_200` | string | 200x200px avatar URL. |
| `photo_max` | string | Avatar URL in highest available resolution. |
| `photo_base` | string | Base avatar URL. |
| `photo_id` | string\|null | Main profile photo ID (e.g. `photo1_123`). |
| `reg_date` | integer | Registration timestamp (Unix time). |
| `rating` | integer | Current user rating score in OpenVK. |
| `background` | array\|null | OpenVK profile backdrop image configuration. |
| `is_dead` | boolean | Memorial page marker. |
| `can_write_private_message` | integer | `1` if the user allows direct messages. |
| `blacklisted_by_me` | integer | `1` if this user was blacklisted by the current user. |
| `blacklisted` | integer | `1` if the current user is blacklisted by this user. |
| `games` | string | Favorite games. |

### JSON Example (v >= 5.0)
```json
{
    "id": 1,
    "first_name": "Pavel",
    "last_name": "Durov",
    "deactivated": false,
    "is_closed": false,
    "can_access_closed": 1,
    "screen_name": "durov",
    "sex": 2,
    "verified": 1,
    "status": "VK",
    "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
    "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
    "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png"
}
```

---

## Below API Version 5.0 (v < 5.0)

In earlier protocol versions (3.x, 4.x), legacy field names are used for compatibility with older clients:

| Field | Type | Description |
| --- | --- | --- |
| `uid` | integer | User ID (equivalent to `id` in API v5.0+). |
| `first_name` | string | User's first name. |
| `last_name` | string | User's last name. |
| `photo` | string | 50x50px square avatar URL. |
| `photo_rec` | string | 50x50px square avatar URL. |
| `photo_medium_rec` | string | 100x100px square avatar URL. |
| `photo_50` | string | 50x50px avatar URL. |
| `photo_100` | string | 100x100px avatar URL. |
| `screen_name` | string | Profile domain address. |
| `sex` | integer | Sex: `1` for female, `2` for male, `0` if unspecified. |

### JSON Example (v < 5.0)
```json
{
    "uid": 1,
    "first_name": "Pavel",
    "last_name": "Durov",
    "sex": 2,
    "photo": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
    "photo_rec": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
    "photo_medium_rec": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
    "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
    "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
    "screen_name": "durov"
}
```
