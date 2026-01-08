<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}
include "db.php";

if (isset($_POST['delete_id'])) {
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id=?");
    $stmt->execute([$_POST['delete_id']]);
    echo "success";
    exit();
}

if (isset($_GET['view_id'])) {
    $stmt = $conn->prepare("SELECT * FROM contact_messages WHERE id=?");
    $stmt->execute([$_GET['view_id']]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit();
}

$stmt = $conn->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC");
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $conn->prepare("SELECT logo FROM school_profile WHERE id=1");
$stmt2->execute();
$school = $stmt2->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Inbox</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
    body{
        background:#f4f6fb;
        font-family:system-ui,sans-serif;
        margin:0;
    }
    .sidebar{
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
    .sidebar img{
        width:56px;
        height:56px;
        border-radius:50%;
        object-fit:cover;
        border:3px solid #6366f1;
        margin-bottom:25px;
    }
    .top-icons{
        display:flex;
        flex-direction:column;
        gap:26px;
    }
    .sidebar a i{
        font-size:26px;
        color:#c7d2fe;
        transition:.3s;
    }
    .sidebar a.active i,.sidebar a:hover i{
        color:#6366f1;
        transform:scale(1.15);
    }
    .spacer{
        flex-grow:1;
    }
    .logout i{
        color:#ef4444;
        font-size:26px;
    }
    .content{
        margin-left:110px;
        padding:30px;
    }
    .card{
        border-radius:18px;
        border:none;
        box-shadow:0 15px 35px rgba(0,0,0,.08);
    }
    .table tbody tr:hover{
        background:#e8f0fe;
    }
    .table thead th{
        background:#6366f1;
        color:white;
    }
    .btn-sm{
        border-radius:6px;
    }
    .logout-icon{
        width:80px;
        height:80px;
        border-radius:50%;
        background:#fee2e2;
        display:flex;
        align-items:center;
        justify-content:center;
        margin:0 auto 15px;
    }
    .logout-icon i{
        font-size:40px;
        color:#dc2626;
    }
    .modal-content{
        border-radius:22px;
        border:none;
        box-shadow:0 25px 60px rgba(0,0,0,.25);
    }
    @media(max-width:768px){
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
        .spacer{
            display:none;
        }
        .content{
            margin-left:0;
            margin-bottom:90px;
            padding:15px;
        }
        .table-responsive{
            overflow-x:auto !important;
        }
        table{
            display:block;
            width:100%;
            white-space:nowrap;
        }
        th,td{
            white-space:nowrap;
            font-size:14px;
            padding:6px 8px;
        }
    }
    @media(max-width:350px){
        .sidebar{
            height:64px;
            padding:0 6px;
            flex-direction:row;
            justify-content:space-between;
            align-items:center;
        }
        .sidebar img{
            width:38px;
            height:38px;
            border-width:2px;
            margin-bottom:0;
        }
        .sidebar a i{
            font-size:22px;
        }
        .top-icons{
            flex-direction:row;
            gap:10px;
            align-items:center;
        }
        .spacer{
            display:none;
        }
        .content{
            margin-left:0;
            margin-bottom:85px;
            padding:10px;
        }
        h4{
            text-align:center;
            font-size:18px;
        }
        .btn,.btn-sm{
            padding:4px 10px;
            font-size:13px;
        }
        .table-responsive{
            overflow-x:hidden !important;
        }
        table, thead, tbody, th, td, tr{
            display:block;
            width:100%;
        }
        thead{
            display:none;
        }
        tbody tr{
            background:#fff;
            margin-bottom:14px;
            padding:12px 14px;
            border-radius:16px;
            box-shadow:0 10px 25px rgba(15,23,42,0.1);
        }
        tbody td{
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:6px 0;
            font-size:13px;
            border:none;
            white-space:normal;
            word-break:break-word;
        }
        tbody td:nth-child(1)::before {
            content:"#";
            font-weight:600;
            color:#475569;
            margin-right:6px;
        }
        tbody td:nth-child(2)::before {
            content:"Name";
            font-weight:600;
            color:#475569;
            margin-right:6px;
        }
        tbody td:nth-child(3)::before {
            content:"Email";
            font-weight:600;
            color:#475569;
            margin-right:6px;
        }
        tbody td:nth-child(4)::before {
            content:"Date";
            font-weight:600;
            color:#475569;
            margin-right:6px;
        }
        tbody td:last-child{
            justify-content:flex-end;
            gap:4px;
            flex-wrap:wrap;
            margin-top:6px;
        }
        tbody td:last-child .btn{
            margin:2px 0;
            width:48%;
        }
    }
</style>
</head>
<body>

<div class="sidebar">
    <?php if(!empty($school['logo']) && file_exists("../uploads/".$school['logo'])): ?>
        <img src="../uploads/<?= htmlspecialchars($school['logo']) ?>" alt="Logo">
    <?php else: ?>
        <img src="../uploads/default-logo.png" alt="Logo">
    <?php endif; ?>
    <div class="top-icons">
        <a href="dashboard.php"><i class="bi bi-grid-fill"></i></a>
        <a href="users.php"><i class="bi bi-people-fill"></i></a>
        <a href="enrollment.php"><i class="bi bi-file-earmark-fill"></i></a>
        <a href="classlist.php"><i class="bi bi-person-square"></i></a>
        <a href="messages.php" class="active"><i class="bi bi-envelope-fill"></i></a>
    </div>
    <div class="spacer"></div>
    <a href="#" class="logout" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-box-arrow-right"></i></a>
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

<div class="content">
    <div class="card p-4">
        <h4 class="mb-4"><i class="bi bi-envelope-fill"></i> Inbox</h4>
        <div class="table-responsive">
            <table class="table table-bordered text-center align-middle">
                <thead>
                    <tr>
                        <th>#</th><th>Name</th><th>Email</th><th>Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($messages as $i=>$m): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($m['name']) ?></td>
                        <td><?= htmlspecialchars($m['email']) ?></td>
                        <td><?= date("F d, Y g:i A", strtotime($m['created_at'])) ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="openMessage(<?= $m['id'] ?>)"><i class="bi bi-eye"></i> Open</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteMessage(<?= $m['id'] ?>)"><i class="bi bi-trash"></i> Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="messageModal">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Message</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <strong id="modalName"></strong>
                <p class="text-muted mb-2" id="modalEmail"></p>
                <hr>
                <p id="modalMessage"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentMessageId = null;
function openMessage(id){
    fetch("messages.php?view_id="+id)
    .then(res=>res.json())
    .then(d=>{
        currentMessageId=d.id;
        modalName.innerText=d.name;
        modalEmail.innerText=d.email;
        modalMessage.innerText=d.message;
        new bootstrap.Modal(messageModal).show();
    });
}
function deleteMessage(id){
    Swal.fire({
        title:'Delete message?',
        text:'This action cannot be undone!',
        icon:'warning',
        showCancelButton:true,
        confirmButtonColor:'#dc3545',
        confirmButtonText:'Yes, delete it'
    }).then(r=>{
        if(r.isConfirmed){
            fetch("messages.php",{
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'delete_id='+id
            }).then(()=>{
                Swal.fire({icon:'success',title:'Deleted!',timer:1200,showConfirmButton:false});
                setTimeout(()=>location.reload(),1200);
            });
        }
    });
}
</script>

</body>
</html>
