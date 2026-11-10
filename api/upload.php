<?php
// Upload katalog: autentikasi admin, MIME nyata, batas ukuran, nama acak, folder aman.
require __DIR__ . '/bootstrap.php';
require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Gunakan POST untuk unggah foto.', 405);
if (!isset($_FILES['photo']) || !is_array($_FILES['photo'])) fail('Pilih foto terlebih dahulu.');
$photo = $_FILES['photo'];
$error = (int)($photo['error'] ?? UPLOAD_ERR_NO_FILE);
if ($error !== UPLOAD_ERR_OK) {
    if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) fail('Ukuran foto melebihi batas server. Maksimal 5 MB.', 413);
    fail('Upload gagal. Periksa ukuran file dan konfigurasi PHP.', 400);
}
$maxBytes = 5 * 1024 * 1024;
$bytes = (int)($photo['size'] ?? 0);
if ($bytes < 1 || $bytes > $maxBytes) fail('Foto harus berukuran maksimal 5 MB.', 413);
$tmp = (string)($photo['tmp_name'] ?? '');
if (!is_uploaded_file($tmp)) fail('File yang dikirim tidak valid.', 400);
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if (!isset($allowed[$mime])) fail('Format gambar harus JPG, PNG, atau WebP.', 415);
$info = @getimagesize($tmp);
if (!$info || ($info['mime'] ?? '') !== $mime) fail('Berkas bukan gambar yang valid.', 415);
if ($info[0] > 8000 || $info[1] > 8000) fail('Dimensi foto terlalu besar.', 413);
$dir = dirname(__DIR__) . '/img/uploads';
if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) fail('Folder upload tidak dapat dibuat. Periksa izin folder.', 500);
// Hindari menjalankan berkas script di folder unggahan Apache.
if (!is_file($dir . '/.htaccess')) {
    @file_put_contents($dir . '/.htaccess', "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar|cgi|pl|py|sh)$\">\nRequire all denied\n</FilesMatch>\n");
}
$kind = ($_GET['kind'] ?? '') === 'game' ? 'game' : 'menu';
$name = $kind . '-' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
if (!move_uploaded_file($tmp, $dir . '/' . $name)) fail('Gagal menyimpan gambar di server.', 500);
@chmod($dir . '/' . $name, 0644);
out(['ok' => true, 'path' => 'img/uploads/' . $name]);
