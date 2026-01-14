# API Documentation

This documentation provides details on the API endpoints for the application.

## Authentication

### Signup

Creates a new user account.

- **URL:** `/api/signup`
- **Method:** `POST`
- **Auth required:** No

#### Parameters

| Name              | Type    | Description                               |
| ----------------- | ------- | ----------------------------------------- |
| `name`            | string  | The name of the user.                     |
| `email`           | string  | The email address of the user.            |
| `password`        | string  | The password for the account.             |
| `organisation_id` | integer | (Optional) The ID of the organisation.    |

#### Success Response

- **Code:** `201 CREATED`
- **Content:** The created user object and an API token.

```json
{
    "user": {
        "name": "Test User",
        "email": "test@example.com",
        "organisation_id": 1,
        "updated_at": "2023-10-27T10:00:00.000000Z",
        "created_at": "2023-10-27T10:00:00.000000Z",
        "id": 1
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

### Login

Logs in a user and returns an API token.

- **URL:** `/api/login`
- **Method:** `POST`
- **Auth required:** No

#### Parameters

| Name       | Type   | Description                   |
| ---------- | ------ | ----------------------------- |
| `email`    | string | The email address of the user.|
| `password` | string | The password for the account. |

#### Success Response

- **Code:** `200 OK`
- **Content:** The user object and an API token.

```json
{
    "user": {
        "id": 1,
        "name": "Test User",
        "email": "test@example.com",
        "organisation_id": 1,
        "first_time_login": true
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "first_time_login": true
}
```

### Logout

Logs out the authenticated user.

- **URL:** `/api/logout`
- **Method:** `POST`
- **Auth required:** Yes

#### Success Response

- **Code:** `204 NO CONTENT`

### Get Authenticated User

Retrieves the authenticated user's details.

- **URL:** `/api/auth/me`
- **Method:** `GET`
- **Auth required:** Yes

#### Success Response

- **Code:** `200 OK`
- **Content:** The authenticated user object.

```json
{
    "user": {
        "id": 1,
        "name": "Test User",
        "email": "test@example.com",
        "organisation_id": 1,
        "first_time_login": false,
        "created_at": "2023-10-27T10:00:00.000000Z",
        "updated_at": "2023-10-27T10:00:00.000000Z"
    }
}
```

---

## Users

### List Users

Retrieves a list of all users.

- **URL:** `/api/users`
- **Method:** `GET`
- **Auth required:** Yes

#### Success Response

- **Code:** `200 OK`
- **Content:** An array of user objects.

### Get User

Retrieves a single user by their ID.

- **URL:** `/api/users/{id}`
- **Method:** `GET`
- **Auth required:** Yes

#### Success Response

- **Code:** `200 OK`
- **Content:** The user object.

### Create User

Creates a new user.

- **URL:** `/api/users`
- **Method:** `POST`
- **Auth required:** Yes

#### Parameters

| Name              | Type    | Description                               |
| ----------------- | ------- | ----------------------------------------- |
| `name`            | string  | The name of the user.                     |
| `email`           | string  | The email address of the user.            |
| `password`        | string  | The password for the account.             |
| `organisation_id` | integer | (Optional) The ID of the organisation.    |

#### Success Response

- **Code:** `201 CREATED`
- **Content:** The created user object.

### Update User

Updates a user's details.

- **URL:** `/api/users/{id}`
- **Method:** `PUT`
- **Auth required:** Yes

#### Parameters

| Name              | Type    | Description                               |
| ----------------- | ------- | ----------------------------------------- |
| `name`            | string  | (Optional) The name of the user.          |
| `email`           | string  | (Optional) The email address of the user. |
| `password`        | string  | (Optional) The password for the account.  |
| `organisation_id` | integer | (Optional) The ID of the organisation.    |

#### Success Response

- **Code:** `200 OK`
- **Content:** The updated user object.

### Delete User

Deletes a user.

- **URL:** `/api/users/{id}`
- **Method:** `DELETE`
- **Auth required:** Yes

#### Success Response

- **Code:** `204 NO CONTENT`

---

## Organisations

### List Organisations

Retrieves a list of all organisations.

- **URL:** `/api/organisations`
- **Method:** `GET`
- **Auth required:** Yes

#### Success Response

- **Code:** `200 OK`
- **Content:** An array of organisation objects.

### Get Organisation

Retrieves a single organisation by its ID.

- **URL:** `/api/organisations/{id}`
- **Method:** `GET`
- **Auth required:** Yes

#### Success Response

- **Code:** `200 OK`
- **Content:** The organisation object.

### Create Organisation

Creates a new organisation.

- **URL:** `/api/organisations`
- **Method:** `POST`
- **Auth required:** Yes

#### Parameters

| Name      | Type   | Description                             |
| --------- | ------ | --------------------------------------- |
| `name`    | string | The name of the organisation.           |
| `address` | string | (Optional) The address of the organisation. |
| `phone`   | string | (Optional) The phone number of the organisation. |
| `email`   | string | (Optional) The email address of the organisation. |

#### Success Response

- **Code:** `201 CREATED`
- **Content:** The created organisation object.

### Update Organisation

Updates an organisation's details.

- **URL:** `/api/organisations/{id}`
- **Method:** `PUT`
- **Auth required:** Yes

#### Parameters

| Name      | Type   | Description                             |
| --------- | ------ | --------------------------------------- |
| `name`    | string | (Optional) The name of the organisation.           |
| `address` | string | (Optional) The address of the organisation. |
| `phone`   | string | (Optional) The phone number of the organisation. |
| `email`   | string | (Optional) The email address of the organisation. |

#### Success Response

- **Code:** `200 OK`
- **Content:** The updated organisation object.

### Delete Organisation

Deletes an organisation.

- **URL:** `/api/organisations/{id}`
- **Method:** `DELETE`
- **Auth required:** Yes

#### Success Response

- **Code:** `204 NO CONTENT`

---

## Google Account Integration

To send emails from a user's own Google account, you must first connect their account using the OAuth 2.0 flow.

### Required Configuration

Before you begin, ensure you have correctly configured the following in your `.env` file:

- `GOOGLE_CLIENT_ID`: Your Google application client ID.
- `GOOGLE_CLIENT_SECRET`: Your Google application client secret.
- `GOOGLE_REDIRECT_URI`: The callback URL. This **must** be set to `http://<your-app-url>/email-provider/google/callback`.
- `FRONTEND_URL`: The URL of your frontend application where users will be redirected after connecting their account (e.g., `http://localhost:3000/settings`).

### How to Connect a Google Account

The connection is established using a browser-based redirect flow, not a direct API call.

1.  **Check Connection Status:**
    First, call the `GET /api/user/email-provider` endpoint to see if an account is already connected.

2.  **Initiate Authorization Flow:**
    If no account is connected, provide a link or button in your frontend that directs the user's browser to the following URL:
    `http://<your-app-url>/email-provider/google/redirect`

3.  **User Consent and Redirect:**
    The user will be taken to Google's consent screen. After they grant permission, they will be redirected back to your application at your configured `GOOGLE_REDIRECT_URI`. The backend handles the token exchange and storage automatically.

4.  **Redirect to Frontend:**
    After the connection is successful, the user will be redirected to the `FRONTEND_URL` you have configured.

5.  **Verify Connection:**
    You can now call `GET /api/user/email-provider` again to confirm the connection and update your UI.

---

## Manage Email Provider Connection

### Check Connection Status

Retrieves the connected email provider for the authenticated user.

- **URL:** `/api/user/email-provider`
- **Method:** `GET`
- **Auth required:** Yes

#### Success Response

- **Code:** `200 OK`
- **Content:** The email provider object.

```json
{
    "id": 1,
    "user_id": 123,
    "provider": "google",
    "created_at": "2023-10-27T10:00:00.000000Z",
    "updated_at": "2023-10-27T10:00:00.000000Z"
}
```

#### Error Response (Not Connected)

- **Code:** `404 NOT FOUND`

```json
{
    "message": "No email provider connected"
}
```

### Disconnect Email Provider

Disconnects the authenticated user's email provider.

- **URL:** `/api/user/email-provider`
- **Method:** `DELETE`
- **Auth required:** Yes

#### Success Response

- **Code:** `200 OK`

```json
{
    "message": "Email provider disconnected successfully"
}
```

---

## Appointments

### List Appointments

Retrieves a list of all appointments.

- **URL:** `/api/appointments`
- **Method:** `GET`
- **Auth required:** Yes

### Create Appointment

Creates a new appointment.

- **URL:** `/api/appointments`
- **Method:** `POST`
- **Auth required:** Yes

#### Parameters
| Name | Type | Description |
| --- | --- | --- |
| `title` | string | The title of the appointment. |
| `description` | string | (Optional) The description of the appointment. |
| `date` | date | The date of the appointment. |
| `time` | time | The time of the appointment (HH:MM). |
| `duration` | string | The duration of the appointment. |
| `user_id` | integer | The ID of the user associated with the appointment. |

### Get Appointment

Retrieves a single appointment by its ID.

- **URL:** `/api/appointments/{id}`
- **Method:** `GET`
- **Auth required:** Yes

### Update Appointment

Updates an appointment's details.

- **URL:** `/api/appointments/{id}`
- **Method:** `PUT`
- **Auth required:** Yes

#### Parameters
| Name | Type | Description |
| --- | --- | --- |
| `title` | string | (Optional) The title of the appointment. |
| `description` | string | (Optional) The description of the appointment. |
| `date` | date | (Optional) The date of the appointment. |
| `time` | time | (Optional) The time of the appointment (HH:MM). |
| `duration` | string | (Optional) The duration of the appointment. |
| `user_id` | integer | (Optional) The ID of the user associated with the appointment. |

### Delete Appointment

Deletes an appointment.

- **URL:** `/api/appointments/{id}`
- **Method:** `DELETE`
- **Auth required:** Yes

---

## Contacts

### List Contacts

Retrieves a list of all contacts.

- **URL:** `/api/contacts`
- **Method:** `GET`
- **Auth required:** Yes

### Get Contacts by Organisation

Retrieves a list of contacts for a specific organisation.

- **URL:** `/api/organisations/{organisation_id}/contacts`
- **Method:** `GET`
- **Auth required:** Yes

### Create Contact

Creates a new contact.

- **URL:** `/api/contacts`
- **Method:** `POST`
- **Auth required:** Yes

#### Parameters
| Name | Type | Description |
| --- | --- | --- |
| `organisation_id` | integer | The ID of the organisation. |
| `name` | string | The name of the contact. |
| `email` | string | (Optional) The email of the contact. |
| `phone` | string | (Optional) The phone number of the contact. |

### Get Contact

Retrieves a single contact by its ID.

- **URL:** `/api/contacts/{id}`
- **Method:** `GET`
- **Auth required:** Yes

### Update Contact

Updates a contact's details.

- **URL:** `/api/contacts/{id}`
- **Method:** `PUT`
- **Auth required:** Yes

#### Parameters
| Name | Type | Description |
| --- | --- | --- |
| `name` | string | (Optional) The name of the contact. |
| `email` | string | (Optional) The email of the contact. |
| `phone` | string | (Optional) The phone number of the contact. |

### Delete Contact

Deletes a contact.

- **URL:** `/api/contacts/{id}`
- **Method:** `DELETE`
- **Auth required:** Yes

---

## Dashboard

### Get Dashboard Stats

Retrieves statistics for the dashboard.

- **URL:** `/api/dashboard/stats`
- **Method:** `GET`
- **Auth required:** Yes

---

## Email Campaigns

### Get Campaigns

Retrieves a list of email campaigns.

- **URL:** `/api/campaigns`
- **Method:** `GET`
- **Auth required:** Yes

### Get Campaign Details

Retrieves the details of a specific email campaign.

- **URL:** `/api/campaigns/{id}`
- **Method:** `GET`
- **Auth required:** Yes

### Create Email Campaign

Creates a new email campaign.

- **URL:** `/api/email-campaigns`
- **Method:** `POST`
- **Auth required:** Yes

#### Parameters
| Name | Type | Description |
| --- | --- | --- |
| `name` | string | The name of the campaign. |
| `subject` | string | The subject of the email. |
| `audience` | array | An array of user IDs to send the campaign to. |
| `content` | string | The HTML content of the email. Supports personalization with `{{first_name}}` and `{{company}}`. |
| `schedule` | string | `now` or `later`. |
| `schedule_time` | datetime | The scheduled time for the campaign (if `schedule` is `later`). |
| `sender` | integer | **(Breaking Change)** The ID of the user who is sending the campaign. This user must have a connected email provider. |

#### Example Request (Send Now)

```json
{
    "name": "Welcome Campaign",
    "subject": "Welcome to our platform!",
     "sender": 123,
    "audience": ["1", "2", "3"],
    "content": "<h1>Hi {{first_name}}!</h1><p>Welcome to {{company}}.</p>",
    "schedule": "now"
}
```

#### Example Request (Schedule for Later)

```json
{
    "name": "Scheduled Campaign",
    "subject": "This is a scheduled email",
    "sender": 123,
    "audience": ["1", "2", "3"],
    "content": "<h1>Hi {{first_name}}!</h1><p>This email was scheduled for later.</p>",
    "schedule": "later",
    "schedule_time": "2025-12-25T10:00:00"
}
```

### Update Campaign

Updates an existing email campaign.

- **URL:** `/api/campaigns/{id}`
- **Method:** `PUT`
- **Auth required:** Yes

#### Parameters
| Name | Type | Description |
| --- | --- | --- |
| `name` | string | (Optional) The name of the campaign. |
| `subject` | string | (Optional) The subject of the email. |
| `audience` | array | (Optional) An array of user IDs to send the campaign to. |
| `content` | string | (Optional) The HTML content of the email. |
| `schedule_time` | datetime | (Optional) The scheduled time for the campaign. |

### Cancel Campaign

Cancels an email campaign.

- **URL:** `/api/campaigns/{id}/cancel`
- **Method:** `POST`
- **Auth required:** Yes
