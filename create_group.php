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
    $meeting_link = trim($_POST['meeting_link']);
    $chat_link = trim($_POST['chat_link']);
    
    // Check if at least one link is provided
    if (empty($meeting_link) && empty($chat_link)) {
        $message = "❌ Please provide at least one link (Chat Link OR Meeting Link) so group members can connect!";
        $message_type = "error";
    } else {
        // Convert empty strings to NULL
        $meeting_link = !empty($meeting_link) ? "'$meeting_link'" : "NULL";
        $chat_link = !empty($chat_link) ? "'$chat_link'" : "NULL";
        
        $sql = "INSERT INTO StudyGroup (subject, max_members, created_by, meeting_link, chat_link) 
                VALUES ('$subject', '$max_members', '$student_id', $meeting_link, $chat_link)";
        
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
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Study Group - CampusTutor</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-container input, .form-container textarea { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .help-text { font-size: 12px; color: #666; margin-top: -10px; margin-bottom: 15px; }
        .required-note { background: #fff3cd; padding: 10px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Create a Study Group</h1>
            <p>Connect with peers to study together</p>
        </div>
        
        <div class="required-note">
            ⚠️ <strong>Important:</strong> You must provide at least ONE link (Chat Link OR Meeting Link) so group members can connect with you.
        </div>
        
        <?php if($message): ?>
            <div class="<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" onsubmit="return validateLinks()">
                <label>📖 Subject / Topic:</label>
                <input type="text" name="subject" placeholder="e.g., Database Systems Study Group" required>
                
                <label>👥 Max Members (3 or 4):</label>
                <select name="max_members" required>
                    <option value="3">3 members</option>
                    <option value="4" selected>4 members</option>
                </select>
                
                <label>💬 Chat Link (WhatsApp/Telegram/Discord):</label>
                <input type="url" name="chat_link" id="chat_link" placeholder="https://chat.whatsapp.com/... or https://t.me/...">
                <div class="help-text">Optional if you provide a Meeting Link below</div>
                
                <label>📅 Meeting Link (Google Meet/Zoom):</label>
                <input type="url" name="meeting_link" id="meeting_link" placeholder="https://meet.google.com/... or https://zoom.us/j/...">
                <div class="help-text">Optional if you provide a Chat Link above</div>
                
                <button type="submit">✨ Create Group</button>
            </form>
        </div>
        
        <p style="margin-top: 20px; text-align: center;">
            <a href="dashboard.php">← Back to Dashboard</a>
        </p>
    </div>
</body>

<script>
function validateLinks() {
    var chatLink = document.getElementById('chat_link').value.trim();
    var meetingLink = document.getElementById('meeting_link').value.trim();
    
    if (chatLink === '' && meetingLink === '') {
        alert('Please provide at least ONE link (Chat Link OR Meeting Link) so group members can connect with you!');
        return false;
    }
    return true;
}
</script>

</html>