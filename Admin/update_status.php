<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    echo json_encode([
        'message' => 'Unauthorized access.',
        'type' => 'danger',
        'reload' => false
    ]);
    exit();
}

include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'], $_POST['status'])) {
        $id = $_POST['id'];
        $status = $_POST['status'];

        $allowed_status = ['Approved', 'Rejected', 'Enrolled'];
        if (!in_array($status, $allowed_status)) {
            echo json_encode([
                'message' => 'Invalid status value.',
                'type' => 'danger',
                'reload' => false
            ]);
            exit();
        }

        $db_status = $status === 'Approved' ? 'Enrolled' : $status;

        if ($status === 'Approved' && isset($_POST['section']) && !empty($_POST['section'])) {
            $section = $_POST['section'];
            $stmt = $conn->prepare("UPDATE enrollment_form SET status = ?, section = ? WHERE id = ?");
            $stmt->execute([$db_status, $section, $id]);
        } else {
            $stmt = $conn->prepare("UPDATE enrollment_form SET status = ? WHERE id = ?");
            $stmt->execute([$db_status, $id]);
        }

        echo json_encode([
            'message' => $status === 'Approved' 
                ? 'Student has been enrolled successfully!' 
                : 'Student has been rejected.',
            'type' => $status === 'Approved' ? 'success' : 'warning',
            'reload' => true
        ]);
        exit();
    } else {
        echo json_encode([
            'message' => 'Missing required data.',
            'type' => 'danger',
            'reload' => false
        ]);
        exit();
    }
} else {
    echo json_encode([
        'message' => 'Invalid request method.',
        'type' => 'danger',
        'reload' => false
    ]);
    exit();
}
