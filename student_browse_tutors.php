<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'dbconnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'tutor')) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$course_filter = isset($_GET['course_id']) ? $_GET['course_id'] : '';

// Get courses the current user teaches (if they are a tutor)
$my_courses = [];
if ($role === 'tutor') {
    $my_courses_result = $conn->query("SELECT course_id FROM QualifiesFor WHERE tutor_id = $current_user_id AND approval_status = 'approved'");
    while ($row = $my_courses_result->fetch_assoc()) {
        $my_courses[] = $row['course_id'];
    }
}

// Build exclusion for own courses
$course_exclude = "";
if (!empty($my_courses)) {
    $course_list = implode("','", $my_courses);
    $course_exclude = "AND qf.course_id NOT IN ('$course_list')";
}

$where = $course_filter ? "AND qf.course_id = '$course_filter'" : "";

// Exclude tutors who teach the same courses as current tutor, and exclude self
$tutors = $conn->query("
    SELECT u.user_id AS tutor_id, u.name, t.avg_rating, t.bio, s.cgpa, s.department,
           GROUP_CONCAT(DISTINCT qf.course_id ORDER BY qf.course_id SEPARATOR ', ') AS courses,
           GROUP_CONCAT(DISTINCT CONCAT(a.day,' ',TIME_FORMAT(a.start_time,'%H:%i'),'-',TIME_FORMAT(a.end_time,'%H:%i')) ORDER BY a.day SEPARATOR ' | ') AS slots
    FROM User u
    JOIN Tutor t ON u.user_id = t.tutor_id
    JOIN Student s ON u.user_id = s.student_id
    JOIN QualifiesFor qf ON t.tutor_id = qf.tutor_id AND qf.approval_status = 'approved'
    LEFT JOIN Availability a ON t.tutor_id = a.tutor_id
    WHERE t.verification_status = 'approved' 
      AND u.user_id != $current_user_id 
      $course_exclude
      $where
    GROUP BY u.user_id
    ORDER BY t.avg_rating DESC
");

$courses = $conn->query("SELECT course_id, course_name FROM Course ORDER BY course_id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Browse Tutors - CampusTutor</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .filter-bar { 
            background: white; 
            padding: 20px; 
            border-radius: 15px; 
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .filter-form {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .filter-form .filter-group {
            flex: 2;
            min-width: 200px;
        }
        .filter-form label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #4a2a5a;
            margin-bottom: 6px;
        }
        .filter-form select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e8ddf2;
            border-radius: 12px;
            font-size: 14px;
            background: white;
        }
        .filter-form button {
            background: linear-gradient(135deg, #6a1b9a 0%, #9c27b0 100%);
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
        }
        .filter-form a {
            background: #f3e8f7;
            color: #6a1b9a;
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
        }
        .tutor-card { 
            background: white; 
            padding: 20px; 
            border-radius: 15px; 
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border-left: 4px solid #9c27b0;
        }
        .badge { 
            display: inline-block; 
            background: #e8f0fe; 
            color: #1e3c72; 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 12px; 
            margin: 3px;
        }
        .btn-book { 
            background: #28a745; 
            color: white; 
            padding: 8px 20px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block; 
            margin-top: 12px;
            font-weight: 600;
        }
        .slots { 
            margin-top: 10px; 
            font-size: 13px; 
            color: #555;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 10px;
        }
        .tutor-name {
            color: #1e3c72;
            margin-bottom: 8px;
        }
        .info-note {
            background: #e8f0fe;
            padding: 10px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: #1e3c72;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Browse Tutors</h1>
            <p>Find and book sessions with approved tutors</p>
        </div>

        <?php if($role === 'tutor' && !empty($my_courses)): ?>
        <div class="info-note">
            ℹ️ You are a tutor for: <strong><?= implode(', ', $my_courses) ?></strong>. 
            You cannot book sessions for these courses (you can teach them instead!).
        </div>
        <?php endif; ?>

        <!-- Improved Filter Bar -->
        <div class="filter-bar">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label>📚 Filter by Course</label>
                    <select name="course_id">
                        <option value="">-- All Courses --</option>
                        <?php while ($c = $courses->fetch_assoc()): ?>
                            <option value="<?= $c['course_id'] ?>" <?= (isset($_GET['course_id']) && $_GET['course_id'] === $c['course_id']) ? 'selected' : '' ?>>
                                <?= $c['course_id'] ?> – <?= $c['course_name'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit">Apply Filter</button>
                <a href="student_browse_tutors.php">Clear</a>
            </form>
        </div>

        <?php if ($tutors->num_rows === 0): ?>
            <p>No tutors found.</p>
        <?php else: ?>
            <?php while ($row = $tutors->fetch_assoc()): ?>
            <div class="tutor-card">
                <h2 class="tutor-name"><?= htmlspecialchars($row['name']) ?></h2>
                <p><?= htmlspecialchars($row['department']) ?> | CGPA: <?= $row['cgpa'] ?> | ⭐ <?= number_format($row['avg_rating'], 1) ?></p>
                <div>
                    <?php foreach (explode(', ', $row['courses']) as $course): ?>
                        <span class="badge"><?= $course ?></span>
                    <?php endforeach; ?>
                </div>
                <?php if ($row['bio']): ?>
                    <p style="color: #555; font-style: italic;">"<?= htmlspecialchars($row['bio']) ?>"</p>
                <?php endif; ?>
                <div class="slots">
                    <strong>🕐 Available slots:</strong>
                    <?= $row['slots'] ? htmlspecialchars($row['slots']) : 'No slots set yet' ?>
                </div>
                <a href="book_session.php?tutor_id=<?= $row['tutor_id'] ?>" class="btn-book">📅 Book a Session</a>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <p><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>