<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// First try using Composer's autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Fallback to direct TCPDF inclusion
    $tcpdf_path = __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php';
    if (!file_exists($tcpdf_path)) {
        die('TCPDF not found. Please run "composer install" in the project directory.');
    }
    require_once $tcpdf_path;
}

if (isset($_POST['booking_data'])) {
    $bookingData = json_decode($_POST['booking_data'], true);
    
    // Calculate number of hours from time slots if not provided
    if (!isset($bookingData['hours']) || empty($bookingData['hours'])) {
        $slots = explode(',', $bookingData['time_slots']);
        $bookingData['hours'] = count($slots);
    }
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('TBS');
    $pdf->SetAuthor('TBS Booking System');
    $pdf->SetTitle('Booking Receipt');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 12);
    
    // Add logo
    $pdf->Image('img/ArenaLOGO.png', 15, 15, 50);
    
    // Add title
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 20, 'Booking Receipt', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Add booking details
    $pdf->SetFont('helvetica', '', 12);
    
    // Create a table-like structure
    $pdf->SetFillColor(245, 245, 245);
    $pdf->SetFont('helvetica', 'B', 12);
    
    // Booking ID
    $pdf->Cell(60, 10, 'Booking ID:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, $bookingData['booking_id'], 0, 1, 'L');
    
    // Booking Date
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 10, 'Booking Date:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, $bookingData['date'], 0, 1, 'L');
    
    // Location
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 10, 'Location:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, $bookingData['location'], 0, 1, 'L');
    
    // Turf
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 10, 'Turf:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, $bookingData['turf'], 0, 1, 'L');
    
    // Time Slots
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 10, 'Time Slots:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, $bookingData['time_slots'], 0, 1, 'L');
    
    // Number of Hours
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 10, 'Number of Hours:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, $bookingData['hours'] . ' hour(s)', 0, 1, 'L');
    
    // Total Amount - Remove ₹ symbol and convert to float
    $amount = floatval(str_replace('₹', '', $bookingData['amount']));
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 10, 'Total Amount:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, '₹' . number_format($amount, 2), 0, 1, 'L');
    
    // Payment Status
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 10, 'Payment Status:', 0, 0, 'L');
    $pdf->SetTextColor(255, 0, 0); // Red color for PENDING
    $pdf->Cell(0, 10, 'PENDING', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0); // Reset to black
    
    $pdf->Ln(10);
    
    // Add terms and conditions
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Terms & Conditions:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 10, '1. Please arrive 15 minutes before your slot time.
2. Payment must be made before using the facility.
3. Cancellation should be done 24 hours prior to the booking time.
4. Please carry a valid ID proof.
5. Follow all safety guidelines and facility rules.', 0, 'L');
    
    // Output PDF
    $pdf->Output('TBS_Booking_Receipt.pdf', 'I');
    exit();
}
?> 