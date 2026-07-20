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

// Handle leave request
if (isset($_GET['leave'])) {
    $group_id = $_GET['leave'];
    
    $group = $conn->query("SELECT created_by FROM StudyGroup WHERE group_id='$group_id'")->fetch_assoc();
    
    if ($group['created_by'] == $student_id) {
        $message = "❌ You are the creator of this group. You cannot leave. Delete the group instead.";
        $message_type = "error";
    } else {
        $conn->query("DELETE FROM GroupMember WHERE group_id='$group_id' AND student_id='$student_id'");
        $message = "✅ You left the group successfully.";
        $message_type = "success";
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $group_id = $_GET['delete'];
    
    $group = $conn->query("SELECT created_by FROM StudyGroup WHERE group_id='$group_id'")->fetch_assoc();
    
    if ($group['created_by'] == $student_id) {
        $conn->query("DELETE FROM GroupMember WHERE group_id='$group_id'");
        $conn->query("DELETE FROM StudyGroup WHERE group_id='$group_id'");
        $message = "✅ Group deleted successfully.";
        $message_type = "success";
    } else {
        $message = "❌ Only the creator can delete the group.";
        $message_type = "error";
    }
}

// Get all groups the student is part of
$my_groups = $conn->query("
    SELECT g.*, 
           COUNT(gm2.student_id) as total_members,
           u.name as creator_name
    FROM GroupMember gm
    JOIN StudyGroup g ON gm.group_id = g.group_id
    JOIN User u ON g.created_by = u.user_id
    LEFT JOIN GroupMember gm2 ON g.group_id = gm2.group_id
    WHERE gm.student_id = '$student_id'
    GROUP BY g.group_id
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Study Groups - CampusTutor</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .group-card { background: white; padding: 20px; border-radius: 15px; margin-bottom: 15px; border-left: 5px solid #1e3c72; }
        .group-title { color: #1e3c72; margin-bottom: 10px; }
        .link-btn { display: inline-block; margin-top: 10px; margin-right: 10px; padding: 8px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 8px; font-size: 14px; }
        .link-btn:hover { background: #1e7e34; }
        .chat-btn { background: #25D366; }
        .chat-btn:hover { background: #128C7E; }
        .leave-btn { background: #ffc107; color: #333; }
        .leave-btn:hover { background: #e0a800; }
        .delete-btn { background: #dc3545; }
        .delete-btn:hover { background: #c82333; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📖 My Study Groups</h1>
            <p>Groups you've joined — connect with members!</p>
        </div>
        
        <?php if($message): ?>
            <div class="<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if($my_groups && $my_groups->num_rows > 0): ?>
            <?php while($group = $my_groups->fetch_assoc()): ?>
                <div class="group-card">
                    <h2 class="group-title">📚 <?= $group['subject'] ?></h2>
                    <p class="meta">👥 Members: <?= $group['total_members'] ?> / <?= $group['max_members'] ?></p>
                    <p class="meta">👑 Created by: <?= $group['creator_name'] ?></p>
                    
                    <?php if($group['chat_link']): ?>
                        <a href="<?= $group['chat_link'] ?>" target="_blank" class="link-btn chat-btn">💬 Join Chat</a>
                    <?php endif; ?>
                    
                    <?php if($group['meeting_link']): ?>
                        <a href="<?= $group['meeting_link'] ?>" target="_blank" class="link-btn">📅 Join Meeting</a>
                    <?php endif; ?>
                    
                    <?php if($group['created_by'] == $student_id): ?>
                        <a href="?delete=<?= $group['group_id'] ?>" 
                           onclick="return confirm('Delete this group? All members will be removed.')"
                           class="link-btn delete-btn">🗑️ Delete Group</a>
                    <?php else: ?>
                        <a href="?leave=<?= $group['group_id'] ?>" 
                           onclick="return confirm('Leave this group?')"
                           class="link-btn leave-btn">🚪 Leave Group</a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="error">
                You haven't joined any study groups yet.
                <br><br>
                <a href="create_group.php">➕ Create a new group</a> | 
                <a href="join_group.php">👥 Join a group</a>
            </div>
        <?php endif; ?>
        
        <p style="margin-top: 20px; text-align: center;">
            <a href="create_group.php">➕ Create a new group</a> | 
            <a href="join_group.php">👥 Join a group</a> |
            <a href="dashboard.php">← Back to Dashboard</a>
        </p>
    </div>
</body>
</html>