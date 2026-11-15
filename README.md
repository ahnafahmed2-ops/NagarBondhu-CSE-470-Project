# NagarBondhu – Smart Urban Problem Reporting & Management System

CSE 470 project. **Implemented so far: F1 – Multi-Category Civic Issue Reporting, F4 – Complaint Tracking and Status History, F5 – Community Issue Verification and Voting, F7 – Automatic Priority and Severity Assessment**, plus register / login / logout (supporting functionality, not counted as a feature).

## Tech stack
| Layer | Technology |
|---|---|
| Backend | **Laravel 12** (PHP 8.2+), MVC |
| Database | SQLite by default; MySQL by editing `.env` |
| Frontend | Blade templates containing plain HTML, CSS and JavaScript (`public/css/style.css`, `public/js/app.js`) |

Approved by the section faculty (Discord, 14 Nov 2026: plain PHP not allowed, Laravel allowed; email: no Python frameworks, no MERN).

## Setup
Requirements: PHP 8.2+ (extensions: `pdo_sqlite`, `mbstring`, `fileinfo`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`) and [Composer](https://getcomposer.org).

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite          # Windows (cmd): type nul > database\database.sqlite
php artisan migrate --seed              # creates tables, the 7 report categories and the demo admin
php artisan storage:link                # makes uploaded photos viewable
php artisan serve                       # http://localhost:8000
```

Run the tests: `php artisan test`

Demo admin (created by the seeder): **admin@nagarbondhu.test** / **password**. Every account you register yourself is a citizen; the `role` column is never mass-assigned, so nobody can make themselves an admin from a form.

To use MySQL instead: create an empty database, then in `.env` set `DB_CONNECTION=mysql`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and run `php artisan migrate --seed`.

If photo uploads fail, raise `upload_max_filesize` and `post_max_size` in `php.ini` to at least 6M (PHP's default is 2M; the app allows 5 MB).

## MVC map (files added on top of the Laravel skeleton)
```
routes/web.php                                  URL -> controller
app/Models/        User, Category, Report, ReportVote, StatusHistory   Eloquent models
app/Http/Controllers/  HomeController, AuthController, ReportController,
                   CommunityController, VoteController (F5), TrackingController (F4),
                   Admin/ReportController (F4 status changes, F7 override)
app/Http/Requests/ReportRequest.php             validation rules for F1
resources/views/   layouts/app, home/, auth/, reports/   Blade views (HTML)
database/migrations/  users (+phone, role), categories (+severity), reports (+tracking_code, priority),
                   report_votes, status_histories
database/seeders/  CategorySeeder (with severity), AdminSeeder
tests/Feature/     ReportTest (F1), TrackingTest (F4), CommunityVoteTest (F5), PriorityTest (F7)
```

## F1 behaviour
A logged-in citizen can create, list (with category filter), view, edit and delete **their own** reports. Each has a category, title, description, location text, optional ward and an optional photo (JPG/PNG/WebP, ≤ 5 MB). New reports start as `Submitted`; edit/delete is allowed only while the status is `Submitted`. Other citizens' reports return 404.

## F4 behaviour – tracking and status history
Every new report gets a tracking code such as `NB-261115-K7Q2M` and a first timeline entry. Statuses follow a fixed workflow, defined in `Report::TRANSITIONS`:
Submitted → Under Review → Verified → Assigned → In Progress → Awaiting Verification → Resolved (→ Reopened → Assigned), with Rejected and Merged as end states. The admin (`/admin/reports`) moves a report to one of the allowed next statuses, with an optional note. Any other move is refused. Each change is stored in `status_histories` with time, actor and note. The owner sees the timeline on their report page. Anyone, even without logging in, can open `/track?code=...` (case-insensitive) and see the status and timeline without the reporter's or admin's name.

## F5 behaviour – community voting
`/community` lists other citizens' open reports, with a ward/area and category filter, highest priority first (it filters by ward text because this version stores no map coordinates). On a report, a citizen can vote "This affects me too", "I saw it – still there" or "It is already fixed", and can take a vote back. Rules: no voting on your own report (403), each type counts once per user, "still there" and "already fixed" replace each other, and resolved/rejected/merged reports refuse votes. The reporter's name is never shown.

## F7 behaviour – automatic priority
Recalculated every time a report is saved or voted on (`Report::calculatePriorityScore`):
score (0–100) = category severity (1–5) × 12 + min(20, 6 × log2(1 + "affects me" votes)) + "still there" votes × 2 (max 10) + 10 if the title or description mentions a hazard (child, school, hospital, accident, electric, night… and Bangla words such as স্কুল, দুর্ঘটনা).
Critical ≥ 70, High ≥ 50, Medium ≥ 30, otherwise Low. Example: a broken streetlight with no votes scores 24 (Low); an open manhole near a school with 3 neighbours affected scores 82 (Critical). The admin can override the level with a reason. The override is logged in the timeline and stays when votes change, until the admin returns it to automatic.
