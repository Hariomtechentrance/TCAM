<?php
// One-time import script to bring filesystem images into DB (safe to run repeatedly)
// This is a CLI helper. Run: php sync_now.php

try {
    $dbPath = __DIR__ . '/tcam_bookings.db';
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // reuse the same scan logic as admin-api sync_media
    $scanDirs = [
        __DIR__ . '/uploads/gallery/',
        __DIR__ . '/gallery/',
        __DIR__ . '/images/',
        __DIR__ . '/bootstrap/',
        __DIR__ . '/',
        __DIR__ . '/uploads/media/',
        __DIR__ . '/uploads/tournaments/',
        __DIR__ . '/uploads/coaches/',
        __DIR__ . '/uploads/'
    ];
    $inserted = ['media'=>[], 'gallery'=>[]];
    $extPattern = '/\.(jpg|jpeg|png|gif|webp)$/i';
    foreach ($scanDirs as $d) {
        if (!is_dir($d)) continue;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d));
        foreach ($it as $f) {
            if (!$f->isFile()) continue;
            $path = $f->getPathname();
            if (!preg_match($extPattern, $path)) continue;
            $rel = ltrim(str_replace(__DIR__, '', $path), '/');
            if (strpos($rel, 'uploads/gallery/') === 0 || strpos($rel, 'gallery/') === 0) {
                $fname = basename($path);
                $uploadsDir = __DIR__ . '/uploads/gallery/'; if (!is_dir($uploadsDir)) mkdir($uploadsDir,0755,true);
                if (strpos($rel, 'uploads/gallery/') !== 0) {
                    $base = pathinfo($fname, PATHINFO_FILENAME);
                    $ext = pathinfo($fname, PATHINFO_EXTENSION);
                    $target = $uploadsDir . $fname;
                    $i = 1; while (file_exists($target)) { $target = $uploadsDir . $base . '_' . $i . '.' . $ext; $i++; }
                    @copy($path, $target);
                    $fname = basename($target);
                }
                $exists = $db->prepare("SELECT COUNT(*) FROM gallery_images WHERE filename = ?");
                $exists->execute([$fname]);
                if ($exists->fetchColumn() == 0) {
                    $stmt = $db->prepare("INSERT INTO gallery_images (filename, original_name, category, caption) VALUES (?,?,?,?)");
                    $stmt->execute([$fname, $fname, 'general', 'Imported']);
                    $inserted['gallery'][] = $fname;
                }
            } else {
                $exists = $db->prepare("SELECT COUNT(*) FROM media_images WHERE filepath = ?");
                $exists->execute([$rel]);
                if ($exists->fetchColumn() == 0) {
                    $fname = basename($path);
                    $section = 'media';
                    if (strpos($rel, 'tournaments/') !== false) $section = 'tournaments';
                    if (strpos($rel, 'coaches/') !== false) $section = 'coaches';
                    $stmt = $db->prepare("INSERT INTO media_images (filename, filepath, section, alt_text, enabled) VALUES (?,?,?,?,1)");
                    $stmt->execute([$fname, $rel, $section, 'Imported']);
                    $inserted['media'][] = $rel;
                }
            }
        }
    }
    echo "Imported: " . count($inserted['gallery']) . " gallery images, " . count($inserted['media']) . " media items\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
