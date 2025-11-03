<?php
session_start();

if (!isset($_SESSION['seller_id'])) {
    header('Location: ../index.html');
    exit();
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=student_registration_template.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers - NO COURSE FIELD!
// Order: Student Number, Name, Organization, Course & Section, Contact Number, Email, Password
fputcsv($output, [
    'Student Number',
    'Name',
    'Organization',
    'Course & Section',
    'Contact Number',
    'Email',
    'Password'
]);

// Sample data rows - NO COURSE!
fputcsv($output, [
    '22-1034',
    'Juan Dela Cruz',
    'VITS',
    'BSIT 3-1',
    '09123456789',
    'juan.delacruz@plv.edu.ph',
    '123123'
]);

fputcsv($output, [
    '22-2222',
    'Maria Santos',
    'VITS',
    'BSIT 5-3',
    '09187654321',
    'maria.santos@plv.edu.ph',
    '123123'
]);

fputcsv($output, [
    '22-1234',
    'Pedro Reyes',
    'VITS',
    'BSIT 3-7',
    '09156789012',
    'pedro.reyes@plv.edu.ph',
    '123123'
]);

fclose($output);
exit();
?>