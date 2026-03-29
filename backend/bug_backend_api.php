<?php
include "inc/dbinfo.inc";
include "classify_bug.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function sendJson($statusCode, $payload) {
    http_response_code($statusCode);
    echo json_encode($payload, JSON_PRETTY_PRINT);
    exit;
}

function connectDB() {
    $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
    if (!$connection) {
        sendJson(500, [
            'success' => false,
            'message' => 'Database connection failed.'
        ]);
    }
    mysqli_set_charset($connection, 'utf8mb4');
    return $connection;
}

function ensureBugSchema($connection) {
    $createQuery = "CREATE TABLE IF NOT EXISTS bugs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        category VARCHAR(50) DEFAULT 'General',
        priority VARCHAR(20) DEFAULT 'Low',
        severity VARCHAR(20) DEFAULT 'Low',
        status VARCHAR(50) DEFAULT 'Open',
        screenshot_url VARCHAR(500) DEFAULT NULL,
        assigned_to VARCHAR(120) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    mysqli_query($connection, $createQuery);

    $columnsToAdd = [
        "ALTER TABLE bugs ADD COLUMN severity VARCHAR(20) DEFAULT 'Low'",
        "ALTER TABLE bugs ADD COLUMN screenshot_url VARCHAR(500) DEFAULT NULL",
        "ALTER TABLE bugs ADD COLUMN assigned_to VARCHAR(120) DEFAULT NULL",
        "ALTER TABLE bugs ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];

    foreach ($columnsToAdd as $sql) {
        try {
            mysqli_query($connection, $sql);
        } catch (mysqli_sql_exception $e) {
            // Ignore duplicate-column errors so the API can run on existing tables.
        }
    }
}

function getJsonBody() {
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJson(400, [
            'success' => false,
            'message' => 'Invalid JSON body.'
        ]);
    }

    return $decoded;
}

function normalizeSeverity($value) {
    $allowed = ['Low', 'Medium', 'High', 'Critical'];
    $value = ucfirst(strtolower(trim((string)$value)));
    return in_array($value, $allowed) ? $value : 'Low';
}

function normalizeStatus($value) {
    $map = [
        'open' => 'Open',
        'in progress' => 'In Progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed'
    ];

    $key = strtolower(trim((string)$value));
    return $map[$key] ?? 'Open';
}

function fetchBugById($connection, $id) {
    $stmt = mysqli_prepare($connection, "SELECT * FROM bugs WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $bug = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $bug;
}

$connection = connectDB();
ensureBugSchema($connection);

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    if ($method === 'GET') {
        if ($id > 0) {
            $bug = fetchBugById($connection, $id);
            if (!$bug) {
                sendJson(404, ['success' => false, 'message' => 'Bug not found.']);
            }
            sendJson(200, ['success' => true, 'data' => $bug]);
        }

        $status = isset($_GET['status']) ? trim($_GET['status']) : '';
        $priority = isset($_GET['priority']) ? trim($_GET['priority']) : '';
        $category = isset($_GET['category']) ? trim($_GET['category']) : '';

        $query = "SELECT * FROM bugs WHERE 1=1";
        $params = [];
        $types = '';

        if ($status !== '') {
            $query .= " AND status = ?";
            $params[] = normalizeStatus($status);
            $types .= 's';
        }
        if ($priority !== '') {
            $query .= " AND priority = ?";
            $params[] = ucfirst(strtolower($priority));
            $types .= 's';
        }
        if ($category !== '') {
            $query .= " AND category = ?";
            $params[] = $category;
            $types .= 's';
        }

        $query .= " ORDER BY created_at DESC";
        $stmt = mysqli_prepare($connection, $query);

        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $bugs = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $bugs[] = $row;
        }
        mysqli_stmt_close($stmt);

        sendJson(200, ['success' => true, 'count' => count($bugs), 'data' => $bugs]);
    }

    if ($method === 'POST') {
        $input = !empty($_POST) ? $_POST : getJsonBody();

        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $severity = normalizeSeverity($input['severity'] ?? 'Low');
        $status = normalizeStatus($input['status'] ?? 'Open');
        $assignedTo = trim($input['assigned_to'] ?? '');
        $screenshotUrl = trim($input['screenshot_url'] ?? '');

        if ($title === '' || $description === '') {
            sendJson(400, [
                'success' => false,
                'message' => 'title and description are required.'
            ]);
        }

        $category = classifyBug($title, $description);
        $priority = getPriority($description);

        $stmt = mysqli_prepare(
            $connection,
            "INSERT INTO bugs (title, description, category, priority, severity, status, screenshot_url, assigned_to)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param(
            $stmt,
            'ssssssss',
            $title,
            $description,
            $category,
            $priority,
            $severity,
            $status,
            $screenshotUrl,
            $assignedTo
        );
        mysqli_stmt_execute($stmt);
        $newId = mysqli_insert_id($connection);
        mysqli_stmt_close($stmt);

        $bug = fetchBugById($connection, $newId);
        sendJson(201, [
            'success' => true,
            'message' => 'Bug created successfully.',
            'data' => $bug
        ]);
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        if ($id <= 0) {
            sendJson(400, ['success' => false, 'message' => 'Bug id is required in the query string. Example: bug_api.php?id=3']);
        }

        $existingBug = fetchBugById($connection, $id);
        if (!$existingBug) {
            sendJson(404, ['success' => false, 'message' => 'Bug not found.']);
        }

        $input = getJsonBody();

        $title = trim($input['title'] ?? $existingBug['title']);
        $description = trim($input['description'] ?? $existingBug['description']);
        $status = normalizeStatus($input['status'] ?? $existingBug['status']);
        $severity = normalizeSeverity($input['severity'] ?? $existingBug['severity']);
        $assignedTo = trim($input['assigned_to'] ?? ($existingBug['assigned_to'] ?? ''));
        $screenshotUrl = trim($input['screenshot_url'] ?? ($existingBug['screenshot_url'] ?? ''));

        $category = classifyBug($title, $description);
        $priority = getPriority($description);

        $stmt = mysqli_prepare(
            $connection,
            "UPDATE bugs
             SET title = ?, description = ?, category = ?, priority = ?, severity = ?, status = ?, screenshot_url = ?, assigned_to = ?
             WHERE id = ?"
        );
        mysqli_stmt_bind_param(
            $stmt,
            'ssssssssi',
            $title,
            $description,
            $category,
            $priority,
            $severity,
            $status,
            $screenshotUrl,
            $assignedTo,
            $id
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $updatedBug = fetchBugById($connection, $id);
        sendJson(200, [
            'success' => true,
            'message' => 'Bug updated successfully.',
            'data' => $updatedBug
        ]);
    }

    if ($method === 'DELETE') {
        if ($id <= 0) {
            sendJson(400, ['success' => false, 'message' => 'Bug id is required in the query string. Example: bug_api.php?id=3']);
        }

        $existingBug = fetchBugById($connection, $id);
        if (!$existingBug) {
            sendJson(404, ['success' => false, 'message' => 'Bug not found.']);
        }

        $stmt = mysqli_prepare($connection, "DELETE FROM bugs WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        sendJson(200, [
            'success' => true,
            'message' => 'Bug deleted successfully.'
        ]);
    }

    sendJson(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
} catch (mysqli_sql_exception $e) {
    sendJson(500, [
        'success' => false,
        'message' => 'Database error.',
        'error' => $e->getMessage()
    ]);
} finally {
    mysqli_close($connection);
}
?>
