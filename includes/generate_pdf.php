<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/security.php';
require_once 'functions.php';

$database = new Database();
$db = $database->getConnection();

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if (empty($type) || empty($id)) {
    http_response_code(400);
    die('Invalid parameters');
}

if ($type === 'quotation') {
    // Get quotation details
    $query = "SELECT q.*, c.name as client_name, c.company, c.address, c.email, c.phone
              FROM quotations q 
              LEFT JOIN clients c ON q.client_id = c.id 
              WHERE q.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $quotation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quotation) {
        http_response_code(404);
        die('Quotation not found');
    }
    
    // Get quotation items
    $query = "SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY id";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate PDF
    $pdf_content = PDFGenerator::generateQuotationPDF($quotation, $items);
    
    // Set headers for PDF display
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="quotation_' . $quotation['quotation_number'] . '.html"');
    
    echo $pdf_content;
    
} elseif ($type === 'invoice') {
    // Get invoice details
    $query = "SELECT i.*, c.name as client_name, c.company, c.address, c.email, c.phone
              FROM invoices i 
              LEFT JOIN clients c ON i.client_id = c.id 
              WHERE i.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invoice) {
        http_response_code(404);
        die('Invoice not found');
    }
    
    // Get invoice items
    $query = "SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate PDF
    $pdf_content = PDFGenerator::generateInvoicePDF($invoice, $items);
    
    // Set headers for PDF display
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="invoice_' . $invoice['invoice_number'] . '.html"');
    
    echo $pdf_content;
    
} else {
    http_response_code(400);
    die('Invalid document type');
}
?>