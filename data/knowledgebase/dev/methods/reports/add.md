OpenVK-KB-Heading: reports.add

# reports.add

Submits a report against content or a user to platform moderators.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Target entity ID or user ID being reported. |
| `type` | string | **Required.** Object type: `post`, `photo`, `video`, `group`, `comment`, `note`, `app`, `user`, `audio`. |
| `comment` | string | **Required.** Complaint text/reason description (cannot be empty). |
| `reason` | integer | Numerical complaint reason code. Default: `0`. |
| `report_source` | string | Report source (compatibility parameter). |

### Result

Returns:
* `1` — report successfully submitted (or self-report / duplicate silently ignored);
* `0` — submission rejected (current user is banned from support).

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `100` | `One of the parameters specified was missing or invalid: type should be ...` — Invalid or unsupported `type`. |
| `100` | `One of the parameters specified was missing or invalid: Bad input` — Invalid `owner_id` (`<= 0`). |
| `100` | `One of the parameters specified was missing or invalid: Comment can't be empty` — Empty comment string. |

### Example Request
```http
POST /method/reports.add HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=5&type=post&comment=Spam%20and%20advertising&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
