function setAuthState(user) {
	localStorage.setItem("auth_user", JSON.stringify(user));
}

function clearAuthState() {
	localStorage.removeItem("auth_user");
}

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

function isAuthenticated() {
	return Boolean(getAuthState());
}

function isAdminOrDeveloper() {
	const user = getAuthState();
	if (!user) {
		return false;
	}
	return user.role === "admin" || user.role === "developer";
}

function requireAuth() {
	if (isAuthenticated()) {
		return;
	}
	window.location.href = "../index.html";
}

function requireAdminOrDeveloper() {
	requireAuth();
	if (!isAdminOrDeveloper()) {
		alert("Access denied. Admin or developer role required.");
		window.location.href = "dashboard.html";
	}
}

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
