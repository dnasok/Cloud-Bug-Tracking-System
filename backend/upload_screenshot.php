<?php
/**
 * File: backend/upload_screenshot.php
 * Purpose: Receives screenshot uploads and returns a public URL for bug records.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
    exit;
}

if (!isset($_FILES['screenshot'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'No screenshot file was provided.'
    ]);
    exit;
}

$file = $_FILES['screenshot'];
if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'File upload failed.'
    ]);
    exit;
}

$maxBytes = 5 * 1024 * 1024; // 5 MB
if (($file['size'] ?? 0) > $maxBytes) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Screenshot is too large. Maximum size is 5 MB.'
    ]);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed = [
    'image/png' => 'png',
    'image/jpeg' => 'jpg',
    'image/webp' => 'webp',
    'image/gif' => 'gif'
];

if (!isset($allowed[$mime])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Unsupported file type. Use PNG, JPG, WEBP, or GIF.'
    ]);
    exit;
}

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to create upload directory.'
    ]);
    exit;
}

$filename = uniqid('bug_', true) . '.' . $allowed[$mime];
$destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Could not save uploaded file.'
    ]);
    exit;
}

$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$relativePath = $scriptDir . '/uploads/' . $filename;

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$url = $host ? ($scheme . '://' . $host . $relativePath) : $relativePath;

http_response_code(201);
echo json_encode([
    'success' => true,
    'message' => 'Screenshot uploaded successfully.',
    'url' => $url,
    'path' => $relativePath
]);
