<?php
include 'dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$selectedCourse = isset($_GET['course_id']) ? $_GET['course_id'] : '';

// Get all courses
$courses = $conn->query("SELECT course_id, course_name FROM Course ORDER BY course_name");

// Get tutors based on filter
if ($selectedCourse != '') {
    $tutors = $conn->query("
        SELECT 
            t.tutor_id,
            u.name AS tutor_name,
            s.department,
            s.cgpa,
            t.avg_rating,
            (SELECT COUNT(*) FROM Recommendation r WHERE r.tutor_id = t.tutor_id) as recommendation_count,
            (SELECT GROUP_CONCAT(DISTINCT c.course_name SEPARATOR ', ') 
             FROM QualifiesFor qf2 
             JOIN Course c ON qf2.course_id = c.course_id 
             WHERE qf2.tutor_id = t.tutor_id AND qf2.approval_status = 'approved') as approved_courses
        FROM Tutor t
        JOIN Student s ON t.tutor_id = s.student_id
        JOIN User u ON t.tutor_id = u.user_id
        WHERE EXISTS (
            SELECT 1 FROM QualifiesFor qf 
            WHERE qf.tutor_id = t.tutor_id 
            AND qf.course_id = '$selectedCourse' 
            AND qf.approval_status = 'approved'
        )
        ORDER BY u.name
    ");
} else {
    $tutors = $conn->query("
        SELECT 
            t.tutor_id,
            u.name AS tutor_name,
            s.department,
            s.cgpa,
            t.avg_rating,
            (SELECT COUNT(*) FROM Recommendation r WHERE r.tutor_id = t.tutor_id) as recommendation_count,
            (SELECT GROUP_CONCAT(DISTINCT c.course_name SEPARATOR ', ') 
             FROM QualifiesFor qf2 
             JOIN Course c ON qf2.course_id = c.course_id 
             WHERE qf2.tutor_id = t.tutor_id AND qf2.approval_status = 'approved') as approved_courses
        FROM Tutor t
        JOIN Student s ON t.tutor_id = s.student_id
        JOIN User u ON t.tutor_id = u.user_id
        WHERE EXISTS (
            SELECT 1 FROM QualifiesFor qf 
            WHERE qf.tutor_id = t.tutor_id AND qf.approval_status = 'approved'
        )
        ORDER BY u.name
    ");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verified Tutors - CampusTutor</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .filter-bar { background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
        .tutor-card { background: white; padding: 20px; border-radius: 15px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .tutor-name { color: #1e3c72; margin-bottom: 10px; }
        .meta { color: #666; font-size: 14px; margin: 5px 0; }
        .badge { display: inline-block; background: #e8f0fe; color: #1e3c72; padding: 3px 10px; border-radius: 15px; font-size: 12px; margin-top: 10px; }
        .rating { color: #f9ab00; font-weight: bold; }
        .btn { display: inline-block; margin-top: 10px; padding: 8px 15px; background: #1e3c72; color: white; text-decoration: none; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Verified Tutors</h1>
            <p>Tutors who earned A/A+ in their courses and are approved to tutor</p>
        </div>

        <div class="filter-bar">
            <form method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <label><strong>Filter by Course:</strong></label>
                <select name="course_id">
                    <option value="">All Courses</option>
                    <?php while($course = $courses->fetch_assoc()): ?>
                        <option value="<?= $course['course_id'] ?>" <?= $selectedCourse == $course['course_id'] ? 'selected' : '' ?>>
                            <?= $course['course_name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit">Apply Filter</button>
            </form>
        </div>

        <?php if($tutors && $tutors->num_rows > 0): ?>
            <?php while($tutor = $tutors->fetch_assoc()): ?>
                <div class="tutor-card">
                    <h2 class="tutor-name"><?= $tutor['tutor_name'] ?></h2>
                    <p class="meta">Department: <?= $tutor['department'] ?></p>
                    <p class="meta">CGPA: <?= $tutor['cgpa'] ?></p>
                    <p class="meta"><strong>Approved Courses:</strong> <?= $tutor['approved_courses'] ?? 'None' ?></p>
                    <p class="rating">⭐ Average Rating: <?= $tutor['avg_rating'] ? number_format($tutor['avg_rating'], 1) . '/5.0' : 'No ratings yet' ?></p>
                    <?php if($tutor['recommendation_count'] > 0): ?>
                        <span class="badge">🎓 Recommended by <?= $tutor['recommendation_count'] ?> professor(s)</span>
                    <?php endif; ?>
                    <br>
                    <a href="tutor_profile.php?tutor_id=<?= $tutor['tutor_id'] ?>" class="btn">View Full Profile →</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No verified tutors found for this course.</p>
        <?php endif; ?>
        
        <p><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>