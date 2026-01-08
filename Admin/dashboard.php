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

if (!$data) {
    die("School profile not found.");
}

$showProfileToast = false;
if (isset($_POST['save_profile'])) {
    $stmt = $conn->prepare("
        UPDATE school_profile SET
            name = :name,
            location = :location,
            email = :email,
            mobile = :mobile,
            telephone = :telephone,
            description = :description
        WHERE id = 1
    ");

    $stmt->execute([
        ':name' => $_POST['name'] ?? $data['name'],
        ':location' => $_POST['location'] ?? $data['location'],
        ':email' => $_POST['email'] ?? $data['email'],
        ':mobile' => $_POST['mobile'] ?? $data['mobile'],
        ':telephone' => $_POST['telephone'] ?? $data['telephone'],
        ':description' => $_POST['description'] ?? $data['description']
    ]);

    $showProfileToast = true;

    $stmt = $conn->prepare("SELECT * FROM school_profile WHERE id = 1");
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
    $logoName = time() . "_" . basename($_FILES['logo']['name']);
    move_uploaded_file($_FILES['logo']['tmp_name'], "../uploads/" . $logoName);

    $stmt = $conn->prepare("UPDATE school_profile SET logo = :logo WHERE id = 1");
    $stmt->execute([':logo' => $logoName]);

    echo 'success';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    body {
        background:#f4f6fb;
        font-family:system-ui,sans-serif;
    }

    .sidebar {
        width:90px;
        height:100vh;
        background:#0f172a;
        position:fixed;
        display:flex;
        flex-direction:column;
        align-items:center;
        padding-top:20px;
        z-index:1000;
    }

    .sidebar img {
        width:56px;
        height:56px;
        border-radius:50%;
        object-fit:cover;
        border:3px solid #6366f1;
        margin-bottom:25px;
    }

    .top-icons {
        display:flex;
        flex-direction:column;
        gap:26px;
    }

    .sidebar a i {
        font-size:26px;
        color:#c7d2fe;
        transition:.3s;
    }

    .sidebar a.active i,
    .sidebar a:hover i {
        color:#6366f1;
        transform:scale(1.15);
    }

    .spacer { flex-grow:1; }

    .logout i {
        color:#ef4444;
        font-size:26px;
    }

    .content {
        margin-left:110px;
        padding:30px;
    }

    .card {
        border-radius:18px;
        border:none;
        box-shadow:0 15px 35px rgba(0,0,0,.08);
    }

    .logo-box {
        height:180px;
        border-radius:16px;
        background:linear-gradient(135deg,#e0e7ff,#f5f3ff);
        display:flex;
        justify-content:center;
        align-items:center;
    }

    .form-control {
        border-radius:14px;
        padding:12px;
    }

    .btn-primary {
        border-radius:30px;
        padding:10px 30px;
    }

    .modal-content {
        border-radius:22px;
        border:none;
        box-shadow:0 25px 60px rgba(0,0,0,.25);
    }

    .logo-upload-box {
        border:2px dashed #6366f1;
        border-radius:18px;
        padding:25px;
        background:#f5f7ff;
        cursor:pointer;
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
    }

    .logo-upload-box i {
        font-size:42px;
        color:#6366f1;
    }

    .logout-icon {
        width:80px;
        height:80px;
        border-radius:50%;
        background:#fee2e2;
        display:flex;
        align-items:center;
        justify-content:center;
        margin:0 auto 15px;
    }

    .logout-icon i {
        font-size:40px;
        color:#dc2626;
    }

    @media (max-width:768px){
        .sidebar{
            width:100%;
            height:70px;
            flex-direction:row;
            bottom:0;
            top:auto;
            padding:0 10px;
            justify-content:space-between;
            align-items:center;
        }
        .sidebar img{
            margin-bottom:0;
        }
        .top-icons{
            flex-direction:row;
            gap:15px;
            align-items:center;
        }
        .spacer{display:none;}
        .content{
            margin-left:0;
            margin-bottom:90px;
            padding:15px;
        }
    }
    
    @media (max-width: 350px) {

        .sidebar {
            height: 64px;
            padding: 0 6px;
            width: 100%;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            bottom: 0;
            top: auto;
        }

        .sidebar img {
            width: 38px;
            height: 38px;
            border-width: 2px;
            margin-bottom: 0;
        }

        .sidebar a i {
            font-size: 22px;
        }

        .top-icons {
            display: flex;
            flex-direction: row;
            gap: 10px;
            align-items: center;
        }

        .logout {
            margin: 0;
            padding: 0;
            font-size: 22px;
        }

        .spacer {
            display: none;
        }

        .content {
            margin-left: 0;
            margin-bottom: 85px;
            padding: 10px;
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
        <a href="dashboard.php" class="active"><i class="bi bi-grid-fill"></i></a>
        <a href="users.php"><i class="bi bi-people-fill"></i></a>
        <a href="enrollment.php"><i class="bi bi-file-earmark-fill"></i></a>
        <a href="classlist.php"><i class="bi bi-person-square"></i></a>
        <a href="messages.php"><i class="bi bi-envelope-fill"></i></a>
    </div>

    <div class="spacer"></div>
        <a href="#" data-bs-toggle="modal" data-bs-target="#logoutModal" class="logout">
        <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>

    <div class="content">
        <form method="POST">

            <div class="card mb-4">
                <div class="card-body text-center">
                    <h4 class="mb-3">School Logo</h4>

                    <div class="logo-box mb-3">
                        <img id="previewLogo" src="../uploads/<?= htmlspecialchars($data['logo']) ?>" class="img-fluid" style="max-height:150px; max-width:100%;">
                    </div>

                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#logoModal">Choose Logo</button>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h4 class="text-center mb-4">School Profile</h4>

                    <input class="form-control mb-3" name="name" value="<?= htmlspecialchars($data['name']) ?>" placeholder="School Name">
                    <input class="form-control mb-3" name="location" value="<?= htmlspecialchars($data['location']) ?>" placeholder="Location">
                    <input class="form-control mb-3" name="email" value="<?= htmlspecialchars($data['email']) ?>" placeholder="Email">
                    <input class="form-control mb-3" name="mobile" value="<?= htmlspecialchars($data['mobile']) ?>" placeholder="Mobile">
                    <input class="form-control mb-3" name="telephone" value="<?= htmlspecialchars($data['telephone']) ?>" placeholder="Telephone">
                    <input class="form-control mb-4" name="description" value="<?= htmlspecialchars($data['description']) ?>" placeholder="Description">

                    <div class="text-center">
                        <button type="submit" name="save_profile" class="btn btn-primary">Save Profile</button>
                    </div>
                </div>
            </div>

        </form>
    </div>

    <div class="modal fade" id="logoModal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content text-center p-4">
                <h5 class="fw-bold mb-3">Update Logo</h5>
                <img id="modalPreview" src="../uploads/<?= htmlspecialchars($data['logo']) ?>" class="mb-3" style="max-width:100%; max-height:250px; object-fit:contain; display:block; margin:auto;">
                <label class="logo-upload-box w-100 mb-3">
                    <i class="bi bi-cloud-arrow-up"></i> 
                    <p class="mt-2">Click to choose image</p> <input type="file" id="logoInput" hidden>
                </label>

                <div class="d-flex justify-content-center">
                    <button id="saveLogoBtn" class="btn btn-primary w-50 rounded-pill">Save Logo </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content p-4 text-center">
        
        <div class="logout-icon mb-3">
            <i class="bi bi-box-arrow-right"></i>
        </div>

        <h5 class="fw-bold mb-2">Logout Confirmation</h5>
        <p class="text-muted mb-4">Are you sure you want to logout from your account?</p>

        <div class="d-flex flex-column gap-2">
            <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
            <a href="logout.php" class="btn btn-danger rounded-pill">Logout</a>
        </div>
        </div>
    </div>
    </div>

    <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index:1100">
        <div id="profileToast" class="toast bg-success text-white">
            <div class="toast-body">Profile updated successfully!</div>
        </div>
        <div id="logoToast" class="toast bg-info text-white mt-2">
            <div class="toast-body">Logo updated successfully!</div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const logoInput=document.getElementById('logoInput');
    const saveLogoBtn=document.getElementById('saveLogoBtn');
    const sidebarLogo=document.getElementById('sidebarLogo');
    const modalPreview=document.getElementById('modalPreview');

    logoInput.addEventListener('change',()=>{
        if(!logoInput.files.length) return;
        const r=new FileReader();
        r.onload=e=>modalPreview.src=e.target.result;
        r.readAsDataURL(logoInput.files[0]);
    });

    <?php if($showProfileToast): ?>
    new bootstrap.Toast(document.getElementById('profileToast'),{delay:2000}).show();
    <?php endif; ?>

    saveLogoBtn.addEventListener('click',()=>{
        if(!logoInput.files.length) return;
        const fd=new FormData();
        fd.append('logo',logoInput.files[0]);
        fetch('<?= $_SERVER['PHP_SELF'] ?>',{method:'POST',body:fd})
        .then(r=>r.text()).then(r=>{
            if(r==='success'){
                sidebarLogo.src=modalPreview.src;
                document.getElementById('previewLogo').src=modalPreview.src;
                new bootstrap.Toast(document.getElementById('logoToast'),{delay:2000}).show();
                bootstrap.Modal.getInstance(document.getElementById('logoModal')).hide();
                logoInput.value='';
            }
        });
    });
</script>

</body>
</html>
