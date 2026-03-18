<?php
/**
 * Secure file upload handler for profile avatars.
 * Multi-layer defense: extension → MIME → getimagesize → GD re-creation → random name.
 */

const AVATAR_MAX_BYTES = 2 * 1024 * 1024;
const AVATAR_MAX_WIDTH = 4000;
const AVATAR_MAX_HEIGHT = 4000;

function avatar_upload_result(bool $ok, ?string $filename = null, ?string $error = null): array
{
    return [
        'ok' => $ok,
        'filename' => $filename,
        'error' => $error,
    ];
}

function avatar_upload_error_for_code(int $error_code): string
{
    return match ($error_code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File size must be at most 2MB.',
        UPLOAD_ERR_PARTIAL => 'Upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'Upload failed on the server. Please try again.',
        default => 'Upload failed. Please try again.',
    };
}

function handle_avatar_upload(array $file, int $user_id): array
{
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return avatar_upload_result(false, error: avatar_upload_error_for_code($file['error']));
    }

    // Size limit: 2MB
    if ($file['size'] > AVATAR_MAX_BYTES) {
        return avatar_upload_result(false, error: 'File size must be at most 2MB.');
    }

    // Extension allowlist
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) {
        return avatar_upload_result(false, error: 'File must be a JPG, PNG, or GIF image.');
    }

    // MIME type check via finfo (magic bytes, not user-supplied Content-Type)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    $allowed_mimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
    ];

    if (!isset($allowed_mimes[$mime])) {
        return avatar_upload_result(false, error: 'File must be a valid JPG, PNG, or GIF image.');
    }

    $true_ext = $allowed_mimes[$mime];

    // Validate image dimensions
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        return avatar_upload_result(false, error: 'Selected file is not a valid image.');
    }

    $width  = $image_info[0];
    $height = $image_info[1];
    if ($width < 1 || $height < 1 || $width > AVATAR_MAX_WIDTH || $height > AVATAR_MAX_HEIGHT) {
        return avatar_upload_result(false, error: 'Image dimensions must not exceed 4000x4000 pixels.');
    }

    // RE-CREATE image with GD (strips metadata, embedded code, polyglot tricks)
    $source = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
        'image/png'  => imagecreatefrompng($file['tmp_name']),
        'image/gif'  => imagecreatefromgif($file['tmp_name']),
        default      => false,
    };

    if (!$source) {
        return avatar_upload_result(false, error: 'Image could not be processed. Please use a different file.');
    }

    // Preserve transparency for PNG/GIF
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagepalettetotruecolor($source);
        imagealphablending($source, true);
        imagesavealpha($source, true);
    }

    // Generate random filename
    $filename  = bin2hex(random_bytes(16)) . '.' . $true_ext;
    $dest_path = '/uploads/avatars/' . $filename;

    // Save re-created image
    $saved = match ($mime) {
        'image/jpeg' => imagejpeg($source, $dest_path, 85),
        'image/png'  => imagepng($source, $dest_path, 8),
        'image/gif'  => imagegif($source, $dest_path),
        default      => false,
    };

    imagedestroy($source);

    if (!$saved) {
        return avatar_upload_result(false, error: 'Image could not be saved. Please try again.');
    }

    // Delete old avatar
    $pdo  = get_db();
    $stmt = $pdo->prepare('SELECT avatar_path FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $old = $stmt->fetchColumn();

    if ($old) {
        $old_full = '/uploads/avatars/' . $old;
        if (file_exists($old_full)) {
            unlink($old_full);
        }
    }

    // Update database
    $stmt = $pdo->prepare('UPDATE users SET avatar_path = ? WHERE id = ?');
    $stmt->execute([$filename, $user_id]);

    return avatar_upload_result(true, filename: $filename);
}
