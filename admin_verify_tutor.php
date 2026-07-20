<?php
include 'dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] != 'admin') {
    die("Only admin can access this page.");
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tutor_id = $_POST['tutor_id'];
    $course_id = $_POST['course_id'];
    $action = $_POST['action'];
    
    $status = ($action == 'approve') ? 'approved' : 'rejected';
    $conn->query("UPDATE QualifiesFor SET approval_status = '$status', approval_date = CURDATE() 
                  WHERE tutor_id = $tutor_id AND course_id = '$course_id'");
    
    $message = ($action == 'approve') ? "✅ Tutor approved for this course!" : "❌ Tutor rejected.";
}

// Get pending tutor approvals
$pending = $conn->query("
    SELECT qf.tutor_id, qf.course_id, qf.grade_obtained, u.name as tutor_name, s.department, s.cgpa, c.course_name
    FROM QualifiesFor qf
    JOIN Tutor t ON qf.tutor_id = t.tutor_id
    JOIN Student s ON t.tutor_id = s.student_id
    JOIN User u ON t.tutor_id = u.user_id
    JOIN Course c ON qf.course_id = c.course_id
    WHERE qf.approval_status = 'pending'
    ORDER BY u.name
");

// Get approved tutors
$approved = $conn->query("
    SELECT u.name as tutor_name, c.course_name, c.course_id, qf.grade_obtained
    FROM QualifiesFor qf
    JOIN Tutor t ON qf.tutor_id = t.tutor_id
    JOIN User u ON t.tutor_id = u.user_id
    JOIN Course c ON qf.course_id = c.course_id
    WHERE qf.approval_status = 'approved'
    ORDER BY c.course_name, u.name
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Verify Tutors - CampusTutor</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .pending-card { background: white; padding: 20px; border-radius: 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .approve-btn { background: #28a745; color: white; padding: 8px 20px; border: none; border-radius: 8px; cursor: pointer; margin-right: 10px; }
        .reject-btn { background: #dc3545; color: white; padding: 8px 20px; border: none; border-radius: 8px; cursor: pointer; }
        .grade-badge { background: #f9ab00; color: #333; padding: 3px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Admin: Verify Tutor Eligibility</h1>
            <p>Approve or reject tutors who applied to teach courses (requires A/A+ grade)</p>
        </div>

        <?php if(isset($message)): ?>
            <div class="success"><?= $message ?></div>
        <?php endif; ?>

        <h2>Pending Tutor Approvals</h2>
        <?php if($pending && $pending->num_rows > 0): ?>
            <?php while($row = $pending->fetch_assoc()): ?>
                <div class="pending-card">
                    <div>
                        <h3><?= $row['tutor_name'] ?></h3>
                        <p class="meta">Department: <?= $row['department'] ?> | CGPA: <?= $row['cgpa'] ?></p>
                        <p class="meta">Course: <?= $row['course_name'] ?> (<?= $row['course_id'] ?>)</p>
                        <span class="grade-badge">🎓 Grade: <?= $row['grade_obtained'] ?></span>
                    </div>
                    <div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="tutor_id" value="<?= $row['tutor_id'] ?>">
                            <input type="hidden" name="course_id" value="<?= $row['course_id'] ?>">
                            <button type="submit" name="action" value="approve" class="approve-btn">✅ Approve</button>
                            <button type="submit" name="action" value="reject" class="reject-btn">❌ Reject</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No pending tutor approvals at this time.</p>
        <?php endif; ?>

        <h2 style="margin-top: 30px;">Approved Tutors by Course</h2>
        <?php if($approved && $approved->num_rows > 0): ?>
            <table style="width: 100%; background: white; border-radius: 15px; overflow: hidden; border-collapse: collapse;">
                <tr style="background: #1e3c72; color: white;">
                    <th style="padding: 12px; text-align: left;">Tutor</th>
                    <th style="padding: 12px; text-align: left;">Course</th>
                    <th style="padding: 12px; text-align: left;">Grade</th>
                 </tr>
                <?php while($row = $approved->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #ddd;">
                        <td style="padding: 10px;"><?= $row['tutor_name'] ?></td>
                        <td style="padding: 10px;"><?= $row['course_name'] ?></td>
                        <td style="padding: 10px;"><?= $row['grade_obtained'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No approved tutors yet.</p>
        <?php endif; ?>

        <p style="margin-top: 20px;"><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>