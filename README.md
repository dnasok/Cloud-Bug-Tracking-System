# Cloud Bug Tracking System

An academic full-stack prototype for reporting, classifying, and managing software bugs. The project pairs a responsive browser frontend with a PHP/MySQL API and includes a local mock mode so the core workflow can be demonstrated without a database or backend server.

## What It Demonstrates

- User signup, login, logout, and session validation
- Role-aware experiences for `user`, `developer`, and `admin` accounts
- Bug creation with title, description, severity, status, assignee, and optional screenshot
- Automatic category and priority classification from bug text
- Search and status/priority filtering on the dashboard
- Bug detail views with screenshot previews
- Privileged status updates, assignment changes, and deletion
- Admin/developer summary metrics for open, in-progress, and resolved bugs
- Local browser storage for demos, or MySQL persistence through the PHP API
- Local filesystem or Amazon S3 screenshot storage

## Technical Overview

The frontend is a set of HTML pages styled with Tailwind CSS via CDN and shared CSS. Vanilla JavaScript modules handle API communication, authentication state, route guards, safe HTML rendering, date formatting, navigation, and mock data.

The backend is a collection of PHP endpoints using MySQLi and prepared statements. Tables are created or upgraded lazily when the API runs. Backend authentication stores the authenticated user in a PHP session, while the frontend API client can switch between backend requests and a local `localStorage` implementation.

Bug classification is intentionally lightweight and explainable: `classify_bug.php` normalizes the title and description, scores weighted keywords, and selects a category from `Security`, `Crash`, `Performance`, `UI`, or `General`. A second weighted score assigns `High`, `Medium`, or `Low` priority.

## Main Workflows

1. Sign in or create an account.
2. Review all submitted bugs from the dashboard.
3. Search by title or filter by status and priority.
4. Submit a bug and optionally attach an image.
5. Open the bug detail view to inspect classification, severity, assignment, and status.
6. As a developer or admin, update the workflow status, assign the bug, or delete it.
7. Review profile metrics or the privileged status summary dashboard.

## Project Structure

### Frontend

- `frontend/index.html`: Login entry point and mock-mode switch.
- `frontend/pages/signup.html`: Account registration.
- `frontend/pages/dashboard.html`: Searchable and filterable bug list.
- `frontend/pages/submit-bug.html`: Bug submission and screenshot preview.
- `frontend/pages/bug-detail.html`: Bug inspection and privileged actions.
- `frontend/pages/admin-dashboard.html`: Status summary for developers and admins.
- `frontend/pages/profile.html`: Current user information and bug metrics.
- `frontend/js/api-client.js`: Mock/backend API abstraction and bug operations.
- `frontend/js/auth.js`: Local auth state, route guards, and logout.
- `frontend/js/utils.js`: Shared validation, escaping, dates, and navigation.
- `frontend/css/styles.css`: Shared visual styles.

### Backend

- `backend/auth.php`: Signup, login, logout, and session endpoints.
- `backend/bug_backend_api.php`: JSON CRUD API for bug records.
- `backend/classify_bug.php`: Keyword-based category and priority scoring.
- `backend/db_functions.php`: MySQL connection and schema helpers.
- `backend/upload_screenshot.php`: Validated image upload to local storage or S3.
- `backend/inc/dbinfo.inc`: Database connection configuration.

The `docs/` directory contains the original project proposal and project report PDFs.

## Run the Frontend Demo

No PHP, MySQL, or Composer installation is required for the mock-mode walkthrough.

From the repository root, start a static server:

```powershell
python -m http.server 5500
```

Open [http://localhost:5500/frontend/index.html](http://localhost:5500/frontend/index.html) and leave **Use local mock mode** enabled.

Seeded demo accounts:

| Role | Username | Password |
| --- | --- | --- |
| Admin | `admin` | `admin123` |
| Developer | `dev` | `dev12345` |
| User | `user` | `user12345` |

Mock users and bugs are stored in browser `localStorage`. The URL query parameter `?mock=1` enables mock mode and `?mock=0` selects the backend mode; the selection is persisted for later requests.

## Run With the PHP/MySQL Backend

1. Serve the repository through Apache or another PHP-capable web server.
2. Create a MySQL database and provide the connection values as server environment variables. The tracked `backend/inc/dbinfo.inc` file contains placeholders only.
3. Open the frontend and disable mock mode.
4. The API creates the `users` and `bugs` tables as needed.

Database environment variables:

```text
DB_SERVER=localhost
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password
DB_DATABASE=bug_tracker_db
```

The primary backend operations are:

| Endpoint | Methods | Purpose |
| --- | --- | --- |
| `backend/auth.php` | `POST` | Signup, login, logout, and session checks via `action` |
| `backend/bug_backend_api.php` | `GET` | List bugs, filter by status/priority/category, or fetch one by `id` |
| `backend/bug_backend_api.php` | `POST` | Create a classified bug record |
| `backend/bug_backend_api.php?id={id}` | `PATCH` / `PUT` | Update a bug |
| `backend/bug_backend_api.php?id={id}` | `DELETE` | Delete a bug |
| `backend/upload_screenshot.php` | `POST` | Upload an approved image file |

### Screenshot storage

Local disk storage is the default and writes files to `backend/uploads/`. To use S3, install the AWS SDK and configure the server environment:

```bash
composer require aws/aws-sdk-php
```

Required variables:

```text
SCREENSHOT_STORAGE=s3
AWS_REGION=<your-region>
AWS_BUCKET=<your-bucket-name>
```

Optional variables include `AWS_S3_PREFIX`, `AWS_S3_ACL`, `AWS_S3_PUBLIC_BASE_URL`, `AWS_ACCESS_KEY_ID`, and `AWS_SECRET_ACCESS_KEY`. Without explicit access keys, the AWS SDK can use the runtime IAM role.

Uploads are limited to PNG, JPG, WEBP, and GIF files up to 5 MB.