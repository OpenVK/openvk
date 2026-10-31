OpenVK-KB-Heading: ovk.aboutInstance

# ovk.aboutInstance

Returns detailed metadata and statistics about the current OpenVK instance: counters (users, groups, posts), administrators list, popular communities, and instance links.

### Authorization
This method is public and does not require user authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `fields` | string | Comma-separated list of sections to include. Possible values: `statistics`, `administrators`, `popular_groups`, `links`. Default: `statistics,administrators,popular_groups,links`. |
| `admin_fields` | string | Comma-separated list of additional fields for administrator profiles (e.g. `photo_50, photo_100, screen_name`). |
| `group_fields` | string | Comma-separated list of additional fields for popular communities. |

### Result

Returns an object containing the requested sections:
* `statistics` (object) — instance statistics:
  * `users_count` (integer) — total registered users;
  * `online_users_count` (integer) — online users;
  * `active_users_count` (integer) — active users;
  * `groups_count` (integer) — total communities;
  * `wall_posts_count` (integer) — total wall posts.
* `administrators` (object) — instance administrators list:
  * `count` (integer) — administrators count;
  * `items` (array) — array of administrator profiles.
* `popular_groups` (object) — popular communities:
  * `count` (integer) — communities count;
  * `items` (array) — array of community objects.
* `links` (object) — instance official links:
  * `count` (integer) — links count;
  * `items` (array) — array of link objects.

### Request Example
```http
POST /method/ovk.aboutInstance HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

fields=statistics,links&v=5.138
```

### Response Example
```json
{
    "response": {
        "statistics": {
            "users_count": 1250,
            "online_users_count": 42,
            "active_users_count": 380,
            "groups_count": 65,
            "wall_posts_count": 4820
        },
        "links": {
            "count": 1,
            "items": [
                {
                    "title": "Source Code",
                    "url": "https://github.com/openvk/openvk"
                }
            ]
        }
    }
}
```
