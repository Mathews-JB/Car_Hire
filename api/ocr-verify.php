<?php
/**
 * OCR Document Verification API
 * ─────────────────────────────────────────────────────────────────────────────
 * Extracts NRC number / Driver's License number from uploaded document images.
 *
 * STRATEGY:
 * 1. Primary:  Google Cloud Vision API (if configured)
 * 2. Fallback: Tesseract OCR via exec() (if installed on server)
 * 3. Fallback: Pattern-based validation only (no OCR)
 *
 * SETUP – Google Vision:
 *   Add to .env:  GOOGLE_VISION_API_KEY=your_key
 *   Enable "Cloud Vision API" in Google Cloud Console
 *
 * SETUP – Tesseract (local):
 *   Windows: Download from https://github.com/UB-Mannheim/tesseract/wiki
 *   Add to .env: TESSERACT_PATH=C:\Program Files\Tesseract-OCR\tesseract.exe
 *
 * REQUEST:
 *   POST /api/ocr-verify.php
 *   Content-Type: multipart/form-data
 *   Fields:
 *     - image       : uploaded file (JPEG/PNG, max 5MB)
 *     - doc_type    : 'NRC' | 'PASSPORT' | 'LICENSE'
 *     - csrf_token  : CSRF token
 *
 * RESPONSE (JSON):
 *   { success, extracted_text, detected_number, is_valid_format, confidence, method }
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/env_loader.php';
require_once __DIR__ . '/../includes/functions.php';

// ── Auth & CSRF ───────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

// ── Validate uploaded file ────────────────────────────────────────────────────
if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No image uploaded or upload error.']);
    exit;
}

$file      = $_FILES['image'];
$doc_type  = strtoupper(trim($_POST['doc_type'] ?? 'NRC'));
$max_size  = 5 * 1024 * 1024; // 5 MB

// Size check
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'error' => 'Image too large. Max 5MB.']);
    exit;
}

// Type check
$allowed_mime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed_mime)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Use JPEG or PNG.']);
    exit;
}

// ── Run OCR ───────────────────────────────────────────────────────────────────
$ocr_result = runOCR($file['tmp_name'], $mime);

// ── Extract & validate document number ───────────────────────────────────────
$extracted_number = extractDocumentNumber($ocr_result['text'], $doc_type);
$validation       = validateDocumentFormat($extracted_number, $doc_type);

// ── Build response ────────────────────────────────────────────────────────────
echo json_encode([
    'success'          => true,
    'method'           => $ocr_result['method'],
    'extracted_text'   => substr($ocr_result['text'], 0, 500), // truncate for safety
    'detected_number'  => $extracted_number,
    'is_valid_format'  => $validation['valid'],
    'format_message'   => $validation['message'],
    'confidence'       => $ocr_result['confidence'],
    'doc_type'         => $doc_type,
]);

// ═════════════════════════════════════════════════════════════════════════════
//  OCR FUNCTIONS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Run OCR on the image, trying available methods in order
 */
function runOCR(string $tmp_path, string $mime): array {
    // Method 1: Google Cloud Vision API
    $google_key = app_config('GOOGLE_VISION_API_KEY');
    if ($google_key) {
        $result = ocrGoogleVision($tmp_path, $mime, $google_key);
        if ($result['success']) {
            return ['text' => $result['text'], 'method' => 'google_vision', 'confidence' => $result['confidence']];
        }
    }

    // Method 2: Local Tesseract
    $tesseract_path = app_config('TESSERACT_PATH', 'tesseract');
    if (isTesseractAvailable($tesseract_path)) {
        $result = ocrTesseract($tmp_path, $tesseract_path);
        if ($result['success']) {
            return ['text' => $result['text'], 'method' => 'tesseract', 'confidence' => 70];
        }
    }

    // Method 3: No OCR available – return empty (validation only)
    return ['text' => '', 'method' => 'none', 'confidence' => 0];
}

/**
 * Google Cloud Vision API OCR
 */
function ocrGoogleVision(string $tmp_path, string $mime, string $api_key): array {
    $image_data = base64_encode(file_get_contents($tmp_path));

    $payload = json_encode([
        'requests' => [[
            'image'    => ['content' => $image_data],
            'features' => [['type' => 'TEXT_DETECTION', 'maxResults' => 1]],
        ]]
    ]);

    $ch = curl_init("https://vision.googleapis.com/v1/images:annotate?key={$api_key}");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 20,
    ]);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        return ['success' => false, 'text' => '', 'confidence' => 0];
    }

    $data = json_decode($response, true);
    $text = $data['responses'][0]['fullTextAnnotation']['text'] ?? '';

    if (empty($text)) {
        return ['success' => false, 'text' => '', 'confidence' => 0];
    }

    // Google Vision provides per-word confidence; we'll use a high default
    $confidence = 95;

    return ['success' => true, 'text' => $text, 'confidence' => $confidence];
}

/**
 * Tesseract OCR (local binary)
 */
function ocrTesseract(string $tmp_path, string $tesseract_path): array {
    // Create a temp output file
    $out_base = sys_get_temp_dir() . '/ocr_' . uniqid();

    // Escape paths for shell
    $safe_input  = escapeshellarg($tmp_path);
    $safe_output = escapeshellarg($out_base);
    $safe_binary = escapeshellarg($tesseract_path);

    // Run Tesseract: tesseract input.jpg output_base txt
    $cmd    = "{$safe_binary} {$safe_input} {$safe_output} -l eng txt 2>&1";
    $output = [];
    $return = 0;
    exec($cmd, $output, $return);

    $txt_file = $out_base . '.txt';
    if ($return !== 0 || !file_exists($txt_file)) {
        return ['success' => false, 'text' => ''];
    }

    $text = file_get_contents($txt_file);
    @unlink($txt_file);

    return ['success' => true, 'text' => $text];
}

/**
 * Check if Tesseract is available on this system
 */
function isTesseractAvailable(string $path): bool {
    $safe = escapeshellarg($path);
    exec("{$safe} --version 2>&1", $out, $ret);
    return $ret === 0;
}

// ═════════════════════════════════════════════════════════════════════════════
//  DOCUMENT NUMBER EXTRACTION
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Extract document number from OCR text using regex patterns
 */
function extractDocumentNumber(string $text, string $doc_type): string {
    if (empty($text)) return '';

    // Normalize whitespace
    $text = preg_replace('/\s+/', ' ', $text);

    switch ($doc_type) {
        case 'NRC':
            return extractNRC($text);
        case 'LICENSE':
            return extractLicense($text);
        case 'PASSPORT':
            return extractPassport($text);
        default:
            return '';
    }
}

/**
 * Extract Zambian NRC number
 * Format: XXXXXX/XX/X  (6 digits / 2 digits / 1 digit)
 * Example: 123456/78/1
 */
function extractNRC(string $text): string {
    // Primary pattern: 6 digits / 2 digits / 1 digit
    if (preg_match('/\b(\d{6}\/\d{2}\/\d{1})\b/', $text, $m)) {
        return $m[1];
    }

    // OCR sometimes misreads slashes as spaces or dashes
    if (preg_match('/\b(\d{6})[\s\-](\d{2})[\s\-](\d{1})\b/', $text, $m)) {
        return "{$m[1]}/{$m[2]}/{$m[3]}";
    }

    // Look for 9-digit sequence (without separators)
    if (preg_match('/\b(\d{6})(\d{2})(\d{1})\b/', $text, $m)) {
        return "{$m[1]}/{$m[2]}/{$m[3]}";
    }

    return '';
}

/**
 * Extract Zambian Driver's License number
 * Format: ZL-XXXXXXXX or similar
 */
function extractLicense(string $text): string {
    // ZL- prefix format
    if (preg_match('/\b(ZL[\-\s]?[A-Z0-9]{6,10})\b/i', $text, $m)) {
        return strtoupper(str_replace(' ', '-', $m[1]));
    }

    // Generic license number patterns used in Zambia
    if (preg_match('/\b([A-Z]{1,3}[\-\s]?\d{5,8})\b/', $text, $m)) {
        return strtoupper($m[1]);
    }

    // Pure numeric license
    if (preg_match('/\bLICEN[SC]E\s*(?:NO\.?|NUMBER\.?)?\s*:?\s*([A-Z0-9\-]{6,12})\b/i', $text, $m)) {
        return strtoupper($m[1]);
    }

    return '';
}

/**
 * Extract Passport number
 * Format: Zambian passports start with letters followed by digits
 */
function extractPassport(string $text): string {
    // Zambian passport: ZA + 7 digits, or similar
    if (preg_match('/\b(ZA\d{7})\b/i', $text, $m)) {
        return strtoupper($m[1]);
    }

    // Generic ICAO passport: 2 letters + 7 alphanumeric
    if (preg_match('/\b([A-Z]{2}[0-9]{7})\b/', $text, $m)) {
        return $m[1];
    }

    // MRZ line extraction (Machine Readable Zone)
    if (preg_match('/P[<A-Z]ZMB([A-Z0-9<]{9})/', $text, $m)) {
        return str_replace('<', '', $m[1]);
    }

    return '';
}

// ═════════════════════════════════════════════════════════════════════════════
//  FORMAT VALIDATION
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Validate the extracted document number format
 */
function validateDocumentFormat(string $number, string $doc_type): array {
    if (empty($number)) {
        return ['valid' => false, 'message' => 'No document number could be detected. Please ensure the image is clear and well-lit.'];
    }

    switch ($doc_type) {
        case 'NRC':
            return validateNRCFormat($number);
        case 'LICENSE':
            return validateLicenseFormat($number);
        case 'PASSPORT':
            return validatePassportFormat($number);
        default:
            return ['valid' => false, 'message' => 'Unknown document type.'];
    }
}

function validateNRCFormat(string $number): array {
    // Zambian NRC: 6 digits / 2 digits / 1 digit
    if (preg_match('/^\d{6}\/\d{2}\/\d{1}$/', $number)) {
        return ['valid' => true, 'message' => "Valid NRC format detected: {$number}"];
    }
    return ['valid' => false, 'message' => "NRC format should be XXXXXX/XX/X (e.g. 123456/78/1). Detected: {$number}"];
}

function validateLicenseFormat(string $number): array {
    // Zambian license: ZL- prefix or alphanumeric 6-12 chars
    if (preg_match('/^ZL[\-]?[A-Z0-9]{6,10}$/i', $number)) {
        return ['valid' => true, 'message' => "Valid Driver's License format: {$number}"];
    }
    if (preg_match('/^[A-Z0-9\-]{6,12}$/i', $number)) {
        return ['valid' => true, 'message' => "License number detected: {$number}"];
    }
    return ['valid' => false, 'message' => "License format appears invalid. Detected: {$number}"];
}

function validatePassportFormat(string $number): array {
    if (preg_match('/^[A-Z]{2}[0-9]{7}$/i', $number)) {
        return ['valid' => true, 'message' => "Valid Passport number: {$number}"];
    }
    return ['valid' => false, 'message' => "Passport format should be 2 letters + 7 digits. Detected: {$number}"];
}
