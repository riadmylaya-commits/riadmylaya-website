<?php
require_once __DIR__ . '/vendor/fpdf/fpdf.php';

function generateRegistrationPDF(array $reg): string {
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 15);

    // Header
    $pdf->SetFont('Helvetica', 'B', 18);
    $pdf->Cell(0, 12, 'Riad Mylaya', 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 6, '163, Derb Boumba, Medina, Marrakech', 0, 1, 'C');
    $pdf->Ln(4);
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Fiche de Police / Guest Registration', 0, 1, 'C');
    $pdf->Ln(6);
    $pdf->SetDrawColor(200, 180, 150);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(4);

    // Fields
    $fields = [
        ['Chambre / Room', $reg['room'] ?? ''],
        ['Nom / Surname', $reg['lastName'] ?? ''],
        ['Prenom / First name', $reg['firstName'] ?? ''],
        ['Date de naissance / Date of birth', $reg['dateOfBirth'] ?? ''],
        ['Lieu de naissance / Place of birth', $reg['placeOfBirth'] ?? ''],
        ['Nationalite / Nationality', $reg['nationality'] ?? ''],
        ['Profession / Occupation', $reg['occupation'] ?? ''],
        ['N. C.I.N / ID card', $reg['cinNumber'] ?? ''],
        ['N. d\'entree au Maroc / Morocco entry', $reg['moroccoEntryNumber'] ?? ''],
        ['Date d\'arrivee / Arrival', $reg['arrivalDate'] ?? ''],
        ['Date de depart / Departure', $reg['departureDate'] ?? ''],
        ['Mineurs accompagnants / Children', (string)($reg['accompanyingChildren'] ?? 0)],
        ['Lieu de provenance / Coming from', $reg['comingFrom'] ?? ''],
        ['Destination / Going to', $reg['goingTo'] ?? ''],
        ['N. Passeport / Passport number', $reg['passportNumber'] ?? ''],
        ['Date de delivrance / Issue date', $reg['passportIssueDate'] ?? ''],
        ['Lieu de delivrance / Issue place', $reg['passportIssuePlace'] ?? ''],
        ['Adresse actuelle / Address', $reg['permanentAddress'] ?? ''],
        ['Date / Marrakech, le', $reg['registrationDate'] ?? ''],
    ];

    foreach ($fields as [$label, $value]) {
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell(80, 7, utf8_decode($label), 0);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 7, utf8_decode((string)$value), 0, 1);
    }

    // Passport photo
    $photo = $reg['passportPhoto'] ?? '';
    if ($photo && strpos($photo, 'base64') !== false) {
        try {
            $imgData = decodeBase64Image($photo);
            $tmpFile = tempnam(sys_get_temp_dir(), 'pp_');
            file_put_contents($tmpFile, $imgData);
            $pdf->Ln(6);
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->Cell(0, 8, 'Photo du passeport / Passport photo:', 0, 1);
            $pdf->Image($tmpFile, 10, null, 50);
            unlink($tmpFile);
        } catch (Exception $e) {
            // skip
        }
    }

    // Signature
    $sig = $reg['signature'] ?? '';
    if ($sig && strpos($sig, 'base64') !== false) {
        try {
            $sigData = decodeBase64Image($sig);
            $tmpFile = tempnam(sys_get_temp_dir(), 'sig_');
            file_put_contents($tmpFile, $sigData);
            $pdf->Ln(6);
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->Cell(0, 8, 'Signature:', 0, 1);
            $pdf->Image($tmpFile, 10, null, 60);
            unlink($tmpFile);
        } catch (Exception $e) {
            // skip
        }
    }

    return $pdf->Output('S');
}

function decodeBase64Image(string $dataUrl): string {
    if (strpos($dataUrl, ',') !== false) {
        $dataUrl = explode(',', $dataUrl, 2)[1];
    }
    return base64_decode($dataUrl);
}
