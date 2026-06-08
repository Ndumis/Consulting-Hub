<?php
// Note: Security class is now defined in config/security.php to avoid conflicts

// Utility Functions
if (!class_exists('Utils')) {
class Utils {
    public static function formatCurrency($amount, $currency = 'ZAR') {
        $symbol = $currency === 'ZAR' ? 'R' : $currency;
        return $symbol . ' ' . number_format((float)$amount, 2);
    }
    
    public static function formatDate($date, $format = 'Y-m-d') {
        return date($format, strtotime($date));
    }
    
    public static function calculateVAT($amount, $vatRate = 0.15) {
        return $amount * $vatRate;
    }
    
    public static function calculateTotal($subtotal, $vatRate = 0.15) {
        $vat = self::calculateVAT($subtotal, $vatRate);
        return $subtotal + $vat;
    }
    
    public static function generateInvoiceNumber() {
        return 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    public static function generateQuotationNumber() {
        return 'QUO-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    public static function generatePONumber() {
        return 'PO-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    public static function generateExpenseNumber() {
        return 'EXP-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    public static function redirect($url) {
        header("Location: $url");
        exit();
    }
    
    public static function showAlert($message, $type = 'info') {
        $alertClass = 'alert-' . $type;
        return "<div class='alert $alertClass'>$message</div>";
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function validateRequired($fields) {
        $errors = [];
        foreach ($fields as $field => $value) {
            if (empty(trim($value))) {
                $errors[] = ucfirst($field) . ' is required';
            }
        }
        return $errors;
    }
    
    /**
     * Log system activity for admin monitoring
     */
    public static function logActivity($department, $activity_type, $description, $target_type = null, $target_id = null) {
        try {
            // Skip logging if no user session (like public forms)
            if (!isset($_SESSION['user_id'])) {
                return;
            }
            
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $db = $database->getConnection();
            
            $user_id = $_SESSION['user_id'];
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $query = "INSERT INTO system_activities 
                      (user_id, department, activity_type, description, target_type, target_id, ip_address, user_agent) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                $user_id, 
                $department, 
                $activity_type, 
                $description, 
                $target_type, 
                $target_id, 
                $ip_address, 
                $user_agent
            ]);
            
        } catch (Exception $e) {
            // Silent fail for logging errors to avoid breaking main functionality
            error_log("Activity logging failed: " . $e->getMessage());
        }
    }
}
}

// PDF Generation Functions  
if (!class_exists('PDFGenerator')) {
class PDFGenerator {
    public static function generateInvoicePDF($invoice_data, $items) {
        $html = self::generateInvoiceHTML($invoice_data, $items);
        // For now, we'll create a simple HTML version
        // In production, you'd use a library like TCPDF or FPDF
        return $html;
    }
    
    public static function generateQuotationPDF($quotation_data, $items) {
        $html = self::generateQuotationHTML($quotation_data, $items);
        return $html;
    }
    
    private static function generateInvoiceHTML($invoice, $items) {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }
        $vat = Utils::calculateVAT($subtotal);
        $total = $subtotal + $vat;
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Invoice ' . $invoice['invoice_number'] . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .invoice-details { margin-bottom: 20px; }
                .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .table th { background-color: #f2f2f2; }
                .totals { text-align: right; }
                .total-row { font-weight: bold; background-color: #f9f9f9; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>INVOICE</h1>
                <h2>' . Security::escapeHTML($invoice['invoice_number']) . '</h2>
            </div>
            
            <div class="invoice-details">
                <p><strong>Invoice Date:</strong> ' . Utils::formatDate($invoice['invoice_date']) . '</p>
                <p><strong>Due Date:</strong> ' . Utils::formatDate($invoice['due_date']) . '</p>
                <p><strong>Client:</strong> ' . Security::escapeHTML($invoice['client_name']) . '</p>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($items as $item) {
            $item_total = $item['quantity'] * $item['unit_price'];
            $html .= '
                <tr>
                    <td>' . Security::escapeHTML($item['description']) . '</td>
                    <td>' . $item['quantity'] . '</td>
                    <td>' . Utils::formatCurrency($item['unit_price']) . '</td>
                    <td>' . Utils::formatCurrency($item_total) . '</td>
                </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="totals">
                <table class="table" style="width: 300px; margin-left: auto;">
                    <tr>
                        <td><strong>Subtotal:</strong></td>
                        <td>' . Utils::formatCurrency($subtotal) . '</td>
                    </tr>
                    <tr>
                        <td><strong>VAT (15%):</strong></td>
                        <td>' . Utils::formatCurrency($vat) . '</td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>Total:</strong></td>
                        <td><strong>' . Utils::formatCurrency($total) . '</strong></td>
                    </tr>
                </table>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    private static function generateQuotationHTML($quotation, $items) {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }
        $vat = Utils::calculateVAT($subtotal);
        $total = $subtotal + $vat;
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Quotation ' . $quotation['quotation_number'] . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .quotation-details { margin-bottom: 20px; }
                .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .table th { background-color: #f2f2f2; }
                .totals { text-align: right; }
                .total-row { font-weight: bold; background-color: #f9f9f9; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>QUOTATION</h1>
                <h2>' . Security::escapeHTML($quotation['quotation_number']) . '</h2>
            </div>
            
            <div class="quotation-details">
                <p><strong>Quotation Date:</strong> ' . Utils::formatDate($quotation['quotation_date']) . '</p>
                <p><strong>Valid Until:</strong> ' . Utils::formatDate($quotation['valid_until']) . '</p>
                <p><strong>Client:</strong> ' . Security::escapeHTML($quotation['client_name']) . '</p>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($items as $item) {
            $item_total = $item['quantity'] * $item['unit_price'];
            $html .= '
                <tr>
                    <td>' . Security::escapeHTML($item['description']) . '</td>
                    <td>' . $item['quantity'] . '</td>
                    <td>' . Utils::formatCurrency($item['unit_price']) . '</td>
                    <td>' . Utils::formatCurrency($item_total) . '</td>
                </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="totals">
                <table class="table" style="width: 300px; margin-left: auto;">
                    <tr>
                        <td><strong>Subtotal:</strong></td>
                        <td>' . Utils::formatCurrency($subtotal) . '</td>
                    </tr>
                    <tr>
                        <td><strong>VAT (15%):</strong></td>
                        <td>' . Utils::formatCurrency($vat) . '</td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>Total:</strong></td>
                        <td><strong>' . Utils::formatCurrency($total) . '</strong></td>
                    </tr>
                </table>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}
}
?>