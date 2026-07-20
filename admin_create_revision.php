<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'dbconnect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $course_id = $_POST['course_id'];
    $exam_date = $_POST['exam_date'];
    $session_date = $_POST['session_date'];
    $topic = $_POST['topic'];
    $min_students = (int)$_POST['min_students'];

    if ($exam_date <= date('Y-m-d')) {
        $message = "Exam date must be in the future.";
        $msg_type = 'error';
    } elseif ($session_date >= $exam_date) {
        $message = "Session date must be before exam date.";
        $msg_type = 'error';
    } else {
        $sql = "INSERT INTO RevisionSession (course_id, exam_date, session_date, topic, min_students, status, tutor_status)
                VALUES ('$course_id', '$exam_date', '$session_date', '$topic', $min_students, 'pending_tutor', 'pending')";
        if ($conn->query($sql)) {
            $message = "✅ Session created!";
            $msg_type = 'success';
        } else {
            $message = "Error: " . $conn->error;
            $msg_type = 'error';
        }
    }
}

if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM RevisionSession WHERE revision_id = " . (int)$_GET['delete']);
    $message = "Session deleted.";
}

$sessions = $conn->query("SELECT rs.*, c.course_name, u.name AS tutor_name, COUNT(rr.request_id) AS student_count FROM RevisionSession rs JOIN Course c ON rs.course_id = c.course_id LEFT JOIN User u ON rs.tutor_id = u.user_id LEFT JOIN RevisionRequest rr ON rs.revision_id = rr.revision_id GROUP BY rs.revision_id");
$courses = $conn->query("SELECT course_id, course_name FROM Course");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Revision Sessions</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="header"><h1>🗓️ Exam Revision Sessions</h1><p>Create sessions for students</p></div>
        <?php if ($message): ?><div class="<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= $message ?></div><?php endif; ?>
        
        <div class="card"><h3>➕ Create Session</h3>
        <form method="POST"><input type="hidden" name="action" value="create">
            <select name="course_id" required><?php while($c=$courses->fetch_assoc()): ?><option value="<?=$c['course_id']?>"><?=$c['course_name']?></option><?php endwhile; ?></select>
            <input type="date" name="exam_date" placeholder="Exam date" required> <input type="datetime-local" name="session_date" placeholder="Session date" required>
            <input type="text" name="topic" placeholder="Topic" required> <input type="number" name="min_students" placeholder="Minimun Student Number" value="3" min="2">
            <button type="submit">Create</button>
        </form></div>
        
        <h2>All Sessions</h2>
        <div class="table-container"><table><tr><th>Course</th><th>Topic</th><th>Session</th><th>Students</th><th>Tutor</th><th>Status</th><th></th></tr>
        <?php while($row=$sessions->fetch_assoc()): ?>
        <tr><td><?=$row['course_name']?></td><td><?=$row['topic']?></td><td><?=date('d M Y',strtotime($row['session_date']))?></td><td><?=$row['student_count']?>/<?=$row['min_students']?></td><td><?=$row['tutor_name']?:'Waiting'?></td><td><?=$row['status']?></td>
        <td><a href="?delete=<?=$row['revision_id']?>" onclick="return confirm('Delete?')">Delete</a></td></tr>
        <?php endwhile; ?>
        </table></div>
        <p><a href="dashboard.php">← Back</a></p>
    </div>
</body>
</html>