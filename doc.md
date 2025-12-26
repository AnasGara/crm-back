# API Documentation

This document provides detailed information about the API endpoints.

## Authentication

### POST /signup

Creates a new user account.

**Request Body:**

*   `name` (string, required): The user's name.
*   `email` (string, required): The user's email address.
*   `password` (string, required): The user's password (min. 6 characters).
*   `organisation_id` (integer, optional): The ID of the organisation to associate the user with.

**Success Response (201):**

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
  "token": "some-api-token"
}
```

**Error Response (422):**

```json
{
  "errors": {
    "email": [
      "The email has already been taken."
    ]
  }
}
```

## Email Campaigns

### POST /email-campaigns

Creates and schedules an email campaign.

**Authentication:** Requires a valid Sanctum API token.

**Request Body:**

*   `name` (string, required): The name of the campaign.
*   `subject` (string, required): The email subject.
*   `audience` (array, required): An array of user IDs to send the campaign to.
*   `content` (string, required): The HTML content of the email.
*   `schedule` (string, required): When to send the campaign ('now' or 'later').
*   `schedule_time` (datetime, required_if:schedule,later): The scheduled time for the campaign (e.g., '2023-12-31 23:59:59').
*   `sender` (integer, required): The ID of the user sending the campaign.

**Success Response (201):** The newly created email campaign object.

## Notifications

### GET /notifications

Retrieves a paginated list of notifications for the authenticated user.

**Authentication:** Requires a valid Sanctum API token.

**Query Parameters:**

*   `per_page` (integer, optional): The number of notifications to return per page (default: 20).

**Success Response (200):** A paginated JSON response of notification objects.

### GET /notifications/unread-count

Gets the number of unread notifications for the authenticated user.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):**

```json
{
  "unread": 5
}
```

### POST /notifications/{id}/read

Marks a specific notification as read.

**URL Parameters:**

*   `id` (integer, required): The ID of the notification.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** The updated notification object.

### POST /notifications/mark-all-read

Marks all unread notifications as read.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):**

```json
{
  "success": true
}
```

## Appointments

### GET /appointments

Retrieves a list of all appointments.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** A JSON array of appointment objects.

### POST /appointments

Creates a new appointment.

**Authentication:** Requires a valid Sanctum API token.

**Request Body:**

*   `title` (string, required): The appointment title.
*   `description` (string, optional): The appointment description.
*   `date` (date, required): The appointment date.
*   `time` (time, required): The appointment time (HH:mm).
*   `duration` (string, required): The appointment duration.
*   `type` (string, optional): The appointment type.
*   `status` (string, optional): The appointment status.
*   `location` (string, optional): The appointment location.
*   `attendees` (array, optional): An array of attendee names.
*   `related_to` (string, optional): A description of what the appointment is related to.
*   `user_id` (integer, required): The ID of the user the appointment belongs to.

**Success Response (201):** The newly created appointment object.

### GET /appointments/{appointment}

Retrieves a specific appointment by its ID.

**URL Parameters:**

*   `appointment` (integer, required): The ID of the appointment.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** The appointment object.

### PUT /appointments/{appointment}

Updates an appointment.

**URL Parameters:**

*   `appointment` (integer, required): The ID of the appointment to update.

**Request Body:**

*   `title` (string, optional): The appointment title.
*   `description` (string, optional): The appointment description.
*   `date` (date, optional): The appointment date.
*   `time` (time, optional): The appointment time (HH:mm).
*   `duration` (string, optional): The appointment duration.
*   `type` (string, optional): The appointment type.
*   `status` (string, optional): The appointment status.
*   `location` (string, optional): The appointment location.
*   `attendees` (array, optional): An array of attendee names.
*   `related_to` (string, optional): A description of what the appointment is related to.
*   `user_id` (integer, optional): The ID of the user the appointment belongs to.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** The updated appointment object.

### DELETE /appointments/{appointment}

Deletes an appointment.

**URL Parameters:**

*   `appointment` (integer, required): The ID of the appointment to delete.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):**

```json
{
  "message": "Appointment deleted successfully."
}
```

### GET /users/{user}/appointments

Retrieves all appointments for a specific user.

**URL Parameters:**

*   `user` (integer, required): The ID of the user.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** A JSON array of appointment objects.

## Contacts

### GET /contacts

Retrieves a list of all contacts.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** A JSON array of contact objects.

### POST /contacts

Creates a new contact.

**Authentication:** Requires a valid Sanctum API token.

**Request Body:**

*   `organisation_id` (integer, required): The ID of the organisation.
*   `name` (string, required): The contact's name.
*   `email` (string, optional): The contact's email address.
*   `phone` (string, optional): The contact's phone number.
*   `company` (string, optional): The contact's company.
*   `position` (string, optional): The contact's position.
*   `location` (string, optional): The contact's location.
*   `status` (string, optional): The contact's status (e.g., 'lead', 'customer').

**Success Response (201):** The newly created contact object.

### GET /contacts/{contact}

Retrieves a specific contact by its ID.

**URL Parameters:**

*   `contact` (integer, required): The ID of the contact.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** The contact object.

### PUT /contacts/{contact}

Updates a contact's details.

**URL Parameters:**

*   `contact` (integer, required): The ID of the contact to update.

**Request Body:**

*   `name` (string, optional): The contact's name.
*   `email` (string, optional): The contact's email address.
*   `phone` (string, optional): The contact's phone number.
*   `company` (string, optional): The contact's company.
*   `position` (string, optional): The contact's position.
*   `location` (string, optional): The contact's location.
*   `status` (string, optional): The contact's status (e.g., 'lead', 'customer').

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** The updated contact object.

### DELETE /contacts/{contact}

Deletes a contact.

**URL Parameters:**

*   `contact` (integer, required): The ID of the contact to delete.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (204):** No content.

### GET /organisations/{organisation}/contacts

Retrieves all contacts for a specific organisation.

**URL Parameters:**

*   `organisation` (integer, required): The ID of the organisation.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** A JSON array of contact objects.

## Opportunities

### GET /opportunities

Retrieves a list of all opportunities.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** A JSON array of opportunity objects.

### POST /opportunities

Creates a new opportunity.

**Authentication:** Requires a valid Sanctum API token.

**Request Body:**

*   `title` (string, required): The opportunity title.
*   `company` (string, required): The company associated with the opportunity.
*   `value` (numeric, required): The value of the opportunity.
*   `stage` (string, required): The current stage of the opportunity (e.g., 'prospecting', 'closed').
*   `probability` (integer, required): The probability of closing the opportunity (0-100).
*   `close_date` (date, required): The expected close date.
*   `contact` (string, required): The primary contact for the opportunity.
*   `description` (string, optional): A description of the opportunity.
*   `organisation_id` (integer, required): The ID of the organisation.
*   `created_by` (integer, required): The ID of the user who created the opportunity.

**Success Response (201):** The newly created opportunity object.

### GET /opportunities/{opportunity}

Retrieves a specific opportunity by its ID.

**URL Parameters:**

*   `opportunity` (integer, required): The ID of the opportunity.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** The opportunity object.

### PUT /opportunities/{opportunity}

Updates an opportunity.

**URL Parameters:**

*   `opportunity` (integer, required): The ID of the opportunity to update.

**Request Body:**

*   `title` (string, optional): The opportunity title.
*   `company` (string, optional): The company associated with the opportunity.
*   `value` (numeric, optional): The value of the opportunity.
*   `stage` (string, optional): The current stage of the opportunity (e.g., 'prospecting', 'closed').
*   `probability` (integer, optional): The probability of closing the opportunity (0-100).
*   `close_date` (date, optional): The expected close date.
*   `contact` (string, optional): The primary contact for the opportunity.
*   `description` (string, optional): A description of the opportunity.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** The updated opportunity object.

### DELETE /opportunities/{opportunity}

Deletes an opportunity.

**URL Parameters:**

*   `opportunity` (integer, required): The ID of the opportunity to delete.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (204):** No content.

### GET /organisations/{organisation}/opportunities

Retrieves all opportunities for a specific organisation.

**URL Parameters:**

*   `organisation` (integer, required): The ID of the organisation.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** A JSON array of opportunity objects.

### GET /users/{user}/opportunities

Retrieves all opportunities created by a specific user.

**URL Parameters:**

*   `user` (integer, required): The ID of the user.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** A JSON array of opportunity objects.

## Tasks

### GET /tasks

Retrieves all tasks for the authenticated user's organisation.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** A JSON array of task objects.

### POST /tasks

Creates a new task.

**Authentication:** Requires a valid Sanctum API token.

**Request Body:**

*   `organisation_id` (integer, required): The ID of the organisation.
*   `assignee_id` (integer, required): The ID of the user the task is assigned to.
*   `title` (string, required): The task title.
*   `description` (string, optional): The task description.
*   `type` (string, optional): The task type (e.g., 'call', 'email').
*   `priority` (string, optional): The task priority (e.g., 'low', 'medium', 'high').
*   `status` (string, optional): The task status (e.g., 'open', 'closed').
*   `due_date` (date, optional): The task due date.
*   `related_to` (email, optional): The email of a user related to the task.

**Success Response (201):** The newly created task object.

### GET /tasks/{task}

Retrieves a specific task by its ID.

**URL Parameters:**

*   `task` (integer, required): The ID of the task.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** The task object.

### PUT /tasks/{task}

Updates a task.

**URL Parameters:**

*   `task` (integer, required): The ID of the task to update.

**Request Body:**

*   `title` (string, optional): The task title.
*   `description` (string, optional): The task description.
*   `type` (string, optional): The task type (e.g., 'call', 'email').
*   `priority` (string, optional): The task priority (e.g., 'low', 'medium', 'high').
*   `status` (string, optional): The task status (e.g., 'open', 'closed').
*   `due_date` (date, optional): The task due date.
*   `related_to` (email, optional): The email of a user related to the task.
*   `assignee_id` (integer, optional): The ID of the user the task is assigned to.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** The updated task object.

### DELETE /tasks/{task}

Deletes a task.

**URL Parameters:**

*   `task` (integer, required): The ID of the task to delete.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (204):** No content.

### GET /organisations/{organisation}/tasks

Retrieves all tasks for a specific organisation.

**URL Parameters:**

*   `organisation` (integer, required): The ID of the organisation.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** A JSON array of task objects.

### GET /users/{user}/tasks

Retrieves all tasks assigned to a specific user.

**URL Parameters:**

*   `user` (integer, required): The ID of the user.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** A JSON array of task objects.

## Organisations

### GET /organisations

Retrieves a list of all organisations.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** A JSON array of organisation objects.

### POST /organisations

Creates a new organisation.

**Authentication:** Requires a valid Sanctum API token.

**Request Body:**

*   `name` (string, required): The organisation's name.
*   `address` (string, optional): The organisation's address.
*   `phone` (string, optional): The organisation's phone number.
*   `email` (string, optional): The organisation's email address.

**Success Response (201):** The newly created organisation object.

### GET /organisations/{organisation}

Retrieves a specific organisation by its ID.

**URL Parameters:**

*   `organisation` (integer, required): The ID of the organisation.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** The organisation object.

### PUT /organisations/{organisation}

Updates an organisation's details.

**URL Parameters:**

*   `organisation` (integer, required): The ID of the organisation to update.

**Request Body:**

*   `name` (string, optional): The organisation's name.
*   `address` (string, optional): The organisation's address.
*   `phone` (string, optional): The organisation's phone number.
*   `email` (string, optional): The organisation's email address.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** The updated organisation object.

### DELETE /organisations/{organisation}

Deletes an organisation.

**URL Parameters:**

*   `organisation` (integer, required): The ID of the organisation to delete.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (204):** No content.

## Invitations

### POST /organisations/{organisation}/invite

Sends an invitation to a user to join an organisation.

**URL Parameters:**

*   `organisation` (integer, required): The ID of the organisation.

**Request Body:**

*   `invitee_id` (integer, required): The ID of the user to invite.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (201):**

```json
{
  "message": "Invitation sent",
  "token": "invitation-token"
}
```

### POST /invitations/accept

Accepts an invitation to join an organisation.

**Request Body:**

*   `token` (string, required): The invitation token.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):**

```json
{
  "message": "Invitation accepted.",
  "user": { ... }
}
```

## Dashboard

### GET /dashboard/stats

Retrieves statistics for the authenticated user's organisation.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):**

```json
{
  "organisation_users": 5,
  "opportunities_count": 10,
  "opportunities_by_stage": {
    "prospecting": 2,
    "qualification": 3,
    "proposal": 1,
    "negotiation": 2,
    "closed": 2
  },
  "pipeline_value": "50000.00",
  "pending_tasks_count": 8,
  "tasks_overdue": 2,
  "task_priorities": {
    "low": 3,
    "medium": 4,
    "high": 1
  },
  "upcoming_tasks": [
    {
      "id": 1,
      "title": "Follow up with Client A",
      "due_date": "2023-11-01",
      "assignee": "John Doe"
    }
  ],
  "appointments_today": 1,
  "contacts_count": 150,
  "monthly_new_contacts": 25
}
```

## Users

### GET /user

Retrieves the currently authenticated user's details. This is an alias for `/auth/me`.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):**

```json
{
  "id": 1,
  "name": "Test User",
  "email": "test@example.com",
  // ... other user fields
}
```

### GET /user/email-provider

Retrieves the connected email provider for the authenticated user.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):**

```json
{
  "id": 1,
  "user_id": 1,
  "provider": "google",
  "access_token": "...",
  "refresh_token": "...",
  "expires_in": 3599,
  "created_at": "...",
  "updated_at": "..."
}
```

**Error Response (404):**

```json
{
  "message": "No email provider connected"
}
```

### DELETE /user/email-provider

Disconnects the email provider for the authenticated user.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):**

```json
{
  "message": "Email provider disconnected successfully"
}
```

### GET /organisation/users

Retrieves all users belonging to the same organisation as the authenticated user.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):**

```json
[
  { "id": 1, "name": "User One", ... },
  { "id": 2, "name": "User Two", ... }
]
```

### GET /organisations/{organisation}/users

Retrieves all users for a specific organisation.

**URL Parameters:**

*   `organisation` (integer, required): The ID of the organisation.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** A JSON array of user objects.

### POST /organisations/{organisation}/add-user-by-email

Adds an existing user to an organisation by their email address.

**URL Parameters:**

*   `organisation` (integer, required): The ID of the organisation.

**Request Body:**

*   `email` (string, required): The email of the user to add.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):**

```json
{
  "message": "User successfully added to the organisation.",
  "user": { ... }
}
```

### POST /users/add-to-organisation

Adds an existing user to an organisation.

**Request Body:**

*   `user_id` (integer, required): The ID of the user.
*   `organisation_id` (integer, required): The ID of the organisation.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):**

```json
{
  "message": "User added to organisation successfully.",
  "user": { ... }
}
```

### PUT /users/{user}/assign-organisation

Assigns a user to a different organisation.

**URL Parameters:**

*   `user` (integer, required): The ID of the user.

**Request Body:**

*   `organisation_id` (integer, required): The ID of the new organisation.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):**

```json
{
  "message": "User assigned to new organisation successfully.",
  "user": { ... }
}
```

### GET /users

Retrieves a list of all users (admin only).

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** A JSON array of user objects.

### POST /users

Creates a new user.

**Authentication:** Requires a valid Sanctum API token.

**Request Body:**

*   `name` (string, required): The user's name.
*   `email` (string, required): The user's email address (must be unique).
*   `password` (string, required): The user's password (min. 6 characters).
*   `organisation_id` (integer, optional): The ID of the organisation.

**Success Response (201):** The newly created user object.

### GET /users/{user}

Retrieves a specific user by their ID.

**URL Parameters:**

*   `user` (integer, required): The ID of the user.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** The user object.

### PUT /users/{user}

Updates a user's details.

**URL Parameters:**

*   `user` (integer, required): The ID of the user to update.

**Request Body:**

*   `name` (string, optional): The user's new name.
*   `email` (string, optional): The user's new email address (must be unique).
*   `password` (string, optional): The user's new password (min. 6 characters).
*   `organisation_id` (integer, optional): The ID of the new organisation.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):** The updated user object.

### DELETE /users/{user}

Deletes a user.

**URL Parameters:**

*   `user` (integer, required): The ID of the user to delete.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (204):** No content.

### POST /login

Logs in a user and returns an API token.

**Request Body:**

*   `email` (string, required): The user's email address.
*   `password` (string, required): The user's password.

**Success Response (200):**

```json
{
  "user": {
    "id": 1,
    "name": "Test User",
    "email": "test@example.com",
    "organisation_id": 1,
    "first_time_login": true
  },
  "token": "some-api-token",
  "first_time_login": true
}
```

**Error Response (401):**

```json
{
  "message": "Invalid credentials"
}
```

### POST /logout

Logs out the authenticated user by deleting their current API token.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (204):** No content.

### GET /auth/me

Retrieves the authenticated user's information.

**Authentication:** Requires a valid Sanctum API token.

**Success Response (200):**

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
