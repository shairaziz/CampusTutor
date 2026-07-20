<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'dbconnect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: login.php");
    exit();
}

$tutor_id = $_SESSION['user_id'];
$message = '';
$msg_type = '';

if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $day = $_POST['day'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    if ($start_time >= $end_time) {
        $message = "❌ End time must be after start time.";
        $msg_type = 'error';
    } else {
        $conn->query("INSERT INTO Availability (tutor_id, day, start_time, end_time) VALUES ($tutor_id, '$day', '$start_time', '$end_time')");
        $message = "✅ Availability slot added.";
        $msg_type = 'success';
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $slot_id = (int)$_POST['slot_id'];
    $conn->query("DELETE FROM Availability WHERE slot_id = $slot_id AND tutor_id = $tutor_id");
    $message = "🗑️ Slot removed.";
    $msg_type = 'success';
}

$slots = $conn->query("SELECT * FROM Availability WHERE tutor_id = $tutor_id ORDER BY FIELD(day,'Mon','Tue','Wed','Thu','Fri','Sat','Sun'), start_time");
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Availability - CampusTutor</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-row { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 My Availability</h1>
            <p>Set your available time slots for students to book</p>
        </div>

        <?php if ($message): ?>
            <div class="<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= $message ?></div>
        <?php endif; ?>

        <h3>Add a New Slot</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <select name="day" required>
                    <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
                        <option><?= $d ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="time" name="start_time" required>
                <input type="time" name="end_time" required>
                <button type="submit">Add Slot</button>
            </div>
        </form>

        <h3>My Current Slots</h3>
        <?php if ($slots->num_rows === 0): ?>
            <p>No availability set yet.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <tr><th>Day</th><th>Start</th><th>End</th><th>Action</th></tr>
                    <?php while ($row = $slots->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['day'] ?></td>
                        <td><?= date('h:i A', strtotime($row['start_time'])) ?></td>
                        <td><?= date('h:i A', strtotime($row['end_time'])) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="slot_id" value="<?= $row['slot_id'] ?>">
                                <button type="submit" style="background:#dc3545; color:white; border:none; padding:4px 12px; border-radius:5px;">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        <?php endif; ?>

        <p><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>