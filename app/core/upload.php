<?php
/**
 * Secure file upload handler for profile avatars.
 * Multi-layer defense: extension → MIME → getimagesize → GD re-creation → random name.
 */

function handle_avatar_upload(array $file, int $user_id): string|false
{
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Size limit: 2MB
    if ($file['size'] > 2 * 1024 * 1024) {
        return false;
    }

    // Extension allowlist
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) {
        return false;
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
        return false;
    }

    $true_ext = $allowed_mimes[$mime];

    // Validate image dimensions
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        return false;
    }

    $width  = $image_info[0];
    $height = $image_info[1];
    if ($width < 1 || $height < 1 || $width > 4000 || $height > 4000) {
        return false;
    }

    // RE-CREATE image with GD (strips metadata, embedded code, polyglot tricks)
    $source = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
        'image/png'  => imagecreatefrompng($file['tmp_name']),
        'image/gif'  => imagecreatefromgif($file['tmp_name']),
        default      => false,
    };

    if (!$source) {
        return false;
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
        return false;
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

    return $filename;
}
