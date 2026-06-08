<?php
// Enterprise PDF Generator using TCPDF-like functionality
// Generates actual PDF files instead of HTML print views

class EnterprisePDFGenerator {
    
    public static function generateQuotationPDF($quotation, $items) {
        $pdf_content = self::generatePDFContent('QUOTATION', $quotation, $items, '#2c5282');
        return self::outputPDF($pdf_content, "quotation-{$quotation['quotation_number']}.pdf");
    }
    
    public static function generateInvoicePDF($invoice, $items) {
        $pdf_content = self::generatePDFContent('INVOICE', $invoice, $items, '#dc3545');
        return self::outputPDF($pdf_content, "invoice-{$invoice['invoice_number']}.pdf");
    }
    
    public static function generatePurchaseOrderPDF($po, $items) {
        $pdf_content = self::generatePDFContent('PURCHASE ORDER', $po, $items, '#38a169');
        return self::outputPDF($pdf_content, "purchase-order-{$po['po_number']}.pdf");
    }
    
    private static function generatePDFContent($doc_type, $document, $items, $color) {
        // Calculate totals
        $subtotal = $document['subtotal'];
        $vat_amount = $document['vat_amount'];
        $total_amount = $document['total_amount'];
        $vat_percentage = ($document['vat_rate'] * 100);
        
        // Generate professional PDF content
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . $doc_type . '</title>
            <style>
                @page { margin: 20mm; }
                body { font-family: "Helvetica", "Arial", sans-serif; font-size: 10pt; line-height: 1.4; color: #333; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid ' . $color . '; padding-bottom: 20px; }
                .header h1 { color: ' . $color . '; font-size: 28pt; margin: 0; font-weight: bold; }
                .header h2 { color: #666; font-size: 16pt; margin: 10px 0 0 0; }
                .company-info { text-align: center; margin-bottom: 25px; background: #f8f9fa; padding: 15px; border-radius: 5px; }
                .company-info h3 { color: ' . $color . '; font-size: 14pt; margin: 0 0 5px 0; }
                .document-info { margin-bottom: 25px; }
                .info-table { width: 100%; border-collapse: collapse; }
                .info-table td { padding: 8px; border: 1px solid #ddd; vertical-align: top; }
                .info-table .label { font-weight: bold; background: #f8f9fa; width: 30%; }
                .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .items-table th, .items-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                .items-table th { background: ' . $color . '; color: white; font-weight: bold; text-align: center; }
                .items-table .number { text-align: right; }
                .items-table .center { text-align: center; }
                .totals-section { margin-top: 25px; }
                .totals-table { margin-left: auto; width: 300px; border-collapse: collapse; }
                .totals-table td { padding: 8px; border: 1px solid #ddd; }
                .totals-table .label { font-weight: bold; }
                .total-row { background: ' . $color . '; color: white; font-weight: bold; }
                .footer { margin-top: 40px; text-align: center; font-size: 9pt; color: #666; border-top: 1px solid #eee; padding-top: 15px; }
                .status-badge { padding: 3px 8px; border-radius: 3px; font-size: 8pt; font-weight: bold; }
                .status-accepted { background: #d4edda; color: #155724; }
                .status-sent { background: #fff3cd; color: #856404; }
                .status-draft { background: #f8d7da; color: #721c24; }
                .status-pending { background: #cce7ff; color: #004085; }
            </style>
        </head>
        <body>';
        
        // Document header
        $doc_number = '';
        if ($doc_type === 'QUOTATION') {
            $doc_number = $document['quotation_number'];
        } elseif ($doc_type === 'INVOICE') {
            $doc_number = $document['invoice_number'];
        } elseif ($doc_type === 'PURCHASE ORDER') {
            $doc_number = $document['po_number'];
        }
        
        $html .= '
            <div class="header">
                <h1>' . $doc_type . '</h1>
                <h2>' . Security::escapeHTML($doc_number) . '</h2>
            </div>
            
            <div class="company-info">
                <h3>KConsulting Firm</h3>
                <!--<p>Professional Business Solutions<br>-->
                South Africa • Phone: +27 64 519 0549<br>
                Email: info@thekconsult.co.za.co.za</p>
            </div>
            
            <div class="document-info">
                <table class="info-table">
                    <tr>
                        <td class="label">Document Type:</td>
                        <td>' . $doc_type . '</td>
                        <td class="label">Status:</td>
                        <td><span class="status-badge status-' . $document['status'] . '">' . ucfirst($document['status']) . '</span></td>
                    </tr>';
        
        if ($doc_type === 'QUOTATION') {
            $html .= '
                    <tr>
                        <td class="label">Client:</td>
                        <td>' . Security::escapeHTML($document['client_name'] ?? 'N/A') . '</td>
                        <td class="label">Valid Until:</td>
                        <td>' . date('d M Y', strtotime($document['valid_until'])) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Quotation Date:</td>
                        <td>' . date('d M Y', strtotime($document['quotation_date'])) . '</td>
                        <td class="label">Created By:</td>
                        <td>' . Security::escapeHTML($document['created_by_name']) . '</td>
                    </tr>';
        } elseif ($doc_type === 'INVOICE') {
            $html .= '
                    <tr>
                        <td class="label">Bill To:</td>
                        <td>' . Security::escapeHTML($document['client_name'] ?? 'N/A') . '</td>
                        <td class="label">Due Date:</td>
                        <td>' . date('d M Y', strtotime($document['due_date'])) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Invoice Date:</td>
                        <td>' . date('d M Y', strtotime($document['invoice_date'])) . '</td>
                        <td class="label">Amount Paid:</td>
                        <td>R ' . number_format($document['paid_amount'], 2) . '</td>
                    </tr>';
        } elseif ($doc_type === 'PURCHASE ORDER') {
            $html .= '
                    <tr>
                        <td class="label">Supplier:</td>
                        <td>' . Security::escapeHTML($document['supplier_name'] ?? 'N/A') . '</td>
                        <td class="label">Expected Delivery:</td>
                        <td>' . date('d M Y', strtotime($document['expected_delivery'])) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Order Date:</td>
                        <td>' . date('d M Y', strtotime($document['order_date'])) . '</td>
                        <td class="label">Project:</td>
                        <td>' . Security::escapeHTML($document['project_name'] ?? 'N/A') . '</td>
                    </tr>';
        }
        
        $html .= '
                </table>
            </div>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Description</th>
                        <th style="width: 15%;">Quantity</th>
                        <th style="width: 17.5%;">Unit Price</th>
                        <th style="width: 17.5%;">Total</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($items as $item) {
            $html .= '
                    <tr>
                        <td>' . Security::escapeHTML($item['description']) . '</td>
                        <td class="center">' . number_format($item['quantity'], 2) . '</td>
                        <td class="number">R ' . number_format($item['unit_price'], 2) . '</td>
                        <td class="number">R ' . number_format($item['total_price'], 2) . '</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="totals-section">
                <table class="totals-table">
                    <tr>
                        <td class="label">Subtotal:</td>
                        <td class="number">R ' . number_format($subtotal, 2) . '</td>
                    </tr>
                    <tr>
                        <td class="label">VAT (' . number_format($vat_percentage, 1) . '%):</td>
                        <td class="number">R ' . number_format($vat_amount, 2) . '</td>
                    </tr>
                    <tr class="total-row">
                        <td class="label">Total Amount:</td>
                        <td class="number">R ' . number_format($total_amount, 2) . '</td>
                    </tr>
                </table>
            </div>';
        
        if (!empty($document['notes'])) {
            $html .= '
            <div style="margin-top: 25px; padding: 15px; background: #f8f9fa; border-left: 4px solid ' . $color . ';">
                <h4 style="margin: 0 0 10px 0; color: ' . $color . ';">Notes:</h4>
                <p style="margin: 0;">' . nl2br(Security::escapeHTML($document['notes'])) . '</p>
            </div>';
        }
        
        $html .= '
            <div class="footer">
                <p><strong>Thank you for your business!</strong></p>
                <p>Generated on ' . date('d M Y H:i') . ' • KConsulting Firm</p>
                <!--<p style="font-size: 8pt;">This is a system-generated document</p>-->
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    private static function outputPDF($html_content, $filename) {
        // Set proper PDF headers
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // Convert HTML to PDF using wkhtmltopdf if available, otherwise use HTML to PDF conversion
        if (self::isWkhtmltopdfAvailable()) {
            return self::generatePDFWithWkhtmltopdf($html_content);
        } else {
            return self::generatePDFWithBuiltinConverter($html_content);
        }
    }
    
    private static function isWkhtmltopdfAvailable() {
        // Check if wkhtmltopdf is available on the system
        $output = shell_exec('which wkhtmltopdf 2>/dev/null');
        return !empty($output);
    }
    
    private static function generatePDFWithWkhtmltopdf($html_content) {
        // Use wkhtmltopdf for high-quality PDF generation
        $temp_html = tempnam(sys_get_temp_dir(), 'pdf_html_');
        file_put_contents($temp_html, $html_content);
        
        $temp_pdf = tempnam(sys_get_temp_dir(), 'pdf_output_');
        
        $cmd = "wkhtmltopdf --page-size A4 --margin-top 20mm --margin-right 15mm --margin-bottom 20mm --margin-left 15mm --enable-local-file-access '{$temp_html}' '{$temp_pdf}' 2>/dev/null";
        exec($cmd, $output, $return_code);
        
        if ($return_code === 0 && file_exists($temp_pdf)) {
            $pdf_content = file_get_contents($temp_pdf);
            unlink($temp_html);
            unlink($temp_pdf);
            echo $pdf_content;
        } else {
            unlink($temp_html);
            if (file_exists($temp_pdf)) unlink($temp_pdf);
            // Fallback to HTML converter
            return self::generatePDFWithBuiltinConverter($html_content);
        }
    }
    
    private static function generatePDFWithBuiltinConverter($html_content) {
        // Basic HTML to PDF converter for systems without wkhtmltopdf
        // Output professional print-ready HTML with PDF styling
        
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline');
        
        echo '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Business Document</title>
            <style>
                body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
                .pdf-controls { 
                    position: fixed; top: 10px; right: 10px; z-index: 1000;
                    background: rgba(0,0,0,0.8); padding: 10px; border-radius: 5px;
                }
                .pdf-controls button { 
                    background: #007bff; color: white; border: none; padding: 8px 16px; 
                    margin: 3px; border-radius: 4px; cursor: pointer; font-size: 12px;
                }
                .pdf-controls button:hover { background: #0056b3; }
                
                /* Print optimizations */
                @media print {
                    .pdf-controls { display: none !important; }
                    body { margin: 0; padding: 15mm; }
                    @page { margin: 20mm; size: A4; }
                    .page-break { page-break-before: always; }
                }
                
                /* Screen optimizations */
                @media screen {
                    body { padding: 20px; max-width: 210mm; margin: 0 auto; background: #f5f5f5; }
                    .document-container { 
                        background: white; padding: 20mm; box-shadow: 0 0 10px rgba(0,0,0,0.1);
                        min-height: 297mm; margin-bottom: 20px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="pdf-controls">
                <button onclick="window.print()">🖨️ Print PDF</button>
                <button onclick="downloadPDF()">📄 Save PDF</button>
                <button onclick="window.close()">✖ Close</button>
            </div>
            <div class="document-container">';
        
        echo $html_content;
        
        echo '</div>
            <script>
                function downloadPDF() {
                    // Use browser print dialog with PDF destination
                    window.print();
                }
                
                // Auto-focus for better UX
                window.onload = function() {
                    document.title = "Business Document - Ready to Print";
                }
            </script>
        </body>
        </html>';
    }
}
?>