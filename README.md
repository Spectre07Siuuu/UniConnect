# UniConnect

UniConnect is a PHP + MySQL student community platform built for campus collaboration. It includes authentication, a social feed, notes sharing, chat, notifications, class routine support, and academic utility tools in one app.

## Features

- Student login and signup flow
- Dashboard with posts, likes, and comments
- Notes upload and discovery
- Real-time style chat UI and messaging endpoints
- Notifications panel
- Profile and routine support
- Academic tools like CGPA and tuition fee calculators

## Tech Stack

- PHP 8
- MySQL / MariaDB
- PDO for database access
- HTML, CSS, and JavaScript
- XAMPP for local development

## Project Structure

```text
.
|-- auth/                  Authentication pages and handlers
|-- config/                Shared configuration such as DB connection
|-- dashboard/             Main logged-in application area
|   |-- academic_modules/  Calculator/tool partials
|   |-- chat/              Chat UI, JS, and PHP handlers
|   |-- css/               Shared dashboard styles
|   |-- javascript/        Shared dashboard scripts
|   |-- notes/             Notes upload/download handlers
|   |-- notifications/     Notification endpoints
|   `-- posts/             Feed endpoints for posts/comments/likes
|-- database/              SQL dump and routine/reference files
|-- images/                App images and currently tracked media
|-- uploads/               User-uploaded files and note assets
`-- index.php              Redirects to auth entry
```

## Local Setup

### 1. Place the project in XAMPP

Keep the project under:

```text
C:\xampp\htdocs\uniconnect
```

### 2. Start Apache and MySQL

Use the XAMPP Control Panel and make sure both services are running.

### 3. Create the database

Create a database named `uniconnect`, then import:

```text
database/uniconnect.sql
```

### 4. Verify database settings

The current local config is in `config/db_connect.php` and expects:

- host: `localhost`, `127.0.0.1`, or `::1`
- username: `root`
- password: empty string
- database: `uniconnect`

Update that file if your local MySQL credentials differ.

### 5. Open the app

Visit:

```text
http://localhost/uniconnect
```

## Git Notes

The repository now ignores local machine noise and future runtime-generated files such as:

- uploaded note files
- uploaded group chat attachments
- generated post images
- generated profile pictures

Already tracked files stay in Git until you intentionally remove them from version control.

## Recommended Cleanup Next

These are the safest structural improvements to make next without breaking behavior:

1. Move hardcoded DB credentials out of `config/db_connect.php` into environment-based config.
2. Separate source assets from runtime assets so `images/` only stores app-owned files and uploads live only under `uploads/`.
3. Split large mixed PHP pages like `dashboard/index.php` into smaller includes for header, sidebar, feed, and widgets.
4. Add a `docs/` folder for screenshots, schema exports, and project notes instead of mixing them into runtime directories.
5. Standardize naming so handlers follow one convention like `fetch_*`, `process_*`, and page controllers consistently.

## Current Caveats

- `database/database_schema.pdf` is intentionally ignored.
- Upload directories currently contain tracked sample/generated files from earlier work.
- The app uses direct PHP page routing rather than a framework structure.

## License

Add your preferred license here if you plan to share or collaborate publicly.
