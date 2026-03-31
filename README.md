# CSD3156-Team-Project-2

Cloud-Based Bug Tracking and Reporting System for Developers

## File Documentation

### Frontend

- `frontend/index.html`: Login entry page with mock-mode toggle and authentication flow.
- `frontend/css/styles.css`: Shared visual styles used by all frontend pages.
- `frontend/js/api-client.js`: API layer for auth and bug operations (mock mode + backend mode).
- `frontend/js/auth.js`: Authentication/session helpers and route guards for protected pages.
- `frontend/js/utils.js`: Utility helpers (validation, escaping, date formatting, navbar mounting).
- `frontend/pages/dashboard.html`: Main bug listing page with search and filter UI.
- `frontend/pages/admin-dashboard.html`: Admin/developer summary dashboard with status counts.
- `frontend/pages/bug-detail.html`: Bug detail page with update/delete actions for privileged roles.
- `frontend/pages/profile.html`: Basic profile page showing user info and system bug counts.
- `frontend/pages/signup.html`: New user registration page.
- `frontend/pages/submit-bug.html`: Bug submission page.

### Backend

- `backend/auth.php`: Authentication API (signup, login, logout, session).
- `backend/bug_backend_api.php`: JSON API for bug CRUD operations.
- `backend/classify_bug.php`: Rule-based bug category and priority classification helpers.
- `backend/db_functions.php`: Shared database connection and users table helpers.
- `backend/inc/dbinfo.inc`: Database configuration constants.

## Local Frontend Testing (No Backend Required)

You can test the full frontend flow locally using a built-in mock API mode (browser localStorage).

### 1) Start a simple local static server

From project root:

```powershell
python -m http.server 5500
```

Then open:

`http://localhost:5500/frontend/index.html`

### 2) Enable mock mode

On the login page, keep **Use local mock mode** checked.

Default test users:

- `admin / admin123`
- `dev / dev12345`
- `user / user12345`

### 3) Test flows

- Sign up a new account
- Login
- View dashboard bug list
- Submit a new bug
- Open bug detail page
- View profile/admin pages based on role

### 4) Switch to real backend later

- Uncheck **Use local mock mode** on login page
- Ensure backend endpoints are running and DB config is valid in `backend/inc/dbinfo.inc`

## Screenshot Upload Storage

The screenshot upload endpoint supports two storage modes:

- Local disk (default): files are stored in `backend/uploads/`
- AWS S3: files are uploaded to your S3 bucket

### Enable S3 mode

Set these environment variables for Apache/PHP:

- `SCREENSHOT_STORAGE=s3`
- `AWS_REGION=<your-region>`
- `AWS_BUCKET=<your-bucket-name>`

Optional:

- `AWS_S3_PREFIX=bug-screenshots`
- `AWS_S3_ACL=public-read`
- `AWS_S3_PUBLIC_BASE_URL=https://<cloudfront-or-custom-domain>`
- `AWS_ACCESS_KEY_ID=<access-key>`
- `AWS_SECRET_ACCESS_KEY=<secret-key>`

If `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` are not set, the endpoint uses the EC2 IAM role.

### Required PHP dependency for S3

Install the AWS SDK in the project root:

```bash
composer require aws/aws-sdk-php
```
