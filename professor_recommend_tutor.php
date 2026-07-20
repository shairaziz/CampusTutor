<?php
include 'dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] != 'professor') {
    die("Only professors can access this page.");
}

$professor_id = $_SESSION['user_id'];
$selectedCourse = isset($_GET['course_id']) ? $_GET['course_id'] : '';
$message = '';
$message_type = '';

// Handle recommendation action
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tutor_id = $_POST['tutor_id'];
    $course_id = $_POST['course_id'];
    $action = $_POST['action'];
    
    if ($action == 'recommend') {
        $check = $conn->query("SELECT * FROM Recommendation WHERE professor_id = $professor_id AND tutor_id = $tutor_id AND course_id = '$course_id'");
        if ($check->num_rows == 0) {
            $conn->query("INSERT INTO Recommendation (professor_id, tutor_id, course_id, note, rec_date) 
                          VALUES ($professor_id, $tutor_id, '$course_id', 'Recommended by professor', CURDATE())");
            $message = "✅ Tutor recommended successfully!";
            $message_type = "success";
        } else {
            $message = "⚠️ Tutor already recommended by you for this course.";
            $message_type = "error";
        }
    } elseif ($action == 'remove') {
        $conn->query("DELETE FROM Recommendation 
                      WHERE professor_id = $professor_id AND tutor_id = $tutor_id AND course_id = '$course_id'");
        $message = "✅ Recommendation removed.";
        $message_type = "success";
    }
}

// Get all courses
$courses = $conn->query("SELECT course_id, course_name FROM Course ORDER BY course_name");

// Get eligible tutors for selected course
$eligibleTutors = null;
if ($selectedCourse != '') {
    $eligibleTutors = $conn->query("
        SELECT t.tutor_id, u.name, s.department, s.cgpa, qf.grade_obtained,
               (SELECT COUNT(*) FROM Recommendation r 
                WHERE r.tutor_id = t.tutor_id AND r.course_id = '$selectedCourse' AND r.professor_id = $professor_id) as already_recommended
        FROM Tutor t
        JOIN Student s ON t.tutor_id = s.student_id
        JOIN User u ON t.tutor_id = u.user_id
        JOIN QualifiesFor qf ON qf.tutor_id = t.tutor_id AND qf.course_id = '$selectedCourse'
        WHERE qf.approval_status = 'approved' AND qf.grade_obtained IN ('A', 'A+')
        ORDER BY u.name
    ");
    
    if (!$eligibleTutors) {
        $message = "No eligible tutors found for this course.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Recommend a Tutor - CampusTutor</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .filter-card { background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
        .tutor-card { background: white; padding: 20px; border-radius: 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .recommend-btn { background: #28a745; color: white; padding: 8px 20px; border: none; border-radius: 8px; cursor: pointer; }
        .remove-btn { background: #dc3545; color: white; padding: 8px 20px; border: none; border-radius: 8px; cursor: pointer; }
        .badge-recommended { background: #28a745; color: white; padding: 3px 10px; border-radius: 15px; font-size: 12px; display: inline-block; }
        .info-text { background: #e3f2fd; padding: 15px; border-radius: 10px; margin-bottom: 20px; color: #1976d2; }
        .no-data { text-align: center; padding: 40px; background: white; border-radius: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⭐ Recommend a Tutor</h1>
            <p>As a professor, you can recommend tutors for courses you teach</p>
        </div>

        <?php if($message): ?>
            <div class="<?= $message_type ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="filter-card">
            <form method="GET" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <label><strong>Select Course:</strong></label>
                    <select name="course_id" required style="width: 100%; padding: 10px;">
                        <option value="">-- Select a Course --</option>
                        <?php while($course = $courses->fetch_assoc()): ?>
                            <option value="<?= $course['course_id'] ?>" <?= $selectedCourse == $course['course_id'] ? 'selected' : '' ?>>
                                <?= $course['course_name'] ?> (<?= $course['course_id'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" style="margin-top: 22px;">Load Eligible Tutors</button>
            </form>
        </div>

        <?php if($selectedCourse == ''): ?>
            <div class="info-text">
                📌 <strong>How to recommend a tutor:</strong><br>
                1. Select a course from the dropdown above<br>
                2. Click "Load Eligible Tutors"<br>
                3. You'll see tutors who earned A/A+ in that course<br>
                4. Click "Recommend This Tutor" to add your recommendation
            </div>
        <?php elseif($eligibleTutors && $eligibleTutors->num_rows > 0): ?>
            <h3>📋 Eligible Tutors for this course (A/A+ grade required)</h3>
            <?php while($tutor = $eligibleTutors->fetch_assoc()): ?>
                <div class="tutor-card">
                    <div>
                        <h3><?= $tutor['name'] ?></h3>
                        <p class="meta">Department: <?= $tutor['department'] ?></p>
                        <p class="meta">CGPA: <?= $tutor['cgpa'] ?> | Grade in course: <?= $tutor['grade_obtained'] ?></p>
                        <?php if($tutor['already_recommended'] > 0): ?>
                            <span class="badge-recommended">✓ Already Recommended by You</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <form method="POST">
                            <input type="hidden" name="tutor_id" value="<?= $tutor['tutor_id'] ?>">
                            <input type="hidden" name="course_id" value="<?= $selectedCourse ?>">
                            <?php if($tutor['already_recommended'] > 0): ?>
                                <button type="submit" name="action" value="remove" class="remove-btn">❌ Remove Recommendation</button>
                            <?php else: ?>
                                <button type="submit" name="action" value="recommend" class="recommend-btn">✅ Recommend This Tutor</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-data">
                <p>❌ No eligible tutors found for <strong><?= $selectedCourse ?></strong></p>
                <p>Tutors need:</p>
                <ul style="text-align: left; display: inline-block;">
                    <li>✅ A or A+ grade in this course</li>
                    <li>✅ Admin approval status = 'approved'</li>
                    <li>✅ Record in QualifiesFor table</li>
                </ul>
                <br>
                <a href="tutor_list.php" class="btn">Browse All Tutors</a>
            </div>
        <?php endif; ?>

        <p style="margin-top: 20px;"><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>