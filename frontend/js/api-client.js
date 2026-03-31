/*
 * File: frontend/js/api-client.js
 * Purpose: Central API client supporting both local mock mode and real backend endpoints.
 */
const MOCK_MODE_KEY = "mock_mode";
const MOCK_USERS_KEY = "mock_users";
const MOCK_BUGS_KEY = "mock_bugs";
const BUG_API_ENDPOINT = "bug_backend_api.php";
const SCREENSHOT_UPLOAD_ENDPOINT = "upload_screenshot.php";

/** Resolves the backend base path from the current frontend route. */
function getApiBasePath() {
	const path = window.location.pathname;
	return path.indexOf("/frontend/pages/") !== -1 ? "../../backend" : "../backend";
}

/** Reads mock mode override from URL query string. */
function getMockModeFromQuery() {
	const params = new URLSearchParams(window.location.search);
	const value = params.get("mock");
	if (value === "1") {
		return true;
	}
	if (value === "0") {
		return false;
	}
	return null;
}

/** Returns whether mock mode is enabled and persists query-driven choices. */
function isMockMode() {
	const fromQuery = getMockModeFromQuery();
	if (fromQuery !== null) {
		localStorage.setItem(MOCK_MODE_KEY, fromQuery ? "1" : "0");
		return fromQuery;
	}

	const saved = localStorage.getItem(MOCK_MODE_KEY);
	if (saved === "1") {
		return true;
	}
	if (saved === "0") {
		return false;
	}

	// Default to mock mode for easier local-first testing.
	localStorage.setItem(MOCK_MODE_KEY, "1");
	return true;
}

/** Persists mock mode state in localStorage. */
function setMockMode(enabled) {
	localStorage.setItem(MOCK_MODE_KEY, enabled ? "1" : "0");
}

/** Seeds local mock users and bugs if they are missing. */
function ensureMockData() {
	if (!localStorage.getItem(MOCK_USERS_KEY)) {
		const seededUsers = [
			{ id: 1, fullname: "Local Admin", email: "admin@local.test", username: "admin", password: "admin123", role: "admin" },
			{ id: 2, fullname: "Local Dev", email: "dev@local.test", username: "dev", password: "dev12345", role: "developer" },
			{ id: 3, fullname: "Local User", email: "user@local.test", username: "user", password: "user12345", role: "user" }
		];
		localStorage.setItem(MOCK_USERS_KEY, JSON.stringify(seededUsers));
	}

	if (!localStorage.getItem(MOCK_BUGS_KEY)) {
		const now = new Date().toISOString();
		const seededBugs = [
			{ id: 1, title: "Login button does not respond", description: "Clicking login on Safari does nothing.", category: "Frontend", priority: "High", status: "Open", created_at: now },
			{ id: 2, title: "Profile page loads slowly", description: "Rendering profile data takes more than 3 seconds.", category: "Performance", priority: "Medium", status: "In Progress", created_at: now },
			{ id: 3, title: "Broken image in dashboard", description: "Bug card thumbnail is broken on mobile viewport.", category: "UI", priority: "Low", status: "Resolved", created_at: now }
		];
		localStorage.setItem(MOCK_BUGS_KEY, JSON.stringify(seededBugs));
	}
}

/** Returns all mock users from localStorage. */
function readMockUsers() {
	ensureMockData();
	return JSON.parse(localStorage.getItem(MOCK_USERS_KEY) || "[]");
}

/** Writes updated mock users to localStorage. */
function writeMockUsers(users) {
	localStorage.setItem(MOCK_USERS_KEY, JSON.stringify(users));
}

/** Returns all mock bugs from localStorage. */
function readMockBugs() {
	ensureMockData();
	return JSON.parse(localStorage.getItem(MOCK_BUGS_KEY) || "[]");
}

/** Writes updated mock bugs to localStorage. */
function writeMockBugs(bugs) {
	localStorage.setItem(MOCK_BUGS_KEY, JSON.stringify(bugs));
}

/** Removes sensitive fields before user data is stored client-side. */
function sanitizeUser(user) {
	return {
		id: user.id,
		fullname: user.fullname,
		email: user.email,
		username: user.username,
		role: user.role
	};
}

/** Sends URL-encoded form data to a backend endpoint and normalizes the response. */
async function postForm(endpoint, payload) {
	const body = new URLSearchParams(payload).toString();
	const response = await fetch(getApiBasePath() + "/" + endpoint, {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded"
		},
		body: body,
		credentials: "same-origin"
	});

	let data;
	try {
		data = await response.json();
	} catch (error) {
		return {
			success: false,
			message: "Invalid API response format."
		};
	}

	if (typeof data.success === "undefined") {
		return {
			success: response.ok,
			message: data.message || (response.ok ? "Request processed." : "Request failed.")
		};
	}

	return data;
}

/** Performs a GET request and parses JSON payload. */
async function getJson(endpoint) {
	const response = await fetch(getApiBasePath() + "/" + endpoint, {
		method: "GET",
		headers: {
			"Accept": "application/json"
		},
		credentials: "same-origin"
	});
	return response.json();
}

/** Sends a JSON request with configurable HTTP options. */
async function requestJson(endpoint, options) {
	const response = await fetch(getApiBasePath() + "/" + endpoint, {
		credentials: "same-origin",
		headers: {
			"Content-Type": "application/json"
		},
		...options
	});

	const payload = await response.json();
	return {
		httpStatus: response.status,
		...payload
	};
}

/** Uploads a screenshot file and returns a URL that can be stored on the bug. */
async function uploadScreenshot(file) {
	if (!file) {
		return { success: false, message: "No screenshot file selected." };
	}

	if (isMockMode()) {
		return {
			success: true,
			url: URL.createObjectURL(file),
			message: "Screenshot uploaded (mock mode)."
		};
	}

	const formData = new FormData();
	formData.append("screenshot", file);

	const response = await fetch(getApiBasePath() + "/" + SCREENSHOT_UPLOAD_ENDPOINT, {
		method: "POST",
		body: formData,
		credentials: "same-origin"
	});

	let result;
	try {
		result = await response.json();
	} catch (error) {
		return {
			success: false,
			message: "Upload failed due to invalid server response."
		};
	}

	return {
		success: Boolean(result.success),
		message: result.message || (response.ok ? "Upload completed." : "Upload failed."),
		url: result.url || ""
	};
}

/** Authenticates a user in mock mode or against the backend auth API. */
async function loginUser(username, password) {
	if (isMockMode()) {
		const users = readMockUsers();
		const user = users.find(function (item) {
			return item.username === username && item.password === password;
		});
		if (!user) {
			return { success: false, message: "Invalid username or password (mock mode)." };
		}
		return { success: true, message: "Login successful.", user: sanitizeUser(user) };
	}

	return postForm("auth.php", {
		action: "login",
		username: username,
		password: password
	});
}

/** Registers a new user in mock mode or against the backend auth API. */
async function signupUser(input) {
	if (isMockMode()) {
		const users = readMockUsers();
		const duplicate = users.find(function (item) {
			return item.username === input.username || item.email === input.email;
		});
		if (duplicate) {
			return { success: false, message: "Username or email already exists (mock mode)." };
		}

		const nextId = users.length ? Math.max.apply(null, users.map(function (item) { return Number(item.id) || 0; })) + 1 : 1;
		users.push({
			id: nextId,
			fullname: input.fullname,
			email: input.email,
			username: input.username,
			password: input.password,
			role: input.role || "user"
		});
		writeMockUsers(users);
		return { success: true, message: "Account created (mock mode)." };
	}

	return postForm("auth.php", {
		action: "signup",
		fullname: input.fullname,
		email: input.email,
		username: input.username,
		password: input.password,
		role: input.role
	});
}

/** Logs out current user session in mock mode or backend mode. */
async function logoutUser() {
	if (isMockMode()) {
		return { success: true, message: "Logged out (mock mode)." };
	}
	return postForm("auth.php", { action: "logout" });
}

/** Checks server session status when backend mode is enabled. */
async function getCurrentSessionUser() {
	if (isMockMode()) {
		return { success: false, message: "Session check is local-only in mock mode." };
	}
	return postForm("auth.php", { action: "session" });
}

/** Fetches all bugs for dashboard and admin views. */
async function getBugList() {
	if (isMockMode()) {
		return { success: true, bugs: readMockBugs() };
	}

	const result = await getJson(BUG_API_ENDPOINT);
	return {
		success: Boolean(result.success),
		bugs: Array.isArray(result.data) ? result.data : [],
		count: Number(result.count || 0)
	};
}

/** Fetches one bug by id for the detail view. */
async function getBugById(id) {
	if (isMockMode()) {
		const result = await getBugList();
		const bugs = Array.isArray(result.bugs) ? result.bugs : [];
		const bug = bugs.find(function (item) {
			return String(item.id) === String(id);
		});
		return { success: Boolean(bug), bug: bug || null };
	}

	const result = await getJson(BUG_API_ENDPOINT + "?id=" + encodeURIComponent(id));
	return {
		success: Boolean(result.success),
		bug: result.data || null
	};
}

/** Creates a new bug record. */
async function submitBug(input) {
	if (isMockMode()) {
		const bugs = readMockBugs();
		const nextId = bugs.length ? Math.max.apply(null, bugs.map(function (item) { return Number(item.id) || 0; })) + 1 : 1;
		const newBug = {
			id: nextId,
			title: input.title,
			description: input.description,
			category: "General",
			priority: "Medium",
			status: "Open",
			screenshot_url: input.screenshot_url || "",
			created_at: new Date().toISOString()
		};
		bugs.unshift(newBug);
		writeMockBugs(bugs);
		return { success: true, message: "Bug submitted (mock mode).", bug: newBug };
	}

	const result = await requestJson(BUG_API_ENDPOINT, {
		method: "POST",
		body: JSON.stringify({
			title: input.title,
			description: input.description,
			severity: input.severity || "Low",
			status: input.status || "Open",
			assigned_to: input.assigned_to || "",
			screenshot_url: input.screenshot_url || ""
		})
	});

	return {
		success: Boolean(result.success),
		message: result.message || "Request processed.",
		bug: result.data || null
	};
}

/** Updates an existing bug by id with partial changes. */
async function updateBug(id, changes) {
	if (isMockMode()) {
		const bugs = readMockBugs();
		const index = bugs.findIndex(function (item) {
			return String(item.id) === String(id);
		});

		if (index < 0) {
			return { success: false, message: "Bug not found." };
		}

		bugs[index] = {
			...bugs[index],
			...changes
		};
		writeMockBugs(bugs);
		return { success: true, message: "Bug updated (mock mode).", bug: bugs[index] };
	}

	const result = await requestJson(BUG_API_ENDPOINT + "?id=" + encodeURIComponent(id), {
		method: "PATCH",
		body: JSON.stringify(changes || {})
	});

	return {
		success: Boolean(result.success),
		message: result.message || "Request processed.",
		bug: result.data || null
	};
}

/** Deletes a bug by id. */
async function deleteBug(id) {
	if (isMockMode()) {
		const bugs = readMockBugs().filter(function (item) {
			return String(item.id) !== String(id);
		});
		writeMockBugs(bugs);
		return { success: true, message: "Bug deleted (mock mode)." };
	}

	const result = await requestJson(BUG_API_ENDPOINT + "?id=" + encodeURIComponent(id), {
		method: "DELETE"
	});

	return {
		success: Boolean(result.success),
		message: result.message || "Request processed."
	};
}
