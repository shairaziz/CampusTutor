<?php 
include 'dbconnect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
// For tutor viewing only their own reviews
$my_reviews = isset($_GET['my_reviews']) ? $_GET['my_reviews'] : '';
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$tutorFilter = "";
if ($my_reviews == '1' && $role == 'tutor') {
    $tutorFilter = "AND r.tutor_id = '$user_id'";
}
// Add search condition to SQL (search by name OR tutor_id)
$searchCondition = "";
if ($search != "") {
    // Check if search is a number (ID) or text (name)
    if (is_numeric($search)) {
        $searchCondition = "AND tut.user_id = '$search'";
    } else {
        $searchCondition = "AND tut.name LIKE '%$search%'";
    }
}

$sql = "SELECT r.*, 
               r.is_anonymous,
               tut.name as tutor_name, 
               tut.user_id as tutor_id,
               stu.name as student_name 
        FROM Review r
        JOIN User tut ON r.tutor_id = tut.user_id
        JOIN User stu ON r.student_id = stu.user_id
        WHERE 1=1 $searchCondition $tutorFilter
        ORDER BY r.review_date DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Reviews - CampusTutor</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .filter-bar { 
            background: white; 
            padding: 20px; 
            border-radius: 15px; 
            margin-bottom: 20px; 
        }
        .filter-bar h3 {
            margin-bottom: 15px;
            color: #1e3c72;
        }
        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-form input {
            flex: 1;
            min-width: 250px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        .filter-form button {
            padding: 12px 20px;
            background: #1e3c72;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .filter-form a {
            padding: 12px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }
        .table-container { 
            background: white; 
            padding: 20px; 
            border-radius: 20px; 
            overflow-x: auto; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        th { 
            padding: 14px 12px; 
            text-align: left; 
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); 
            color: white; 
        }
        td { 
            padding: 14px 12px; 
            border-bottom: 1px solid #f0f0f0; 
        }
        .stars { 
            color: #f9ab00; 
        }
        .search-badge {
            background: #e8f0fe;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            margin-left: 10px;
        }
        .hint {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⭐ Tutor Reviews</h1>
            <p>See all reviews or search by preference</p>
        </div>

        <!-- FILTER BAR -->
        <div class="filter-bar">
            
            <form method="GET" class="filter-form">
                <input type="text" name="search" placeholder="Filter by tutor name (e.g., 'Sarah') or tutor ID (e.g., '22')" value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Search</button>
                <?php if($search): ?>
                    <a href="view_reviews.php">Clear Filter</a>
                <?php endif; ?>
            </form>
            
            <?php if($search): ?>
                <p style="margin-top: 10px; color: #666;">Showing reviews for: <strong>"<?= htmlspecialchars($search) ?>"</strong></p>
            <?php endif; ?>
        </div>

        <?php
        if ($result->num_rows == 0) {
            if ($search) {
                echo "<p>No reviews found for '<strong>$search</strong>'. <a href='view_reviews.php'>Clear filter</a> to see all reviews.</p>";
            } else {
                echo "<p>No reviews yet. <a href='add_review.php'>Write the first review!</a></p>";
            }
        } else {
        ?>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tutor</th>
                        <th>Tutor ID</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Date</th>
                        <th>Reviewed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): 
                        $stars = str_repeat("★", $row['rating']) . str_repeat("☆", 5 - $row['rating']);
                    ?>
                        <tr>
                            <td><?php echo $row['tutor_name']; ?></td>
                            <td><?php echo $row['tutor_id']; ?></td>
                            <td class="stars"><?php echo $stars; ?> (<?php echo $row['rating']; ?>/5)</td>
                            <td><?php echo nl2br(htmlspecialchars($row['comment'])); ?></td>
                            <td><?php echo $row['review_date']; ?></td>
							<td><?php 
                                if ($row['is_anonymous'] == 1) {
                                echo " Anonymous Student";
                                } else {
                                echo $row['student_name'];
                                }
                                ?>
                                </td>
                            
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <?php } ?>
        
        <p><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>