<?php
session_start();
if (!isset($_SESSION['admin'])) {
    echo json_encode(["status"=>"error"]);
    exit();
}
include "db.php";

$student_id = $_POST['student_id'];

foreach ($_POST['subject'] as $subject) {

    $prelim   = $_POST['prelim'][$subject]   ?? null;
    $midterm  = $_POST['midterm'][$subject]  ?? null;
    $finals   = $_POST['finals'][$subject]   ?? null;
    $instructor = $_POST['instructor'][$subject] ?? null;

    if ($prelim !== null || $midterm !== null || $finals !== null) {
        $p = $prelim  !== "" ? $prelim  : 0;
        $m = $midterm !== "" ? $midterm : 0;
        $f = $finals  !== "" ? $finals  : 0;

        $count = ($prelim!==""?1:0)+($midterm!==""?1:0)+($finals!==""?1:0);
        $average = $count ? ($p+$m+$f)/$count : null;
        $remarks = $average !== null ? ($average>=75?"Passed":"Failed") : null;
    } else {
        $average = null;
        $remarks = null;
    }
    $check = $conn->prepare("
        SELECT id FROM grades WHERE student_id=? AND subject=?
    ");
    $check->execute([$student_id,$subject]);

    if ($check->rowCount()) {
        $update = $conn->prepare("
            UPDATE grades SET
            instructor=?,
            prelim=COALESCE(?,prelim),
            midterm=COALESCE(?,midterm),
            finals=COALESCE(?,finals),
            average=?,
            remarks=?
            WHERE student_id=? AND subject=?
        ");
        $update->execute([
            $instructor,$prelim,$midterm,$finals,
            $average,$remarks,$student_id,$subject
        ]);
    } else {
        $insert = $conn->prepare("
            INSERT INTO grades
            (student_id,subject,instructor,prelim,midterm,finals,average,remarks)
            VALUES (?,?,?,?,?,?,?,?)
        ");
        $insert->execute([
            $student_id,$subject,$instructor,
            $prelim,$midterm,$finals,$average,$remarks
        ]);
    }
}

echo json_encode(["status"=>"success"]);
