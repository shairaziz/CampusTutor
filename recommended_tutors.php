<?php
include 'dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$selectedCourse = isset($_GET['course_id']) ? $_GET['course_id'] : '';

$courses = $conn->query("SELECT course_id, course_name FROM Course ORDER BY course_name");

// Get recommended tutors - YOUR ORIGINAL WORKING QUERY
if ($selectedCourse) {
    $recommended = $conn->query("
        SELECT 
            r.tutor_id, 
            u.name as tutor_name, 
            s.department, 
            s.cgpa, 
            t.avg_rating,
            COUNT(DISTINCT r.professor_id) as recommendation_count,
            GROUP_CONCAT(DISTINCT c.course_name ORDER BY c.course_name SEPARATOR ', ') as course_names
        FROM Recommendation r
        JOIN Tutor t ON r.tutor_id = t.tutor_id
        JOIN Student s ON t.tutor_id = s.student_id
        JOIN User u ON t.tutor_id = u.user_id
        JOIN Course c ON r.course_id = c.course_id
        WHERE r.course_id = '$selectedCourse'
        GROUP BY r.tutor_id
        ORDER BY recommendation_count DESC, t.avg_rating DESC
    ");
} else {
    $recommended = $conn->query("
        SELECT 
            r.tutor_id, 
            u.name as tutor_name, 
            s.department, 
            s.cgpa, 
            t.avg_rating,
            COUNT(DISTINCT r.professor_id) as recommendation_count,
            GROUP_CONCAT(DISTINCT c.course_name ORDER BY c.course_name SEPARATOR ', ') as course_names
        FROM Recommendation r
        JOIN Tutor t ON r.tutor_id = t.tutor_id
        JOIN Student s ON t.tutor_id = s.student_id
        JOIN User u ON t.tutor_id = u.user_id
        JOIN Course c ON r.course_id = c.course_id
        GROUP BY r.tutor_id
        ORDER BY recommendation_count DESC, t.avg_rating DESC
    ");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Recommended Tutors - CampusTutor</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .filter-bar { background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
        .tutor-card { background: white; padding: 20px; border-radius: 15px; margin-bottom: 15px; position: relative; }
        .medal { position: absolute; top: 15px; right: 20px; font-size: 35px; }
        .course-badge { display: inline-block; background: #e8f0fe; color: #1e3c72; padding: 3px 10px; border-radius: 15px; font-size: 12px; margin: 5px 5px 0 0; }
        .btn-profile { display: inline-block; margin-top: 10px; padding: 8px 15px; background: #1e3c72; color: white; text-decoration: none; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏆 Top Recommended Tutors</h1>
            <p>Ranked by professor recommendations</p>
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

        <?php if($recommended && $recommended->num_rows > 0): ?>
            <?php 
            $rank = 1;
            while($tutor = $recommended->fetch_assoc()): 
                $medal = '';
                if($rank == 1) $medal = '🥇';
                elseif($rank == 2) $medal = '🥈';
                elseif($rank == 3) $medal = '🥉';
            ?>
                <div class="tutor-card">
                    <?php if($medal): ?>
                        <div class="medal"><?= $medal ?></div>
                    <?php endif; ?>
                    
                    <h2><?= $tutor['tutor_name'] ?></h2>
                    
                    <p class="meta">Department: <?= $tutor['department'] ?> | CGPA: <?= $tutor['cgpa'] ?></p>
                    <p class="meta">⭐ Average Rating: <?= $tutor['avg_rating'] ? number_format($tutor['avg_rating'], 1) . '/5.0' : 'No ratings yet' ?></p>
                    
                    <div>
                        <strong>Courses:</strong><br>
                        <?php 
                        $courses_list = explode(',', $tutor['course_names']);
                        foreach($courses_list as $crs):
                        ?>
                            <span class="course-badge"><?= trim($crs) ?></span>
                        <?php endforeach; ?>
                    </div>
                    
                    <p class="meta"><strong>🎓 Recommended by:</strong> <?= $tutor['recommendation_count'] ?> professor(s)</p>
                    
                    <a href="tutor_profile.php?tutor_id=<?= $tutor['tutor_id'] ?>" class="btn-profile">View Full Profile →</a>
                </div>
            <?php 
            $rank++;
            endwhile; 
            ?>
        <?php else: ?>
            <p>No recommended tutors found yet. Professors can recommend tutors from their dashboard.</p>
        <?php endif; ?>

        <p><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>