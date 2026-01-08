<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}
include "db.php";

$stmt = $conn->prepare("SELECT * FROM school_profile WHERE id = 1");
$stmt->execute();
$data = $stmt->fetch(PDO::FETCH_ASSOC);

$currentUser = $_SESSION['admin'];
$stmt = $conn->prepare("SELECT * FROM admin WHERE email != :currentUser ORDER BY id ASC");
$stmt->execute([':currentUser' => $currentUser]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$toastMessage = "";
$toastType = "";

if (isset($_POST['add_user'])) {
    $email = $_POST['username'] . '@admin';
    $password = $_POST['password'];
    $stmt = $conn->prepare("INSERT INTO admin (email, password) VALUES (:email, :password)");
    if($stmt->execute([':email' => $email, ':password' => $password])){
        $toastMessage = "$email added successfully!";
        $toastType = "success";
    } else {
        $toastMessage = "Failed to add user.";
        $toastType = "danger";
    }
}

if (isset($_POST['edit_user'])) {
    $id = $_POST['edit_id'];
    $email = $_POST['edit_email'];
    $password = $_POST['edit_password'];
    if (!empty($password)) {
        $stmt = $conn->prepare("UPDATE admin SET email=:email, password=:password WHERE id=:id");
        $success = $stmt->execute([':email'=>$email, ':password'=>$password, ':id'=>$id]);
    } else {
        $stmt = $conn->prepare("UPDATE admin SET email=:email WHERE id=:id");
        $success = $stmt->execute([':email'=>$email, ':id'=>$id]);
    }
    if($success){
        $toastMessage = "$email updated successfully!";
        $toastType = "success";
    } else {
        $toastMessage = "Failed to update user.";
        $toastType = "danger";
    }
}

if (isset($_POST['delete_user'])) {
    $deleteId = $_POST['delete_id'];
    $stmt = $conn->prepare("SELECT email FROM admin WHERE id = :id");
    $stmt->execute([':id'=>$deleteId]);
    $userEmail = $stmt->fetchColumn();
    $stmt = $conn->prepare("DELETE FROM admin WHERE id = :id");
    if($stmt->execute([':id' => $deleteId])){
        $toastMessage = "$userEmail deleted successfully!";
        $toastType = "success";
    } else {
        $toastMessage = "Failed to delete user.";
        $toastType = "danger";
    }
}

$stmt = $conn->prepare("SELECT * FROM admin WHERE email != :currentUser ORDER BY id ASC");
$stmt->execute([':currentUser' => $currentUser]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
            <title>Users Management</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        html, body {
            max-width: 100%;
            overflow-x: hidden;
        }

        body {
            background: #f4f6fb;
            font-family: system-ui, sans-serif;
        }

        .sidebar {
            width: 90px;
            height: 100vh;
            background: #0f172a;
            position: fixed;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 20px;
            z-index: 1000;
        }

        .sidebar img {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #6366f1;
            margin-bottom: 25px;
        }

        .top-icons {
            display: flex;
            flex-direction: column;
            gap: 26px;
        }

        .sidebar a i {
            font-size: 26px;
            color: #c7d2fe;
            transition: .3s;
        }

        .sidebar a.active i,
        .sidebar a:hover i {
            color: #6366f1;
            transform: scale(1.15);
        }

        .spacer {
            flex-grow: 1;
        }

        .logout i {
            color: #ef4444;
            font-size: 26px;
        }

        .content {
            margin-left: 110px;
            padding: 30px;
            max-width: 100%;
            overflow-x: hidden;
        }

        .card {
            border-radius: 18px;
            border: none;
            box-shadow: 0 15px 35px rgba(0,0,0,.08);
        }

        .users-table {
            margin-top: 20px;
            width: 100%;
            overflow-x: auto;
        }

        .users-table table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
            min-width: unset;
        }

        .users-table th {
            background: #f0f4ff;
            border: none;
        }

        .users-table td {
            background: white;
            border: none;
            border-radius: 12px;
        }

        .users-table tr:hover td {
            background: #e0e7ff;
        }

        table {
            white-space: nowrap;
        }

        .btn-primary,
        .btn-success,
        .btn-danger {
            border-radius: 30px;
            padding: 6px 16px;
        }

        .modal-content {
            border-radius: 22px;
            border: none;
            box-shadow: 0 25px 60px rgba(0,0,0,.25);
        }

        .logout-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #fee2e2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }

        .logout-icon i {
            font-size: 40px;
            color: #dc2626;
        }

        @media (max-width: 768px) {

            html, body {
                overflow-x: hidden;
            }

            .sidebar {
                width: 100%;
                height: 70px;
                flex-direction: row;
                bottom: 0;
                top: auto;
                padding: 0 10px;
                justify-content: space-between;
                align-items: center;
                left: 0;
                right: 0;
            }

            .sidebar img {
                margin-bottom: 0;
                width: 46px;
                height: 46px;
            }

            .top-icons {
                flex-direction: row;
                gap: 14px;
                align-items: center;
            }

            .spacer {
                display: none;
            }

            .content {
                margin-left: 0;
                margin-bottom: 90px;
                padding: 15px;
                width: 100%;
            }

            .card {
                width: 100%;
            }

            .users-table {
                overflow-x: auto;
            }
        }

        @media (max-width: 350px) {

            .sidebar {
                height: 64px;
                padding: 0 6px;
            }

            .sidebar img {
                width: 38px;
                height: 38px;
                border-width: 2px;
            }

            .sidebar a i {
                font-size: 22px;
            }

            .top-icons {
                gap: 10px;
            }

            .content {
                padding: 10px;
                margin-bottom: 85px;
            }

            h4 {
                font-size: 16px;
            }

            .btn {
                padding: 4px 12px;
                font-size: 13px;
            }

            table {
                font-size: 13px;
            }
        }

    </style>
    </head>
    <body>

    <div class="sidebar">
        <?php if (!empty($data['logo']) && file_exists("../uploads/".$data['logo'])): ?>
            <img id="sidebarLogo" src="../uploads/<?= htmlspecialchars($data['logo']) ?>">
        <?php else: ?>
            <img id="sidebarLogo" src="/path/to/default-logo.png">
        <?php endif; ?>
        <div class="top-icons">
            <a href="dashboard.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard"><i class="bi bi-grid-fill"></i></a>
            <a href="users.php" class="active" data-bs-toggle="tooltip" data-bs-placement="right" title="Users"><i class="bi bi-people-fill"></i></a>
            <a href="enrollment.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Files"><i class="bi bi-file-earmark-fill"></i></a>
            <a href="classlist.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Video"><i class="bi bi-person-square"></i></a>
            <a href="messages.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Messages"><i class="bi bi-envelope-fill"></i></a>
        </div>
        <div class="spacer"></div>
            <a href="#" data-bs-toggle="modal" data-bs-target="#logoutModal" class="logout"><i class="bi bi-box-arrow-right"></i></a>
    </div>

    <div class="content">
        <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Users Management</h4>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-person-plus"></i></button>
        </div>
        <div class="users-table table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Email</th>
                        <th>Password</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            <tbody>
                <?php if($users): ?>
                <?php foreach($users as $index => $user): ?>
                <tr>
                    <td><?= $index+1 ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= str_repeat('*', strlen($user['password'])) ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm edit-btn" 
                        data-id="<?= $user['id'] ?>" 
                        data-email="<?= htmlspecialchars($user['email']) ?>" 
                        data-bs-toggle="modal" data-bs-target="#editUserModal"><i class="bi bi-pencil-square"></i></button>
                        <button class="btn btn-danger btn-sm delete-btn" 
                        data-id="<?= $user['id'] ?>" 
                        data-email="<?= htmlspecialchars($user['email']) ?>" 
                        data-bs-toggle="modal" data-bs-target="#deleteUserModal"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
            </table>
        </div>
        </div>
    </div>

    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content p-0 overflow-hidden">
            <div style="background: linear-gradient(135deg,#6366f1,#8b5cf6); color:white; padding:20px; text-align:center;">
                <i class="bi bi-person-plus" style="font-size:40px; display:block; margin-bottom:10px;"></i>
                <h5 class="fw-bold mb-0">Add New User</h5>
            </div>
            <div class="p-4">
                <div class="mb-3">
                    <label>Email:</label>
                    <div class="input-group rounded-pill overflow-hidden">
                        <input type="text" name="username" class="form-control border-0" placeholder="Enter email username" required style="border-radius:50px 0 0 50px;">
                        <span class="input-group-text bg-primary text-white border-0" style="border-radius:0 50px 50px 0;">@admin</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label>Password:</label>
                    <input type="password" name="password" class="form-control rounded-pill" placeholder="Enter password" required>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 p-3" style="background:#f0f4ff;">
                <button type="submit" name="add_user" class="btn btn-success rounded-pill px-4">Confirm</button>
                <button type="button" class="btn btn-danger rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
            </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content p-0 overflow-hidden">
                <div style="background: linear-gradient(135deg,#6366f1,#8b5cf6); color:white; padding:20px; text-align:center;">
                    <i class="bi bi-pencil-square" style="font-size:40px; display:block; margin-bottom:10px;"></i>
                    <h5 class="fw-bold mb-0">Edit User</h5>
                </div>
            <div class="p-4">
                <input type="hidden" name="edit_id" id="edit_id">
                    <div class="mb-3">
                        <label>Email:</label>
                        <input type="text" name="edit_email" id="edit_email" class="form-control rounded-pill" required>
                    </div>
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="edit_password" class="form-control rounded-pill" placeholder="<?= str_repeat('*', strlen($user['password'])) ?>">
                    </div>
            </div>
            <div class="d-flex justify-content-end gap-2 p-3" style="background:#f0f4ff;">
                <button type="submit" name="edit_user" class="btn btn-success rounded-pill px-4">Save Changes</button>
                <button type="button" class="btn btn-danger rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
            </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content p-0 overflow-hidden text-center">
                <div style="background: linear-gradient(135deg,#ef4444,#f87171); color:white; padding:20px; text-align:center;">
                    <i class="bi bi-trash" style="font-size:40px; display:block; margin-bottom:10px;"></i>
                    <h5 class="fw-bold mb-0">Delete User</h5>
                </div>
                <div class="p-4">
                    <p>Are you sure you want to delete this user?</p>
                    <input type="hidden" name="delete_id" id="delete_id">
                    <p><strong id="delete_email"></strong></p>
                </div>
                <div class="d-flex justify-content-center gap-2 p-3" style="background:#fef2f2;">
                    <button type="submit" name="delete_user" class="btn btn-danger rounded-pill px-4">Delete</button>
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content p-4 text-center">
                <div class="logout-icon mb-3"><i class="bi bi-box-arrow-right"></i></div>
                    <h5 class="fw-bold mb-2">Logout Confirmation</h5>
                    <p class="text-muted mb-4">Are you sure you want to logout from your account?</p>
                <div class="d-flex flex-column gap-2">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <a href="logout.php" class="btn btn-danger rounded-pill">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 1100;">
        <div id="actionToast" class="toast align-items-center text-bg-<?= $toastType ?: 'success' ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"><?= htmlspecialchars($toastMessage) ?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const toastEl = document.getElementById('actionToast');
        if(toastEl && "<?= $toastMessage ?>" !== ""){
            const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
        }
        document.querySelectorAll('.edit-btn').forEach(btn=>{
            btn.addEventListener('click',()=>{ 
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_email').value = btn.dataset.email;
            });
        });
        document.querySelectorAll('.delete-btn').forEach(btn=>{
            btn.addEventListener('click',()=>{ 
                document.getElementById('delete_id').value = btn.dataset.id;
                document.getElementById('delete_email').textContent = btn.dataset.email;
            });
        });
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(t => new bootstrap.Tooltip(t));
    });
    </script>
    </body>
</html>
