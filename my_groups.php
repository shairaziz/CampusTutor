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
    
    // Check if user is the creator (creator cannot leave)
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

// Handle delete request (only creator can delete)
if (isset($_GET['delete'])) {
    $group_id = $_GET['delete'];
    
    // Verify user is creator
    $group = $conn->query("SELECT created_by FROM StudyGroup WHERE group_id='$group_id'")->fetch_assoc();
    
    if ($group['created_by'] == $student_id) {
        // Delete group members first (foreign key constraint)
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
</head>
<body>
    <div class="container">
        <h1>📖 My Study Groups</h1>
        
        <?php if($message): ?>
            <div class="<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if($my_groups->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Subject</th>
                    <th>Created By</th>
                    <th>Members</th>
                    <th>Actions</th>
                </tr>
                <?php while($group = $my_groups->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $group['subject']; ?></td>
                    <td><?php echo $group['creator_name']; ?></td>
                    <td><?php echo $group['total_members']; ?> / <?php echo $group['max_members']; ?></td>
                    <td>
                        <?php if($group['created_by'] == $student_id): ?>
                            <a href="?delete=<?php echo $group['group_id']; ?>" 
                               onclick="return confirm('Delete this group? All members will be removed.')"
                               class="delete-btn">Delete Group</a>
                        <?php else: ?>
                            <a href="?leave=<?php echo $group['group_id']; ?>" 
                               onclick="return confirm('Leave this group?')"
                               class="leave-btn">Leave</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>You haven't joined any study groups yet.</p>
        <?php endif; ?>
        
        <p style="margin-top: 20px;">
            <a href="create_group.php">➕ Create a new group</a> | 
            <a href="join_group.php">👥 Join a group</a> |
            <a href="dashboard.php">← Back to Dashboard</a>
        </p>
    </div>
</body>
</html>