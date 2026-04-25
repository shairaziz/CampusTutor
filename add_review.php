<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'dbconnect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tutor_id = $_POST['tutor_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    
    // CHECK 1: Prevent self-review
    if ($student_id == $tutor_id) {
        $message = "❌ You cannot review yourself!";
        $message_type = "error";
    }
    // CHECK 2: Check if review already exists
    else {
        $check = $conn->query("SELECT * FROM Review WHERE student_id='$student_id' AND tutor_id='$tutor_id'");
        
        if ($check->num_rows > 0) {
            $message = "❌ You have already reviewed this tutor!";
            $message_type = "error";
        } else {
            // Insert the review
            $sql = "INSERT INTO Review (student_id, tutor_id, rating, comment) 
                    VALUES ('$student_id', '$tutor_id', '$rating', '$comment')";
            
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

// Get all tutors (EXCLUDE the logged-in user if they are a tutor)
$tutors = $conn->query("
    SELECT t.tutor_id, u.name 
    FROM Tutor t 
    JOIN User u ON t.tutor_id = u.user_id
    WHERE t.tutor_id != '$student_id'
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Write a Review</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Write a Review</h1>
        
        <?php if($message): ?>
            <div class="<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
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
                <option value="5" selected>⭐⭐⭐⭐⭐ 5 Stars (Default)</option>
                <option value="4">⭐⭐⭐⭐ 4 Stars</option>
                <option value="3">⭐⭐⭐ 3 Stars</option>
                <option value="2">⭐⭐ 2 Stars</option>
                <option value="1">⭐ 1 Star</option>
            </select>
            
            <label>Comment:</label>
            <textarea name="comment" rows="4" placeholder="Share your experience with this tutor..."></textarea>
            
            <button type="submit">Submit Review</button>
        </form>
        
        <p><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>
