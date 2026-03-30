/*
 * File: frontend/js/auth.js
 * Purpose: Stores auth state and provides role guards plus logout flow.
 */

/** Saves authenticated user details in localStorage. */
function setAuthState(user) {
	localStorage.setItem("auth_user", JSON.stringify(user));
}

/** Clears persisted auth state from localStorage. */
function clearAuthState() {
	localStorage.removeItem("auth_user");
}

/** Reads current auth state and self-heals invalid JSON values. */
function getAuthState() {
	const raw = localStorage.getItem("auth_user");
	if (!raw) {
		return null;
	}

	try {
		return JSON.parse(raw);
	} catch (error) {
		clearAuthState();
		return null;
	}
}

/** Returns true when a user object is stored locally. */
function isAuthenticated() {
	return Boolean(getAuthState());
}

/** Returns true for admin or developer roles. */
function isAdminOrDeveloper() {
	const user = getAuthState();
	if (!user) {
		return false;
	}
	return user.role === "admin" || user.role === "developer";
}

/** Redirects to login when no active auth state exists. */
function requireAuth() {
	if (isAuthenticated()) {
		return;
	}
	window.location.href = "../index.html";
}

/** Guards privileged routes and redirects unauthorized users. */
function requireAdminOrDeveloper() {
	requireAuth();
	if (!isAdminOrDeveloper()) {
		alert("Access denied. Admin or developer role required.");
		window.location.href = "dashboard.html";
	}
}

/** Attempts logout request, clears local auth, and redirects to login page. */
async function doLogout() {
	try {
		await logoutUser();
	} catch (error) {
		console.error(error);
	}
	clearAuthState();
	const currentPath = window.location.pathname;
	if (currentPath.indexOf("/frontend/pages/") !== -1) {
		window.location.href = "../index.html";
		return;
	}
	window.location.href = "index.html";
}
