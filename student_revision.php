<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'dbconnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'tutor')) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$message = '';
$msg_type = '';

// Get courses the current user teaches (if they are a tutor)
$my_courses = [];
if ($role === 'tutor') {
    $my_courses_result = $conn->query("SELECT course_id FROM QualifiesFor WHERE tutor_id = $student_id AND approval_status = 'approved'");
    while ($row = $my_courses_result->fetch_assoc()) {
        $my_courses[] = $row['course_id'];
    }
}

// Build exclusion for own courses
$course_exclude = "";
if (!empty($my_courses)) {
    $course_list = implode("','", $my_courses);
    $course_exclude = "AND rs.course_id NOT IN ('$course_list')";
}

// Handle JOIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revision_id'])) {
    $revision_id = (int)$_POST['revision_id'];

    $check = $conn->query("
        SELECT rs.revision_id, rs.status, rs.exam_date, rs.min_students,
               COUNT(rr.request_id) AS joined
        FROM RevisionSession rs
        LEFT JOIN RevisionRequest rr ON rs.revision_id = rr.revision_id
        WHERE rs.revision_id = $revision_id
        GROUP BY rs.revision_id
    ");
    $sess = $check->fetch_assoc();

    if (!$sess) {
        $message = "Session not found.";
        $msg_type = 'error';
    } elseif ($sess['status'] === 'cancelled') {
        $message = "This session has been cancelled.";
        $msg_type = 'error';
    } elseif ($sess['status'] === 'pending_tutor') {
        $message = "No tutor has accepted this session yet. Please check back later.";
        $msg_type = 'error';
    } else {
        $days_left = (strtotime($sess['exam_date']) - strtotime(date('Y-m-d'))) / 86400;
        if ($days_left > 7) {
            $message = "Joining opens 7 days before the exam. Exam is in " . ceil($days_left) . " days.";
            $msg_type = 'error';
        } else {
            $already = $conn->query("SELECT request_id FROM RevisionRequest WHERE revision_id = $revision_id AND student_id = $student_id");
            if ($already->num_rows > 0) {
                $message = "You have already joined this session.";
                $msg_type = 'error';
            } else {
                $conn->query("INSERT INTO RevisionRequest (revision_id, student_id) VALUES ($revision_id, $student_id)");
                $new_count = $sess['joined'] + 1;
                if ($new_count >= $sess['min_students'] && $sess['status'] === 'pending') {
                    $conn->query("UPDATE RevisionSession SET status = 'confirmed' WHERE revision_id = $revision_id");
                    $message = "✅ Joined! The session is now CONFIRMED — enough students have joined.";
                    $msg_type = 'success';
                } else {
                    $remaining = $sess['min_students'] - $new_count;
                    $message = "✅ You've joined! Waiting for $remaining more student(s) to confirm.";
                    $msg_type = 'success';
                }
            }
        }
    }
}

// Handle LEAVE
if (isset($_GET['leave'])) {
    $rid = (int)$_GET['leave'];
    $conn->query("DELETE FROM RevisionRequest WHERE revision_id = $rid AND student_id = $student_id");
    $message = "You have left the session.";
    $msg_type = 'warning';
}

// Get ALL available revision sessions (excluding courses the tutor teaches)
$available_sessions = $conn->query("
    SELECT
        rs.revision_id,
        rs.course_id,
        rs.exam_date,
        rs.session_date,
        rs.topic,
        rs.min_students,
        rs.status,
        c.course_name,
        u.name AS tutor_name,
        COUNT(rr.request_id) AS joined_count,
        MAX(rr.student_id = $student_id) AS i_joined,
        DATEDIFF(rs.exam_date, CURDATE()) AS days_left
    FROM RevisionSession rs
    JOIN Course c ON rs.course_id = c.course_id
    LEFT JOIN User u ON rs.tutor_id = u.user_id
    LEFT JOIN RevisionRequest rr ON rs.revision_id = rr.revision_id
    WHERE rs.status IN ('pending', 'confirmed')
      $course_exclude
    GROUP BY rs.revision_id
    ORDER BY rs.exam_date ASC
");

// Get sessions student has joined
$my_sessions = $conn->query("
    SELECT
        rs.revision_id,
        rs.course_id,
        rs.exam_date,
        rs.session_date,
        rs.topic,
        rs.min_students,
        rs.status,
        c.course_name,
        u.name AS tutor_name,
        COUNT(rr.request_id) AS joined_count
    FROM RevisionSession rs
    JOIN Course c ON rs.course_id = c.course_id
    LEFT JOIN User u ON rs.tutor_id = u.user_id
    JOIN RevisionRequest rr ON rs.revision_id = rr.revision_id
    WHERE rr.student_id = $student_id
    GROUP BY rs.revision_id
    ORDER BY rs.exam_date ASC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Revision Sessions - CampusTutor</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .revision-card { background: white; padding: 20px; border-radius: 15px; margin-bottom: 15px; border-left: 5px solid #1e3c72; }
        .urgent { background: #fdecea; color: #c0392b; padding: 2px 8px; border-radius: 8px; font-size: 12px; display: inline-block; margin-left: 10px; }
        .btn-join { background: #28a745; color: white; padding: 8px 20px; border: none; border-radius: 8px; cursor: pointer; }
        .btn-leave { background: #dc3545; color: white; padding: 8px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; }
        .prog-bar { background: #ecf0f1; border-radius: 4px; height: 8px; width: 100px; display: inline-block; }
        .prog-fill { background: #28a745; height: 8px; border-radius: 4px; }
        .section-title { margin: 30px 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #1e3c72; }
        .info-note { background: #e8f0fe; padding: 10px 15px; border-radius: 10px; margin-bottom: 20px; color: #1e3c72; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Exam Revision Sessions</h1>
            <p>Sessions with confirmed tutors. Join when your exam is within 7 days.</p>
        </div>

        <?php if($role === 'tutor' && !empty($my_courses)): ?>
        <div class="info-note">
            ℹ️ You are a tutor for: <strong><?= implode(', ', $my_courses) ?></strong>. 
            Revision sessions for these courses are hidden (you can conduct them instead!).
        </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= $message ?></div>
        <?php endif; ?>

        <!-- My Joined Sessions Section -->
        <h2 class="section-title">📋 My Joined Sessions</h2>
        <?php if ($my_sessions->num_rows === 0): ?>
            <p>You haven't joined any revision sessions yet.</p>
        <?php else: ?>
            <?php while ($row = $my_sessions->fetch_assoc()):
                $pct = $row['min_students'] > 0 ? min(100, round($row['joined_count'] / $row['min_students'] * 100)) : 0;
            ?>
            <div class="revision-card">
                <div style="display: flex; justify-content: space-between;">
                    <div><h2><?= htmlspecialchars($row['topic']) ?></h2><p><?= $row['course_id'] ?> – <?= $row['course_name'] ?></p></div>
                    <div><span class="badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></div>
                </div>
                <p>🗓️ Session: <strong><?= date('d M Y, h:i A', strtotime($row['session_date'])) ?></strong></p>
                <p>🎓 Exam: <strong><?= date('d M Y', strtotime($row['exam_date'])) ?></strong></p>
                <p>👥 Joined: <strong><?= $row['joined_count'] ?>/<?= $row['min_students'] ?></strong> <span class="prog-bar"><span class="prog-fill" style="width:<?= $pct ?>%"></span></span></p>
                <?php if ($row['tutor_name']): ?><p>👨‍🏫 Tutor: <?= htmlspecialchars($row['tutor_name']) ?></p><?php endif; ?>
                <div><span style="color:green;">✅ You're in</span> <a href="?leave=<?= $row['revision_id'] ?>" class="btn-leave" onclick="return confirm('Leave this session?')">Leave Session</a></div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <!-- Available Sessions Section -->
        <h2 class="section-title">🎯 Available Sessions</h2>
        <?php if ($available_sessions->num_rows === 0): ?>
            <p>No revision sessions available yet.</p>
        <?php else: ?>
            <?php while ($row = $available_sessions->fetch_assoc()):
                $pct = $row['min_students'] > 0 ? min(100, round($row['joined_count'] / $row['min_students'] * 100)) : 0;
                $joinable = ($row['days_left'] <= 7 && $row['days_left'] >= 0);
                if ($row['i_joined']) continue;
            ?>
            <div class="revision-card">
                <div style="display: flex; justify-content: space-between;">
                    <div><h2><?= htmlspecialchars($row['topic']) ?></h2><p><?= $row['course_id'] ?> – <?= $row['course_name'] ?></p></div>
                    <div><?php if ($row['days_left'] <= 7) echo "<span class='urgent'>⚡ {$row['days_left']} day(s) left!</span>"; ?></div>
                </div>
                <p>🗓️ Session: <strong><?= date('d M Y, h:i A', strtotime($row['session_date'])) ?></strong></p>
                <p>🎓 Exam: <strong><?= date('d M Y', strtotime($row['exam_date'])) ?></strong></p>
                <p>👥 Joined: <strong><?= $row['joined_count'] ?>/<?= $row['min_students'] ?></strong> <span class="prog-bar"><span class="prog-fill" style="width:<?= $pct ?>%"></span></span></p>
                <?php if ($row['tutor_name']): ?><p>👨‍🏫 Tutor: <?= htmlspecialchars($row['tutor_name']) ?></p><?php endif; ?>
                <div>
                    <?php if ($row['i_joined']): ?>
                        <span style="color:green;">✅ You're in</span> <a href="?leave=<?= $row['revision_id'] ?>" class="btn-leave">Leave</a>
                    <?php elseif (!$joinable): ?>
                        <button disabled style="background:#ccc;">Opens in <?= max(0, $row['days_left'] - 7) ?> day(s)</button>
                    <?php else: ?>
                        <form method="POST" style="display:inline;"><input type="hidden" name="revision_id" value="<?= $row['revision_id'] ?>"><button type="submit" class="btn-join">Join Session</button></form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <p><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>