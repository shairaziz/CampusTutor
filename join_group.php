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

// Handle join request
if (isset($_GET['join'])) {
    $group_id = $_GET['join'];
    
    // Check if group is full
    $group = $conn->query("SELECT * FROM StudyGroup WHERE group_id='$group_id'")->fetch_assoc();
    $member_count = $conn->query("SELECT COUNT(*) as count FROM GroupMember WHERE group_id='$group_id'")->fetch_assoc();
    
    if ($member_count['count'] >= $group['max_members']) {
        $message = "❌ This group is full!";
        $message_type = "error";
    } else {
        // Check if already a member
        $check = $conn->query("SELECT * FROM GroupMember WHERE group_id='$group_id' AND student_id='$student_id'");
        if ($check->num_rows == 0) {
            $conn->query("INSERT INTO GroupMember (group_id, student_id) VALUES ('$group_id', '$student_id')");
            $message = "✅ You joined the group successfully!";
            $message_type = "success";
        } else {
            $message = "❌ You are already a member of this group!";
            $message_type = "error";
        }
    }
}

// Get all available groups (not full AND not created by the logged-in user)
$available_groups = $conn->query("
    SELECT g.*, 
           COUNT(gm.student_id) as current_members,
           u.name as creator_name
    FROM StudyGroup g
    JOIN User u ON g.created_by = u.user_id
    LEFT JOIN GroupMember gm ON g.group_id = gm.group_id
    WHERE g.created_by != '$student_id'
    GROUP BY g.group_id
    HAVING current_members < g.max_members
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Join Study Group - CampusTutor</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .join-btn { background: #28a745; color: white; padding: 5px 15px; text-decoration: none; border-radius: 5px; display: inline-block; }
        .join-btn:hover { background: #1e7e34; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #1e3c72; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👥 Join a Study Group</h1>
            <p>Groups you haven't joined yet (your own groups won't appear here)</p>
        </div>
        
        <?php if($message): ?>
            <div class="<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if($available_groups && $available_groups->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Created By</th>
                            <th>Members</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($group = $available_groups->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $group['subject']; ?></td>
                                <td><?php echo $group['creator_name']; ?></td>
                                <td><?php echo $group['current_members']; ?> / <?php echo $group['max_members']; ?></td>
                                <td>
                                    <a href="?join=<?php echo $group['group_id']; ?>" 
                                       onclick="return confirm('Join this group?')"
                                       class="join-btn">Join</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="error">
                No available study groups right now. 
                <a href="create_group.php">Create a new group</a>
            </div>
        <?php endif; ?>
        
        <p style="margin-top: 20px;">
            <a href="create_group.php">➕ Create a new group</a> | 
            <a href="my_groups.php">📖 My Study Groups</a> |
            <a href="dashboard.php">← Back to Dashboard</a>
        </p>
    </div>
</body>
</html>