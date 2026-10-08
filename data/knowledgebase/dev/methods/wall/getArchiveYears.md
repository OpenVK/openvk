OpenVK-KB-Heading: wall.getArchiveYears

# wall.getArchiveYears

Returns a list of years for which archived wall posts exist for a user or community.

### Authorization
Requires user authorization token. Archival data is accessible only to the wall owner or community administrator.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Identifier of the wall owner (positive for user, negative for group). |

### Result

Returns an array of integers (`array of integer`), representing calendar years (e.g., `[2024, 2023, 2022]`) for which archived posts exist.

### Possible Errors

| Code | Description |
| --- | --- |
| `15` | `Access denied` — owner not found or current user lacks permission to access the archive. |

### Example Request
```http
POST /method/wall.getArchiveYears HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": [
        2024,
        2023,
        2022
    ]
}
```
