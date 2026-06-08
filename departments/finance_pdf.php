<?php
require_once '../config/session.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';
require_once '../includes/pdf_generator.php';

// Check department access
Security::requireDepartmentAccess('Finance');

$database = new Database();
$db = $database->getConnection();

$type = Security::sanitizeInput($_GET['type'] ?? '');
$id = (int)($_GET['id'] ?? 0);

if (!$type || !$id) {
    die('Invalid request');
}

// Process PDF generation requests using Enterprise PDF Generator
switch ($type) {
    case 'quotation':
        // Get quotation data
        $query = "SELECT q.*, c.name as client_name, c.email as client_email, u.username as created_by_name
                  FROM quotations q
                  LEFT JOIN clients c ON q.client_id = c.id
                  LEFT JOIN users u ON q.created_by = u.id
                  WHERE q.id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $quotation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$quotation) {
            die('Quotation not found');
        }
        
        // Get quotation items
        $query = "SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY id";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generate PDF
        EnterprisePDFGenerator::generateQuotationPDF($quotation, $items);
        break;
        
    case 'invoice':
        // Get invoice data
        $query = "SELECT i.*, c.name as client_name, c.email as client_email, u.username as created_by_name
                  FROM invoices i
                  LEFT JOIN clients c ON i.client_id = c.id
                  LEFT JOIN users u ON i.created_by = u.id
                  WHERE i.id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            die('Invoice not found');
        }
        
        // Get invoice items
        $query = "SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generate PDF
        EnterprisePDFGenerator::generateInvoicePDF($invoice, $items);
        break;
        
    case 'purchase_order':
        // Get purchase order data
        $query = "SELECT po.*, p.name as project_name, u.username as created_by_name
                  FROM purchase_orders po 
                  LEFT JOIN projects p ON po.project_id = p.id 
                  LEFT JOIN users u ON po.created_by = u.id
                  WHERE po.id = ? AND po.status IN ('approved', 'completed')";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $po = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$po) {
            die('Purchase Order not found or not yet approved');
        }
        
        // Get purchase order items
        $query = "SELECT * FROM purchase_order_items WHERE purchase_order_id = ? ORDER BY id";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generate PDF
        EnterprisePDFGenerator::generatePurchaseOrderPDF($po, $items);
        break;
        
    default:
        die('Invalid document type');
}
?>