# Changelog

All notable changes to this project are documented in this file. The format is
based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project
follows [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-10-13

First stable release.

### Plants and rooms

- Plants with species, room, light, acquisition date, notes and a cover photo
- Search by name or species, filter by room or pet toxicity, sort by name, next
  care or date added
- Archive (with a reason) and restore plants; propagate a cutting into a new plant
- Rooms with a usual light level, plant counts and moving several plants at once
- Species catalog of 42 houseplants with care defaults and pet toxicity; households
  can add their own species

### Care schedules

- Watering, fertilizing, misting and repotting schedules created from the species
- Seasonal intervals: longer in winter, shorter in summer, depending on the
  household's hemisphere; optional winter pause for fertilizer
- Today list grouped by room with done, snooze, skip, "did it yesterday" and
  "water all", without page reloads
- Coming up view for the next 7 days and an iCal feed
- Care notes per schedule, full care history per plant and care stats

### Journal

- Journal entries with photos per plant, a photo timeline, a household-wide feed,
  editing, deleting and CSV export

### Households and sharing

- Several households per user, switchable from the header
- Roles: owner, editor, caretaker and viewer
- Invitation links and adding members by email
- Printable care sheet for plant sitters

### Accounts

- Sign up, sign in with throttling, profile and password changes
- Daily email with the tasks due, sent after 7:00 in the household's timezone
- Personal API tokens, JSON export of all data and account deletion

### API

- Token based JSON API for today's tasks, plants, schedules and journal entries,
  with rate limiting; see `docs/api.md`

### Operations

- Docker Compose setup with MySQL 8.4, an hourly scheduler and Mailpit
- Demo data with two users and two households
- CI with coding style, PHPStan level 8, tests on PHP 8.3 and 8.4, MySQL
  migrations and the Docker build

[1.0.0]: https://github.com/sorin-work/plant-care/releases/tag/v1.0.0
