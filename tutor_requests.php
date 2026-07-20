<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'dbconnect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: login.php");
    exit();
}

$tutor_id = $_SESSION['user_id'];
$message = '';
$msg_type = '';

if (isset($_POST['action'], $_POST['session_id'])) {
    $session_id = (int)$_POST['session_id'];
    $action = $_POST['action'] === 'accept' ? 'accepted' : 'rejected';
    $conn->query("UPDATE Session SET status = '$action' WHERE session_id = $session_id AND tutor_id = $tutor_id");
    $message = $action === 'accepted' ? "✅ Session accepted." : "❌ Session rejected.";
    $msg_type = 'success';
}

$sessions = $conn->query("
    SELECT s.session_id, u.name AS student_name, s.course_id, s.session_date, s.start_time, s.end_time, s.status
    FROM Session s
    JOIN User u ON s.student_id = u.user_id
    WHERE s.tutor_id = $tutor_id
    ORDER BY FIELD(s.status,'pending','accepted','rejected','completed'), s.session_date ASC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Session Requests - CampusTutor</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .badge { padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-pending { background: #f39c12; color: white; }
        .badge-accepted { background: #28a745; color: white; }
        .badge-rejected { background: #dc3545; color: white; }
        .badge-completed { background: #1e3c72; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📥 Session Requests</h1>
            <p>Accept or reject student booking requests</p>
        </div>

        <?php if ($message): ?>
            <div class="<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($sessions->num_rows === 0): ?>
            <p>No session requests yet.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <tr><th>Student</th><th>Course</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr>
                    <?php while ($row = $sessions->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                        <td><?= $row['course_id'] ?></td>
                        <td><?= date('d M Y', strtotime($row['session_date'])) ?></td>
                        <td><?= date('h:i A', strtotime($row['start_time'])) ?> – <?= date('h:i A', strtotime($row['end_time'])) ?></td>
                        <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                        <td>
                            <?php if ($row['status'] === 'pending'): ?>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="session_id" value="<?= $row['session_id'] ?>">
                                    <button type="submit" name="action" value="accept" style="background:#28a745; color:white; border:none; padding:4px 12px; border-radius:5px;">Accept</button>
                                </form>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="session_id" value="<?= $row['session_id'] ?>">
                                    <button type="submit" name="action" value="reject" style="background:#dc3545; color:white; border:none; padding:4px 12px; border-radius:5px;">Reject</button>
                                </form>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        <?php endif; ?>

        <p><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>