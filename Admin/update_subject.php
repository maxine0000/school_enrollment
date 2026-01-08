<?php
include 'db.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $id = intval($_POST['id']);
    $subject_name = trim($_POST['subject_name']);
    $course = trim($_POST['course']);
    $instructor = trim($_POST['instructor']);
    $year_level = trim($_POST['year_level']);
    $hours = intval($_POST['hours']);

    if(!$subject_name || !$course || !$instructor || !$year_level || !$hours){
        echo json_encode(['status'=>'error','message'=>'All fields are required']);
        exit();
    }

    try{
        $stmt = $conn->prepare("UPDATE subjects SET subject_name=?, course=?, instructor=?, year_level=?, hours=? WHERE id=?");
        $stmt->execute([$subject_name,$course,$instructor,$year_level,$hours,$id]);
        echo json_encode(['status'=>'success','message'=>'Subject updated successfully']);
    } catch(PDOException $e){
        echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    }
}
?>
