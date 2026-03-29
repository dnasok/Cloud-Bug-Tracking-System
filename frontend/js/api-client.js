const MOCK_MODE_KEY = "mock_mode";
const MOCK_USERS_KEY = "mock_users";
const MOCK_BUGS_KEY = "mock_bugs";

function getApiBasePath() {
	const path = window.location.pathname;
	return path.indexOf("/frontend/pages/") !== -1 ? "../../backend" : "../backend";
}

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

function setMockMode(enabled) {
	localStorage.setItem(MOCK_MODE_KEY, enabled ? "1" : "0");
}

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

function readMockUsers() {
	ensureMockData();
	return JSON.parse(localStorage.getItem(MOCK_USERS_KEY) || "[]");
}

function writeMockUsers(users) {
	localStorage.setItem(MOCK_USERS_KEY, JSON.stringify(users));
}

function readMockBugs() {
	ensureMockData();
	return JSON.parse(localStorage.getItem(MOCK_BUGS_KEY) || "[]");
}

function writeMockBugs(bugs) {
	localStorage.setItem(MOCK_BUGS_KEY, JSON.stringify(bugs));
}

function sanitizeUser(user) {
	return {
		id: user.id,
		fullname: user.fullname,
		email: user.email,
		username: user.username,
		role: user.role
	};
}

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

	return response.json();
}

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

async function logoutUser() {
	if (isMockMode()) {
		return { success: true, message: "Logged out (mock mode)." };
	}
	return postForm("auth.php", { action: "logout" });
}

async function getCurrentSessionUser() {
	if (isMockMode()) {
		return { success: false, message: "Session check is local-only in mock mode." };
	}
	return postForm("auth.php", { action: "session" });
}

async function getBugList() {
	if (isMockMode()) {
		return { success: true, bugs: readMockBugs() };
	}
	return getJson("view_bugs.php");
}

async function getBugById(id) {
	const result = await getBugList();
	const bugs = Array.isArray(result.bugs) ? result.bugs : [];
	const bug = bugs.find(function (item) {
		return String(item.id) === String(id);
	});
	return { success: Boolean(bug), bug: bug || null };
}

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
			created_at: new Date().toISOString()
		};
		bugs.unshift(newBug);
		writeMockBugs(bugs);
		return { success: true, message: "Bug submitted (mock mode).", bug: newBug };
	}

	const payload = new URLSearchParams({
		title: input.title,
		description: input.description
	}).toString();

	const response = await fetch(getApiBasePath() + "/submit_bug.php", {
		method: "POST",
		headers: { "Content-Type": "application/x-www-form-urlencoded" },
		body: payload,
		credentials: "same-origin"
	});

	const text = await response.text();
	return {
		success: response.ok,
		message: text
	};
}
