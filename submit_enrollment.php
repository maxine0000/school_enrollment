<?php
session_start();
require "Admin/db.php";

if (!isset($_POST['enroll_submit'])) {
    header("Location: enroll.php");
    exit();
}

if (empty($_POST['schedule_id'])) {
    die("❌ Please select an appointment schedule before enrolling.");
}

$schedule_id = $_POST['schedule_id'];

$stmt = $conn->prepare("SELECT slots FROM enrollment_schedule WHERE id=? AND status='active'");
$stmt->execute([$schedule_id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule || $schedule['slots'] <= 0) {
    die("❌ Selected schedule is already full.");
}

$passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);

$stmt = $conn->prepare("
    INSERT INTO students 
    (schedule_id, username, email, password,
     last_name, first_name, middle_name, sex, birthdate,
     contact_number, address,
     guardian_name, guardian_contact, guardian_address,
     course, year_level)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->execute([
    $schedule_id,
    $_POST['username'],
    $_POST['email'],
    $passwordHash,

    $_POST['last_name'],
    $_POST['first_name'],
    $_POST['middle_name'],
    $_POST['sex'],
    $_POST['birthdate'],
    $_POST['contact_number'],
    $_POST['address'],

    $_POST['guardian_name'],
    $_POST['guardian_contact'],
    $_POST['guardian_address'],

    $_POST['course'],
    $_POST['year_level']
]);

$student_id = $conn->lastInsertId();

$stmt = $conn->prepare("
    INSERT INTO educational_background
    (student_id, elementary, elementary_year,
     junior_high, junior_year,
     senior_high, senior_year)
    VALUES (?,?,?,?,?,?,?)
");

$stmt->execute([
    $student_id,
    $_POST['elem_school'],
    $_POST['elem_year'],
    $_POST['junior_school'],
    $_POST['junior_year'],
    $_POST['senior_school'],
    $_POST['senior_year']
]);

$conn->prepare("
    UPDATE enrollment_schedule 
    SET slots = slots - 1 
    WHERE id=?
")->execute([$schedule_id]);

header("Location: enroll_success.php");
exit();
