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

The app now supports environment-based database settings through `.env` with local XAMPP-friendly fallbacks.

You can copy `.env.example` to `.env` and adjust:

- `UNICONNECT_DB_HOSTS`
- `UNICONNECT_DB_NAME`
- `UNICONNECT_DB_USER`
- `UNICONNECT_DB_PASS`
- `UNICONNECT_DB_CHARSET`

If `.env` is missing, `config/db_connect.php` falls back to the current local defaults:

- host: `localhost`, `127.0.0.1`, or `::1`
- username: `root`
- password: empty string
- database: `uniconnect`

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

