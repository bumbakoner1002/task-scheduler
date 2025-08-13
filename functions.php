<?php

// Base URL for email verification and unsubscribe links
// Change this to the actual URL when deploying on server
$baseUrl = 'http://localhost/task-scheduler';

// Add a new task
function addTask(string $task_name): bool {
    $file = __DIR__ . '/tasks.txt';
    $tasks = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

    foreach ($tasks as $task) {
        if (strcasecmp($task['name'], $task_name) === 0) {
            return false; // Duplicate
        }
    }

    $new_task = [
        'id' => uniqid(),
        'name' => $task_name,
        'completed' => false
    ];

    $tasks[] = $new_task;
    return file_put_contents($file, json_encode($tasks, JSON_PRETTY_PRINT)) !== false;
}

// Get all tasks
function getAllTasks(): array {
    $file = __DIR__ . '/tasks.txt';
    if (!file_exists($file)) return [];
    $tasks = json_decode(file_get_contents($file), true);
    return is_array($tasks) ? $tasks : [];
}

// Mark task complete/incomplete
function markTaskAsCompleted(string $task_id, bool $is_completed): bool {
    $file = __DIR__ . '/tasks.txt';
    if (!file_exists($file)) return false;

    $tasks = json_decode(file_get_contents($file), true);
    foreach ($tasks as &$task) {
        if ($task['id'] === $task_id) {
            $task['completed'] = $is_completed;
            break;
        }
    }

    return file_put_contents($file, json_encode($tasks, JSON_PRETTY_PRINT)) !== false;
}

// Delete task
function deleteTask(string $task_id): bool {
    $file = __DIR__ . '/tasks.txt';
    if (!file_exists($file)) return false;

    $tasks = json_decode(file_get_contents($file), true);
    $tasks = array_filter($tasks, fn($task) => $task['id'] !== $task_id);

    return file_put_contents($file, json_encode(array_values($tasks), JSON_PRETTY_PRINT)) !== false;
}

// Generate 6-digit code
function generateVerificationCode(): string {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Subscribe email & send verification
function subscribeEmail(string $email): bool {
    $pending_file = __DIR__ . '/pending_subscriptions.txt';
    $code = generateVerificationCode();

    $pending = file_exists($pending_file) ? json_decode(file_get_contents($pending_file), true) : [];
    $pending[$email] = [
        'code' => $code,
        'timestamp' => time()
    ];
    if (file_put_contents($pending_file, json_encode($pending, JSON_PRETTY_PRINT)) === false) {
        return false;
    }

    global $baseUrl;
    $verify_link = $baseUrl . '/verify.php?email=' . urlencode($email) . '&code=' . $code;

    $subject = 'Verify subscription to Task Planner';
    $headers = "From: no-reply@example.com\r\nContent-Type: text/html\r\n";
    $message = "<p>Click to verify your subscription:</p><p><a id=\"verification-link\" href=\"$verify_link\">Verify Subscription</a></p>";

    return mail($email, $subject, $message, $headers);
}

// Verify subscription via code
function verifySubscription(string $email, string $code): bool {
    $pending_file = __DIR__ . '/pending_subscriptions.txt';
    $subscribers_file = __DIR__ . '/subscribers.txt';

    $pending = file_exists($pending_file) ? json_decode(file_get_contents($pending_file), true) : [];
    $data = file_exists($subscribers_file) ? json_decode(file_get_contents($subscribers_file), true) : [];
    $subscribers = is_array($data) ? $data : [];

    // Remove entries older than 24 hours
    foreach ($pending as $key => $value) {
        if (time() - $value['timestamp'] > 86400) {
            unset($pending[$key]);
        }
    }

    if (!isset($pending[$email]) || $pending[$email]['code'] !== $code) {
        file_put_contents($pending_file, json_encode($pending, JSON_PRETTY_PRINT));
        return false;
    }

    if (!in_array($email, $subscribers)) {
        $subscribers[] = $email;
    }

    unset($pending[$email]);
    if (
        file_put_contents($pending_file, json_encode($pending, JSON_PRETTY_PRINT)) === false ||
        file_put_contents($subscribers_file, json_encode($subscribers, JSON_PRETTY_PRINT)) === false
    ) {
        return false;
    }
    return true;
}

// Unsubscribe email
function unsubscribeEmail(string $email): bool {
    $subscribers_file = __DIR__ . '/subscribers.txt';
    $data = file_exists($subscribers_file) ? json_decode(file_get_contents($subscribers_file), true) : [];
    $subscribers = is_array($data) ? $data : [];

    $updated = array_filter($subscribers, fn($e) => $e !== $email);
    if (file_put_contents($subscribers_file, json_encode(array_values($updated), JSON_PRETTY_PRINT)) === false) {
        return false;
    }
    return true;
}

// Sends task reminders to all subscribers
function sendTaskReminders(): void {
    $subscribers_file = __DIR__ . '/subscribers.txt';
    $tasks_file = __DIR__ . '/tasks.txt';

    if (!file_exists($subscribers_file) || !file_exists($tasks_file)) {
        return;
    }

    $subscribers = json_decode(file_get_contents($subscribers_file), true);
    $tasks = json_decode(file_get_contents($tasks_file), true);

    if (!is_array($subscribers) || !is_array($tasks)) {
        return;
    }

    $pending_tasks = array_filter($tasks, fn($task) => empty($task['completed']));

    if (empty($pending_tasks)) return;

    foreach ($subscribers as $email) {
        sendTaskEmail($email, $pending_tasks);
    }
}

// Send task email to one subscriber
function sendTaskEmail(string $email, array $pending_tasks): bool {
    $subject = 'Task Planner - Pending Tasks Reminder';
    $headers = "From: no-reply@example.com\r\nContent-Type: text/html\r\n";

    $task_list_html = '';
    foreach ($pending_tasks as $task) {
        $task_list_html .= "<li>{$task['name']}</li>";
    }

    global $baseUrl;
    $unsubscribe_link = $baseUrl . '/unsubscribe.php?email=' . urlencode($email);

    $message = "
        <h2>Pending Tasks Reminder</h2>
        <p>Here are the current pending tasks:</p>
        <ul>$task_list_html</ul>
        <p><a id=\"unsubscribe-link\" href=\"$unsubscribe_link\">Unsubscribe from notifications</a></p>
    ";

    return mail($email, $subject, $message, $headers);
}