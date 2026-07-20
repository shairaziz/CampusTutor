<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'dbconnect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - CampusTutor</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 CampusTutor</h1>
            <p>Welcome, <strong><?php echo $user_name; ?></strong>!</p>
            <span class="role-badge role-<?php echo $role; ?>">
                <?php echo strtoupper($role); ?>
            </span>
        </div>
        
        <div class="menu">
            
            <!-- ========================================== -->
            <!-- TUTOR ONLY MENU (FIRST - AT TOP) -->
            <!-- ========================================== -->
            <?php if($role == 'tutor'): ?>
                
                <div class="card">
                    <h3>🎓 Tutor Features</h3>
                    <a href="tutor_availability.php">▶️ Set Availability (Session)</a>
                    <a href="tutor_requests.php">📥 View Session Requests</a>
                    <a href="tutor_revision_requests.php">📋 Exam Revision Requests</a>
                    <a href="view_reviews.php?my_reviews=1">⭐ View My Reviews (only my reviews)</a>
                    <a href="tutor_profile.php?tutor_id=<?php echo $user_id; ?>">👤 My Public Profile</a>
                 
                </div>
                
            <?php endif; ?>
            
            <!-- ========================================== -->
            <!-- STUDENT FEATURES (For both STUDENT and TUTOR) -->
            <!-- ========================================== -->
            <?php if($role == 'student' || $role == 'tutor'): ?>
                
                <!-- BROWSE TUTORS -->
                <div class="card">
                    <h3>🔍 Browse All Tutors</h3>
                    <a href="tutor_list.php">👤 Find a Tutor</a>
                    <a href="recommended_tutors.php">🏆 Top Recommended Tutors</a>
                    <a href="tutor_profile.php?tutor_id=2">📋 View Sample Tutor Profile</a>
                </div>
                
                <!-- SESSION BOOKING (Student Feature) -->
                <div class="card">
                    <h3>Session Booking</h3>
                    <a href="student_browse_tutors.php">🔍 Browse Tutors</a>
                    <a href="student_sessions.php">📋 My Booked Sessions</a>
                </div>
				
				<!-- REVIEWS -->
                <div class="card">
                    <h3>Tutor Reviews</h3>
                    <a href="add_review.php">⭐ Write a Review</a>
                    <a href="view_reviews.php">📋 View All Reviews (all tutors)</a>
                </div>
                
                <!-- STUDY GROUPS -->
                <div class="card">
                    <h3>Study Groups</h3>
                    <a href="create_group.php">➕ Create Study Group</a>
                    <a href="join_group.php">👥 Join Study Group</a>
                    <a href="my_groups.php">📖 My Study Groups</a>
                </div>
                
                <!-- EXAM REVISION (Student Feature) -->
                <div class="card">
                    <h3>Exam Revision</h3>
                    <a href="student_revision.php">🎯 Join Revision Sessions</a>
                </div>
                
                
                
            <?php endif; ?>
            
            <!-- ========================================== -->
            <!-- PROFESSOR ONLY MENU -->
            <!-- ========================================== -->
            <?php if($role == 'professor'): ?>
                
                <div class="card">
                    <h3>👨‍🏫 Professor Features</h3>
                    <a href="tutor_list.php">📋 Browse Verified Tutors</a>
                    <a href="professor_recommend_tutor.php">⭐ Recommend a Tutor</a>
                    <a href="recommended_tutors.php">🏆 View Recommended Tutors</a>
                    <a href="view_reviews.php">📋 View Tutor Reviews</a>
                </div>
                
            <?php endif; ?>
            
            <!-- ========================================== -->
            <!-- ADMIN ONLY MENU -->
            <!-- ========================================== -->
            <?php if($role == 'admin'): ?>
                
                <div class="card">
                    <h3>🖥️ Admin Features</h3>
                    <a href="admin_verify_tutor.php">✅ Approve Pending Eligible Tutors</a>
                    <a href="admin_create_revision.php">➕ Create Exam Revision Session</a>
                    <a href="tutor_list.php">👤 View All Tutors</a>
                    <a href="view_reviews.php">📋 View All Reviews</a>
                    <a href="recommended_tutors.php">🏆 View Recommendations</a>
                </div>
                
            <?php endif; ?>
            
            <!-- ========================================== -->
            <!-- LOGOUT BUTTON (Everyone sees this) -->
            <!-- ========================================== -->
            <div class="card">
                <h3>🔧 Menu</h3>
                <a href="logout.php" class="logout">🚪 Logout</a>
            </div>
            
        </div>
    </div>
</body>
</html>