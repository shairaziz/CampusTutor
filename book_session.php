<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'dbconnect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$tutor_id = isset($_GET['tutor_id']) ? (int)$_GET['tutor_id'] : 0;
$message = '';
$msg_type = '';

$tutor = $conn->query("
    SELECT u.name, t.bio, t.avg_rating, s.department, s.cgpa
    FROM User u
    JOIN Tutor t ON u.user_id = t.tutor_id
    JOIN Student s ON u.user_id = s.student_id
    WHERE u.user_id = $tutor_id AND t.verification_status = 'approved'
")->fetch_assoc();

if (!$tutor) { die("Tutor not found."); }

$avail = $conn->query("SELECT * FROM Availability WHERE tutor_id = $tutor_id ORDER BY FIELD(day,'Mon','Tue','Wed','Thu','Fri','Sat','Sun'), start_time");

$courses = $conn->query("
    SELECT qf.course_id, c.course_name FROM QualifiesFor qf
    JOIN Course c ON qf.course_id = c.course_id
    WHERE qf.tutor_id = $tutor_id AND qf.approval_status = 'approved'
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $session_date = $_POST['session_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $check = $conn->query("
        SELECT session_id FROM Session
        WHERE student_id = $student_id AND tutor_id = $tutor_id
          AND session_date = '$session_date' AND status IN ('pending','accepted')
    ");
    if ($check->num_rows > 0) {
        $message = "❌ You already have a pending or accepted session with this tutor on that date.";
        $msg_type = 'error';
    } elseif ($start_time >= $end_time) {
        $message = "❌ End time must be after start time.";
        $msg_type = 'error';
    } else {
        $sql = "INSERT INTO Session (student_id, tutor_id, course_id, session_date, start_time, end_time, status)
                VALUES ($student_id, $tutor_id, '$course_id', '$session_date', '$start_time', '$end_time', 'pending')";
        if ($conn->query($sql)) {
            $message = "✅ Session request sent! Waiting for tutor to accept.";
            $msg_type = 'success';
        } else {
            $message = "❌ Error: " . $conn->error;
            $msg_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Session - CampusTutor</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Book a Session</h1>
            <p>Request a tutoring session</p>
        </div>

        <a href="student_browse_tutors.php">← Back to Tutors</a>

        <div class="tutor-info" style="background: #e8f0fe; padding: 20px; border-radius: 15px; margin: 20px 0;">
            <h2><?= htmlspecialchars($tutor['name']) ?></h2>
            <p><?= $tutor['department'] ?> | CGPA: <?= $tutor['cgpa'] ?> | ⭐ <?= number_format($tutor['avg_rating'], 1) ?></p>
            <?php if ($tutor['bio']): ?><p><em><?= htmlspecialchars($tutor['bio']) ?></em></p><?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= $message ?></div>
        <?php endif; ?>

        <h3>🕐 Available Slots</h3>
        <?php if ($avail->num_rows === 0): ?>
            <p>No availability set by this tutor.</p>
        <?php else: ?>
            <div class="table-container">
                <table class="slot-table">
                    <tr><th>Day</th><th>Start</th><th>End</th></tr>
                    <?php while ($sl = $avail->fetch_assoc()): ?>
                    <tr>
                        <td><?= $sl['day'] ?></td>
                        <td><?= date('h:i A', strtotime($sl['start_time'])) ?></td>
                        <td><?= date('h:i A', strtotime($sl['end_time'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        <?php endif; ?>

        <h3>📝 Request a Session</h3>
        <form method="POST">
            <label>Course</label>
            <select name="course_id" required>
                <option value="">-- Select Course --</option>
                <?php while ($c = $courses->fetch_assoc()): ?>
                    <option value="<?= $c['course_id'] ?>"><?= $c['course_id'] ?> – <?= $c['course_name'] ?></option>
                <?php endwhile; ?>
            </select>

            <label>Date</label>
            <input type="date" name="session_date" min="<?= date('Y-m-d') ?>" required>

            <label>Start Time</label>
            <input type="time" name="start_time" required>

            <label>End Time</label>
            <input type="time" name="end_time" required>

            <button type="submit">Send Booking Request</button>
        </form>

        <p><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>