OpenVK-KB-Heading: users.report

# users.report

Submits a complaint/report to platform moderators about a user profile.

### Authorization
Requires user authorization token.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_id` | integer | **Required.** Identifier of the user being reported. |
| `type` | string | Reason category: `"spam"` (spam), `"insult"` (offensive behavior), `"advertisement"` (unauthorized advertising). Default: `"spam"`. |
| `comment` | string | Additional comment or detailed description of the violation. |

### Result

Returns `1` on success.

### Possible Errors

| Code | Description |
| --- | --- |
| `12` | `Can't report yourself.` — cannot file a report against your own profile. |

### Example Request
```http
POST /method/users.report HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=2&type=spam&comment=Unwanted+messages&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
