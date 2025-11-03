<?php
session_start();

if (!isset($_SESSION['seller_id'])) {
    die("Please log in as seller first");
}

require_once '../database/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>CSV Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2, h3, h4 { color: #333; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 5px; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; }
        .info { color: blue; background: #d1ecf1; padding: 10px; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        table td, table th { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table th { background: #f2f2f2; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>";

echo "<h2>CSV Upload Test & Debugging Tool</h2>";
echo "<p class='info'><strong>Logged in as Seller ID:</strong> " . $_SESSION['seller_id'] . "</p>";

// Check if students table exists and show structure
try {
    $stmt = $pdo->query("DESCRIBE students");
    echo "<h3>✅ Students Table Structure:</h3>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Highlight important columns
    echo "<p class='success'><strong>Important:</strong> Make sure you have these columns: student_number, student_name, <strong>organization</strong>, section, contact_number, email, password</p>";
    
} catch (PDOException $e) {
    echo "<p class='error'><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check student_seller_affiliations table
try {
    $stmt = $pdo->query("DESCRIBE student_seller_affiliations");
    echo "<h3>✅ Student Seller Affiliations Table Structure:</h3>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<p class='error'><strong>Affiliations Table Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<hr>";
    echo "<h3>📄 File Upload Test Results:</h3>";
    
    $file = $_FILES['test_file'];
    
    echo "<div class='info'>";
    echo "<strong>File Name:</strong> " . htmlspecialchars($file['name']) . "<br>";
    echo "<strong>File Size:</strong> " . number_format($file['size']) . " bytes<br>";
    echo "<strong>File Type:</strong> " . htmlspecialchars($file['type']) . "<br>";
    echo "<strong>Upload Error Code:</strong> " . $file['error'] . "<br>";
    echo "</div>";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        echo "<h4>✅ File Uploaded Successfully - Reading CSV Content:</h4>";
        
        $handle = fopen($file['tmp_name'], "r");
        
        // Check for BOM
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        } else {
            echo "<p class='info'>UTF-8 BOM detected and skipped</p>";
        }
        
        $rowNum = 0;
        echo "<table>";
        
        while (($row = fgetcsv($handle)) !== FALSE) {
            if ($rowNum === 0) {
                // Header row
                echo "<tr style='background: #007bff; color: white;'>";
                echo "<th>Row #</th>";
                foreach ($row as $index => $cell) {
                    echo "<th>Column $index: " . htmlspecialchars(trim($cell)) . "</th>";
                }
                echo "</tr>";
                
                // Validate headers
                $expected_headers = ['Student Number', 'Name', 'Organization', 'Section', 'Contact Number', 'Email', 'Password'];
                $trimmed_row = array_map('trim', $row);
                
                if ($trimmed_row === $expected_headers) {
                    echo "<tr><td colspan='" . (count($row) + 1) . "' class='success'>✅ Headers are correct!</td></tr>";
                } else {
                    echo "<tr><td colspan='" . (count($row) + 1) . "' class='error'>❌ Headers don't match! Expected: " . implode(', ', $expected_headers) . "</td></tr>";
                }
            } else {
                // Data rows
                echo "<tr>";
                echo "<td><strong>$rowNum</strong></td>";
                foreach ($row as $cell) {
                    $trimmed = trim($cell);
                    echo "<td>" . htmlspecialchars($trimmed) . (empty($trimmed) ? " <em>(empty)</em>" : "") . "</td>";
                }
                echo "</tr>";
            }
            $rowNum++;
        }
        
        echo "</table>";
        echo "<p class='success'><strong>Total Rows:</strong> $rowNum (including header)</p>";
        
        fclose($handle);
        
        // Show what would be inserted
        echo "<h4>📋 Data Preview (what will be inserted):</h4>";
        $handle2 = fopen($file['tmp_name'], "r");
        $bom2 = fread($handle2, 3);
        if ($bom2 !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle2);
        }
        
        $header = fgetcsv($handle2); // Skip header
        $previewCount = 0;
        
        echo "<table>";
        echo "<tr style='background: #28a745; color: white;'>";
        echo "<th>Student Number</th><th>Name</th><th>Organization</th><th>Section</th><th>Contact</th><th>Email</th><th>Password</th></tr>";
        
        while (($data = fgetcsv($handle2)) !== FALSE && $previewCount < 5) {
            if (empty(array_filter($data))) continue;
            
            $data = array_map('trim', $data);
            echo "<tr>";
            echo "<td>" . htmlspecialchars($data[0] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($data[1] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($data[2] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($data[3] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($data[4] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($data[5] ?? '') . "</td>";
            echo "<td>" . (empty($data[6] ?? '') ? "<em class='error'>MISSING</em>" : "✅ Present (length: " . strlen($data[6]) . ")") . "</td>";
            echo "</tr>";
            $previewCount++;
        }
        echo "</table>";
        
        fclose($handle2);
        
    } else {
        echo "<p class='error'><strong>Upload Error:</strong> " . $file['error'] . "</p>";
    }
}
?>

<hr>
<h3>📤 Upload Test CSV File:</h3>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="test_file" accept=".csv" required>
    <button type="submit" class="btn">Test Upload & Analyze</button>
</form>

<hr>
<h3>📝 Correct CSV Format (NEW STRUCTURE - NO COURSE!):</h3>
<div class="info">
    <strong>⚠️ Important:</strong> The CSV must have exactly these 7 columns in this order:
</div>
<pre>Student Number,Name,Organization,Section,Contact Number,Email,Password
2024-00001,Juan Dela Cruz,Computer Science Society,BSCS-4A,09171234567,juan@plv.edu.ph,password123
2024-00002,Maria Santos,Information Technology Club,BSIT-3B,09187654321,maria@plv.edu.ph,secure456
2024-00003,Pedro Reyes,Engineering Society,BSENG-2A,09156789012,pedro@plv.edu.ph,mypass789</pre>

<h4>❌ OLD FORMAT (INCORRECT - HAS COURSE):</h4>
<pre style="background: #f8d7da; color: #721c24;">Student Number,Student Name,<strong style="text-decoration: line-through;">Course</strong>,Section,Password,Contact Number,Email
22-1234,Juan Dela Cruz,<strong style="text-decoration: line-through;">BSCS</strong>,3-1,password123,09171234567,juan@plv.edu.ph</pre>

<hr>
<h3>🔗 Quick Actions:</h3>
<p>
    <a href="registered-students.php" class="btn">← Back to Registered Students</a>
    <a href="download-student-template.php" class="btn" style="background: #28a745;">📥 Download Correct Template</a>
</p>

</body>
</html>