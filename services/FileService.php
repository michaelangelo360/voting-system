<?php
/**
 * FileService.php
 * Service for handling file uploads
 */
namespace Services;

class FileService {
    /**
     * Upload an event image
     * 
     * @param array $file File from $_FILES
     * @param int $eventId
     * @return array|false
     */
    public function uploadEventImage($file, $eventId) {
        return $this->uploadFile($file, EVENT_IMAGES_DIR, $eventId);
    }
    
    /**
     * Upload a category image
     * 
     * @param array $file File from $_FILES
     * @param int $categoryId
     * @return array|false
     */
    public function uploadCategoryImage($file, $categoryId) {
        return $this->uploadFile($file, CATEGORY_IMAGES_DIR, $categoryId);
    }
    
    /**
     * Upload a nominee image
     * 
     * @param array $file File from $_FILES
     * @param int $nomineeId
     * @return array|false
     */
    public function uploadNomineeImage($file, $nomineeId) {
        return $this->uploadFile($file, NOMINEE_IMAGES_DIR, $nomineeId);
    }
    
    /**
     * Upload a file
     * 
     * @param array $file File from $_FILES
     * @param string $directory Upload directory
     * @param string $filename Custom filename (without extension)
     * @return array|false
     */
    private function uploadFile($file, $directory, $filename = null) {
        // Check if directory exists, create if not
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Validate file size
        if ($file['size'] > MAX_FILE_SIZE) {
            return false;
        }
        
        // Validate file type
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $file['tmp_name']);
        finfo_close($fileInfo);
        
        $allowedMimeTypes = [
            'image/jpeg', 'image/png', 'image/gif'
        ];
        
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return false;
        }
        
        // Generate filename if not provided
        if ($filename === null) {
            $filename = uniqid();
        }
        
        // Get file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Create full filename
        $fullFilename = "{$filename}.{$extension}";
        $filepath = $directory . $fullFilename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return false;
        }
        
        // Return file information
        return [
            'url' => $this->getRelativePath($filepath),
            'filename' => $fullFilename,
            'mime_type' => $mimeType,
            'size' => $file['size']
        ];
    }
    
    /**
     * Get URL for a file
     * 
     * @param string $filepath Relative file path
     * @return string
     */
    public function getFileUrl($filepath) {
        return APP_URL . '/' . $filepath;
    }
    
    /**
     * Get relative path for a file
     * 
     * @param string $filepath Full file path
     * @return string
     */
    private function getRelativePath($filepath) {
        $basePath = realpath(dirname(__DIR__)) . '/';
        return str_replace($basePath, '', $filepath);
    }
    
    /**
     * Delete a file
     * 
     * @param string $filepath Path to file
     * @return bool
     */
    public function deleteFile($filepath) {
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
    
    /**
     * Delete event image
     * 
     * @param int $eventId
     * @param string $extension File extension
     * @return bool
     */
    public function deleteEventImage($eventId, $extension = null) {
        $filepath = EVENT_IMAGES_DIR . $eventId;
        
        if ($extension) {
            $filepath .= ".{$extension}";
            return $this->deleteFile($filepath);
        } else {
            // Try to find the file with any extension
            foreach (ALLOWED_EXTENSIONS as $ext) {
                if ($this->deleteFile("{$filepath}.{$ext}")) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Delete category image
     * 
     * @param int $categoryId
     * @param string $extension File extension
     * @return bool
     */
    public function deleteCategoryImage($categoryId, $extension = null) {
        $filepath = CATEGORY_IMAGES_DIR . $categoryId;
        
        if ($extension) {
            $filepath .= ".{$extension}";
            return $this->deleteFile($filepath);
        } else {
            // Try to find the file with any extension
            foreach (ALLOWED_EXTENSIONS as $ext) {
                if ($this->deleteFile("{$filepath}.{$ext}")) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Delete nominee image
     * 
     * @param int $nomineeId
     * @param string $extension File extension
     * @return bool
     */
    public function deleteNomineeImage($nomineeId, $extension = null) {
        $filepath = NOMINEE_IMAGES_DIR . $nomineeId;
        
        if ($extension) {
            $filepath .= ".{$extension}";
            return $this->deleteFile($filepath);
        } else {
            // Try to find the file with any extension
            foreach (ALLOWED_EXTENSIONS as $ext) {
                if ($this->deleteFile("{$filepath}.{$ext}")) {
                    return true;
                }
            }
        }
        
        return false;
    }
}