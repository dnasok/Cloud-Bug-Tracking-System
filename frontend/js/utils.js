/*
 * File: frontend/js/utils.js
 * Purpose: Shared utility functions for validation, safe rendering, date formatting, and navbar UI.
 */

/** Validates basic email format for signup input checks. */
function isValidEmail(value) {
	return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

/** Escapes HTML-sensitive characters to prevent injected markup rendering. */
function escapeHtml(value) {
	const map = {
		"&": "&amp;",
		"<": "&lt;",
		">": "&gt;",
		'"': "&quot;",
		"'": "&#039;"
	};
	return String(value).replace(/[&<>"']/g, function (char) {
		return map[char];
	});
}

/** Formats date-like values for display and handles invalid values safely. */
function formatDate(value) {
	if (!value) {
		return "-";
	}
	const date = new Date(value);
	if (Number.isNaN(date.getTime())) {
		return String(value);
	}
	return date.toLocaleString();
}

/** Injects a shared top navigation bar on authenticated pages. */
function mountNavbar(input) {
	const options = input || {};
	const basePath = options.basePath || "";
	const user = getAuthState();
	if (!user) {
		return;
	}

	const nav = document.createElement("header");
	nav.className = "bg-slate-900 text-white shadow";
	nav.innerHTML =
		"<div class=\"max-w-7xl mx-auto px-4 py-3 flex flex-wrap gap-3 items-center justify-between\">" +
			"<div class=\"flex items-center gap-4\">" +
				"<a class=\"font-bold text-lg\" href=\"" + basePath + "dashboard.html\">Bug Tracker</a>" +
				"<a class=\"text-sm text-slate-200 hover:text-white\" href=\"" + basePath + "dashboard.html\">Dashboard</a>" +
				"<a class=\"text-sm text-slate-200 hover:text-white\" href=\"" + basePath + "submit-bug.html\">Submit Bug</a>" +
				(isAdminOrDeveloper() ? "<a class=\"text-sm text-slate-200 hover:text-white\" href=\"" + basePath + "admin-dashboard.html\">Admin</a>" : "") +
			"</div>" +
			"<div class=\"flex items-center gap-3\">" +
				"<span class=\"text-sm text-slate-300\">" + escapeHtml(user.username) + " (" + escapeHtml(user.role) + ")</span>" +
				"<a class=\"text-sm text-slate-200 hover:text-white\" href=\"" + basePath + "profile.html\">Profile</a>" +
				"<button id=\"logoutBtn\" class=\"rounded bg-red-600 px-3 py-1 text-sm font-semibold hover:bg-red-700\">Logout</button>" +
			"</div>" +
		"</div>";

	document.body.insertBefore(nav, document.body.firstChild);
	const logoutBtn = document.getElementById("logoutBtn");
	if (logoutBtn) {
		logoutBtn.addEventListener("click", doLogout);
	}
}
