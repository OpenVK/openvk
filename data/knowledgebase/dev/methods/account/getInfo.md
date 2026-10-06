OpenVK-KB-Heading: account.getInfo

# account.getInfo

Returns information about the current account.

### Authorization
This method requires `account` access permission or user authorization.

### Parameters
This method does not accept any parameters.

### Result
Returns an object with account properties:

* `2fa_required` — `1` if two-factor authentication is enabled.
* `country` — user country code.
* `https_required` — `1` if HTTPS connection is enforced.
* `intro` — intro bitmask.
* `lang` — interface language ID.
* `music_available` — `true` if the music section is accessible.
* `own_posts_default` — `1` if only user's own posts are displayed by default on the wall.
* `no_wall_replies` — `1` if wall comments are disabled.

### Example Response

```json
{
    "response": {
        "2fa_required": 0,
        "country": "RU",
        "eu_user": false,
        "https_required": 1,
        "phone": "",
        "link_redirects": "{}",
        "intro": 0,
        "community_comments": false,
        "is_live_streaming_enabled": false,
        "is_new_live_streaming_enabled": false,
        "lang": 1,
        "no_wall_replies": 0,
        "own_posts_default": 0,
        "music_available": true
    }
}
```
