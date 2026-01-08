<?php
session_start();
require_once "Admin/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

$name = trim($_POST['name']);
$email = trim($_POST['email']);
$message = trim($_POST['message']);
$captcha = trim($_POST['captcha_code']);

if (!$name || !$email || !$message) {
$_SESSION['contact_error'] = "All fields are required.";
header("Location: contact_now.php"); exit;
}

if (!isset($_SESSION['captcha_code']) || strtoupper($captcha) !== $_SESSION['captcha_code']) {
$_SESSION['contact_error'] = "Invalid captcha.";
header("Location: contact_now.php"); exit;
}

$stmt = $conn->prepare("
INSERT INTO contact_messages (name,email,message)
VALUES (?,?,?)
");
$stmt->execute([$name,$email,$message]);

unset($_SESSION['captcha_code']);
$_SESSION['contact_success'] = true;

header("Location: contact_now.php");
exit;
}
