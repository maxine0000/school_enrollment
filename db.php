<?php
$conn = mysqli_connect("localhost", "root", "", "school_enrollment");
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
