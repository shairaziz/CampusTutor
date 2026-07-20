<?php
include 'dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$tutor_id = isset($_GET['tutor_id']) ? (int)$_GET['tutor_id'] : 0;

// Get tutor details
$tutor = $conn->query("
    SELECT t.tutor_id, u.name, u.email, u.phone, s.department, s.semester, s.cgpa, t.avg_rating, t.bio, t.verification_status
    FROM Tutor t
    JOIN Student s ON t.tutor_id = s.student_id
    JOIN User u ON t.tutor_id = u.user_id
    WHERE t.tutor_id = $tutor_id
")->fetch_assoc();

if (!$tutor) {
    die("Tutor not found.");
}

// Get approved courses
$courses = $conn->query("
    SELECT c.course_id, c.course_name, qf.grade_obtained
    FROM QualifiesFor qf
    JOIN Course c ON qf.course_id = c.course_id
    WHERE qf.tutor_id = $tutor_id AND qf.approval_status = 'approved'
");

// Get reviews
$reviews = $conn->query("
    SELECT r.rating, r.comment, r.review_date, u.name as student_name
    FROM Review r
    JOIN User u ON r.student_id = u.user_id
    WHERE r.tutor_id = $tutor_id
    ORDER BY r.review_date DESC
");

// Get recommendations
$recommendations = $conn->query("
    SELECT pu.name as professor_name, c.course_name, r.note
    FROM Recommendation r
    JOIN Professor p ON r.professor_id = p.professor_id
    JOIN User pu ON p.professor_id = pu.user_id
    JOIN Course c ON r.course_id = c.course_id
    WHERE r.tutor_id = $tutor_id
");
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $tutor['name'] ?> - Tutor Profile</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-header { text-align: center; padding: 20px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; border-radius: 15px; margin-bottom: 20px; }
        .avatar { font-size: 60px; }
        .info-section { background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
        .review-card { background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 10px; }
        .stars { color: #f9ab00; }
        .badge-approved { background: #28a745; color: white; padding: 3px 10px; border-radius: 15px; font-size: 12px; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <div class="avatar">👨‍🏫</div>
            <h1><?= $tutor['name'] ?></h1>
            <p>⭐ Average Rating: <?= $tutor['avg_rating'] ? number_format($tutor['avg_rating'], 1) . '/5.0' : 'No ratings yet' ?></p>
            <?php if($tutor['verification_status'] == 'approved'): ?>
                <span class="badge-approved">✓ Verified Tutor</span>
            <?php endif; ?>
        </div>

        <div class="info-section">
            <h3>📞 Contact Information</h3>
            <p>Email: <?= $tutor['email'] ?></p>
            <p>Phone: <?= $tutor['phone'] ?: 'Not provided' ?></p>
        </div>

        <div class="info-section">
            <h3>🎓 Academic Information</h3>
            <p>Department: <?= $tutor['department'] ?></p>
            <p>Semester: <?= $tutor['semester'] ?></p>
            <p>CGPA: <?= $tutor['cgpa'] ?></p>
        </div>

        <div class="info-section">
            <h3>📚 Approved Courses (A/A+ grade required)</h3>
            <?php if($courses->num_rows > 0): ?>
                <ul>
                    <?php while($course = $courses->fetch_assoc()): ?>
                        <li><?= $course['course_name'] ?> (<?= $course['course_id'] ?>) - Grade: <?= $course['grade_obtained'] ?></li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No approved courses yet.</p>
            <?php endif; ?>
        </div>

        <?php if($recommendations->num_rows > 0): ?>
        <div class="info-section">
            <h3>🏆 Professor Recommendations</h3>
            <?php while($rec = $recommendations->fetch_assoc()): ?>
                <p>✅ Recommended by <strong><?= $rec['professor_name'] ?></strong> for <?= $rec['course_name'] ?></p>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <div class="info-section">
            <h3>⭐ Student Reviews</h3>
            <?php if($reviews->num_rows > 0): ?>
                <?php while($review = $reviews->fetch_assoc()): ?>
                    <div class="review-card">
                        <div class="stars"><?= str_repeat("★", $review['rating']) . str_repeat("☆", 5 - $review['rating']) ?></div>
                        <p><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                        <small>— <?= $review['student_name'] ?> on <?= $review['review_date'] ?></small>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No reviews yet.</p>
            <?php endif; ?>
        </div>

        <p><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>