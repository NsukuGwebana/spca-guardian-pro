# Production upgrade (November 2026, part 2)

## Security
- **Prepared statements everywhere.** Every query that uses visitor or staff input goes through `q()`, which binds values instead of pasting them into SQL.
- **CSRF protection.** Forms must come from this site (Origin/Referer check), and staff forms, AJAX calls and `?action=` links carry a per-session token that is added automatically.
- **XSS escaping.** Database values are printed through `e()`.
- **Password reset by email.** `forgot_password.php` emails a one-time link that expires in an hour; only a SHA-256 hash of it is stored. Staff accounts now have an email address.
- **Login lockout.** Five wrong passwords for a username, or twenty from one IP address, lock sign-in for 15 minutes.
- Receipt and thank-you emails, and removing users, only work from a submitted form (POST), never from a link. You can't remove yourself or the last administrator.

## Error handling
- `bootstrap.php` is loaded by every page. Database and PHP errors are written to `logs/error.log` (blocked from the web), and visitors see a plain-language page instead of SQL errors. AJAX endpoints get JSON errors.
- Pop-up `alert()` messages after saving were replaced with messages on the page.
- Adoption and foster forms check every answer before saving (SA ID check digit, phone number, email), show all problems at once and keep what the visitor typed. The date of birth is read from the SA ID number.
- Duplicate open applications for the same animal are refused.

## Workflows
- **Adoption pipeline** (`admin_adoptions.php`): Pending → Home visit → Approved → Adopted, or Rejected at any step. Illegal jumps are refused, a home visit needs a date, and handover needs a tick to confirm the ID document was checked. The applicant is emailed at every step, the animal's status follows the application, and the kennel is freed on adoption.
- Applicants get a confirmation email, and the public tracking page shows a progress bar with what happens next.
- **Activity log** (`activity_log.php`): sign-ins, adoption and foster decisions, edits, deletions, exports and password resets, with filters and CSV download.
- Fixed: approving a foster set the animal to "Pending Foster", which isn't a valid status, so approval failed.

## Analytics
- **Management dashboard** (`analytics.php`): average days to adoption, kennel occupancy, open applications (and how many are overdue), approval rate, average length of stay, donations; charts of adoptions and donations per month, cruelty reports by area, and animals by species. Every chart has a table view and a CSV download.
- Animals, donations, users and adoptions lists have search, filters, pagination and **Download CSV** (opens in Excel, protected against formula injection).

## Code structure
| Old name | New name |
|---|---|
| `1.php` | `followup_schedule.php` |
| `2.php` | `followup_visits.php` |
| `2.2.php` | `adoption_history.php` |
| `4.php` | `email_contacts.php` |
| `z.php` | `volunteerDashboard.php` |
| `track_.php` | `track_volunteer.php` |
| `admin dashboard.php` | `adminDashboard.php` |
| `manual allo.php` | `kennel_allocation.php` |

- One database connection (`db()`), one staff header (`partials/topbar.php`), and shared rules in `lib/` (workflow, validation, listing). Repeated connection code was removed from 35 pages.
- Replaced pages moved to `_unused/`: `pending_adoptions.php`, `past_adoptions.php`, `SearchUser.*` (search is now built into the users list).

## Tooling
- PHPUnit tests (`tests/`), GitHub Actions CI on PHP 8.2 and 8.3, `Dockerfile` + `docker-compose.yml` (app, MySQL and a mail catcher), and an ER diagram in the README.
- Database changes are in `database/migrations/`.

# Fixes and improvements (November 2026)

Every page was loaded as an Administrator, a Vet and a Volunteer against a copy of the project database, and the links and SQL queries were checked against the real schema.

## Security

- **Passwords removed from the code.** The database password and the Gmail app password were written into about 50 files. They now live in one `config.php`, which Git ignores. `config.example.php` shows what to fill in.
- **Staff pages now need a login.** About 80 staff pages (user lists, adoption applications, donations, medical records) could be opened by anyone who knew the address. Each now checks the session through `Session_handler.php` and sends visitors to the login page.
- **No more self-made Administrators.** The public sign-up form let anyone choose the Administrator role. Anyone can still sign up as a Volunteer, but only a signed-in Administrator can create Administrator or Vet accounts.
- **Each role only sees its own pages.** Vets can open the vet pages and the animal list, Volunteers their activity and event pages, and Administrators everything. Anyone else gets a clear "You don't have access" page with a link back to their own dashboard.
- **Login hardened.** The session ID is renewed on login, and wrong usernames and wrong passwords get the same message, so usernames can't be guessed.
- **Delete user** now uses a prepared statement instead of putting the ID straight into the SQL.

## Bugs fixed

| Where | Problem | Fix |
|---|---|---|
| Login (`2login2Sysdev.php`) | HTML was sent before the redirect, so login failed on servers without output buffering | All PHP runs first; the page HTML is only sent when there's a message to show |
| Login, medical treatments, animal list | Linked to `VetDashboard.php`, but the file is `vetDashboard.php` (Linux servers are case-sensitive) | Corrected the file name |
| Vet dashboard | Crashed because it queried a `vaccinations` table that doesn't exist | Counts open vaccination treatments from the `treatments` table |
| Adoption appointments | Queried the wrong table (`appointments` instead of `appointment`) and a missing `id` column | Uses `appointment` and `appointment_id` |
| Medical records update | Saved treatments to a column that doesn't exist (`appointment_id`) | Uses `app_id`, which links treatments to appointments |
| Contact Us form | Posted to `message.php`, which didn't exist, so messages were lost | Added `message.php`, which validates the form and saves to `message_submissions` |
| Delete user | Syntax error, so the page couldn't run at all | Rewritten |
| Animal list, sidebar menus, vet dashboard | Links to `regA.php` and `regA.PHP`, but the file was `regA.PHP` | Renamed the file to `regA.php` and fixed the links |
| Several pages | Broken links: `dashboard.html`, `dashboard.php`, `login.html`, `ForgotPassword.html`, `EditAnimal.php`, `RegisterAnimal.html`, `view_appointments.php`, `report_cruelty.html`, `admind.php`, `community.html`, `saveProcedure.php` | Pointed each at the page that exists |
| About Us menu | Linked to a Track Volunteer page that was never built | Removed the link |
| Log procedure | Showed "Animal is already healthy" on every visit and read a name it never loaded | Loads the animal's name and removed the stray message |
| Back to Dashboard (40 pages) | Always went to the Administrator dashboard, even for Vets and Volunteers; the animal list's Back went to the Vet dashboard for everyone | New `dashboard.php` sends each user to their own dashboard; every Back to Dashboard link uses it |
| Medical records update | Back button always opened animal #53 | Returns to the animal you were editing |
| Homepage, dashboards | Pictures linked to `images/` but the folder is `Images/`, so they vanished on Linux hosting | Fixed the folder name in every link |
| Animal lists, profiles, adoption and foster pages | Animals without a photo showed "No Image" text in five different styles, and broken photos showed a broken-image icon | One shared "No photo yet" placeholder everywhere, also used if a photo file is missing |
| Animal profile, PDF export, medical update, save investigation | Errors when opened without an animal or report selected | Send the user back to the right list instead |

## Simpler forms

| Form | Before | After | Notes |
|---|---|---|---|
| Adopt / foster an animal | 17 fields | 7 + 2 tick boxes | Keeps the SA ID or passport number; the ID document is checked at the home visit instead of uploaded |
| Log volunteer hours | 13 fields | 8 | Hours are worked out automatically; supervisor fields removed |
| Donate | 9 fields | 5 | Staff-only donor ID removed from the public form; date is set to today |
| Report cruelty | 8 fields | 6 | Date is set to today; witnesses can go in the description |
| Register an animal | 15 fields | 8 | Weight, width and height replaced by one Size choice (Small, Medium, Large) that still drives kennel allocation |
| Edit an animal | 13 fields | 8 | Kennel, size and intake date are kept as they are |

Fields that left a form are saved as empty values, so the database didn't need to change.

## One consistent look

- `spca-theme.css` gives every form and list page the same font (Poppins), colours, inputs, buttons, tables and messages.
- Every staff page has the same top bar with **My dashboard** and **Log out**.
- Every Back button looks the same, says where it goes ("Back to dashboard", "Back to animals", "Back to home"), and goes to a fixed page instead of the browser's previous page.
- Page titles follow one pattern: "Page name | SPCA Guardian Pro".
- Reports open on the latest year that has data instead of an empty current year.

## Tidy-up

- 19 old or duplicate pages that nothing linked to (for example `regAold.php`, `tryVoLUnTEER.php`, `zz.php`) moved to `_unused/`.
- Added `README.md`, `database/schema.sql` (structure only, no personal data), `index.php` (opens the homepage) and `.gitignore`.
- Email settings (SMTP server, login, key and sender) now live in `config.php`, so the system can send through Brevo or any other email service without code changes.
- Homepage video shrunk from 21 MB (4K) to under 1 MB, so the page loads faster and fits free hosting limits.
