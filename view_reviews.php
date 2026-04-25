<?php include 'dbconnect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Reviews - CampusTutor</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>⭐ Tutor Reviews</h1>
        
        <?php
        $sql = "SELECT r.*, 
                       tut.name as tutor_name, 
                       stu.name as student_name 
                FROM Review r
                JOIN User tut ON r.tutor_id = tut.user_id
                JOIN User stu ON r.student_id = stu.user_id
                ORDER BY r.review_date DESC";
        
        $result = $conn->query($sql);
        
        if ($result->num_rows == 0) {
            echo "<p>No reviews yet. <a href='add_review.php'>Write the first review!</a></p>";
        } else {
        ?>
        
        <table>
            <tr>
                <th>Tutor</th>
                <th>Rating</th>
                <th>Review</th>
                <th>Date</th>
                <th>Reviewed By</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): 
                $stars = str_repeat("★", $row['rating']) . str_repeat("☆", 5 - $row['rating']);
            ?>
            <tr>
                <td><?php echo $row['tutor_name']; ?></td>
                <td class="stars"><?php echo $stars; ?> (<?php echo $row['rating']; ?>/5)</td>
                <td><?php echo nl2br(htmlspecialchars($row['comment'])); ?></td>
                <td><?php echo $row['review_date']; ?></td>
                <td><?php echo $row['student_name']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        
        <?php } ?>
        
        <p><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>