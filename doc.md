# Email Campaign API Documentation

This documentation provides details on how to use the Email Campaign API to create and manage email campaigns.

## Create Email Campaign

Creates a new email campaign.

- **URL:** `/api/email-campaigns`
- **Method:** `POST`
- **Auth required:** Yes

### Parameters

| Name            | Type    | Description                                                                                                                              |
| --------------- | ------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| `name`          | string  | The name of the campaign.                                                                                                                |
| `subject`       | string  | The subject of the email.                                                                                                                |
| `sender`        | string  | The email address of the sender.                                                                                                         |
| `audience`      | array   | An array of user IDs to send the campaign to.                                                                                            |
| `content`       | string  | The HTML content of the email. Supports personalization with `{{first_name}}` and `{{company}}`.                                           |
| `schedule`      | string  | Determines when the campaign should be sent. Can be `now` or `later`.                                                                    |
| `schedule_time` | string  | The scheduled time for the campaign to be sent. Required if `schedule` is `later`. Should be in `YYYY-MM-DDTHH:MM` format and in the future. |

### Example Request (Send Now)

```json
{
    "name": "Welcome Campaign",
    "subject": "Welcome to our platform!",
    "sender": "no-reply@example.com",
    "audience": ["1", "2", "3"],
    "content": "<h1>Hi {{first_name}}!</h1><p>Welcome to {{company}}.</p>",
    "schedule": "now"
}
```

### Example Request (Schedule for Later)

```json
{
    "name": "Scheduled Campaign",
    "subject": "This is a scheduled email",
    "sender": "no-reply@example.com",
    "audience": ["1", "2", "3"],
    "content": "<h1>Hi {{first_name}}!</h1><p>This email was scheduled for later.</p>",
    "schedule": "later",
    "schedule_time": "2025-12-25T10:00:00"
}
```

### Success Response

- **Code:** `201 CREATED`
- **Content:** The created email campaign object.

```json
{
    "id": 1,
    "name": "Welcome Campaign",
    "subject": "Welcome to our platform!",
    "sender": "no-reply@example.com",
    "audience": ["1", "2", "3"],
    "content": "<h1>Hi {{first_name}}!</h1><p>Welcome to {{company}}.</p>",
    "schedule": "now",
    "schedule_time": null,
    "created_at": "2023-10-27T10:00:00.000000Z",
    "updated_at": "2023-10-27T10:00:00.000000Z"
}
```
