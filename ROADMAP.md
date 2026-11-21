# Roadmap

Things I might add to plant-care. The app covers the daily loop today (plants,
schedules that follow the seasons, the today list, journal, sharing, the
morning email and a small API), so nothing here is promised or scheduled.
These are ideas, written down with enough detail that I (or anyone else) can
pick one up later without starting from zero. Items may change, move between
groups or be dropped.

What the app does today is in the [README](README.md) and the notes under
[docs/](docs/).

- **Next**: small or well understood. I would start with these.
- **Later**: worth doing, but bigger or waiting on a decision.
- **Maybe**: not sure yet that they are worth the extra complexity.

Sizes are rough: **S** is an evening or two, **M** a few evenings up to a
couple of weekends, **L** several weeks of spare time.

Each item is meant to fit in one pull request and to be covered by the test
suite. There are no browser tests: `tests/Unit` covers plain classes such as
`src/Care`, `src/Calendar` and `src/Household/HouseholdClock.php`, and
`tests/Functional` drives controllers, commands and the API through Symfony's
test client against SQLite, with every test wrapped in a transaction. So logic
worth testing (dates, intervals, diffs, parsing) belongs in a small service
under `src/`, not inside a controller or a Stimulus controller. Items that add
columns bring their own Doctrine migration under `migrations/`, written for
MySQL, because CI runs the migrations against MySQL 8.4 and then
`doctrine:schema:validate`. File names below are relative to the repository
root.

When an item ships, its Overview cell says `Shipped`, its section moves under
a `## Shipped` heading at the end and shrinks to one paragraph on what was
built and where, and the same pull request updates the README, the notes under
`docs/` and the `Unreleased` section of the CHANGELOG.

"Depends on" lists real prerequisites only. Items that merely touch the same
file are not listed there; the bigger overlaps are mentioned in the items.

## Overview

| #   | Item                                         | Group | Area          | Size | Depends on |
| --- | -------------------------------------------- | ----- | ------------- | ---- | ---------- |
| 1   | Green CI on PHP 8.4                          | Next  | ops           | S    | -          |
| 2   | Undo for done, skip and snooze               | Next  | today         | S    | -          |
| 3   | A morning email that does not lose a day     | Next  | email         | S    | -          |
| 4   | Password reset                               | Next  | accounts      | M    | -          |
| 5   | Smaller photos without location data         | Next  | photos        | M    | -          |
| 6   | Backups and restore                          | Later | ops           | M    | -          |
| 7   | Photo files that outlive their rows          | Later | photos        | S    | -          |
| 8   | Away mode                                    | Later | today         | M    | -          |
| 9   | A link for the plant sitter                  | Later | sharing       | M    | -          |
| 10  | Restoring and deleting archived plants       | Later | plants        | S    | 7          |
| 11  | "Soil still wet"                             | Later | care          | S    | -          |
| 12  | Correcting the care history                  | Later | care          | M    | 2          |
| 13  | Notes and amounts on care                    | Later | care          | S    | -          |
| 14  | Treatments that end                          | Later | care          | M    | -          |
| 15  | Your own kinds of task                       | Later | care          | L    | -          |
| 16  | Climates and seasonal factors                | Later | care          | M    | -          |
| 17  | Choosing when the email comes                | Later | email         | S    | 3          |
| 18  | A better calendar feed                       | Later | calendar      | S    | -          |
| 19  | Invitations by email                         | Later | sharing       | S    | -          |
| 20  | Verified email addresses                     | Later | accounts      | M    | 4          |
| 21  | Closing sign-ups on a private instance       | Later | ops           | S    | -          |
| 22  | Named API tokens with scopes                 | Later | api           | M    | -          |
| 23  | Writing plants, rooms and schedules by API   | Later | api           | M    | 22         |
| 24  | Importing plants from a spreadsheet          | Later | plants        | M    | -          |
| 25  | Export with photos                           | Later | account       | S    | -          |
| 26  | Plant labels with QR codes                   | Later | plants        | S    | -          |
| 27  | Pot, soil and repotting details              | Later | plants        | M    | -          |
| 28  | Where a cutting came from                    | Later | plants        | S    | -          |
| 29  | Managing your own species                    | Later | catalog       | S    | -          |
| 30  | Accessibility pass                           | Later | ui            | M    | -          |
| 31  | Running behind a reverse proxy               | Later | ops           | S    | -          |
| 32  | Household activity log                       | Later | sharing       | M    | -          |
| 33  | A Content Security Policy without inline code | Later | security      | M    | -          |
| 34  | More useful stats                            | Later | stats         | M    | -          |
| 35  | Intervals that learn from what you do        | Maybe | care          | L    | 11, 12     |
| 36  | Push notifications                           | Maybe | notifications | L    | 17         |
| 37  | Readings from soil sensors                   | Maybe | api           | L    | 22         |
| 38  | Translations                                 | Maybe | ui            | L    | -          |
| 39  | Two-step sign in                             | Maybe | accounts      | M    | -          |
| 40  | Large households                             | Maybe | performance   | M    | -          |

## Next

### 1. Green CI on PHP 8.4

The `checks (8.4)` job fails on every pull request since mid-November, and
not because of the change under test. PHPUnit runs with `failOnDeprecation`
in `phpunit.dist.xml`, and on PHP 8.4 one test triggers "Since
symfony/var-exporter 7.3: Using ProxyHelper::generateLazyGhost() is
deprecated, use native lazy objects instead" (from
`AccountTest::testCreateAndRevokeApiToken`). Dependabot pull requests are red
for the same reason, so a real failure would not stand out.

- Turn on native lazy objects in `config/packages/doctrine.yaml`
  (`orm.enable_native_lazy_objects: true`). They need PHP 8.4, and the app
  also runs on 8.3 (the Docker image is `php:8.3-apache`, CI tests both), so
  the setting has to depend on the PHP version, or the image moves to 8.4 and
  8.3 leaves the matrix.
- Tricky: the deprecation shows up only in one test because only that path
  loads a proxy before use. Run the whole suite on 8.4 with
  `--display-deprecations` to make sure there is not a second one behind it.
- Tricky: native lazy objects change when an entity is initialised. Code that
  compares `getId()` on a proxy without loading it (the voter's
  `findOneFor`, `MemberManager::ensureAnotherOwner`) should behave the same,
  but the functional tests are the proof, not my guess.
- Undecided: drop 8.3. The Docker image is the way most people run this, and
  moving it to 8.4 is one line. I lean towards keeping 8.3 in CI until 8.3
  stops getting security fixes, with the version check in the config.
- Tests: none new. The job itself is the test; the pull request should show
  both matrix jobs green.

### 2. Undo for done, skip and snooze

One click on the today list moves a schedule forward, and there is no way
back. Pressing "Done" on the wrong row, "Skip this time" instead of "Did it
yesterday", or "Water all" in the wrong room all need a trip to the schedule
form to type a due date by hand, and the care event stays in the history and
the stats anyway.

- `CareTasks` returns the `CareEvent` it logged. Add the state before the
  change to the event, so it can be put back: `previousNextDueOn`,
  `previousLastDoneOn` and `previousSnoozedUntil` (all nullable dates) on
  `CareEvent`, written in `CareTasks::log` from values read before
  `CareScheduler` runs.
- A new `CareTasks::undo(CareEvent $event, User $user)`: restores the three
  dates on the schedule and deletes the event, in one transaction. A route
  `POST /care-events/{id}/undo` in `CareTaskController`, guarded by
  `Permission::CARE` on the event's plant and a CSRF token per event.
- The JSON answer that `task_controller.js` already reads gets the event id,
  and the row shows "Watered. Undo" for a while instead of disappearing at
  once. Without JavaScript the flash message carries an undo button.
- `task_done_many` ("water all") logs several events; the flash offers one
  undo for all of them, so the route takes a list of ids as well.
- Tricky: undo only the latest event of a schedule. If someone else marked the
  same plant done after me, putting my "before" dates back would erase their
  work. Refuse with "Someone took care of it since" when a newer event exists
  for the same plant and type.
- Tricky: `markDone` returns the existing event when the task was already done
  that day (the idempotency in `CareTasks::markDone`). Undoing that returned
  event must undo the original, not a second copy, which works as long as the
  undo goes by event id.
- Tricky: "Did it yesterday" after "Done" today plans from yesterday and
  moves the next due date one day earlier. Undo fixes the slip; item 12 deals
  with the history in general.
- Undecided: a time limit. A day is enough for slips, and anything older is
  history to correct (item 12). I lean towards allowing undo until the end of
  the household's day, computed with `HouseholdClock`.
- Tests: unit test that `CareScheduler` changes are reversed exactly for done,
  skip and snooze, including the winter pause; functional tests in
  `tests/Functional/CareTaskControllerTest.php` for undo, refusal after a newer
  event, refusal for a viewer, and the bulk case.

### 3. A morning email that does not lose a day

`DigestMailer::sendTo` writes `lastDigestOn` and flushes before it sends. When
the mailer throws (SMTP down, a bad address), the day is marked as sent and
nobody gets a mail until tomorrow. And `SendDigestCommand` loops over every
user with no `try`, so one failing address stops the mails for everyone after
it in the list. The scheduler service in `docker-compose.yml` just runs the
command again an hour later.

- Send first, then mark the day: set `lastDigestOn` after
  `$this->mailer->send()` returned. Keep marking it when there was nothing to
  send, so the job does not count the tasks every hour.
- Catch the transport exception per user in the command, log it with the
  user id (not the address) and go on. The command ends with
  `Command::FAILURE` and "Sent 12, failed 1", so cron mail or a log watcher
  sees it.
- `isDue` takes the timezone of the user's first household
  (`$memberships[0]`), while the mail covers all households. Someone in Berlin
  with an office household in New York gets the mail at 7:00 Berlin time with
  "today" for New York being yesterday's date for a few hours. Compute the
  section dates per household (already done through `HouseholdClock`) and say
  so in the README; choosing which household's morning counts is item 17.
- Tricky: with Messenger the mail would be queued and the failure would happen
  later. The app has no Messenger and `mailer.yaml` sends directly, so the
  exception arrives in `send()`. Keep it that way; a queue would need a worker
  service in compose for one mail a day.
- Tricky: a mail that keeps failing for one person is retried every hour all
  day. That is fine for an hourly job; it stops at midnight when the date
  changes.
- Tests: `tests/Functional/Command/SendDigestCommandTest.php` with a mailer
  transport that throws for one address: the others are sent, the failing
  user's `lastDigestOn` stays empty, and the exit code is 1.

### 4. Password reset

There is no way back into an account after forgetting the password. Sign in
throttles after five attempts, and the README's only advice is clearing the
rate limiter pool. The app already sends mail, so a reset link is cheap.

- `symfonycasts/reset-password-bundle` does the token handling (selector plus
  hashed verifier, expiry, throttling of requests) and is the usual choice for
  Symfony. It adds a `ResetPasswordRequest` entity and a migration.
- Routes `/reset-password` (ask), `/reset-password/check-email` and
  `/reset-password/reset/{token}`, public in `access_control` next to
  `login` and `register`. A link from `templates/security/login.html.twig`.
- The mail uses the same `TemplatedEmail` setup as `DigestMailer`, with a text
  and an HTML template under `templates/email/`, and `DEFAULT_URI` for the
  link (already used for the calendar links).
- After a reset: the password hash changes, so remember-me cookies stop
  working (they are signed with the password by default) and other sessions
  are signed out when they refresh the user. The page says so.
- Tricky: do not reveal whether an address has an account. The ask page always
  answers "If there is an account for this address, a link is on its way".
- Tricky: the request form needs its own rate limit by IP and by address, or
  it becomes a way to send mail to anyone. The bundle limits per user; add a
  limiter in `config/packages/rate_limiter.yaml` for the IP.
- Tricky: invitation links. Someone who opens an invite, signs in and has
  forgotten the password should come back to the invitation afterwards. The
  target path saved by `InvitationController::show` should survive the reset.
- Tests: functional tests for the whole flow with Mailpit-free assertions
  (`assertEmailCount`, reading the link from the mail), an expired token, an
  unknown address that gets the same answer and no mail, and the throttle.

### 5. Smaller photos without location data

`PhotoStorage::store` keeps the upload byte for byte. A phone photo is 3 to 8
MB, the cover appears on every plant card and the photo timeline loads all of
them at full size, which is slow on a phone and fills the `photos` volume.
Worse, phone photos carry EXIF data with the GPS position, and photos are
visible to every member, including viewers and anyone given a plant sitter
link later. A houseplant photo tells people where you live.

- On upload, decode the image, rotate it according to its EXIF orientation,
  scale it to at most 2000 px on the long side and write it again. Writing a
  new file drops all metadata, which is the point. Also write a thumbnail of
  about 400 px for cards, the journal list and the timeline.
- GD needs no extra service. It is not in the image yet: the `Dockerfile`
  needs `gd` with JPEG and WebP support in `docker-php-ext-install`, and the
  CI jobs need it in the `extensions` list of `setup-php`. Imagick is better at large
  images but needs a system library; GD is enough here.
- Names: keep the random name and add a suffix for the thumbnail
  (`<hex>.jpg` and `<hex>.thumb.jpg`). `PhotoStorage::isValidName`, the route
  requirement in `PhotoController` and `PhotoStorage::delete` (which must
  remove both) change together.
- Templates use the thumbnail where the image is small (`plant/index`,
  `journal/_entry`, `journal/photos`) and link to the full image.
- Existing photos: a console command `app:photos:rebuild` that rewrites every
  stored photo and creates the thumbnails, safe to run twice. The entrypoint
  does not run it; the CHANGELOG says to run it once.
- Tricky: memory. GD holds the decoded image in memory, 4 bytes per pixel, so
  a 48 MP photo needs about 200 MB, more than PHP's default limit. Read the
  size with `getimagesize` first (already done) and refuse anything above a
  pixel count that fits `memory_limit` in `docker/php.ini`, with a clear
  message.
- Tricky: PNG screenshots with transparency should stay PNG, and WebP stays
  WebP when GD supports it, so the extension does not change under the user.
- Undecided: keep the original next to the resized copy. It would keep the
  full quality for people who care and keep the GPS data I want gone. I lean
  towards not keeping it.
- Tests: unit tests in `tests/Unit/Photo/PhotoStorageTest.php` with a small
  JPEG fixture that has an orientation flag and a GPS tag: the stored file is
  upright, has no EXIF, is no larger than the limit, and has a thumbnail; a
  functional test that the thumbnail route needs membership like the photo.
