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

## Later

### 6. Backups and restore

Everything lives in two Docker volumes, `db-data` and `photos`, and the README
only says that `docker compose down -v` removes them. There is no backup
command and nothing that says how to get a household back after a broken disk
or a bad upgrade. Years of journal photos are the one thing in this app that
cannot be recreated.

- A `backup` service in `docker-compose.yml`, off by default (a compose
  profile), that runs once a night: `mysqldump --single-transaction` of the
  database plus a tar of the photo directory into a `backups` volume or a
  bind-mounted folder, named by date, keeping the last N (`BACKUP_KEEP`,
  default 14).
- A `bin/restore` script, or a documented sequence in a new
  `docs/operations.md`: stop `app` and `scheduler`, load the dump, unpack the
  photos, start again. Migrations run on start, so a dump from an older
  version upgrades itself.
- Without Docker: the same two steps as console-free shell commands in the
  docs, for people running `symfony serve` or their own Apache.
- Tricky: the dump and the photos must belong together. A photo uploaded
  between the dump and the tar is harmless (a file without a row, see item 7);
  a row without a file shows a broken image. Dump the database first, then
  copy the photos.
- Tricky: a backup nobody has restored is a hope. CI can restore a dump made
  from the demo seed into a fresh MySQL in the existing `migrations` job and
  run `doctrine:schema:validate`, which proves the dump format at least.
- Undecided: encryption and off-site copies. Both belong to whoever runs the
  instance; the docs can point at restic or rclone for the backup folder
  instead of building it in.
- Tests: the CI restore step above; the scripts are shell and are checked with
  `sh -n` and a run against the compose stack by hand.

### 7. Photo files that outlive their rows

Photos are files, and the database does not delete files. `JournalController`
and `PlantController::handleCover` remove the file when an entry is deleted or
a cover is replaced, but nothing does when a whole household goes: leaving as
the last member (`MemberManager::leave`) or deleting the account removes the
household, and the foreign keys cascade to plants and journal entries in
MySQL, so the photo files stay on disk with no row pointing at them. A failed
flush after `PhotoStorage::store` leaves one behind too.

- A console command `app:photos:prune` that lists the files in `PHOTO_DIR`,
  keeps those referenced by `JournalEntry.photo` or `Plant.coverPhoto`, and
  deletes the rest. `--dry-run` prints what it would delete.
- Run it from the `scheduler` loop once a day, next to `app:digest:send`.
- Delete the files of a household right away as well, in `MemberManager::leave`
  when the household goes: collect the names before the remove, delete the
  files after the flush succeeded.
- Tricky: an upload in progress. A file written a second ago may belong to a
  form that is about to flush. Skip files younger than an hour.
- Tricky: the same file name must never be referenced twice. Propagation
  (`Propagator`) does not copy the cover today; if it ever does, it must copy
  the file, or deleting one plant's cover breaks the other's.
- Tests: functional test for the command with a temporary `PHOTO_DIR`: a
  referenced file stays, an orphan older than an hour goes, a fresh orphan
  stays; a test that leaving a household as the last member removes its files.

### 8. Away mode

The question every plant app gets: "I am away for two weeks, what now?" Today
the answer is to snooze each task by hand (at most 14 days, `MAX_SNOOZE_DAYS`
in `CareScheduler`) and to come back to a today list where everything is late.
The care sheet and the "Coming up" page help with planning, but there is no
way to say "nobody is home from the 3rd to the 17th".

- Data: an `AwayPeriod` entity on `Household` with `startsOn`, `endsOn` and an
  optional note. One period at a time is enough.
- During the period the today list shows a banner instead of tasks, the
  morning email is not sent for that household, and "Coming up" shows the
  period.
- At the end, an "I am back" view lists what would have been due while away,
  with "watered before I left" (plan from the day before the trip), "done now"
  and "skip" per row or for all, so the due dates do not all land on the first
  day back.
- "Before you go": a list of the tasks that fall inside the period, so I can
  water everything the evening before. `UpcomingPlanner::nextDays` already
  does this for seven days; it takes a range.
- Tricky: a caretaker who stays home (a flatmate). Away mode is per household,
  so "everyone is away" is the meaning, and a sitter (item 9) still sees the
  list. If any member stays, they simply do not turn it on.
- Tricky: due dates are not changed during the period, so turning it off early
  or deleting it leaves no damage. Only the "I am back" choices write.
- Undecided: whether to stretch watering intervals before the trip ("water
  deeply, then wait"). That is gardening advice, not scheduling; I would leave
  it to the note.
- Tests: unit tests for which tasks fall into a period; functional tests for
  the banner, the digest skipping the household, and the "I am back" actions.

### 9. A link for the plant sitter

The README says the printable care sheet is for plant sitters. Paper works,
but the sitter cannot tick anything off, and adding them as a caretaker means
they need an account and get the household forever (until someone removes
them).

- A sitter link: an owner creates it with an end date, like an invitation
  (`Invitation` already stores only a hash of the token). Opening it shows the
  today list and "Coming up" for that household, read from the same
  `DueTaskFinder`, with "Done" buttons and the schedule notes ("bottom water,
  300 ml"). No account, no other pages.
- Data: `SitterLink` with `household`, `tokenHash`, `name` ("Anna, August"),
  `expiresAt`, `revokedAt` and `createdBy`. `CareEvent.user` is null for sitter
  actions today; add a nullable `sitterLink` so the history says "Anna (sitter
  link)".
- A separate firewall or a public route prefix (`/sit/{token}`) in
  `access_control`, CSRF on its forms, and `HouseholdVoter` is not involved:
  the controller checks the token and the expiry itself.
- Tricky: the link is a password in a URL. It ends up in chat apps and
  browser history. Keep it short-lived (default 21 days, at most 60), let the
  owner revoke it, show when it was last used, and do not show photos or the
  journal through it.
- Tricky: the sitter's phone has no timezone setting in the app; the household
  clock decides what "today" is, as for members.
- Undecided: let the sitter write a short note or add a photo ("the fern looks
  sad"). Useful and the most-asked thing between sitter and owner, but it opens
  uploads to people without accounts. I lean towards a text note only, stored
  as a journal entry with the sitter's name.
- Depends on nothing, but fits well with item 8: away mode could offer to
  create the sitter link for the same dates.
- Tests: functional tests for the link: works before expiry, 410 after expiry
  and after revoke, cannot reach another household's tasks, logs events with
  the sitter link, and only `MANAGE` can create one.

### 10. Restoring and deleting archived plants

Archiving keeps a plant's schedules as they were. Restoring a plant that has
been away for half a year (`Plant::restore`) brings back schedules whose
`nextDueOn` is months in the past, so the today list says "182 days late" for
every task of that plant. And there is no way to delete a plant for good:
`PlantController` only archives, so a plant added by mistake stays in the
archive with its photos.

- On restore, ask for each schedule: "start today" (due today), "keep the old
  dates" or "plan from today" (one interval from today). Default to due today
  for watering and plan from today for the rest.
- A "Delete for good" button on the archived list
  (`templates/plant/archived.html.twig`) for owners and editors, with a
  confirmation that names what goes: schedules, care history, journal entries
  and photos.
- Deleting removes the photo files after the flush (see item 7 for the file
  side) and the plant row; foreign keys already cascade to schedules, events
  and journal entries.
- Tricky: the stats count care events, and deleting a plant removes its events,
  so last month's numbers change. That is what deleting means; the
  confirmation can say so.
- Tricky: a cutting made from a deleted plant keeps the text "Propagated from
  X" in its notes; with item 28 the link becomes null, which is fine.
- Tests: functional tests for restore with each choice and for deleting
  (rows gone, files gone, a viewer gets 403, a plant of another household 404).

### 11. "Soil still wet"

A fixed interval is a guess, and the advice in every care guide is to check the
soil before watering. When I check and the soil is still wet, the app gives me
three wrong buttons: "Done" logs watering that did not happen, "Skip" waits a
whole interval, and "Later" snoozes without saying why.

- A "Checked, not needed yet" action for watering and misting: logs a new
  `CareAction::Checked` and moves the due date by a short step (two days, or a
  quarter of the interval, whichever is longer), not a full interval.
- `CareAction` gets the new case; the history (`plant/history.html.twig`), the
  stats (`CareStats` counts done and skipped today) and the API presenter
  learn it. The API gets `POST /api/tasks/{id}/checked`.
- On the task row it goes into the "Later" menu in
  `templates/care/_task_row.html.twig`, above the snooze choices.
- Tricky: checked is not snoozed. A snooze keeps `nextDueOn` and hides the
  task; checked moves `nextDueOn`. That difference matters for item 35, which
  wants to learn the real interval from these events.
- Tricky: idempotent per day like `markDone`: checking twice on one day moves
  the date once.
- Tests: unit test for the step in `CareScheduler`; functional tests for the
  route, the history line and the API endpoint.

### 12. Correcting the care history

The history page (`plant_history`) is read-only. If I forgot to tick the
watering on Tuesday and do it on Thursday, the log says Thursday, the next due
date counts from Thursday, and "did it yesterday" only goes back seven days.
Mistakes older than the undo window of item 2 cannot be fixed at all.

- On the history page, owners, editors and the person who logged an event can
  change its date or delete it. A form to add a past event ("watered on the
  12th") as well.
- After any change the schedule is recomputed from the history: `lastDoneOn`
  is the latest done event and `nextDueOn` is `CareScheduler::nextDueAfter`
  of that day. Snoozes and skips after the last done event still count.
- Put that rule in one place, a `ScheduleReplayer` in `src/Care/` that takes a
  schedule and its events and returns the dates, so it can be unit tested on
  its own.
- Tricky: the replay must give today's answer for a schedule nobody touched
  in the history, or editing an unrelated old event moves today's due date.
  The test: replaying the demo data changes nothing.
- Tricky: skips and snoozes are logged with the day they happened but do not
  store the number of days snoozed. Replay can only treat the latest snooze as
  "keep `snoozedUntil` as it is", which is good enough.
- Tricky: the interval may have changed since (someone edited the schedule).
  Replay uses the current interval; the history does not store old ones.
- Tests: unit tests for `ScheduleReplayer` (the main value of the item) and
  functional tests for edit, delete and add with permissions.

### 13. Notes and amounts on care

A care event is a type, an action, a day and a person. "Fertilized at half
strength", "200 ml", "repotted into a 17 cm pot" or "found thrips while
misting" go into a separate journal entry today, if anywhere. The schedule's
`notes` field says how it should be done, not how it was.

- Data: a nullable `note` (160 characters, like `CareSchedule.notes`) on
  `CareEvent`.
- The "Done" button stays one click. A small "add a note" link next to the
  confirmation (the JSON answer of `task_done` already has the message) opens
  a field that updates the event just logged.
- The history page and the API presenter show the note; the export includes
  it.
- Tricky: structured amounts (millilitres, grams) are tempting for stats, but
  nobody measures every time. Keep it free text; item 35 does not need it.
- Tests: functional tests for adding a note to one's own event, refusing it on
  someone else's event for a caretaker, and the note in the API and export.

### 14. Treatments that end

Pests are the other half of plant care. Treating spider mites means spraying
every 5 to 7 days for three or four weeks, then stopping. The schedules here
repeat forever, and they are one per type per plant (`uniq_schedule_plant_type`
on `CareSchedule`), so a treatment cannot even sit next to misting.

- A `Treatment` entity on a plant: what (free text, "neem oil", "systemic
  granules"), why (a short list: pests, disease, other, plus a note), an
  interval in days and a number of times or an end date.
- Treatments show up on the today list with the other tasks, through a second
  query next to `CareScheduleRepository::findDue`, and in "Coming up" and the
  calendar feed. Done logs a `CareEvent` with a new `CareTaskType::Treat` and
  counts down.
- When the last one is done, the treatment closes and a journal entry asks
  "Did it work?" with a link to add a photo.
- Tricky: `DueTask` wraps a `CareSchedule` today, and `TaskGrouper`, the
  digest templates and the API presenter all expect one. Either a treatment is
  a `CareSchedule` with an end (and the unique constraint goes), or `DueTask`
  becomes an interface with two implementations. I lean towards a schedule
  with `endsAfter` and `remaining` columns and a nullable `label`, and
  dropping the unique constraint only for the new type. That also prepares
  item 15.
- Tricky: an infested plant should usually be isolated. A "move to quarantine"
  shortcut that moves it to a room called Quarantine would be nice but is just
  a room; do not build a concept for it.
- Tests: unit tests for counting down and closing; functional tests for the
  today list, the API and the calendar showing a treatment.

### 15. Your own kinds of task

Four task types are hard-coded: `CareTaskType` is an enum and
`SeasonalIntervalCalculator::FACTORS` has a row per case. People ask for
"rotate towards the light", "wipe the leaves", "check for pests", "flush the
soil" and "top up the water reservoir" (self-watering pots). Today these go
into notes and are forgotten.

- Custom task types per household: a name, an icon from a short fixed list,
  whether it is seasonal and its four seasonal factors (default 1.0).
- Data: a `TaskKind` entity (`household`, `name`, `icon`, `seasonal`,
  factors). `CareSchedule.type` stays for the four built-in ones and a nullable
  `kind` is added; exactly one of them is set. The same for `CareEvent`.
- Tricky: `type` is used everywhere as an enum: labels, icons, routes
  (`schedule_new` takes the type in the URL), `ScheduleFactory`, the API
  (`type` is one of four strings), stats grouped by `type->value`. A
  `TaskLabel` value object with `label()`, `icon()` and a stable key
  (`water` or `kind-12`) that both sources produce keeps the templates simple.
  Do this refactoring as the first commit, with no behaviour change.
- Tricky: the API. Clients that switch on `type` will see a new value. Add
  `kind: {id, name}` and set `type` to `custom` for these, and say so in
  `docs/api.md`.
- Tricky: unique per plant and type. A custom kind is also one schedule per
  plant, so the unique constraint becomes `(plant_id, type, kind_id)`, which
  MySQL treats as unique only when no column is null; the migration needs a
  generated column or two constraints.
- Depends on nothing, but if item 14 lands first, its treatment type follows
  the same pattern.
- Tests: unit tests for the seasonal factors of a custom kind; functional
  tests for creating a kind, a schedule using it, the today list, the API
  shape and that another household cannot use it.

### 16. Climates and seasonal factors

Seasons are meteorological months, north or south (`SeasonResolver`, the
table in `docs/scheduling.md`), and the factors are constants. That fits
central Europe. In Singapore or Bogotá there is no winter; in a flat with
floor heating winter is the dry season and plants need more water, not less;
and plants under a grow light do not care about the month.

- A third `Hemisphere` choice, "no seasons" (call the setting "Climate" in
  the UI): every day is spring, factor 1.0.
- Seasonal factors per household, editable on the settings page, starting
  from today's table, with a "reset to defaults". `SeasonalIntervalCalculator`
  takes the household's factors instead of the constant.
- Per plant: "grow light, ignore seasons" is the existing `seasonal` flag on
  each schedule; a single switch on the plant form that sets all its schedules
  is enough.
- Tricky: the winter pause for fertilizer in `CareScheduler::skipWinter` has
  no meaning without winter; hide the option for the new choice.
- Tricky: changing factors does not move existing due dates, because the
  interval is applied when care is done. Say so next to the form, or offer
  "replan everything now" (which item 12's replayer can do).
- Tests: unit tests for the calculator with custom factors and for the new
  climate; functional test for the settings form and validation (factors
  between 0.25 and 4).

### 17. Choosing when the email comes

The morning email goes out after 7:00 (`DigestMailer::SEND_HOUR`) in the
timezone of the user's first household, every day something is due. Some
people want it at 6, some at 18:00 to water after work, some only on Saturday
with a summary of the week, and someone in two households wants only one of
them.

- Data on `User`: `digestHour` (0 to 23, default 7) and `digestDays` (a set of
  weekdays, default all), plus a `digestTimezoneHousehold` or a plain
  `timezone` on the user. I lean towards a user timezone, filled from the
  first household, because "my morning" belongs to the person.
- Per membership: `digestEnabled` moves from `User` to `Membership`, so each
  household can be in or out of the mail.
- A weekly mail on the chosen day lists what is due over the next seven days
  using `UpcomingPlanner`.
- Tricky: `isDue` is called hourly, so an hour that does not exist (a daylight
  saving switch at 2:00) must still send. Compare "local hour >= chosen hour"
  as today, not equality.
- Tricky: the migration copies `digestEnabled` from users to all their
  memberships.
- Depends on item 3, which fixes when the day counts as sent.
- Tests: unit tests for `isDue` across a DST change and for weekdays;
  functional tests for the Account form and a two-household user with one
  turned off.

### 18. A better calendar feed

The iCal feed (`CalendarController`, `IcsWriter`) has one event per schedule,
on the next due date only, for all of the user's households together. Calendar
apps poll it every few hours and show today's task, but nothing beyond the
next date, no reminder, and no way to subscribe to one household only.

- A `?household=<id>` filter, or one feed URL per household on the Account
  page, using the same `calendarToken`.
- The next three occurrences per schedule, computed with
  `CareScheduler::nextDueAfter` from the due date. Mark the later ones as
  estimates in the description, because each depends on when the first one is
  done.
- An optional `VALARM` at the digest hour for people who use the calendar
  instead of the email.
- `SEQUENCE` and a stable `DTSTAMP`, so calendar apps update an event instead
  of showing a duplicate when the date moves. Today the stamp is "now" on every
  request.
- Tricky: overdue tasks. The feed puts them on `nextDueOn` in the past, where
  nobody looks. Show overdue tasks on today's date with "(2 days late)".
- Tricky: calendar apps cache aggressively; Google refreshes subscribed feeds
  every few hours at best. Say so in the README rather than promising
  something the app cannot control.
- Tests: unit tests for `IcsWriter` with alarms and sequences; functional tests
  for the household filter, the future occurrences and the overdue rule in
  `tests/Functional/CalendarTest.php`.

### 19. Invitations by email

An invitation link is shown once in a flash message (`invite_link` in
`HouseholdController::invite`) and the owner has to copy it into a chat. The
other way, adding by email, only works for people who already have an account
(`MemberManager::add` says "Ask them to sign up first"). The members page shows
pending invitations but not who an invitation was for.

- An optional email field on the invite form. When filled, the app sends the
  link with the household name and the inviter's name, and stores the address
  (not as a login, just as a label) on the invitation.
- "Add by email" for an address without an account turns into "send an
  invitation" instead of an error.
- The members page lists pending and accepted invitations with the address or
  "link", who sent it and when it was used, and a "send again" for pending
  ones.
- Tricky: anyone who can invite could use the app to mail strangers. Owners
  only (already `MANAGE`), a rate limit per household, and a plain mail
  without links other than the invitation.
- Tricky: the invitation is not tied to the address. Whoever opens the link can
  accept it, as today. Tying it to the address needs verified addresses
  (item 20); without that, the address is only a label.
- Tests: functional tests for sending (`assertEmailCount`), the address on the
  members page, the rate limit, and the existing-account path still adding
  directly.

### 20. Verified email addresses

Sign up accepts any address, and nothing checks it. The morning email then goes
to whatever was typed, which is a way to send mail to a stranger every day,
and a typo locks the user out once password reset (item 4) exists. The email
cannot be changed at all after sign up: `ProfileType` only has the display
name and the digest checkbox.

- `symfonycasts/verify-email-bundle` signs a confirmation link with the app
  secret; no table needed. `isVerified` on `User`.
- Unverified accounts can use the app, but get no morning email and cannot be
  found by "add by email" until they confirm. A banner offers to send the link
  again.
- Changing the address on the Account page: the new address gets a
  confirmation link, the old one keeps working until it is confirmed, and the
  old address gets a "your address was changed" notice.
- The demo seed marks its accounts as verified; the migration marks existing
  accounts as verified too, so nobody loses their mail on upgrade.
- Tricky: `User::getUserIdentifier` is the email, and remember-me cookies hold
  it. Changing it signs out other devices; say so on the page.
- Depends on item 4 for the mail templates and the throttling setup.
- Tests: functional tests for sign up without mail until confirmed, the link,
  an expired or tampered link, and changing the address.

### 21. Closing sign-ups on a private instance

Anyone who can reach the app can create an account at `/register`. Most
people run this for one home, behind their router or on a small server, and
an open sign-up page on a public address invites spam accounts that use the
morning email. There is also no throttle on `/register` at all.

- An environment variable `REGISTRATION` with `open` (today's default),
  `invite` (sign up only through an invitation link) and `closed`. The login
  page hides the sign-up link when it is not open, and `/register` answers 404
  for `closed`.
- With `invite`, `InvitationController::show` keeps working for people without
  an account: the saved target path already sends them back to the invitation;
  the register page checks that such an invitation is in the session.
- A rate limit on `/register` per IP, like the login throttling in
  `security.yaml`.
- Tricky: the first account. With `closed`, an empty instance has no way in.
  A console command `app:user:create` covers that, and is useful anyway.
- Tests: functional tests for each mode, the invitation path with `invite`,
  the throttle, and the command.

### 22. Named API tokens with scopes

Each user has one API token (`User.apiTokenHash`), with all the rights of the
user in every household. Giving a token to a Home Assistant script means
giving it the right to mark tasks done everywhere, and replacing it for one
script breaks all the others.

- An `ApiToken` entity: `user`, `name`, `tokenHash`, `scope` (`read` or
  `write`), an optional `household`, `createdAt`, `lastUsedAt`,
  `expiresAt`. The migration moves today's token into it as "Default", write,
  all households.
- `ApiTokenHandler` looks up the new table and puts the scope into the token's
  attributes. A small voter or a check in `ApiController` refuses `POST`
  routes for read tokens (403 with "This token is read-only") and other
  households for a token bound to one.
- The Account page lists tokens with their last use and a revoke button per
  token.
- Tricky: `lastUsedAt` on every request is a write per call. Write it at most
  once a minute.
- Tricky: the rate limit is keyed on the `Authorization` header
  (`ApiRateLimitListener`), so per token already. Keep it that way.
- Tests: functional tests in `tests/Functional/Api/` for a read token refused
  on `done`, a household-bound token refused on another household, revoking
  one token keeping the others, and the migrated token still working.

### 23. Writing plants, rooms and schedules by API

The API can read plants and act on tasks, but cannot create or change
anything else, so a script cannot add a plant or change an interval. And
`docs/api.md` says `type` is one of `water`, `fertilize`, `repot`, while the
app also has `mist`.

- `POST /api/plants`, `PATCH /api/plants/{id}`, `POST
  /api/plants/{id}/archive`, `GET|POST /api/rooms`, `PATCH /api/schedules/{id}`
  (interval, seasonal, notes, next due date) and `GET /api/upcoming` using
  `UpcomingPlanner`.
- Validation goes through the same entity constraints the forms use, mapped to
  the 422 shape in `ApiExceptionListener` with a field-by-field `errors`
  object.
- Creating a plant runs `ScheduleFactory::createDefaults`, as the web form
  does.
- Fix the `type` list in `docs/api.md` in the same pull request.
- Tricky: `PATCH` with partial JSON and Symfony forms do not mix well
  (missing fields are cleared unless `submit($data, false)`). Use the
  serializer or plain setters, not the web forms.
- Tricky: write endpoints need `Permission::EDIT`, not `CARE`, and respect the
  token scope from item 22.
- Tests: functional tests per endpoint: create, validation errors, permission
  per role, read-only token refused.

### 24. Importing plants from a spreadsheet

Someone with forty plants starts by typing forty forms, picking species and
rooms one by one. People moving from another app or from a spreadsheet have a
list already.

- An import page (owners and editors) that takes CSV with the columns name,
  species, room, light, acquired, notes, and optionally watering and
  fertilizing intervals.
- A preview step: each row with the matched species (by common or scientific
  name, through `SpeciesRepository::search`), the room (created when missing)
  and any problem, before anything is saved. Then one transaction.
- Parsing and matching in a plain `src/Import/PlantCsvReader.php`, so it can be
  unit tested with odd files.
- Tricky: spreadsheets export with `;` in much of Europe and with a BOM.
  Detect the separator from the header line and strip the BOM (the journal
  export writes one).
- Tricky: a plant without a known last watering is due today, as in
  `ScheduleFactory`. Importing forty plants puts forty watering tasks on today.
  Offer "spread the first watering over the next week".
- Tricky: size. Cap the file at a few hundred rows and a few hundred KB.
- Tests: unit tests for the reader (separators, BOM, quotes, unknown species,
  bad dates); functional tests for preview and import and for a caretaker
  being refused.

### 25. Export with photos

`DataExport::forUser` writes everything as JSON, but photos appear only as
file names, which mean nothing outside this instance. The journal CSV
(`journal_export`) says "yes" or "no" for the photo. Leaving the app means
leaving the photos behind.

- `/account/export.zip`: the JSON from `DataExport` plus a `photos/` folder
  with every photo of the user's households, the JSON pointing at the files by
  relative path.
- Stream the zip instead of building it in memory or a temp file per request.
  The `zip` extension is already in the image and CI; `ZipStream-PHP` would
  avoid holding the archive at all.
- Tricky: a household with years of photos is hundreds of MB. With item 5
  photos are smaller, but the export should still stream and not have a PHP
  time limit that kills it halfway (`set_time_limit` for this route only).
- Tricky: the export covers households where the user is a viewer too, as
  today. Photos of other members' journals are part of that; it matches what
  the user can see in the app.
- Tests: functional test that the zip contains the JSON and the photos of a
  test household and nothing of another household.

### 26. Plant labels with QR codes

With twenty plants on three shelves, finding the right one in the app takes
longer than watering it. A small label in the pot that opens the plant's page
is a common wish, and printing is already part of the app (the care sheet).

- A "Labels" page that prints a sheet of small labels: plant name, species,
  room and a QR code with the absolute URL of the plant page.
- QR codes generated on the server as SVG with `endroid/qr-code` or
  `chillerlan/php-qrcode`, both plain PHP without services.
- The plant page opened from a label (a `?from=label` parameter, or just the
  normal page) shows the open tasks of that plant at the top with the "Done"
  buttons, so scanning and tapping is two steps.
- Tricky: the URL needs a login. Fine for members; a sitter (item 9) would get
  the login page. With item 9 the label could point at the sitter view for
  that plant when opened with a sitter link in the same browser; I would leave
  that out of the first version.
- Tricky: `DEFAULT_URI` decides the host in the code. An instance reached
  under two names (LAN address and public name) prints the one in the config.
- Tests: functional test that the labels page shows one code per active plant
  and only for the current household; a unit test that the code decodes back
  to the URL is overkill, a snapshot of the SVG is not useful either.

### 27. Pot, soil and repotting details

Repotting is a reminder every 18 months by default, and when it is done the app
only knows that it happened. The pot size, whether it has a drainage hole,
the soil mix and whether the plant is in a self-watering pot all change how
often it needs water, and people look them up at the next repotting.

- Fields on `Plant`: `potDiameterCm`, `potMaterial` (plastic, terracotta,
  ceramic, glass, other), `hasDrainage`, `selfWatering`, `soil` (free text).
- When a repot task is marked done, ask (optionally) for the new pot size and
  soil, and log the change in the journal: "Repotted from 12 cm into 17 cm".
- The plant page and the care sheet show the pot.
- Tricky: it is tempting to adjust the watering interval from the pot
  (terracotta dries faster). That is guesswork; show a hint at most, and leave
  the numbers to item 35.
- Tests: functional tests for the form fields and the journal line written at
  repotting.

### 28. Where a cutting came from

`Propagator::propagate` copies the parent's species, room and schedules and
writes "Propagated from Monty on 3 May" into the notes and a journal entry on
the parent. The link is only text: renaming the parent breaks it, and there is
no list of the cuttings a plant gave.

- Data: `parent` (nullable, `onDelete: SET NULL`) on `Plant`.
- The plant page shows "Cutting of Monty" with a link and a "Cuttings" list
  with their status (active, archived because given away, died).
- `ArchiveReason` already has reasons; "given away" is the typical end of a
  cutting, so the list can show who has the others if the note says.
- Tricky: plants in different households. A cutting given to a friend's
  household could keep the link, but that leaks the parent's name into another
  household. Keep the link within one household only.
- Tests: unit test in `PropagatorTest` that the parent is set; functional test
  for the lists on both pages.

### 29. Managing your own species

Households can add species (`SpeciesController::new`), but not change or
delete them: there is no edit route, so a typo in the scientific name stays,
and the unique constraint on `(household, scientificName)` blocks adding it
again correctly. The catalog has care defaults but no humidity or temperature
needs, which are what the misting schedule is about.

- Edit and delete for custom species (owners and editors). Deleting a species
  that plants use sets their species to null, which the foreign key already
  does (`onDelete: SET NULL` on `Plant.species`); the confirmation counts them.
- "Copy from the catalog": start a custom species from a built-in one to tweak
  the intervals for one's own flat.
- Optional `humidity` (low, medium, high) and a minimum temperature in
  `data/species.json` and on the entity, shown on the species page and the care
  sheet, and used to suggest a misting schedule for humidity lovers when a
  plant is added.
- Tricky: `app:species:import` runs on every container start and updates the
  shared catalog from `data/species.json`. It must never touch custom species
  (it matches on the scientific name with `household IS NULL` today, which
  should stay so).
- Tests: functional tests for edit, delete and copy with permissions; the data
  test in `tests/Unit/Catalog/SpeciesDataTest.php` checks the new fields.

### 30. Accessibility pass

The basics are in place: `lang`, a skip link, labels on forms, `aria-current`
in the navigation, live regions on task rows. Gaps I know of:

- The task type icons are emoji. `_task_row.html.twig` and `plant/show` hide
  them with `aria-hidden`, but `plant/index`, `plant/history` and the
  schedule page headings do not, so a screen reader reads "droplet Water
  Monty".
- After "Done" on the today list the row is removed and focus is lost; it
  should move to the next row's "Done" button or to the heading when the list
  is empty.
- The "Later" menu is a `<details>` element; it works with the keyboard, but
  the open state is not announced as a menu and Escape does not close it.
- Badges ("3 days late") rely on colour for urgency; the text is there, which
  is fine, but the contrast of `.badge-late` and the muted text in both colour
  schemes (`color-scheme: light dark`) needs checking.
- The photo timeline and plant cards need `alt` text that is more than the
  plant name: the journal note or the date.
- Approach: a pass with axe in the browser and a screen reader, fixed page by
  page, plus a short checklist in `docs/` for new templates.
- Tricky: no browser tests, so most of this cannot be tested automatically.
  Functional tests can at least assert `aria-hidden` on icons and `alt` on
  images in the rendered HTML with the DOM crawler.
- Tests: crawler-based assertions as above for the pages touched.

### 31. Running behind a reverse proxy

People put this behind Caddy, Traefik or nginx for HTTPS. `framework.yaml` has
no `trusted_proxies`, so behind a proxy every request seems to come from the
proxy's address: `ApiRateLimitListener` then counts all anonymous API calls
against one IP, the per-IP part of the login throttling is shared by every
visitor, so one person guessing passwords locks out everyone, and
`cookie_secure: auto` sees plain HTTP and sends cookies without the `Secure`
flag.

- `TRUSTED_PROXIES` and `TRUSTED_HEADERS` environment variables wired into
  `framework.yaml`, empty by default.
- A `docs/operations.md` section with a Caddy and an nginx example, the
  variables to set, and `DEFAULT_URI` with `https`.
- HSTS when the request is secure, in `SecurityHeadersListener`.
- Tricky: trusting `X-Forwarded-For` from anyone lets clients choose their own
  IP and dodge the throttling. Only trust the configured proxy addresses; the
  docs must say not to use `0.0.0.0/0` unless the app port is not reachable
  directly.
- Tests: functional test that with a trusted proxy configured the rate limit
  key uses the forwarded address, and without it the remote address.

### 32. Household activity log

With several people in a household, things change without anyone knowing who
did it: an interval edited, a plant archived, a member removed, a role
changed, settings switched to the southern hemisphere. Care has a history
(`CareEvent`); everything else does not.

- An `Activity` entity: household, user, action (a short string such as
  `plant.archived`, `schedule.updated`, `member.role_changed`), the subject's
  id and name at that moment, a small JSON detail (old and new values for
  edits) and a timestamp.
- Written from the controllers and `MemberManager`, through one
  `ActivityLog::record()` service. A Doctrine listener would catch every
  change but cannot tell a user's edit from a seed or a migration; explicit
  calls are clearer.
- An "Activity" page for owners, newest first, paged with `Page`.
- Tricky: people leave. `user` is nullable with `SET NULL`, and the display
  name is stored on the row so the log still reads well.
- Tricky: retention. A year of entries is fine; a cleanup in the scheduler
  after 12 months keeps the table small.
- Tests: functional tests that each recorded action shows up with the right
  detail, and that only owners see the page.

### 33. A Content Security Policy without inline code

`SecurityHeadersListener` sends a CSP with `script-src 'self' 'unsafe-inline'
data:`, because `importmap()` prints an inline import map and module script,
and the care sheet uses `onclick="window.print()"`. With `'unsafe-inline'` an
injected `<script>` would run, so the CSP is only half a defence.

- Nonces: generate one per request, add it to the CSP and to the importmap
  tags. `importmap()` accepts attributes for its script tags, so a Twig global
  with the nonce is enough; `nelmio/security-bundle` does the same if a bundle
  is preferred.
- Move `onclick` handlers into Stimulus controllers (`print_controller.js`),
  and check templates for other inline handlers or `style` attributes.
- The `data:` in `script-src` is there for CSS imports mapped to empty data
  modules; check whether AssetMapper still needs it once CSS is linked with
  `<link>` tags.
- Tricky: the photo route sets its own strict CSP (`default-src 'none';
  sandbox`) and the listener leaves existing ones alone; keep that.
- Tricky: a mistake breaks every page's JavaScript without a test failing.
  A functional test can parse the CSP header and check that every `<script>`
  in the rendered page carries the nonce.
- Tests: as above, for the base layout, the today page and the care sheet.

### 34. More useful stats

The stats page (`CareStats`) counts done and skipped events per type and per
week for the last eight weeks, and the five most cared-for plants. It answers
"how much did we do", not the questions people have: are we on time, which
plants are always late, who does the watering.

- On time: the share of done events that happened on or before the due date.
  This needs the due date at the time of the event, which is not stored; add
  `dueOn` to `CareEvent`, written in `CareTasks::log`. Old events stay
  without it and are left out of that number.
- Per plant: average days late, so the plant that always gets forgotten
  stands out.
- Per member: who did what, for households that share chores. Opt-in per
  household; not every flat wants a chore leaderboard.
- A range choice (8 weeks, 6 months, a year) instead of the fixed `WEEKS`.
- Tricky: `CareStats` loads every event of the range as entities and counts in
  PHP. For a year of a big household that is tens of thousands of objects;
  move the counting into `GROUP BY` queries in `CareEventRepository`.
- Tests: unit tests for the on-time rule; functional tests for the page with
  a range and for the per-member section being off by default.

## Maybe

### 35. Intervals that learn from what you do

The base interval comes from the species and stays until someone edits it.
In practice the real rhythm shows in the history: a pothos marked "Did it
yesterday" three times in a row, or checked and found still wet every time
(item 11), is on the wrong interval. Some plant apps adjust the interval on
their own; I am not sure that is a good idea.

- A suggestion, not an automatic change: when the last five or six watering
  events of a schedule disagree with the interval by more than a day or two on
  average, the plant page says "You water Monty every 9 days on average, the
  schedule says 7. Use 9?" with one click to accept.
- The calculation in a plain `src/Care/IntervalSuggester.php`: take the gaps
  between done events, undo the seasonal factor of the season each gap fell in
  (so a winter gap of 11 days counts as 7), drop outliers such as a holiday,
  and take the median. "Checked, not needed" events lengthen the gap they fall
  in.
- Tricky: seasons. Without dividing out the factor, every autumn would
  suggest longer intervals and every spring shorter ones, and accepting both
  would undo the seasonal logic.
- Tricky: late is not the same as needed less. If a plant is watered every 10
  days because people forget, the plant may be suffering. Only
  "checked, not needed" and done-early events are real evidence; plain lateness
  is not. This is the main reason this item is a Maybe.
- Depends on items 11 and 12: without "checked" events and without corrected
  history the input is too noisy.
- Tests: unit tests for the suggester with seasonal gaps, outliers and too few
  events.

### 36. Push notifications

Email is the only reminder. Many people would rather get a notification on the
phone, and installing the site to the home screen is close: the layout is
already responsive and has a theme colour.

- A web app manifest and a small service worker, so the site can be installed.
  Nothing offline at first; the service worker only receives pushes.
- Web Push with VAPID keys generated per instance (`minishlink/web-push` in
  PHP), sent from the same hourly job as the email and with the same rules
  (item 17): one notification per user per day with "5 things for your plants
  today".
- Data: a `PushSubscription` entity per browser (endpoint, keys, user,
  created, last success), managed from the Account page.
- Tricky: push goes through the browser vendor's push service (Google, Mozilla,
  Apple). That is not a service the instance owner signs up for, and the
  payload is encrypted, but it is a third party seeing that a notification
  was sent. Make it opt-in per device and say so.
- Tricky: iOS only delivers web push to sites added to the home screen, and
  subscriptions expire or are revoked silently. Remove a subscription after
  the push service answers 404 or 410.
- Tricky: the service worker must be served from the site root with the right
  scope, and the CSP (item 33) needs `worker-src 'self'`.
- Depends on item 17 for the per-user timing rules.
- Tests: unit tests for building the payload; functional tests for storing and
  deleting subscriptions and for the job removing a gone subscription, with
  the push client replaced by a fake.

### 37. Readings from soil sensors

`docs/api.md` names home automation as a use of the API. Cheap soil moisture
sensors (Xiaomi Mi Flora and similar, read through Home Assistant or ESPHome)
already know when a pot is dry. Feeding their readings in would turn a guessed
interval into a measured one.

- `POST /api/plants/{id}/readings` with `moisture` (percent), optionally
  `light` and `temperature`, and a timestamp. A `Reading` entity, kept for a
  limited time (90 days) and summarised per day afterwards.
- Per plant, a "water below N%" threshold. When a reading falls below it, the
  water task becomes due today, whatever the schedule says; the schedule
  stays as the fallback when the sensor goes quiet.
- A small chart on the plant page of the last 30 days.
- Tricky: sensors drift and die. If no reading came for two days, say so on the
  plant page and fall back to the schedule.
- Tricky: volume. A sensor every 15 minutes is about 100 rows a day per plant;
  for ten plants that is fine, but the summary step keeps it from growing.
- Tricky: the API token for a sensor bridge should be write-only to readings,
  which is a scope that item 22 would need to know about.
- Depends on item 22.
- Tests: functional tests for posting readings, the threshold making the task
  due, the staleness rule; unit test for the daily summary.

### 38. Translations

All text is English and written into the Twig templates, PHP flash messages
and form labels. The households in the demo are in Berlin. Many people would
use a plant app in their own language, and the `intl` extension is already in
the image.

- Symfony's translator with XLIFF or YAML catalogues, the user's language on
  the Account page and `Accept-Language` for signed-out pages.
- Dates through `format_date` (twig/intl-extra) instead of fixed patterns like
  `'j M Y'`, so month names follow the language.
- Species names: the catalog has a common English name; other languages need
  their own common names in `data/species.json`.
- Tricky: plural rules ("1 day late", "3 days late") are written as
  ternaries in templates and PHP. ICU message format handles them, but every
  string has to be touched.
- Tricky: the morning email is rendered in the job, without a request; it needs
  the recipient's language set explicitly.
- Undecided: whether one more language is worth keeping every string in
  catalogues forever. I would only do it with someone who maintains the second
  language.
- Tests: a functional test that a page renders in the second language, and a
  lint step (`lint:xliff` or `translation:extract --dump-messages`) that finds
  untranslated strings.

### 39. Two-step sign in

Accounts are protected by a password only. The data is not sensitive in the
way bank data is, but a household's photos show the inside of someone's home,
and an account can invite people and delete households.

- TOTP codes from an authenticator app, with `scheb/2fa-bundle` and its TOTP
  provider, plus ten one-time recovery codes. Opt-in per user on the Account
  page.
- The API stays token-based and is not affected; the calendar feed stays
  token-based too.
- Tricky: losing the phone. Recovery codes are the way back; password reset
  (item 4) must not skip the second step, or it becomes the way around it.
- Tricky: remember-me cookies. Decide whether a trusted device skips the code
  for 30 days; the bundle supports trusted devices.
- Tests: functional tests for enabling, signing in with a code, a wrong code,
  a recovery code being single use, and reset still asking for the code.

### 40. Large households

The app is built for a home with a few dozen plants, and the queries fit that.
Some people have hundreds (collectors, offices, a small shop), and several
places would slow down:

- `CareStats::forHousehold` loads every event in the range as an entity (see
  item 34).
- The calendar feed loads every schedule of every household of the user with
  its plant and room, on every poll from every calendar app.
- The plant list (`PlantRepository::findActive`) loads all active plants with
  their schedules, with no paging; the search is a `LIKE` on name and species.
- `SendDigestCommand` asks for memberships twice per user (`isDue` and
  `sendTo`) and runs `findDue` per household per user, so a household with
  five members is queried five times an hour.
- `DataExport` runs one query per plant for journal entries and one for care
  events.
- Approach: a fixture with a household of 1,000 plants and two years of
  history in a functional test, and assertions on the number of queries with
  the existing `tests/Support/Doctrine/QueryCounter.php`, not on time. Fix
  what the counts show: paging on the plant list, `GROUP BY` for stats, a
  conditional `ETag` for the feed, one `findDue` per household per job run.
- Tricky: SQLite in tests and MySQL in production plan queries differently.
  Query counts are the same on both; anything about indexes needs a check
  against MySQL in the `migrations` job.
- Undecided: whether anyone runs this at that size. I would only do the parts
  that also make small households simpler, and keep the fixture test.
- Tests: the query-count tests above.
