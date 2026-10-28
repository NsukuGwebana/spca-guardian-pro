<?php
ob_start(); // Start output buffering to prevent accidental output

include("databaseConnection.php");
require_once('tcpdf/tcpdf.php');

$animal = null;
$med = null;

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM animals WHERE id = $id AND is_deleted = 0";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $animal = $result->fetch_assoc();
    } else {
        ob_end_clean(); // clear buffer before outputting error
        exit("Animal not found or removed.");
    }

    $med_sql = "SELECT * FROM medical_care WHERE id = $id ORDER BY Appointment_Date DESC LIMIT 1";
    $med_result = $conn->query($med_sql);
    if ($med_result && $med_result->num_rows > 0) {
        $med = $med_result->fetch_assoc();
    }
}

$conn->close();

// Company info
$companyName = "SPCA";
$systemName = "Animal Guardian Pro";
$companySlogan = "Caring for Animals Since 2023";

// Create PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator($companyName);
$pdf->SetAuthor($companyName);
$pdf->SetTitle('Animal Profile - ' . $animal['Aname']);
$pdf->setHeaderData('', 0, $companyName, $systemName);
$pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
$pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
$pdf->SetMargins(15, 25, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->AddPage();

// Build HTML content
$html = '
<style>
body { font-family: Arial; color:#134f5c; }
.section-title { font-weight:bold; font-size:16px; margin-top:15px; margin-bottom:10px; }
.info-table { width:100%; border-collapse: collapse; margin-bottom:10px; }
.info-table td { padding:6px; border:1px solid #dee2e6; }
.label { font-weight:bold; background:#e9ecef; width:30%; }
.status-available { background:#d4edda; color:#155724; font-weight:bold; padding:3px 8px; border-radius:10px; }
.status-not-available { background:#f8d7da; color:#721c24; font-weight:bold; padding:3px 8px; border-radius:10px; }
.health-status { background:#e8f4fd; color:#0c5460; font-weight:bold; padding:3px 8px; border-radius:10px; }
.animal-image { text-align:center; margin-bottom:10px; }
.animal-image img { max-width:200px; max-height:200px; border:2px solid #134f5c; border-radius:10px; }
</style>

<h2 style="text-align:center;">' . $companyName . '</h2>
<p style="text-align:center; font-style:italic;">' . $companySlogan . '</p>
<h3 style="text-align:center;">Animal Profile - ' . htmlspecialchars($animal['Aname']) . '</h3>

<div class="animal-image">';
if (!empty($animal['animal_picture'])) {
    $imagePath = $_SERVER['DOCUMENT_ROOT'] . parse_url($animal['animal_picture'], PHP_URL_PATH);
    if (file_exists($imagePath)) {
        $html .= '<img src="' . $imagePath . '">';
    } else {
        $html .= 'No Image Available';
    }
} else {
    $html .= 'No Image Available';
}
$html .= '</div>

<table class="info-table">
<tr><td class="label">Animal ID</td><td>' . htmlspecialchars($animal['id']) . '</td></tr>
<tr><td class="label">Name</td><td>' . htmlspecialchars($animal['Aname']) . '</td></tr>
<tr><td class="label">Species</td><td>' . htmlspecialchars($animal['Species']) . '</td></tr>
<tr><td class="label">Breed</td><td>' . htmlspecialchars($animal['Animal_breed']) . '</td></tr>
<tr><td class="label">Age</td><td>' . htmlspecialchars($animal['Aage']) . ' years</td></tr>
<tr><td class="label">Color</td><td>' . htmlspecialchars($animal['Color']) . '</td></tr>
<tr><td class="label">Weight</td><td>' . htmlspecialchars($animal['Aweight']) . ' kg</td></tr>
<tr><td class="label">Size</td><td>' . htmlspecialchars($animal['Width']) . 'm × ' . htmlspecialchars($animal['Height']) . 'm</td></tr>
<tr><td class="label">Health Status</td><td><span class="health-status">' . htmlspecialchars($animal['Health_status']) . '</span></td></tr>
<tr><td class="label">Adoption Status</td><td><span class="' . ($animal['status']=='Available'?'status-available':'status-not-available') . '">' . htmlspecialchars($animal['status']) . '</span></td></tr>
<tr><td class="label">Intake Date</td><td>' . htmlspecialchars($animal['Intake_date']) . '</td></tr>
<tr><td class="label">Registered On</td><td>' . htmlspecialchars($animal['created_at']) . '</td></tr>
<tr><td class="label">Kennel Status</td><td>' . htmlspecialchars($animal['Kennel_status']) . '</td></tr>
</table>';

$html .= '<h4 class="section-title">Latest Medical Record</h4>';
if ($med) {
    $html .= '<table class="info-table">
    <tr><td class="label">Vet ID</td><td>' . htmlspecialchars($med['Vet_id']) . '</td></tr>
    <tr><td class="label">Appointment Date</td><td>' . htmlspecialchars($med['Appointment_Date']) . '</td></tr>
    <tr><td class="label">Treatment Taken</td><td>' . htmlspecialchars($med['Treatment_type']) . '</td></tr>
    </table>';
} else {
    $html .= '<p>No medical record found.</p>';
}

$html .= '<p><em>This profile was generated by ' . $companyName . ' system on ' . date('Y-m-d H:i:s') . '.</em></p>';

$pdf->writeHTML($html, true, false, true, false, '');

// ✅ Clean any accidental output before sending headers
ob_end_clean();

$pdf->Output('animal_profile_' . $animal['id'] . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $animal['Aname']) . '.pdf', 'D');
exit;
?>

