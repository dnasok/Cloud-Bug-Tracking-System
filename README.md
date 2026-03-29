# CSD3156-Team-Project-2
Cloud-Based Bug Tracking and Reporting System for Developers

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
