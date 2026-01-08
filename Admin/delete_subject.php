    <?php
    session_start();
    header('Content-Type: application/json');
    include 'db.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $id = $_POST['id'] ?? '';

        if (empty($id)) {
            echo json_encode(['status'=>'error','message'=>'Invalid ID']);
            exit;
        }

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("SELECT subject_name FROM subjects WHERE id = ?");
            $stmt->execute([$id]);
            $subject = $stmt->fetchColumn();

            if (!$subject) {
                echo json_encode(['status'=>'error','message'=>'Subject not found']);
                exit;
            }

            $stmt = $conn->prepare("DELETE FROM grades WHERE subject = ?");
            $stmt->execute([$subject]);

            $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
            $stmt->execute([$id]);

            $conn->commit();

            echo json_encode(['status'=>'success','message'=>'Subject and grades deleted']);

        } catch (PDOException $e) {
            $conn->rollBack();
            echo json_encode(['status'=>'error','message'=>'Delete failed']);
        }
    }
