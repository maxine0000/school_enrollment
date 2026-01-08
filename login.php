<?php
session_start();
require 'db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login_id = trim($_POST['login_id']); 
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $login_id);
    $stmt->execute();
    $adminResult = $stmt->get_result();

    if ($adminResult->num_rows === 1) {
        $admin = $adminResult->fetch_assoc();
        if ($password === $admin['password']) { 
            $_SESSION['admin'] = $admin['email'];
            header("Location: Admin/dashboard.php");
            exit();
        }
    }

    $stmt = $conn->prepare("
        SELECT s.id AS student_id, s.username, s.password, e.status 
        FROM studentportal s 
        LEFT JOIN enrollment_form e ON s.id = e.student_id
        WHERE s.username = ? 
        LIMIT 1
    ");
    $stmt->bind_param("s", $login_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc();

        if ($password === $student['password']) { 
            if ($student['status'] !== 'Enrolled') {
                $status = $student['status'] ?? 'Pending';
                $_SESSION['login_error'] = "Your account is not enrolled yet. Current status: $status";
                header("Location: index.php");
                exit();
            }

            $_SESSION['student_id'] = $student['student_id'];
            $_SESSION['student_username'] = $student['username'];
            header("Location: Users/dashboard.php");
            exit();
        }
    }

    $_SESSION['login_error'] = "Invalid credentials";
    header("Location: index.php");
    exit();
}
?>
