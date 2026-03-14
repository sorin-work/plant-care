# JSON API

A small read/write API for scripts, home automation and other apps. Everything
the API can do is also available in the web interface.

## Authentication

Create a personal token on the **Account** page (click your name in the top bar).
The token is shown once; only a hash is stored. Send it as a bearer token:

```bash
curl -H "Authorization: Bearer pc_..." http://localhost:4414/api/me
```

The API is stateless: the session cookie of the web app is not accepted. Revoking
or replacing the token on the Account page invalidates the old one immediately.

## Households

Most endpoints work on one household. Pass `?household=<id>` to choose it; without
it the oldest household you are a member of is used. `GET /api/me` lists your
households and your role in each.

Permissions are the same as in the web app: viewers can read, caretakers and
editors can also complete, snooze and skip tasks.

## Errors

Errors are JSON with an `error` message and the matching status code:

| Status | Meaning                                               |
| ------ | ----------------------------------------------------- |
| 401    | Missing or invalid token                              |
| 403    | You are a member but your role does not allow it      |
| 404    | Not found, or it belongs to a household you are not in |
| 422    | Invalid input, e.g. snoozing for more than 14 days     |

```json
{ "error": "A task can be snoozed for 1 to 14 days." }
```

## Endpoints

### `GET /api/me`

```json
{
  "id": 1,
  "email": "demo@example.com",
  "displayName": "Demo",
  "households": [
    { "id": 1, "name": "Demo home", "role": "owner", "timezone": "Europe/Berlin", "hemisphere": "north" }
  ]
}
```

### `GET /api/today`

Tasks that are due today or overdue, most overdue first. "Today" is the current
date in the household's timezone.

```json
{
  "household": 1,
  "date": "2026-03-14",
  "tasks": [
    {
      "id": 12,
      "type": "water",
      "intervalDays": 7,
      "seasonal": true,
      "nextDueOn": "2026-03-12",
      "lastDoneOn": "2026-03-05",
      "snoozedUntil": null,
      "daysOverdue": 2,
      "plant": { "id": 4, "name": "Monty", "room": "Living room" }
    }
  ]
}
```

`type` is one of `water`, `fertilize`, `repot`. `intervalDays` is the base
interval; with `seasonal` it is stretched in winter and shortened in summer.

### `GET /api/plants`

Active (not archived) plants of the household, sorted by name.

```json
{
  "household": 1,
  "plants": [
    {
      "id": 4,
      "name": "Monty",
      "initials": "M",
      "species": "Monstera deliciosa",
      "room": "Living room",
      "light": "bright_indirect",
      "acquiredOn": "2025-02-01",
      "hasPhoto": true
    }
  ]
}
```

`light` is one of `low`, `medium`, `bright_indirect`, `direct`.

### `GET /api/plants/{id}`

One plant with its schedules (same shape as the tasks above, without `plant` and
`daysOverdue`) and the last ten care events:

```json
{
  "id": 4,
  "name": "Monty",
  "species": "Monstera deliciosa",
  "room": "Living room",
  "light": "bright_indirect",
  "notes": "Big one next to the sofa.",
  "archived": false,
  "schedules": [{ "id": 12, "type": "water", "intervalDays": 7, "...": "..." }],
  "recentCare": [{ "type": "water", "action": "done", "on": "2026-03-05", "by": "Demo" }]
}
```

### `POST /api/tasks/{id}/done`

Marks the task done today and returns the updated schedule.

### `POST /api/tasks/{id}/skip`

Skips this occurrence: the task is planned again one interval from today without
counting as done. Returns the updated schedule.

### `POST /api/tasks/{id}/snooze`

Hides the task for a few days without changing its due date.

```bash
curl -X POST -H "Authorization: Bearer pc_..." -H "Content-Type: application/json" \
     -d '{"days": 3}' http://localhost:4414/api/tasks/12/snooze
```

`days` must be a whole number from 1 to 14 (default 1).
