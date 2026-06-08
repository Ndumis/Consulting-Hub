<?php

class FileUpload {
    private static $allowed_extensions = ['pdf', 'doc', 'docx'];
    private static $max_file_size = 5 * 1024 * 1024; // 5MB
    private static $upload_dir = __DIR__ . '/../uploads/cv_files/';
    
    public static function validateFile($file) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "No file was uploaded or an upload error occurred.";
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > self::$max_file_size) {
            $errors[] = "File size exceeds maximum allowed size of 5MB.";
        }
        
        // Check file extension
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, self::$allowed_extensions)) {
            $errors[] = "Invalid file type. Only PDF, DOC, and DOCX files are allowed.";
        }
        
        // Check MIME type for additional security
        $allowed_mimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_mimes)) {
            $errors[] = "Invalid file format detected.";
        }
        
        return $errors;
    }
    
    public static function uploadFile($file, $candidate_id) {
        // Validate file first
        $validation_errors = self::validateFile($file);
        if (!empty($validation_errors)) {
            return ['success' => false, 'errors' => $validation_errors];
        }
        
        // Create unique filename
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = 'cv_' . $candidate_id . '_' . time() . '.' . $file_extension;
        $target_path = self::$upload_dir . $new_filename;
        
        // Ensure upload directory exists
        if (!is_dir(self::$upload_dir)) {
            if (!mkdir(self::$upload_dir, 0755, true)) {
                return ['success' => false, 'errors' => ['Failed to create upload directory.']];
            }
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // Set proper permissions
            chmod($target_path, 0644);
            
            return [
                'success' => true, 
                'filename' => $new_filename,
                'path' => $target_path
            ];
        } else {
            return ['success' => false, 'errors' => ['Failed to upload file.']];
        }
    }
    
    public static function deleteFile($filename) {
        if (empty($filename)) return false;
        
        $file_path = self::$upload_dir . $filename;
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        return true; // File doesn't exist, consider as deleted
    }
    
    public static function getFilePath($filename) {
        return self::$upload_dir . $filename;
    }
    
    public static function getFileUrl($filename) {
        return '../uploads/cv_files/' . $filename;
    }
}
?>