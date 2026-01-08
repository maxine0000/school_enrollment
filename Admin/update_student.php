<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    echo json_encode([
        'message' => 'Unauthorized access.',
        'type' => 'danger'
    ]);
    exit();
}

include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $enrollId = $_POST['id'];

        $stmt = $conn->prepare("SELECT student_id FROM enrollment_form WHERE id = ?");
        $stmt->execute([$enrollId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            echo json_encode([
                'message' => 'Student not found.',
                'type' => 'danger'
            ]);
            exit();
        }

        $studentId = $student['student_id'];

        $stmt1 = $conn->prepare("
            UPDATE studentportal
            SET username = ?, email = ?
            WHERE id = ?
        ");
        $stmt1->execute([
            $_POST['username'],
            $_POST['email'],
            $studentId
        ]);

        $stmt2 = $conn->prepare("
            UPDATE enrollment_form
            SET first_name = ?, middle_name = ?, last_name = ?, 
                course = ?, year_level = ?, section = ?
            WHERE id = ?
        ");
        $stmt2->execute([
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['last_name'],
            $_POST['course'],
            $_POST['year_level'],
            $_POST['section'],
            $enrollId
        ]);

        echo json_encode([
            'message' => 'Student details updated successfully!',
            'type' => 'success'
        ]);
        exit();

    } catch (PDOException $e) {
        echo json_encode([
            'message' => 'Error: ' . $e->getMessage(),
            'type' => 'danger'
        ]);
        exit();
    }
} else {
    echo json_encode([
        'message' => 'Invalid request.',
        'type' => 'danger'
    ]);
    exit();
}
