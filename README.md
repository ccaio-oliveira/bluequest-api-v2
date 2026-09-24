# BlueQuest — API

REST API for BlueQuest, a group fitness challenge app. It handles authentication, challenges, tasks, completions with photo proof, rankings, invites and history.

iOS client: [bluequest-ios](https://github.com/ccaio-oliveira/bluequest-ios)

## Tech stack

- **PHP 8.3+** and **Laravel 13**
- **MySQL**
- **Laravel Sanctum** for token authentication, plus Google sign-in

## Design highlights

- **Domain rules live in `app/Domain`**, as small classes with no HTTP or database concerns: challenge state, occurrence state, completion rules, streaks and task validity. Controllers stay thin.
- **Occurrences are computed on demand** from each task's recurrence rule instead of being stored, so there is nothing to backfill when a challenge or a task changes.
- **Every challenge has its own timezone.** "Today", deadlines and expirations are resolved in the challenge's timezone, never the server's.
- **Task edits don't rewrite history.** Editing a task that has already produced occurrences closes the current version and starts a new one from the next day, so past days keep the rules they were played under.
- **Photos are stored as paths.** The public URL is built from the incoming request, so the same record works across local, tunneled and production hosts.

## Running locally

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set your MySQL credentials in `.env`, then:

```bash
php artisan migrate
php artisan storage:link
php artisan db:seed --class=DemoSeeder
php artisan serve
```

`DemoSeeder` creates a sample challenge with users, tasks and completions. It can be run more than once.

Completion photos are uploaded at up to 8 MB, so PHP's `upload_max_filesize` and `post_max_size` must allow at least that.
