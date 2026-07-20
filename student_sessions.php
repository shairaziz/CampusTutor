<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'dbconnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'tutor')) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

$sessions = $conn->query("
    SELECT s.session_id, u.name AS tutor_name, s.course_id, s.session_date, s.start_time, s.end_time, s.status
    FROM Session s
    JOIN User u ON s.tutor_id = u.user_id
    WHERE s.student_id = $student_id
    ORDER BY s.session_date DESC, s.start_time DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Sessions - CampusTutor</title>
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
            <h1>📋 My Booked Sessions</h1>
            <p>View all your tutoring session requests</p>
        </div>

        <?php if ($sessions->num_rows === 0): ?>
            <p>No sessions yet. <a href="student_browse_tutors.php">Book one now!</a></p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <tr><th>Tutor</th><th>Course</th><th>Date</th><th>Time</th><th>Status</th></tr>
                    <?php while ($row = $sessions->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['tutor_name']) ?></td>
                        <td><?= $row['course_id'] ?></td>
                        <td><?= date('d M Y', strtotime($row['session_date'])) ?></td>
                        <td><?= date('h:i A', strtotime($row['start_time'])) ?> – <?= date('h:i A', strtotime($row['end_time'])) ?></td>
                        <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        <?php endif; ?>

        <p><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>