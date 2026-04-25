<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'dbconnect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject = $_POST['subject'];
    $max_members = $_POST['max_members'];
    
    // Create the study group
    $sql = "INSERT INTO StudyGroup (subject, max_members, created_by) 
            VALUES ('$subject', '$max_members', '$student_id')";
    
    if ($conn->query($sql) === TRUE) {
        $group_id = $conn->insert_id;
        
        // Creator automatically joins
        $conn->query("INSERT INTO GroupMember (group_id, student_id) VALUES ('$group_id', '$student_id')");
        
        $message = "✅ Study group created successfully!";
        $message_type = "success";
    } else {
        $message = "❌ Error: " . $conn->error;
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Study Group - CampusTutor</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>📚 Create a Study Group</h1>
        
        <?php if($message): ?>
            <div class="<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <label>📖 Subject / Topic:</label>
            <input type="text" name="subject" placeholder="e.g., Database Systems Study Group" required>
            
            <label>👥 Max Members (3 or 4):</label>
            <select name="max_members" required>
                <option value="3">3 members</option>
                <option value="4" selected>4 members</option>
            </select>
            
            <button type="submit">✨ Create Group</button>
        </form>
        
        <p style="margin-top: 20px; text-align: center;">
            <a href="dashboard.php">← Back to Dashboard</a>
        </p>
    </div>
</body>
</html>