<?php
require_once 'functions.php';

// Handle Task Add Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task-name'])) {
    $raw_task = trim($_POST['task-name']);
    if ($raw_task && preg_match('/^[a-zA-Z0-9\s.,!?]+$/', $raw_task)) {
        $task_name = htmlspecialchars($raw_task);
        $result = addTask($task_name);
        $task_message = $result ? '✅ Task added successfully!' : '❌ Task already exists.';
    } else {
        $result = false;
        $task_message = '❌ Invalid task name.';
    }
}

// Handle Task Complete/Incomplete Toggle
if (isset($_POST['toggle-task-id'])) {
    $id = htmlspecialchars($_POST['toggle-task-id']);
    $is_completed = isset($_POST['completed']);
    markTaskAsCompleted($id, $is_completed);
}

// Handle Task Delete
if (isset($_POST['delete-task-id'])) {
    $id = htmlspecialchars($_POST['delete-task-id']);
    $deleteResult = deleteTask($id);
}

// Handle Email Subscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $subscribeResult = subscribeEmail($email);
        $subscribe_message = $subscribeResult ? '📩 Subscription email sent successfully!' : '❌ Failed to send subscription email.';
    } else {
        $subscribeResult = false;
        $subscribe_message = '❌ Invalid email address.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Task Scheduler</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Success/Error Popup Messages -->
<?php if (isset($result)): ?>
    <div id="action-message" class="<?= $result ? 'success' : 'error' ?>">
        <?= $task_message ?>
    </div>
<?php endif; ?>

<?php if (isset($deleteResult)): ?>
    <div id="action-message" class="<?= $deleteResult ? 'success' : 'error' ?>">
        <?= $deleteResult ? '🗑️ Task deleted successfully!' : '❌ Failed to delete task.' ?>
    </div>
<?php endif; ?>

<?php if (isset($subscribeResult)): ?>
    <div id="action-message" class="<?= $subscribeResult ? 'success' : 'error' ?>">
        <?= $subscribe_message ?>
    </div>
<?php endif; ?>

<div class="container">

    <!-- Task Add Form -->
    <div class="task-box">
        <form method="POST">
            <h2>📝 Add New Task</h2>
            <input type="text" name="task-name" id="task-name" placeholder="Enter new task" required>
            <button type="submit" id="add-task">Add Task</button>
        </form>
    </div>

    <!-- Task List Display -->
    <ul class="tasks-list">
        <?php
        $tasks = getAllTasks();
        foreach ($tasks as $task):
            $isCompleted = $task['completed'] ? 'checked' : '';
            $completedClass = $task['completed'] ? 'completed' : '';
        ?>
        <li class="task-item <?= $completedClass ?>">
            <form method="POST" style="display:inline;">
                <input type="hidden" name="toggle-task-id" value="<?= $task['id'] ?>">
                <input type="checkbox" class="task-status" onchange="this.form.submit()" name="completed" <?= $isCompleted ?>>
            </form>
            <?= htmlspecialchars($task['name']) ?>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="delete-task-id" value="<?= $task['id'] ?>">
                <button type="submit" class="delete-task">🗑️ Delete</button>
            </form>
        </li>
        <?php endforeach; ?>
    </ul>

    <!-- Email Subscribe Box -->
    <div id="subscribe-container">
        <h2>📩 Get Task Reminders</h2>
        <form method="POST">
            <input type="email" name="email" placeholder="Enter your email" required />
            <button type="submit" id="submit-email">Subscribe</button>
        </form>
    </div>

</div>

</body>
</html>