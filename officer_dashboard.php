<?php
require_once 'includes/auth_check.php';
checkAccess('Officer');
require_once 'config/db.php';

$stmt = $pdo->prepare("
    SELECT i.Incident_Ref, i.Incident_Date, i.Location, r.Resident_Name, t.Type_Name, s.Status_Name
    FROM INCIDENT i
    JOIN RESIDENT r ON i.Resident_ID = r.Resident_ID
    JOIN INCIDENT_TYPE t ON i.Type_ID = t.Type_ID
    JOIN INCIDENT_STATUS s ON i.Status_ID = s.Status_ID
    WHERE i.Officer_ID = ?
");
$stmt->execute([$_SESSION['officer_id']]);
$assigned_incidents = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head><title>Officer Dashboard</title></head>
<body>
    <h1>Officer Case Dashboard</h1>
    <p>Welcome, <strong><?= $_SESSION['officer_name'] ?></strong> (Officer ID: <?= $_SESSION['officer_id'] ?>)</p>
    <hr>
    <h3>Incidents Assigned to You</h3>
    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>Ref #</th>
            <th>Date</th>
            <th>Location</th>
            <th>Complainant</th>
            <th>Type</th>
            <th>Status</th>
        </tr>
        <?php foreach ($assigned_incidents as $inc): ?>
        <tr>
            <td><?= $inc['Incident_Ref'] ?></td>
            <td><?= $inc['Incident_Date'] ?></td>
            <td><?= $inc['Location'] ?></td>
            <td><?= $inc['Resident_Name'] ?></td>
            <td><?= $inc['Type_Name'] ?></td>
            <td><?= $inc['Status_Name'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($assigned_incidents)): ?>
        <tr><td colspan="6">No incidents currently assigned.</td></tr>
        <?php endif; ?>
    </table>
    <br>
    <a href="logout.php">Logout</a>
</body>
</html>