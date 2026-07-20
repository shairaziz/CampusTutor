<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'dbconnect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Allow both students AND tutors to write reviews
if ($_SESSION['role'] != 'student' && $_SESSION['role'] != 'tutor') {
    die("Only students can write reviews.");
}

$student_id = $_SESSION['user_id'];
$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tutor_id = $_POST['tutor_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    
    // Prevent self-review
    if ($student_id == $tutor_id) {
        $message = "❌ You cannot review yourself!";
        $message_type = "error";
    }
    else {
        // Check if review already exists
        $check = $conn->query("SELECT * FROM Review WHERE student_id='$student_id' AND tutor_id='$tutor_id'");
        
        if ($check->num_rows > 0) {
            $message = "❌ You have already reviewed this tutor!";
            $message_type = "error";
        } else {
            // Insert the review with anonymous flag
            $sql = "INSERT INTO Review (student_id, tutor_id, rating, comment, is_anonymous, review_date) 
                    VALUES ('$student_id', '$tutor_id', '$rating', '$comment', '$is_anonymous', NOW())";
            
            if ($conn->query($sql) === TRUE) {
                // Update tutor's average rating
                $updateRating = "
                    UPDATE Tutor 
                    SET avg_rating = (
                        SELECT AVG(rating) FROM Review WHERE tutor_id = '$tutor_id'
                    )
                    WHERE tutor_id = '$tutor_id'
                ";
                $conn->query($updateRating);
                
                $message = "✅ Review added successfully!";
                $message_type = "success";
            } else {
                $message = "❌ Error: " . $conn->error;
                $message_type = "error";
            }
        }
    }
}

// Get all approved tutors
$tutors = $conn->query("
    SELECT DISTINCT t.tutor_id, u.name 
    FROM Tutor t 
    JOIN User u ON t.tutor_id = u.user_id
    JOIN QualifiesFor qf ON qf.tutor_id = t.tutor_id
    WHERE qf.approval_status = 'approved'
    ORDER BY u.name
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Write a Review - CampusTutor</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .checkbox-group { margin: 15px 0; display: flex; align-items: center; gap: 10px; }
        .checkbox-group input { width: auto; margin: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✍️ Write a Review</h1>
            <p>Share your experience with a tutor</p>
        </div>
        
        <?php if($message): ?>
            <div class="<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST">
                <label>Select Tutor:</label>
                <select name="tutor_id" required>
                    <option value="">-- Select Tutor --</option>
                    <?php while($tutor = $tutors->fetch_assoc()): ?>
                        <option value="<?php echo $tutor['tutor_id']; ?>">
                            <?php echo $tutor['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <label>Rating:</label>
                <select name="rating" required>
                    <option value="5" selected>⭐⭐⭐⭐⭐ 5 Stars (Excellent)</option>
                    <option value="4">⭐⭐⭐⭐ 4 Stars (Good)</option>
                    <option value="3">⭐⭐⭐ 3 Stars (Average)</option>
                    <option value="2">⭐⭐ 2 Stars (Below Average)</option>
                    <option value="1">⭐ 1 Star (Poor)</option>
                </select>
                
                <label>Comment:</label>
                <textarea name="comment" rows="5" placeholder="Share your experience with this tutor..."></textarea>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="is_anonymous" id="is_anonymous" value="1">
                    <label for="is_anonymous" style="margin: 0;"> Make my review anonymous (hide my name)</label>
                </div>
                
                <button type="submit">Submit Review</button>
            </form>
        </div>
        
        <p style="margin-top: 20px;"><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>