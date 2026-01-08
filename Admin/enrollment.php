<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include "db.php";

$stmt = $conn->prepare("SELECT logo FROM school_profile WHERE id = 1");
$stmt->execute();
$school = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $stmt = $conn->prepare("
        INSERT INTO enrollment_schedule 
        (schedule_date, start_time, end_time, slots)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $_POST['schedule_date'],
        $_POST['start_time'],
        $_POST['end_time'],
        $_POST['slots']
    ]);

    $_SESSION['schedule_added'] = true;

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

/* DELETE SCHEDULE */
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM enrollment_schedule WHERE id = ?");
    $stmt->execute([$_GET['delete']]);

    $_SESSION['schedule_deleted'] = true;

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}



$schedules = $conn->prepare("SELECT * FROM enrollment_schedule ORDER BY schedule_date ASC, start_time ASC");
$schedules->execute();

$requests = $conn->prepare("
    SELECT ef.*, sp.email, sp.username, sp.password, s.schedule_date, s.start_time, s.end_time,
           ea.elementary_school, ea.elementary_year,
           ea.junior_high_school, ea.junior_high_year,
           ea.senior_high_school, ea.senior_high_year
    FROM enrollment_form ef
    JOIN studentportal sp ON ef.student_id = sp.id
    LEFT JOIN enrollment_schedule s ON ef.appointment_schedule = s.id
    LEFT JOIN educational_attainment ea ON ef.student_id = ea.student_id
    WHERE ef.status = 'Pending'
    ORDER BY ef.created_at ASC
");
$requests->execute();

$course_stmt = $conn->prepare("SELECT * FROM courses ORDER BY course_name");
$course_stmt->execute();
$courses = $course_stmt->fetchAll(PDO::FETCH_ASSOC);

$year_stmt = $conn->prepare("SELECT * FROM year_levels ORDER BY id");
$year_stmt->execute();
$years = $year_stmt->fetchAll(PDO::FETCH_ASSOC);

$subjects_stmt = $conn->prepare("SELECT * FROM subjects ORDER BY created_at DESC");
$subjects_stmt->execute();
$subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Enrollment Management</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
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
        transition: 0.3s;
    }

    .sidebar a.active i,
    .sidebar a:hover i {
        color: #6366f1;
        transform: scale(1.15);
    }

    .spacer { flex-grow: 1; }

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

    #logoutModal .modal-content {
        border-radius: 22px;
        border: none;
        box-shadow: 0 25px 60px rgba(0,0,0,0.25);
        padding: 30px 20px;
        text-align: center;
    }

    #logoutModal h5 {
        font-weight: 700;
        margin-bottom: 10px;
    }

    #logoutModal p {
        color: #6b7280;
        margin-bottom: 20px;
    }

    #logoutModal .btn {
        border-radius: 30px;
        padding: 8px 25px;
        font-weight: 500;
        transition: 0.3s;
    }

    #logoutModal .btn-light:hover {
        background: #f3f4f6;
    }

    #logoutModal .btn-danger:hover {
        background: #b91c1c;
        border-color: #b91c1c;
    }

    .content {
        margin-left: 110px;
        padding: 30px;
        min-height: 100vh;
    }

    .section-card {
        background: white;
        border-radius: 18px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.08);
        overflow-x: auto;
    }

    .section-title {
        text-align: center;
        font-size: 26px;
        margin-bottom: 20px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th {
        background: #f1f3f5;
        font-weight: 600;
        text-align: center;
        white-space: nowrap;
    }

    .table td {
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }

    .table input, .table select {
        min-width: 80px;
    }

    .btn-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        padding: 0;
    }

    @media (max-width: 1024px){
        .section-card {
            padding: 20px;
        }
        .section-title {
            font-size: 22px;
        }
        .table th, .table td {
            font-size: 14px;
            padding: 6px 8px;
        }
        .btn-circle {
            width: 32px;
            height: 32px;
        }
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
        .section-card{
            padding:15px;
            margin-bottom:20px;
        }
        .section-title{
            font-size:20px;
        }
        .table th, .table td{
            font-size:13px;
            padding:4px 6px;
        }
        .btn-circle{
            width:28px;
            height:28px;
        }
        .table{
            display:block;
            overflow-x:auto;
            white-space: nowrap;
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

    .logout {
        margin: 0;
        padding: 0;
        font-size: 22px; 
    }

    .spacer {
        display: none;
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
    <?php if (!empty($school['logo']) && file_exists("../uploads/".$school['logo'])): ?>
        <img src="../uploads/<?= htmlspecialchars($school['logo']) ?>" alt="School Logo">
    <?php else: ?>
        <img src="../uploads/default-logo.png" alt="Default Logo">
    <?php endif; ?>

    <div class="top-icons">
        <a href="dashboard.php" title="Dashboard"><i class="bi bi-grid-fill"></i></a>
        <a href="users.php" title="Users"><i class="bi bi-people-fill"></i></a>
        <a href="enrollment.php" class="active" title="Enrollment"><i class="bi bi-file-earmark-fill"></i></a>
        <a href="classlist.php" title="Video"><i class="bi bi-person-square"></i></a>
        <a href="messages.php" title="Messages"><i class="bi bi-envelope-fill"></i></a>
    </div>

    <!-- Sidebar Logout Button -->
<div class="spacer"></div>
<a href="#" data-bs-toggle="modal" data-bs-target="#logoutModal" class="logout">
    <div class="logout-icon-sidebar">
        <i class="bi bi-box-arrow-right"></i>
    </div>
</a>

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


<div class="content">
<div class="section-card">
    <div class="section-title">Enrollment Scheduling Appointment</div>

    <form id="scheduleForm" method="POST" action="" novalidate>
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Slots</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="date" name="schedule_date" class="form-control" min="<?= date('Y-m-d') ?>"></td>
                    <td><input type="time" name="start_time" class="form-control"></td>
                    <td><input type="time" name="end_time" class="form-control"></td>
                    <td><input type="number" name="slots" class="form-control" min="1"></td>
                    <td>
                        <button type="submit" name="add_schedule" class="btn btn-success btn-circle">
                            <i class="bi bi-plus"></i>
                        </button>
                    </td>
                </tr>

                <?php while ($row = $schedules->fetch(PDO::FETCH_ASSOC)): ?>
                <tr id="row-<?= $row['id'] ?>">
                    <td><?= date("F d, Y | l", strtotime($row['schedule_date'])) ?></td>
                    <td><?= date("h:i A", strtotime($row['start_time'])) ?></td>
                    <td><?= date("h:i A", strtotime($row['end_time'])) ?></td>
                    <td><?= $row['slots'] ?></td>
                    <td>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-circle btn-delete-schedule">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </form>
</div>

<div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1100;">
    <div id="actionToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" style="min-width:300px; max-width:400px;">
        <div class="d-flex">
            <div class="toast-body">Action Completed!</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const actionToastEl = document.getElementById('actionToast');

    function showToast(message, type='success'){
        actionToastEl.className = `toast align-items-center text-white bg-${type} border-0`;
        actionToastEl.querySelector('.toast-body').textContent = message;
        new bootstrap.Toast(actionToastEl, { delay: 3000 }).show();
    }

    const scheduleForm = document.getElementById('scheduleForm');

    scheduleForm.addEventListener('submit', function(e){
        const dateInput = this.querySelector('input[name="schedule_date"]').value;
        const startTime = this.querySelector('input[name="start_time"]').value;
        const endTime = this.querySelector('input[name="end_time"]').value;
        const slots = this.querySelector('input[name="slots"]').value;

        if(!dateInput || !startTime || !endTime || !slots || parseInt(slots) <= 0){
            e.preventDefault();
            showToast('Please fill all fields and set Slots > 0!', 'danger');
            return;
        }

        const selectedDateTime = new Date(dateInput + 'T' + startTime);
        const now = new Date();
        if(selectedDateTime <= now){
            e.preventDefault();
            showToast('Appointment must be set for a future date and time!', 'danger');
            return;
        }

    });

    document.querySelectorAll('.btn-delete-schedule').forEach(button => {
        button.addEventListener('click', function(e){
            e.preventDefault();
            const url = this.getAttribute('href');
            const row = this.closest('tr');

            Swal.fire({
                title: 'Are you sure?',
                text: "This schedule will be deleted permanently!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if(result.isConfirmed){
                    fetch(url)
                        .then(res => res.text())
                        .then(data => {
                            row.remove();
                            showToast('Schedule Deleted!');
                        })
                        .catch(err => {
                            showToast('Something went wrong!', 'danger');
                        });
                }
            });
        });
    });

    <?php if(isset($_SESSION['schedule_added'])): ?>
        showToast('Schedule Added!');
        <?php unset($_SESSION['schedule_added']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['schedule_deleted'])): ?>
        showToast('Schedule Deleted!');
        <?php unset($_SESSION['schedule_deleted']); ?>
    <?php endif; ?>
});
</script>

<div class="section-card">
    <div class="section-title">Student Approval Request</div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Appointment Date</th>
                <th>Time</th>
                <th>Username</th>
                <th>Name</th>
                <th>Course</th>
                <th>Year</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $requests->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><?= date("F d, Y | l", strtotime($row['schedule_date'])) ?> <?= date("h:i A", strtotime($row['start_time'])) ?> – <?= date("h:i A", strtotime($row['end_time'])) ?></td>
                <td><?= date("F d, Y | h:i A", strtotime($row['created_at'])) ?></td>
                <td><?= htmlspecialchars($row['username'])  ?></td>
                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']) ?></td>
                <td><?= htmlspecialchars($row['course']) ?></td>
                <td><?= htmlspecialchars($row['year_level']) ?></td>
                <td>
                    <span class="badge bg-warning"><?= strtoupper($row['status']) ?></span>
                </td>
                <td>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['id'] ?>">Open</button>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php
$requests->execute();
while ($row = $requests->fetch(PDO::FETCH_ASSOC)):
?>
<div class="modal fade" id="viewModal<?= $row['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Enrollment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <table class="table table-bordered">
                    <tr><th>Appointment Schedule</th><td><?= htmlspecialchars($row['appointment_schedule']) ?></td></tr>
                    <tr><th>Enrollment Status</th><td><?= htmlspecialchars($row['status']) ?></td></tr>
                    <tr><th>Username</th><td><?= htmlspecialchars($row['username']) ?></td></tr>
                    <tr><th>Password</th><td><?= str_repeat('•', strlen($row['password']) + 3) ?></td></tr>
                    <tr><th>First Name</th><td><?= htmlspecialchars($row['first_name']) ?></td></tr>
                    <tr><th>Middle Name</th><td><?= htmlspecialchars($row['middle_name']) ?></td></tr>
                    <tr><th>Last Name</th><td><?= htmlspecialchars($row['last_name']) ?></td></tr>
                    <tr><th>Sex</th><td><?= htmlspecialchars($row['sex']) ?></td></tr>
                    <tr><th>Course</th><td><?= htmlspecialchars($row['course'] ?: 'Select Course') ?></td></tr>
                    <tr><th>Year</th><td><?= htmlspecialchars($row['year_level'] ?: 'Select Year Level') ?></td></tr>
                    <tr>
                        <th>Section</th>
                        <td>
                            <select name="section" class="form-select form-select-sm mt-2" form="form-<?= $row['id'] ?>" required>
                                <option value="">Select Section...</option>
                                <?php 
                                $sections = ['A', 'B', 'C', 'D'];
                                foreach ($sections as $sec): ?>
                                    <option value="<?= $sec ?>" <?= ($row['section'] == $sec) ? 'selected' : '' ?>><?= $sec ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr><th>Birthdate</th><td><?= htmlspecialchars($row['dob']) ?></td></tr>
                    <tr><th>Home Address</th><td><?= htmlspecialchars($row['home_address']) ?></td></tr>
                    <tr><th>Phone Number</th><td><?= htmlspecialchars($row['contact_number']) ?></td></tr>
                    <tr><th>Email</th><td><?= htmlspecialchars($row['email'] ?? '') ?></td></tr>
                    <tr><th>Guardian Name</th><td><?= htmlspecialchars($row['guardian_name']) ?></td></tr>
                    <tr><th>Guardian Phone Number</th><td><?= htmlspecialchars($row['guardian_contact']) ?></td></tr>
                    <tr><th>Guardian Address</th><td><?= htmlspecialchars($row['guardian_address']) ?></td></tr>
                    <tr><th>Elementary School Name</th><td><?= htmlspecialchars($row['elementary_school'] ?? '') ?></td></tr>
                    <tr><th>Elementary Graduation Year</th><td><?= htmlspecialchars($row['elementary_year'] ?? '') ?></td></tr>
                    <tr><th>Junior High School Name</th><td><?= htmlspecialchars($row['junior_high_school'] ?? '') ?></td></tr>
                    <tr><th>Junior High Graduation Year</th><td><?= htmlspecialchars($row['junior_high_year'] ?? '') ?></td></tr>
                    <tr><th>Senior High School Name</th><td><?= htmlspecialchars($row['senior_high_school'] ?? '') ?></td></tr>
                    <tr><th>Senior High Graduation Year</th><td><?= htmlspecialchars($row['senior_high_year'] ?? '') ?></td></tr>
                </table>
            </div>

            <div class="modal-footer">
                <form method="POST" action="update_status.php" id="form-<?= $row['id'] ?>" class="d-inline status-form">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="status" value="Approved">
                    <button type="submit" class="btn btn-success mt-2">Enroll</button>
                </form>

                <form method="POST" action="update_status.php" id="form-<?= $row['id'] ?>-reject" class="d-inline status-form">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="status" value="Rejected">
                    <button type="submit" class="btn btn-danger mt-2">Reject</button>
                </form>
            </div>

        </div>
    </div>
</div>
<?php endwhile; ?>


<div class="section-card">
    <div class="section-title">Masterlist</div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Username</th>
                <th>Name</th>
                <th>Course</th>
                <th>Year</th>
                <th>Section</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $masterlist = $conn->prepare("
            SELECT ef.*, sp.email, sp.username
            FROM enrollment_form ef
            JOIN studentportal sp ON ef.student_id = sp.id
            WHERE ef.status = 'Enrolled'
            ORDER BY ef.created_at ASC
        ");
        $masterlist->execute();
        while ($row = $masterlist->fetch(PDO::FETCH_ASSOC)):
        ?>
            <tr>
                <td>@<?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']) ?></td>
                <td><?= htmlspecialchars($row['course']) ?></td>
                <td><?= htmlspecialchars($row['year_level']) ?></td>
                <td><?= htmlspecialchars($row['section'] ?? '-') ?></td>
                <td><span class="badge bg-success"><?= strtoupper($row['status']) ?></span></td>
                <td>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#masterModal<?= $row['id'] ?>">Edit</button>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php
$masterlist->execute();
while ($row = $masterlist->fetch(PDO::FETCH_ASSOC)):
?>
<div class="modal fade" id="masterModal<?= $row['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Edit Student Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form method="POST" action="update_student.php" class="update-student-form">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <table class="table table-bordered">
                        <tr><th>Username</th><td><input type="text" name="username" class="form-control" value="<?= htmlspecialchars($row['username']) ?>"></td></tr>
                        <tr><th>First Name</th><td><input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($row['first_name']) ?>"></td></tr>
                        <tr><th>Middle Name</th><td><input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($row['middle_name']) ?>"></td></tr>
                        <tr><th>Last Name</th><td><input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($row['last_name']) ?>"></td></tr>
                        <tr><th>Email</th><td><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($row['email']) ?>"></td></tr>
                        <tr>
                            <th>Course</th>
                            <td>
                                <select name="course" class="form-select" required>
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?= htmlspecialchars($c['course_name']) ?>" <?= ($row['course'] == $c['course_name']) ? 'selected' : '' ?>><?= htmlspecialchars($c['course_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Year Level</th>
                            <td>
                                <select name="year_level" class="form-select" required>
                                    <option value="">Select Year</option>
                                    <?php foreach ($years as $y): ?>
                                        <option value="<?= htmlspecialchars($y['year_name']) ?>" <?= ($row['year_level'] == $y['year_name']) ? 'selected' : '' ?>><?= htmlspecialchars($y['year_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                        <th>Section</th>
                        <td>
                            <select name="section" class="form-select">
                                <option value="">Select a section...</option>
                                <?php 
                                $sections = ['A','B','C','D'];
                                foreach($sections as $sec): ?>
                                    <option value="<?= $sec ?>" <?= ($row['section'] == $sec) ? 'selected' : '' ?>><?= $sec ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        </tr>
                    </table>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Save Changes</button>
                </form>
                <form method="POST" action="delete_student.php" class="delete-student-form d-inline">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>

        </div>
    </div>
</div>
<?php endwhile; ?>

<div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index:1100">
    <div id="actionToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" style="min-width:300px; max-width:500px;">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toastEl = document.getElementById('actionToast');
    const toastMsg = document.getElementById('toastMessage');

    function showToast(message, type='success', modal=null, reload=false){
        toastMsg.textContent = message;
        toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
        const bsToast = new bootstrap.Toast(toastEl, { delay: 2000 });
        bsToast.show();

        if(modal){
            setTimeout(() => {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if(bsModal) bsModal.hide();
            }, 500);
        }

        if(reload){
            setTimeout(()=> location.reload(), 1500);
        }
    }

    document.querySelectorAll('.update-student-form').forEach(form=>{
        form.addEventListener('submit', function(e){
            e.preventDefault();
            const modal = this.closest('.modal');
            const formData = new FormData(this);
            fetch(this.action, { method:'POST', body:formData })
                .then(res => res.json())
                .then(data => {
                    showToast(data.message, data.type, modal, true);
                })
                .catch(err => {
                    showToast('Something went wrong!', 'danger', modal);
                });
        });
    });
});
</script>


<div class="section-card">
    <div class="section-title">Subjects Management</div>

    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>Subject</th>
                <th>Course</th>
                <th>Instructor</th>
                <th>Year</th>
                <th>Hours</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr id="newSubjectRow">
                <td><input type="text" class="form-control" id="subject_name" placeholder="Subject"></td>
                <td>
                    <select class="form-select" id="course">
                        <option value="">Select course</option>
                        <?php foreach($courses as $c): ?>
                        <option value="<?= htmlspecialchars($c['course_name']) ?>"><?= htmlspecialchars($c['course_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="text" class="form-control" id="instructor" placeholder="Instructor"></td>
                <td>
                    <select class="form-select" id="year_level">
                        <option value="">Select year</option>
                        <?php foreach($years as $y): ?>
                        <option value="<?= htmlspecialchars($y['year_name']) ?>"><?= htmlspecialchars($y['year_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" class="form-control" id="hours" min="1"></td>
                <td>
                    <button class="btn btn-success btn-circle" id="addSubjectBtn"><i class="bi bi-plus"></i></button>
                </td>
            </tr>

            <?php foreach($subjects as $sub): ?>
            <tr id="subject-<?= $sub['id'] ?>">
                <td class="sub-name"><?= htmlspecialchars($sub['subject_name']) ?></td>
                <td class="sub-course"><?= htmlspecialchars($sub['course']) ?></td>
                <td class="sub-instructor"><?= htmlspecialchars($sub['instructor']) ?></td>
                <td class="sub-year"><?= htmlspecialchars($sub['year_level']) ?></td>
                <td class="sub-hours"><?= htmlspecialchars($sub['hours']) ?></td>
                <td>
                    <button class="btn btn-primary btn-sm edit-subject" data-id="<?= $sub['id'] ?>" data-mode="edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-danger btn-sm delete-subject" data-id="<?= $sub['id'] ?>">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function(){

    const coursesList = <?php echo json_encode(array_column($courses, 'course_name')); ?>;
    const yearsList = <?php echo json_encode(array_column($years, 'year_name')); ?>;
    document.getElementById('addSubjectBtn').addEventListener('click', function(e){
        e.preventDefault();
        const subject_name = document.getElementById('subject_name').value.trim();
        const course = document.getElementById('course').value;
        const instructor = document.getElementById('instructor').value.trim();
        const year_level = document.getElementById('year_level').value;
        const hours = document.getElementById('hours').value;

        if(!subject_name || !course || !instructor || !year_level || !hours){
            alert('Please fill all fields');
            return;
        }

        const formData = new FormData();
        formData.append('subject_name', subject_name);
        formData.append('course', course);
        formData.append('instructor', instructor);
        formData.append('year_level', year_level);
        formData.append('hours', hours);

        fetch('add_subject.php', { method:'POST', body:formData })
        .then(res=>res.json())
        .then(data=>{
            if(data.status==='success'){
                const tbody = document.querySelector('#newSubjectRow').parentElement;
                const newRow = document.createElement('tr');
                newRow.id = `subject-${data.id}`;
                newRow.innerHTML = `
                    <td class="sub-name">${subject_name}</td>
                    <td class="sub-course">${course}</td>
                    <td class="sub-instructor">${instructor}</td>
                    <td class="sub-year">${year_level}</td>
                    <td class="sub-hours">${hours}</td>
                    <td>
                        <button class="btn btn-primary btn-sm edit-subject" data-id="${data.id}" data-mode="edit"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-danger btn-sm delete-subject" data-id="${data.id}"><i class="bi bi-trash"></i></button>
                    </td>
                `;
                tbody.appendChild(newRow);
                ['subject_name','course','instructor','year_level','hours'].forEach(id => document.getElementById(id).value='');
            } else {
                alert(data.message);
            }
        });
    });

    document.addEventListener('click', function(e){
        const delBtn = e.target.closest('.delete-subject');
        if(delBtn){
            const id = delBtn.dataset.id;
            Swal.fire({
                title:'Are you sure?',
                text:"This subject will be deleted!",
                icon:'warning',
                showCancelButton:true,
                confirmButtonText:'Yes, delete it!',
                cancelButtonText:'Cancel'
            }).then(result=>{
                if(result.isConfirmed){
                    const formData = new FormData();
                    formData.append('id', id);
                    fetch('delete_subject.php',{method:'POST',body:formData})
                    .then(res=>res.json())
                    .then(data=>{
                        if(data.status==='success') document.getElementById('subject-'+id).remove();
                        else alert(data.message);
                    });
                }
            });
        }

        const editBtn = e.target.closest('.edit-subject');
        if(editBtn){
            const row = editBtn.closest('tr');
            const id = editBtn.dataset.id;

            if(editBtn.dataset.mode==='edit'){
                row.querySelectorAll('td.sub-name, td.sub-instructor, td.sub-hours').forEach(td=>{
                    td.setAttribute('contenteditable','true');
                    td.classList.add('border','border-primary','rounded');
                });

                let courseTd = row.querySelector('td.sub-course');
                let currentCourse = courseTd.textContent.trim();
                let courseSelect = document.createElement('select');
                courseSelect.className = 'form-select';
                coursesList.forEach(c=>{
                    let option = document.createElement('option');
                    option.value = c;
                    option.textContent = c;
                    if(c === currentCourse) option.selected = true;
                    courseSelect.appendChild(option);
                });
                courseTd.innerHTML = '';
                courseTd.appendChild(courseSelect);

                let yearTd = row.querySelector('td.sub-year');
                let currentYear = yearTd.textContent.trim();
                let yearSelect = document.createElement('select');
                yearSelect.className = 'form-select';
                yearsList.forEach(y=>{
                    let option = document.createElement('option');
                    option.value = y;
                    option.textContent = y;
                    if(y === currentYear) option.selected = true;
                    yearSelect.appendChild(option);
                });
                yearTd.innerHTML = '';
                yearTd.appendChild(yearSelect);

                editBtn.innerHTML='<i class="bi bi-check-lg"></i>';
                editBtn.classList.remove('btn-primary');
                editBtn.classList.add('btn-success');
                editBtn.dataset.mode='save';

            } else {
                const updatedData={
                    subject_name: row.querySelector('.sub-name').textContent.trim(),
                    course: row.querySelector('td.sub-course select').value,
                    instructor: row.querySelector('.sub-instructor').textContent.trim(),
                    year_level: row.querySelector('td.sub-year select').value,
                    hours: row.querySelector('.sub-hours').textContent.trim()
                };
                if(!updatedData.subject_name || !updatedData.course || !updatedData.instructor || !updatedData.year_level || !updatedData.hours){
                    alert('All fields required'); return;
                }

                const formData = new FormData();
                formData.append('id',id);
                for(let key in updatedData) formData.append(key, updatedData[key]);

                fetch('update_subject.php',{method:'POST',body:formData})
                .then(res=>res.json())
                .then(data=>{
                    if(data.status==='success'){
                        row.querySelector('td.sub-name').textContent = updatedData.subject_name;
                        row.querySelector('td.sub-course').textContent = updatedData.course;
                        row.querySelector('td.sub-instructor').textContent = updatedData.instructor;
                        row.querySelector('td.sub-year').textContent = updatedData.year_level;
                        row.querySelector('td.sub-hours').textContent = updatedData.hours;

                        row.querySelectorAll('td').forEach(td=> td.removeAttribute('contenteditable'));
                        row.querySelectorAll('td').forEach(td=> td.classList.remove('border','border-primary','rounded'));
                        editBtn.innerHTML='<i class="bi bi-pencil"></i>';
                        editBtn.classList.remove('btn-success');
                        editBtn.classList.add('btn-primary');
                        editBtn.dataset.mode='edit';
                    } else { alert(data.message); }
                });
            }
        }
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const toastEl = document.getElementById('actionToast');
    const toastMsg = document.getElementById('toastMessage');

    function showToast(message, type='success', modal=null, reload=false){
        toastMsg.textContent = message;
        toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
        const bsToast = new bootstrap.Toast(toastEl, { delay: 2000 });
        bsToast.show();

        if(modal){
            setTimeout(() => {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if(bsModal) bsModal.hide();
            }, 800);
        }

        if(reload){
            setTimeout(()=> location.reload(), 2000);
        }
    }

    document.querySelectorAll('.status-form').forEach(form=>{
        form.addEventListener('submit', function(e){
            e.preventDefault();
            const modal = this.closest('.modal');
            const formData = new FormData(this);
            fetch(this.action, { method:'POST', body:formData })
                .then(res=>res.json())
                .then(data=>{
                    showToast(data.message, data.type, modal, data.reload);
                })
                .catch(err=>showToast('Something went wrong!','danger', modal));
        });
    });

    document.querySelectorAll('.update-student-form').forEach(form=>{
        form.addEventListener('submit', function(e){
            e.preventDefault();
            const modal = this.closest('.modal');
            const formData = new FormData(this);
            fetch(this.action, { method:'POST', body:formData })
                .then(res=>res.json())
                .then(data=>{
                    showToast(data.message, data.type, modal, data.reload);
                })
                .catch(err=>showToast('Something went wrong!','danger', modal));
        });
    });

    document.querySelectorAll('.delete-student-form').forEach(form=>{
        form.addEventListener('submit', function(e){
            e.preventDefault();
            if(!confirm('Delete this student?')) return;
            const modal = this.closest('.modal');
            const formData = new FormData(this);
            fetch(this.action, { method:'POST', body:formData })
                .then(res=>res.json())
                .then(data=>{
                    showToast(data.message, data.type, modal, data.reload);
                })
                .catch(err=>showToast('Something went wrong!','danger', modal));
        });
    });
});
</script>


</body>
</html>
