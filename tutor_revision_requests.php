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

// Handle ACCEPT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revision_id'])) {
    $revision_id = (int)$_POST['revision_id'];
    $conn->query("UPDATE RevisionSession SET tutor_id = $tutor_id, tutor_status = 'accepted', status = 'pending' WHERE revision_id = $revision_id AND status = 'pending_tutor'");
    if ($conn->affected_rows > 0) {
        $message = "✅ You accepted this session! Students can now join.";
        $msg_type = 'success';
    } else {
        $message = "❌ Another tutor already accepted this session.";
        $msg_type = 'error';
    }
}

// Get sessions tutor is conducting (accepted)
$my_sessions = $conn->query("
    SELECT rs.*, c.course_name, COUNT(rr.request_id) AS student_count
    FROM RevisionSession rs
    JOIN Course c ON rs.course_id = c.course_id
    LEFT JOIN RevisionRequest rr ON rs.revision_id = rr.revision_id
    WHERE rs.tutor_id = $tutor_id
    GROUP BY rs.revision_id
    ORDER BY rs.exam_date ASC
");

// Get pending requests (available for tutor to accept)
$pending_requests = $conn->query("
    SELECT rs.*, c.course_name
    FROM RevisionSession rs
    JOIN Course c ON rs.course_id = c.course_id
    JOIN QualifiesFor qf ON qf.course_id = rs.course_id AND qf.tutor_id = $tutor_id AND qf.approval_status = 'approved'
    WHERE rs.status = 'pending_tutor'
    ORDER BY rs.exam_date ASC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tutor - Revision Sessions</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .section-title { margin: 30px 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #1e3c72; }
        .card { background: white; padding: 20px; border-radius: 15px; margin-bottom: 15px; border-left: 5px solid #1e3c72; }
        .btn-accept { background: #28a745; color: white; padding: 8px 20px; border: none; border-radius: 8px; cursor: pointer; }
        .badge-conducting { background: #28a745; color: white; padding: 3px 10px; border-radius: 10px; font-size: 12px; display: inline-block; }
        .badge-pending { background: #f39c12; color: white; padding: 3px 10px; border-radius: 10px; font-size: 12px; display: inline-block; }
        .info-note { background: #e8f0fe; padding: 10px 15px; border-radius: 10px; margin-bottom: 20px; color: #1e3c72; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Revision Session Management</h1>
            <p>Sessions you are conducting + requests waiting for a tutor</p>
        </div>

        <?php if ($message): ?>
            <div class="<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="info-note">
            ℹ️ You can only accept revision sessions for courses where you are an approved tutor (A/A+ grade required).
        </div>

        <!-- Sessions I Am Conducting -->
        <h2 class="section-title">✅ Sessions I Am Conducting</h2>
        <?php if ($my_sessions->num_rows === 0): ?>
            <p>You are not conducting any revision sessions yet.</p>
        <?php else: ?>
            <?php while ($row = $my_sessions->fetch_assoc()): ?>
            <div class="card">
                <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
                    <h2><?= htmlspecialchars($row['topic']) ?></h2>
                    <span class="badge-conducting">✅ You are conducting</span>
                </div>
                <p><?= $row['course_name'] ?> (<?= $row['course_id'] ?>)</p>
                <p>🗓️ Session: <strong><?= date('d M Y, h:i A', strtotime($row['session_date'])) ?></strong></p>
                <p>🎓 Exam: <strong><?= date('d M Y', strtotime($row['exam_date'])) ?></strong></p>
                <p>👥 Students Joined: <strong><?= $row['student_count'] ?>/<?= $row['min_students'] ?></strong></p>
                <p>📊 Status: <strong><?= ucfirst($row['status']) ?></strong></p>
                <?php if ($row['status'] === 'confirmed'): ?>
                    <p style="color: green;">✅ Session confirmed! Enough students have joined.</p>
                <?php elseif ($row['status'] === 'pending'): ?>
                    <p style="color: orange;">⏳ Waiting for more students to join...</p>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <!-- Pending Requests -->
        <h2 class="section-title">📥 Pending Revision Requests</h2>
        <?php if ($pending_requests->num_rows === 0): ?>
            <p>No pending revision requests for your courses.</p>
        <?php else: ?>
            <?php while ($row = $pending_requests->fetch_assoc()): ?>
            <div class="card">
                <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
                    <h2><?= htmlspecialchars($row['topic']) ?></h2>
                    <span class="badge-pending">⏳ Awaiting Tutor</span>
                </div>
                <p><?= $row['course_name'] ?> (<?= $row['course_id'] ?>)</p>
                <p>🗓️ Session: <strong><?= date('d M Y, h:i A', strtotime($row['session_date'])) ?></strong></p>
                <p>🎓 Exam: <strong><?= date('d M Y', strtotime($row['exam_date'])) ?></strong></p>
                <p>👥 Min Students Required: <strong><?= $row['min_students'] ?></strong></p>
                <form method="POST">
                    <input type="hidden" name="revision_id" value="<?= $row['revision_id'] ?>">
                    <button type="submit" class="btn-accept" onclick="return confirm('Accept this revision session?')">✅ Accept Session</button>
                </form>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <p><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>