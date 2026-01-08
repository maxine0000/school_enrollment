<?php
include 'db.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $subject_name = trim($_POST['subject_name'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $instructor = trim($_POST['instructor'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '');
    $hours = intval($_POST['hours'] ?? 0);

    if(!$subject_name || !$course || !$instructor || !$year_level || !$hours){
        echo json_encode(['status'=>'error','message'=>'All fields are required']);
        exit();
    }

    try{
        $stmt = $conn->prepare("INSERT INTO subjects (subject_name, course, instructor, year_level, hours, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$subject_name, $course, $instructor, $year_level, $hours]);
        $id = $conn->lastInsertId();
        echo json_encode(['status'=>'success','message'=>'Subject updated successfully!']);
    }catch(PDOException $e){
        echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    }
}
?>
