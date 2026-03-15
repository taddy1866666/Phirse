<?php
session_start();

if (!isset($_SESSION['seller_id'])) {
    header('Location: ../index.html');
    exit();
}

require_once '../database/config.php';

$seller_id = $_SESSION['seller_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    
    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header('Location: registered-students.php?error=' . urlencode('File upload error'));
        exit();
    }
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        header('Location: registered-students.php?error=' . urlencode('File size must not exceed 5MB'));
        exit();
    }
    
    // Validate file extension - only CSV
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_extension !== 'csv') {
        header('Location: registered-students.php?error=' . urlencode('Only CSV files are allowed'));
        exit();
    }
    
    // Read CSV file
    $csv_file = fopen($file['tmp_name'], 'r');
    
    if ($csv_file === false) {
        header('Location: registered-students.php?error=' . urlencode('Could not open CSV file'));
        exit();
    }
    
    // Skip BOM if present
    $bom = fread($csv_file, 3);
    if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
        rewind($csv_file);
    }
    
    // Read header row
    $header = fgetcsv($csv_file);
    
    if ($header === false) {
        fclose($csv_file);
        header('Location: registered-students.php?error=' . urlencode('CSV file is empty'));
        exit();
    }
    
    // Trim whitespace from headers
    $header = array_map('trim', $header);
    
    // Validate headers
    $expected_headers = [
        'Student Number',
        'Name',
        'Organization',
        'Course & Section',
        'Contact Number',
        'Email',
        'Password'
    ];
    
    // Check if headers match (case-insensitive)
    $header_lower = array_map('strtolower', $header);
    $expected_lower = array_map('strtolower', $expected_headers);
    
    // More flexible header matching
    $header_mismatch = false;
    foreach ($expected_lower as $index => $expected) {
        if (!isset($header_lower[$index]) || $header_lower[$index] !== $expected) {
            $header_mismatch = true;
            break;
        }
    }
    
    if ($header_mismatch) {
        fclose($csv_file);
        $error_msg = 'Invalid CSV format. Expected headers: ' . implode(', ', $expected_headers) . '. Got: ' . implode(', ', $header);
        header('Location: registered-students.php?error=' . urlencode($error_msg));
        exit();
    }
    
    // Process data rows
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    $row_number = 1; // Start from 1 (after header)

    // Track duplicates inside the CSV file
    $seen_student_numbers = [];
    $seen_contact_numbers = [];
    
    try {
        $pdo->beginTransaction();
        
        while (($data = fgetcsv($csv_file)) !== false) {
            $row_number++;
            
            // Skip empty rows
            if (empty(array_filter($data))) {
                continue;
            }
            
            // Trim whitespace from all fields
            $data = array_map('trim', $data);
            
            // Extract data
            // Order: Student Number, Name, Organization, Course & Section, Contact Number, Email, Password
            $student_number = $data[0] ?? '';
            $student_name = $data[1] ?? '';
            $organization = $data[2] ?? '';
            $course_section = $data[3] ?? '';
            $contact_number = $data[4] ?? '';
            $email = $data[5] ?? '';
            $password = $data[6] ?? '';
            
            // Validate required fields
            if (empty($student_number) || empty($student_name) || empty($organization) || empty($password)) {
                $error_count++;
                $errors[] = "Row $row_number: Missing required fields (Student Number, Name, Organization, Password)";
                continue;
            }
            
            // Validate password length
            if (strlen($password) < 6) {
                $error_count++;
                $errors[] = "Row $row_number: Password must be at least 6 characters";
                continue;
            }
            
            // Check for duplicates inside uploaded CSV
            if (isset($seen_student_numbers[$student_number])) {
                $error_count++;
                $errors[] = "Row $row_number: Duplicate student number in file '$student_number' (also on row {$seen_student_numbers[$student_number]})";
                continue;
            }
            $seen_student_numbers[$student_number] = $row_number;

            if (!empty($contact_number)) {
                if (isset($seen_contact_numbers[$contact_number])) {
                    $error_count++;
                    $errors[] = "Row $row_number: Duplicate contact number in file '$contact_number' (also on row {$seen_contact_numbers[$contact_number]})";
                    continue;
                }
                $seen_contact_numbers[$contact_number] = $row_number;
            }

            // Check if student number already exists in DB
            $check_stmt = $pdo->prepare("SELECT id, contact_number FROM students WHERE student_number = ?");
            $check_stmt->execute([$student_number]);
            $existing_student = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_student) {
                // Student number exists -> create affiliation if not exists, but do NOT create duplicate student
                $existing_id = $existing_student['id'];

                // If contact number provided in CSV, ensure it doesn't conflict with another student
                if (!empty($contact_number)) {
                    $contact_check = $pdo->prepare("SELECT id FROM students WHERE contact_number = ? AND id != ?");
                    $contact_check->execute([$contact_number, $existing_id]);
                    if ($contact_check->fetch()) {
                        $error_count++;
                        $errors[] = "Row $row_number: Contact number '$contact_number' already used by another student";
                        continue;
                    }
                }

                // Check affiliation
                $aff_check = $pdo->prepare("SELECT id FROM student_seller_affiliations WHERE student_id = ? AND seller_id = ?");
                $aff_check->execute([$existing_id, $seller_id]);

                if ($aff_check->fetch()) {
                    // already affiliated -> skip with warning
                    $error_count++;
                    $errors[] = "Row $row_number: Student number '$student_number' already exists and is already registered with your organization";
                    continue;
                }

                // Create affiliation
                $affiliation_stmt = $pdo->prepare(
                    "INSERT INTO student_seller_affiliations (student_id, seller_id) VALUES (?, ?)"
                );
                $affiliation_stmt->execute([$existing_id, $seller_id]);
                $success_count++;
                continue;
            }

            // If contact number provided, ensure it's not already used by any other student
            if (!empty($contact_number)) {
                $contact_check = $pdo->prepare("SELECT id FROM students WHERE contact_number = ?");
                $contact_check->execute([$contact_number]);
                if ($contact_check->fetch()) {
                    $error_count++;
                    $errors[] = "Row $row_number: Contact number '$contact_number' already exists";
                    continue;
                }
            }
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert student - FIXED: Using course_section column
            $insert_stmt = $pdo->prepare("
                INSERT INTO students 
                (student_number, student_name, organization, course_section, contact_number, email, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $insert_stmt->execute([
                $student_number,
                $student_name,
                $organization,
                $course_section,
                $contact_number,
                $email,
                $hashed_password
            ]);
            
            // Get the inserted student ID
            $student_id = $pdo->lastInsertId();
            
            // Create affiliation with current seller
            $affiliation_stmt = $pdo->prepare("
                INSERT INTO student_seller_affiliations (student_id, seller_id) 
                VALUES (?, ?)
            ");
            $affiliation_stmt->execute([$student_id, $seller_id]);
            
            $success_count++;
        }
        
        $pdo->commit();
        fclose($csv_file);
        
        // Build success/error message
        if ($success_count > 0 && $error_count === 0) {
            header('Location: registered-students.php?message=' . urlencode("Successfully registered $success_count student(s)!"));
        } elseif ($success_count > 0 && $error_count > 0) {
            $error_msg = "Partially successful: $success_count registered, but $error_count error(s) occurred. ";
            if (count($errors) <= 5) {
                $error_msg .= "Errors: " . implode("; ", $errors);
            } else {
                $error_msg .= "First 5 errors: " . implode("; ", array_slice($errors, 0, 5));
            }
            header('Location: registered-students.php?error=' . urlencode($error_msg));
        } else {
            $error_msg = "Registration failed. $error_count error(s) occurred: " . implode("; ", array_slice($errors, 0, 10));
            header('Location: registered-students.php?error=' . urlencode($error_msg));
        }
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        fclose($csv_file);
        
        $error_message = "Database error: " . $e->getMessage();
        header('Location: registered-students.php?error=' . urlencode($error_message));
        exit();
    }
    
} else {
    header('Location: registered-students.php?error=' . urlencode('No file uploaded'));
    exit();
}
?>