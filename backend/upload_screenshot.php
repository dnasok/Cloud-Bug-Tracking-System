<?php
/**
 * File: backend/upload_screenshot.php
 * Purpose: Receives screenshot uploads and returns a URL for bug records.
 *
 * Storage modes via env:
 * - SCREENSHOT_STORAGE=local (default)
 * - SCREENSHOT_STORAGE=s3
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

function sendJson($statusCode, $payload) {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}

if (!isset($_FILES['screenshot'])) {
    sendJson(400, [
        'success' => false,
        'message' => 'No screenshot file was provided.'
    ]);
}

$file = $_FILES['screenshot'];
if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
    sendJson(400, [
        'success' => false,
        'message' => 'File upload failed.'
    ]);
}

$maxBytes = 5 * 1024 * 1024; // 5 MB
if (($file['size'] ?? 0) > $maxBytes) {
    sendJson(400, [
        'success' => false,
        'message' => 'Screenshot is too large. Maximum size is 5 MB.'
    ]);
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
    sendJson(400, [
        'success' => false,
        'message' => 'Unsupported file type. Use PNG, JPG, WEBP, or GIF.'
    ]);
}

$extension = $allowed[$mime];
$filename = uniqid('bug_', true) . '.' . $extension;
$storageDriver = strtolower(trim((string)(getenv('SCREENSHOT_STORAGE') ?: 'local')));

if ($storageDriver === 's3') {
    $autoloadCandidates = [
        __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php'
    ];

    foreach ($autoloadCandidates as $autoloadPath) {
        if (is_file($autoloadPath)) {
            require_once $autoloadPath;
            break;
        }
    }

    if (!class_exists('Aws\\S3\\S3Client')) {
        sendJson(500, [
            'success' => false,
            'message' => 'AWS SDK not found. Install aws/aws-sdk-php with Composer.'
        ]);
    }

    $region = trim((string)(getenv('AWS_REGION') ?: ''));
    $bucket = trim((string)(getenv('AWS_BUCKET') ?: ''));
    if ($region === '' || $bucket === '') {
        sendJson(500, [
            'success' => false,
            'message' => 'Missing AWS_REGION or AWS_BUCKET environment variables.'
        ]);
    }

    $prefix = trim((string)(getenv('AWS_S3_PREFIX') ?: 'bug-screenshots'), '/');
    $acl = trim((string)(getenv('AWS_S3_ACL') ?: 'public-read'));
    $key = ($prefix === '' ? '' : $prefix . '/') . $filename;

    $clientConfig = [
        'version' => 'latest',
        'region' => $region
    ];

    $accessKeyId = trim((string)(getenv('AWS_ACCESS_KEY_ID') ?: ''));
    $secretAccessKey = trim((string)(getenv('AWS_SECRET_ACCESS_KEY') ?: ''));
    if ($accessKeyId !== '' && $secretAccessKey !== '') {
        $clientConfig['credentials'] = [
            'key' => $accessKeyId,
            'secret' => $secretAccessKey
        ];
    }

    try {
        $client = new Aws\S3\S3Client($clientConfig);
        $client->putObject([
            'Bucket' => $bucket,
            'Key' => $key,
            'SourceFile' => $file['tmp_name'],
            'ContentType' => $mime,
            'ACL' => $acl
        ]);

        $publicBaseUrl = trim((string)(getenv('AWS_S3_PUBLIC_BASE_URL') ?: ''));
        if ($publicBaseUrl !== '') {
            $url = rtrim($publicBaseUrl, '/') . '/' . $key;
        } else {
            $url = $client->getObjectUrl($bucket, $key);
        }

        sendJson(201, [
            'success' => true,
            'message' => 'Screenshot uploaded successfully.',
            'url' => $url,
            'path' => $key,
            'storage' => 's3'
        ]);
    } catch (Throwable $e) {
        sendJson(500, [
            'success' => false,
            'message' => 'S3 upload failed.',
            'error' => $e->getMessage()
        ]);
    }
}

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    sendJson(500, [
        'success' => false,
        'message' => 'Unable to create upload directory.'
    ]);
}

$destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    sendJson(500, [
        'success' => false,
        'message' => 'Could not save uploaded file.'
    ]);
}

$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$relativePath = $scriptDir . '/uploads/' . $filename;

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$url = $host ? ($scheme . '://' . $host . $relativePath) : $relativePath;

sendJson(201, [
    'success' => true,
    'message' => 'Screenshot uploaded successfully.',
    'url' => $url,
    'path' => $relativePath,
    'storage' => 'local'
]);
