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
            <!-- STUDENT & TUTOR MENU (Reviews + Study Groups) -->
            <!-- ========================================== -->
            <?php if($role == 'student' || $role == 'tutor'): ?>
                
                <div class="card">
                    <h3>⭐ Reviews</h3>
                    <a href="add_review.php">✍️ Write a Review</a>
                    <a href="view_reviews.php">👀 View All Reviews</a>
                </div>
                
                <div class="card">
                    <h3>📚 Study Groups</h3>
                    <a href="create_group.php">➕ Create Study Group</a>
                    <a href="join_group.php">👥 Join Study Group</a>
                    <a href="my_groups.php">📖 My Study Groups</a>
                </div>
				
				
                
            <?php endif; ?>
            
            <!-- ========================================== -->
            <!-- TUTOR ONLY MENU -->
            <!-- ========================================== -->
            <?php if($role == 'tutor'): ?>
                
                <div class="card">
                    <h3>📅 Tutor Features</h3>
                    <a href="set_availability.php">⏰ Set Availability</a>
                    <a href="view_reviews.php">⭐ View My Reviews</a>
                </div>
                
            <?php endif; ?>
            
            <!-- ========================================== -->
            <!-- PROFESSOR ONLY MENU -->
            <!-- ========================================== -->
            <?php if($role == 'professor'): ?>
                
                <div class="card">
                    <h3>👨‍🏫 Professor Features</h3>
                    <a href="recommend_tutor.php">⭐ Recommend a Tutor</a>
                    <a href="view_reviews.php">👀 View Tutor Reviews</a>
                </div>
                
            <?php endif; ?>
            
            <!-- ========================================== -->
            <!-- ADMIN ONLY MENU -->
            <!-- ========================================== -->
            <?php if($role == 'admin'): ?>
                
                <div class="card">
                    <h3>👑 Admin Features</h3>
                    <a href="approve_tutors.php">✅ Approve Tutors</a>
                    <a href="view_reviews.php">👀 View All Reviews</a>
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