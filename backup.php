<?php
// Define a secret key for authorization
$secretKey = 'yourSecretKey123'; // Change this to a strong, unique key

// Check for the secret key in the URL
if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    die("Unauthorized access.");
}

// Database configuration
$dbHost     = 'localhost';          // Database host
$dbUsername = 'root';   // Database username
$dbPassword = '';   // Database password
$dbName     = 'wptest';       // Database name

// Paths
$backupDir = __DIR__ . '/backups/'; // Backup directory
$fileName = 'wp-backup-' . date('Y-m-d-H-i-s'); // Backup filename with timestamp

// Ensure the backup directory exists
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Backup Files (Compressing WordPress directory)
$rootPath = realpath(__DIR__ . '/../'); // Root directory of WordPress installation
$zipFile = $backupDir . $fileName . '-files.zip';
$zip = new ZipArchive();

if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
    $zip->close();
    echo "Files backup created: $zipFile\n";
} else {
    echo "Failed to create files backup.\n";
}

// Backup Database (Exporting SQL)
$sqlFile = $backupDir . $fileName . '-database.sql';
$command = "mysqldump --host=$dbHost --user=$dbUsername --password=$dbPassword $dbName > $sqlFile";

exec($command, $output, $result);
if ($result === 0) {
    echo "Database backup created: $sqlFile\n";
} else {
    echo "Failed to create database backup.\n";
}

// Optional: Clean up old backups (e.g., older than 30 days)
$daysToKeep = 30;
foreach (glob($backupDir . '*') as $file) {
    if (is_file($file) && time() - filemtime($file) >= $daysToKeep * 86400) {
        unlink($file);
        echo "Old backup deleted: $file\n";
    }
}
