# API Documentation

This documentation provides details on how to use the API.

## Authentication

### Signup

- **URL:** `/api/signup`
- **Method:** `POST`
- **Auth required:** No

**Parameters**

| Name | Type | Description |
|---|---|---|
| `name` | string | **Required.** The name of the user. |
| `email` | string | **Required.** The email of the user. Must be unique. |
| `password` | string | **Required.** The password of the user. Minimum 6 characters. |
| `organisation_id` | integer | *Optional.* The ID of the organisation to join. |

**Success Response**

- **Code:** `201 CREATED`
- **Content:**
```json
{
    "user": { ... },
    "token": "..."
}
```

### Login

- **URL:** `/api/login`
- **Method:** `POST`
- **Auth required:** No

**Parameters**

| Name | Type | Description |
|---|---|---|
| `email` | string | **Required.** The email of the user. |
| `password` | string | **Required.** The password of the user. |

**Success Response**

- **Code:** `200 OK`
- **Content:**
```json
{
    "user": { ... },
    "token": "...",
    "first_time_login": true
}
```

### Logout

- **URL:** `/api/logout`
- **Method:** `POST`
- **Auth required:** Yes

**Success Response**

- **Code:** `204 NO CONTENT`

### Get Authenticated User

- **URL:** `/api/auth/me`
- **Method:** `GET`
- **Auth required:** Yes

**Success Response**

- **Code:** `200 OK`
- **Content:**
```json
{
    "user": { ... }
}
```

---

## Users

### List Users

- **URL:** `/api/users`
- **Method:** `GET`
- **Auth required:** Yes

### Get User

- **URL:** `/api/users/{id}`
- **Method:** `GET`
- **Auth required:** Yes

### Create User

- **URL:** `/api/users`
- **Method:** `POST`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `name` | string | **Required.** |
| `email` | string | **Required.** Must be unique. |
| `password` | string | **Required.** Minimum 6 characters. |
| `organisation_id` | integer | *Optional.* |

### Update User

- **URL:** `/api/users/{id}`
- **Method:** `PUT`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `name` | string | *Optional.* |
| `email` | string | *Optional.* Must be unique. |
| `password` | string | *Optional.* Minimum 6 characters. |
| `organisation_id` | integer | *Optional.* |

### Delete User

- **URL:** `/api/users/{id}`
- **Method:** `DELETE`
- **Auth required:** Yes

### Get Users by Organisation

- **URL:** `/api/organisations/{id}/users`
- **Method:** `GET`
- **Auth required:** Yes

### Get Authenticated User's Organisation Users

- **URL:** `/api/organisation/users`
- **Method:** `GET`
- **Auth required:** Yes

### Add User to Organisation by Email

- **URL:** `/api/organisations/{id}/add-user-by-email`
- **Method:** `POST`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `email` | string | **Required.** |

---

## Organisations

### List Organisations

- **URL:** `/api/organisations`
- **Method:** `GET`
- **Auth required:** Yes

### Get Organisation

- **URL:** `/api/organisations/{id}`
- **Method:** `GET`
- **Auth required:** Yes

### Create Organisation

- **URL:** `/api/organisations`
- **Method:** `POST`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `name` | string | **Required.** |
| `address` | string | *Optional.* |
| `phone` | string | *Optional.* |
| `email` | string | *Optional.* |

### Update Organisation

- **URL:** `/api/organisations/{id}`
- **Method:** `PUT`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `name` | string | *Optional.* |
| `address` | string | *Optional.* |
| `phone` | string | *Optional.* |
| `email` | string | *Optional.* |

### Delete Organisation

- **URL:** `/api/organisations/{id}`
- **Method:** `DELETE`
- **Auth required:** Yes

---

## Invitations

### Send Invitation

- **URL:** `/api/organisations/{id}/invite`
- **Method:** `POST`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `invitee_id` | integer | **Required.** The ID of the user to invite. |

### Accept Invitation

- **URL:** `/api/invitations/accept`
- **Method:** `POST`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `token` | string | **Required.** The invitation token. |

---

## Tasks

### List Tasks

- **URL:** `/api/tasks`
- **Method:** `GET`
- **Auth required:** Yes

### Get Task

- **URL:** `/api/tasks/{id}`
- **Method:** `GET`
- **Auth required:** Yes

### Create Task

- **URL:** `/api/tasks`
- **Method:** `POST`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `organisation_id` | integer | **Required.** |
| `assignee_id` | integer | **Required.** |
| `title` | string | **Required.** |
| `description` | string | *Optional.* |
| `type` | string | *Optional.* |
| `priority` | string | *Optional.* |
| `status` | string | *Optional.* |
| `due_date` | date | *Optional.* |
| `related_to` | email | *Optional.* |

### Update Task

- **URL:** `/api/tasks/{id}`
- **Method:** `PUT`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `title` | string | *Optional.* |
| `description` | string | *Optional.* |
| `type` | string | *Optional.* |
| `priority` | string | *Optional.* |
| `status` | string | *Optional.* |
| `due_date` | date | *Optional.* |
| `related_to` | email | *Optional.* |
| `assignee_id` | integer | *Optional.* |

### Delete Task

- **URL:** `/api/tasks/{id}`
- **Method:** `DELETE`
- **Auth required:** Yes

### Get Tasks by Organisation

- **URL:** `/api/organisations/{id}/tasks`
- **Method:** `GET`
- **Auth required:** Yes

### Get Tasks by Assignee

- **URL:** `/api/users/{id}/tasks`
- **Method:** `GET`
- **Auth required:** Yes

---

## Opportunities

### List Opportunities

- **URL:** `/api/opportunities`
- **Method:** `GET`
- **Auth required:** Yes

### Get Opportunity

- **URL:** `/api/opportunities/{id}`
- **Method:** `GET`
- **Auth required:** Yes

### Create Opportunity

- **URL:** `/api/opportunities`
- **Method:** `POST`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `title` | string | **Required.** |
| `company` | string | **Required.** |
| `value` | numeric | **Required.** |
| `stage` | string | **Required.** |
| `probability` | integer | **Required.** Min 0, max 100. |
| `close_date` | date | **Required.** |
| `contact` | string | **Required.** |
| `description` | string | *Optional.* |
| `organisation_id` | integer | **Required.** |
| `created_by` | integer | **Required.** |

### Update Opportunity

- **URL:** `/api/opportunities/{id}`
- **Method:** `PUT`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `title` | string | *Optional.* |
| `company` | string | *Optional.* |
| `value` | numeric | *Optional.* |
| `stage` | string | *Optional.* |
| `probability` | integer | *Optional.* Between 0 and 100. |
| `close_date` | date | *Optional.* |
| `contact` | string | *Optional.* |
| `description` | string | *Optional.* |

### Delete Opportunity

- **URL:** `/api/opportunities/{id}`
- **Method:** `DELETE`
- **Auth required:** Yes

### Get Opportunities by Organisation

- **URL:** `/api/organisations/{id}/opportunities`
- **Method:** `GET`
- **Auth required:** Yes

### Get Opportunities by Creator

- **URL:** `/api/users/{id}/opportunities`
- **Method:** `GET`
- **Auth required:** Yes

---

## Contacts

### List Contacts

- **URL:** `/api/contacts`
- **Method:** `GET`
- **Auth required:** Yes

### Get Contact

- **URL:** `/api/contacts/{id}`
- **Method:** `GET`
- **Auth required:** Yes

### Create Contact

- **URL:** `/api/contacts`
- **Method:** `POST`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `organisation_id` | integer | **Required.** |
| `name` | string | **Required.** |
| `email` | string | *Optional.* |
| `phone` | string | *Optional.* |
| `company` | string | *Optional.* |
| `position` | string | *Optional.* |
| `location` | string | *Optional.* |
| `status` | string | *Optional.* |

### Update Contact

- **URL:** `/api/contacts/{id}`
- **Method:** `PUT`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `name` | string | *Optional.* |
| `email` | string | *Optional.* |
| `phone` | string | *Optional.* |
| `company` | string | *Optional.* |
| `position` | string | *Optional.* |
| `location` | string | *Optional.* |
| `status` | string | *Optional.* |

### Delete Contact

- **URL:** `/api/contacts/{id}`
- **Method:** `DELETE`
- **Auth required:** Yes

### Get Contacts by Organisation

- **URL:** `/api/organisations/{id}/contacts`
- **Method:** `GET`
- **Auth required:** Yes

---

## Appointments

### List Appointments

- **URL:** `/api/appointments`
- **Method:** `GET`
- **Auth required:** Yes

### Get Appointment

- **URL:** `/api/appointments/{id}`
- **Method:** `GET`
- **Auth required:** Yes

### Create Appointment

- **URL:** `/api/appointments`
- **Method:** `POST`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `title` | string | **Required.** |
| `description` | string | *Optional.* |
| `date` | date | **Required.** |
| `time` | string | **Required.** Format: H:i |
| `duration` | string | **Required.** |
| `type` | string | *Optional.* |
| `status` | string | *Optional.* |
| `location` | string | *Optional.* |
| `attendees` | array | *Optional.* |
| `related_to` | string | *Optional.* |
| `user_id` | integer | **Required.** |

### Update Appointment

- **URL:** `/api/appointments/{id}`
- **Method:** `PUT`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `title` | string | *Optional.* |
| `description` | string | *Optional.* |
| `date` | date | *Optional.* |
| `time` | string | *Optional.* Format: H:i |
| `duration` | string | *Optional.* |
| `type` | string | *Optional.* |
| `status` | string | *Optional.* |
| `location` | string | *Optional.* |
| `attendees` | array | *Optional.* |
| `related_to` | string | *Optional.* |
| `user_id` | integer | *Optional.* |

### Delete Appointment

- **URL:** `/api/appointments/{id}`
- **Method:** `DELETE`
- **Auth required:** Yes

### Get Appointments by User

- **URL:** `/api/users/{id}/appointments`
- **Method:** `GET`
- **Auth required:** Yes

---

## Dashboard

### Get Dashboard Stats

- **URL:** `/api/dashboard/stats`
- **Method:** `GET`
- **Auth required:** Yes

---

## Notifications

### List Notifications

- **URL:** `/api/notifications`
- **Method:** `GET`
- **Auth required:** Yes

### Get Unread Notification Count

- **URL:** `/api/notifications/unread-count`
- **Method:** `GET`
- **Auth required:** Yes

### Mark Notification as Read

- **URL:** `/api/notifications/{id}/read`
- **Method:** `POST`
- **Auth required:** Yes

### Mark All Notifications as Read

- **URL:** `/api/notifications/mark-all-read`
- **Method:** `POST`
- **Auth required:** Yes

---

## Leads

### List Leads

- **URL:** `/api/leads`
- **Method:** `GET`
- **Auth required:** Yes

### Get Lead

- **URL:** `/api/leads/{id}`
- **Method:** `GET`
- **Auth required:** Yes

### Create Lead

- **URL:** `/api/leads`
- **Method:** `POST`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `full_name` | string | **Required.** |
| `email` | string | *Optional.* |
| `position` | string | *Optional.* |
| `company` | string | *Optional.* |
| `location` | string | *Optional.* |
| `profile_url` | url | *Optional.* |
| `followers` | integer | *Optional.* |
| `connections` | integer | *Optional.* |
| `education` | string | *Optional.* |
| `personal_message` | string | *Optional.* |
| `message_length` | integer | *Optional.* |
| `generated_at` | date | *Optional.* |
| `total_leads` | integer | *Optional.* |

### Update Lead

- **URL:** `/api/leads/{id}`
- **Method:** `PUT`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `full_name` | string | *Optional.* |
| `email` | string | *Optional.* |
| `position` | string | *Optional.* |
| `company` | string | *Optional.* |
| `location` | string | *Optional.* |
| `profile_url` | url | *Optional.* |
| `followers` | integer | *Optional.* |
| `connections` | integer | *Optional.* |
| `education` | string | *Optional.* |
| `personal_message` | string | *Optional.* |
| `message_length` | integer | *Optional.* |
| `generated_at` | date | *Optional.* |
| `total_leads` | integer | *Optional.* |

### Delete Lead

- **URL:** `/api/leads/{id}`
- **Method:** `DELETE`
- **Auth required:** Yes

### Get Leads by Organisation

- **URL:** `/api/organisations/{id}/leads`
- **Method:** `GET`
- **Auth required:** Yes

### Filter Leads

- **URL:** `/api/leads/filter/search`
- **Method:** `GET`
- **Auth required:** Yes

**Query Parameters**

| Name | Type | Description |
|---|---|---|
| `full_name` | string | *Optional.* |
| `email` | string | *Optional.* |
| `location` | string | *Optional.* |
| `company` | string | *Optional.* |
| `position` | string | *Optional.* |
| `from_date` | date | *Optional.* |
| `to_date` | date | *Optional.* |
| `min_followers` | integer | *Optional.* |
| `min_connections` | integer | *Optional.* |

---

## Email Campaigns

### Create Email Campaign

- **URL:** `/api/email-campaigns`
- **Method:** `POST`
- **Auth required:** Yes

**Parameters**

| Name | Type | Description |
|---|---|---|
| `name` | string | **Required.** |
| `subject` | string | **Required.** |
| `audience` | array | **Required.** Array of user IDs. |
| `content` | string | **Required.** |
| `schedule` | string | **Required.** `now` or `later`. |
| `schedule_time` | datetime | **Required if `schedule` is `later`.** |
| `sender` | integer | **Required.** The ID of the sending user. |

---

## Email Provider

### Get Email Provider

- **URL:** `/api/user/email-provider`
- **Method:** `GET`
- **Auth required:** Yes

### Delete Email Provider

- **URL:** `/api/user/email-provider`
- **Method:** `DELETE`
- **Auth required:** Yes

### Redirect to Provider

- **URL:** `/email-provider/{provider}/redirect`
- **Method:** `GET`
- **Auth required:** No

### Provider Callback

- **URL:** `/email-provider/{provider}/callback`
- **Method:** `GET`
- **Auth required:** No
