<?php
session_start();
require_once("../Database/Connections.php");

class CandidateManager
{
    private $conn;
    private $uploads_dir = 'uploads/';
    private $base_path;
    private $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    private $max_file_size = 5 * 1024 * 1024; // 5MB

    public function __construct($connection)
    {
        $this->conn = $connection;
        $this->base_path = str_replace('\\', '/', __DIR__) . '/';
        $this->initialize();
    }

    private function initialize(): void
    {
        if (!isset($_SESSION['Email'])) {
            header("Location: ../login.php");
            exit();
        }
        $this->createDirectories();
        $this->createTable();
    }

    private function createDirectories(): void
    {
        $directories = ['resumes', 'resume_images', 'temp'];
        foreach ($directories as $dir) {
            $path = $this->base_path . $this->uploads_dir . $dir . '/';
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }

    private function createTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS candidates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            job_title VARCHAR(255) NOT NULL,
            position VARCHAR(255) NOT NULL,
            experience_years INT NOT NULL,
            age INT NOT NULL,
            contact_number VARCHAR(20) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            address TEXT NOT NULL,
            resume_path VARCHAR(500) DEFAULT NULL,
            extracted_image_path VARCHAR(500) DEFAULT NULL,
            status ENUM('new','reviewed','shortlisted','interviewed','rejected','hired') DEFAULT 'new',
            source VARCHAR(100) DEFAULT 'Direct Application',
            skills TEXT DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->conn->exec($sql);

        // Ensure all necessary columns exist for existing tables
        $columns = [
            'extracted_image_path' => "VARCHAR(500) DEFAULT NULL AFTER resume_path",
            'source' => "VARCHAR(100) DEFAULT 'Direct Application' AFTER status",
            'skills' => "TEXT DEFAULT NULL AFTER source",
            'notes' => "TEXT DEFAULT NULL AFTER skills"
        ];

        foreach ($columns as $column => $definition) {
            try {
                $this->conn->query("SELECT $column FROM candidates LIMIT 1");
            } catch (PDOException $e) {
                $this->conn->exec("ALTER TABLE candidates ADD COLUMN $column $definition");
            }
        }
    }

    public function handleRequests(): array
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'validate_resume') {
            $this->handleResumeValidation();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'check_pin') {
            $this->checkPin();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $result = $this->processForm($_POST, $_FILES);
            $_SESSION['formResult'] = $result;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }

        return $_SESSION['formResult'] ?? ['message' => '', 'error' => ''];
    }

    private function processForm(array $post, array $files): array
    {
        try {
            switch ($post['action']) {
                case 'add':
                    return $this->addCandidate($post, $files);
                case 'update':
                    return $this->updateCandidate($post, $files);
                case 'delete':
                    return $this->deleteCandidate((int) $post['id']);
                case 'save_cropped_image':
                    return $this->saveCroppedImage($post['image_data'] ?? '');
                default:
                    throw new Exception("Invalid action");
            }
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function saveCroppedImage(string $base64Data): array
    {
        header('Content-Type: application/json');
        try {
            if (empty($base64Data)) {
                throw new Exception("No image data provided");
            }

            // Remove header "data:image/jpeg;base64,"
            $base64Data = preg_replace('#^data:image/\w+;base64,#i', '', $base64Data);
            $data = base64_decode($base64Data);

            if (!$data) {
                throw new Exception("Invalid base64 image data");
            }

            $filename = 'profile_cropped_' . uniqid() . '_' . time() . '.jpg';
            $relPath = $this->uploads_dir . 'resume_images/' . $filename;
            $absPath = $this->base_path . $relPath;

            $dir = dirname($absPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            if (file_put_contents($absPath, $data)) {
                echo json_encode(['success' => true, 'path' => $relPath, 'message' => 'Image cropped and saved']);
                exit;
            }
            throw new Exception("Failed to save file");
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    private function addCandidate(array $data, array $files): array
    {
        $this->validateCandidateData($data);
        $this->checkDuplicateEmail($data['email']);

        $resume_path = $this->uploadFile($files['resume'], 'resumes/');


        // Prioritize image from client (cropped/validated)
        $extracted_image = null;
        if (!empty($data['extracted_image_path'])) {
            $extracted_image = $this->handleExtractedImage($data['extracted_image_path']);
        }

        // Fallback: extract if no valid image provided
        if (!$extracted_image) {
            $extracted_image = $this->extractImageFromResume($this->base_path . $resume_path, 'permanent');
        }

        if (empty($data['full_name']) || empty($data['email'])) {
            $extracted_info = $this->extractInfoFromResume($this->base_path . $resume_path, $files['resume']['name']);
            if (empty($data['full_name']) && !empty($extracted_info['name'])) {
                $data['full_name'] = $extracted_info['name'];
            }
            if (empty($data['email']) && !empty($extracted_info['email'])) {
                $data['email'] = $extracted_info['email'];
            }
        }

        $stmt = $this->conn->prepare("INSERT INTO candidates 
            (full_name, job_title, position, experience_years, age, contact_number, 
             email, address, resume_path, extracted_image_path, source, skills, notes, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            trim($data['full_name']),
            trim($data['job_title']),
            trim($data['position']),
            (int) $data['experience_years'],
            (int) $data['age'],
            $this->cleanPhoneNumber($data['contact_number']),
            trim($data['email']),
            trim($data['address']),
            $resume_path,
            $extracted_image,
            $data['source'] ?? 'Direct Application',
            trim($data['skills'] ?? ''),
            trim($data['notes'] ?? ''),
            $data['status'] ?? 'new'
        ]);

        return ['message' => "Candidate added successfully!"];
    }

    private function updateCandidate(array $data, array $files): array
    {
        $id = (int) $data['id'];
        $candidate = $this->getCandidate($id);

        if (!$candidate) {
            throw new Exception("Candidate not found.");
        }

        $this->validateCandidateData($data);

        $resume_path = $candidate['resume_path'];
        $extracted_image = $candidate['extracted_image_path'];

        if (isset($files['resume']) && $files['resume']['error'] === UPLOAD_ERR_OK) {
            $this->deleteFile($resume_path);
            $resume_path = $this->uploadFile($files['resume'], 'resumes/');

            $new_image = null;
            if (!empty($data['extracted_image_path'])) {
                $new_image = $this->handleExtractedImage($data['extracted_image_path']);
            }

            if (!$new_image) {
                $new_image = $this->extractImageFromResume($this->base_path . $resume_path, 'permanent');
            }

            if ($new_image) {
                $this->deleteFile($extracted_image);
                $extracted_image = $new_image;
            }

            // Extract Name/Email from new resume
            $extracted_info = $this->extractInfoFromResume($this->base_path . $resume_path, $files['resume']['name']);
            if (!empty($extracted_info['name'])) {
                $data['full_name'] = $extracted_info['name'];
            }
            if (!empty($extracted_info['email'])) {
                $data['email'] = $extracted_info['email'];
            }
        } elseif (isset($files['resume']) && $files['resume']['error'] !== UPLOAD_ERR_NO_FILE) {
            throw new Exception("Resume upload failed. Error code: " . $files['resume']['error']);
        }

        $stmt = $this->conn->prepare("UPDATE candidates SET 
            full_name=?, job_title=?, position=?, experience_years=?, age=?, 
            contact_number=?, email=?, address=?, resume_path=?, extracted_image_path=?, 
            source=?, skills=?, notes=?, status=? WHERE id=?");

        $stmt->execute([
            trim($data['full_name']),
            trim($data['job_title']),
            trim($data['position']),
            (int) $data['experience_years'],
            (int) $data['age'],
            $this->cleanPhoneNumber($data['contact_number']),
            trim($data['email']),
            trim($data['address']),
            $resume_path,
            $extracted_image,
            $data['source'] ?? 'Direct Application',
            trim($data['skills'] ?? ''),
            trim($data['notes'] ?? ''),
            $data['status'] ?? 'new',
            $id
        ]);

        return ['message' => "Candidate updated successfully!"];
    }

    private function deleteCandidate(int $id): array
    {
        $candidate = $this->getCandidate($id);
        if (!$candidate) {
            throw new Exception("Candidate not found.");
        }

        $this->deleteFile($candidate['resume_path']);
        $this->deleteFile($candidate['extracted_image_path']);

        $stmt = $this->conn->prepare("DELETE FROM candidates WHERE id = ?");
        $stmt->execute([$id]);

        return ['message' => "Candidate deleted successfully!"];
    }

    private function uploadFile(array $file, string $subdir): string
    {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowed_extensions)) {
            throw new Exception("Invalid file type. Allowed: " . implode(', ', $this->allowed_extensions));
        }

        if ($file['size'] > $this->max_file_size) {
            throw new Exception("File too large. Maximum size: 5MB");
        }

        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.\-]/', '_', $file['name']);
        $destination = $this->base_path . $this->uploads_dir . $subdir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("Failed to upload file.");
        }

        return $this->uploads_dir . $subdir . $filename;
    }

    private function handleExtractedImage(?string $path): ?string
    {
        if (empty($path))
            return null;

        $path = str_replace('\\', '/', $path);

        // Treat paths starting with 'uploads/' correctly
        if (strpos($path, $this->uploads_dir) === false) {
            // If path doesn't start with uploads/, prepended it? 
            // Start validation usually returns 'uploads/temp/...' 
        }

        // If it's already in permanent storage
        if (strpos($path, 'resume_images/') !== false) {
            // Verify file exists
            if (file_exists($this->base_path . $path)) {
                return $path;
            }
            return null;
        }

        // If it's in temp storage, move it to permanent
        if (strpos($path, 'temp/') !== false) {
            $absSrc = $this->base_path . $path;
            if (file_exists($absSrc)) {
                $filename = 'profile_' . uniqid() . '_' . time() . '.jpg';
                $relDest = $this->uploads_dir . 'resume_images/' . $filename;
                $absDest = $this->base_path . $relDest;

                if (rename($absSrc, $absDest)) {
                    return $relDest;
                }
            }
        }

        return null;
    }

    private function extractImageFromResume(string $path, string $mode): ?string
    {
        if (!file_exists($path)) {
            return null;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            return $this->extractFromImageResume($path, $mode);
        }

        if ($ext === 'pdf') {
            return $this->extractFromPDF($path, $mode);
        }

        if ($ext === 'docx') {
            return $this->extractFromDOCX($path, $mode);
        }

        return null;
    }

    private function extractFromImageResume(string $imagePath, string $mode): ?string
    {
        // Resize the full image so the frontend scan can find the face anywhere on the resume.
        return $this->createProfileImage($imagePath, 0, 0, 0, 0, $mode);
    }

    private function extractFromPDF(string $pdfPath, string $mode): ?string
    {
        $content = @file_get_contents($pdfPath);
        if (!$content) {
            return null;
        }

        $bestImagePath = null;
        $maxSize = 0;

        $startPos = 0;
        while (($jpegStart = strpos($content, "\xFF\xD8", $startPos)) !== false) {
            $jpegEnd = strpos($content, "\xFF\xD9", $jpegStart + 2);

            if ($jpegEnd !== false) {
                $imageData = substr($content, $jpegStart, ($jpegEnd + 2) - $jpegStart);
                $size = strlen($imageData);

                // Most profile pictures are > 10KB, icons are usually small
                if ($size > $maxSize && $size > 5000) {
                    $tempPath = $this->base_path . $this->uploads_dir . 'temp/' . uniqid('pdf_extract_') . '.jpg';
                    if (file_put_contents($tempPath, $imageData) && @getimagesize($tempPath)) {
                        if ($bestImagePath)
                            @unlink($this->base_path . $bestImagePath);
                        $bestImagePath = $this->createProfileImage($tempPath, 0, 0, 0, 0, $mode);
                        $maxSize = $size;
                    }
                }
            }
            $startPos = $jpegStart + 2;
            if ($startPos > strlen($content))
                break;
        }

        return $bestImagePath;
    }

    private function extractFromDOCX(string $docxPath, string $mode): ?string
    {
        if (!extension_loaded('zip')) {
            return null;
        }

        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== TRUE) {
            return null;
        }

        $bestImagePath = null;
        $maxSize = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            if (preg_match('/^word\/media\/image\d+\.(jpg|jpeg|png)$/i', $filename)) {
                $imageData = $zip->getFromIndex($i);
                $size = strlen($imageData);

                if ($imageData && $size > $maxSize && $size > 5000) {
                    $tempPath = $this->base_path . $this->uploads_dir . 'temp/' . uniqid('docx_extract_') . '.' . pathinfo($filename, PATHINFO_EXTENSION);
                    if (file_put_contents($tempPath, $imageData)) {
                        if ($bestImagePath)
                            @unlink($this->base_path . $bestImagePath);
                        $bestImagePath = $this->createProfileImage($tempPath, 0, 0, 0, 0, $mode);
                        $maxSize = $size;
                    }
                }
            }
        }

        $zip->close();
        return $bestImagePath;
    }

    private function createProfileImage(string $src, int $x, int $y, int $w, int $h, string $mode): ?string
    {
        if (!extension_loaded('gd')) {
            return $this->copyImage($src, $mode);
        }

        $info = @getimagesize($src);
        if (!$info)
            return null;

        list($srcWidth, $srcHeight) = $info;

        // NEW STRATEGY: Resize FULL image to max 1200px width for scanning (High res helps tiny faces)
        $targetWidth = 1200;
        $ratio = $srcWidth / $srcHeight;

        if ($srcWidth > $targetWidth) {
            $newWidth = $targetWidth;
            $newHeight = $targetWidth / $ratio;
        } else {
            $newWidth = $srcWidth;
            $newHeight = $srcHeight;
        }

        // Create destination image
        $dst = imagecreatetruecolor($newWidth, $newHeight);

        // Create source image
        $src_img = null;
        if ($info[2] == IMAGETYPE_JPEG)
            $src_img = @imagecreatefromjpeg($src);
        elseif ($info[2] == IMAGETYPE_PNG)
            $src_img = @imagecreatefrompng($src);

        if (!$src_img)
            return $this->copyImage($src, $mode);

        // Handle transparency
        if ($info[2] == IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        // Resample full image
        imagecopyresampled($dst, $src_img, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);
        imagedestroy($src_img);

        // Save image
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        $subdir = ($mode === 'permanent') ? 'resume_images/' : 'temp/';
        $prefix = ($mode === 'permanent') ? 'profile_' : 'temp_';
        $filename = $prefix . uniqid() . '.' . $ext;
        $destination = $this->base_path . $this->uploads_dir . $subdir . $filename;

        // Create directory if needed
        $dir = dirname($destination);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $success = false;
        if ($ext == 'jpg' || $ext == 'jpeg') {
            $success = @imagejpeg($dst, $destination, 85);
        } elseif ($ext == 'png') {
            $success = @imagepng($dst, $destination, 8);
        }

        imagedestroy($dst);

        if ($success && file_exists($destination)) {
            return $this->uploads_dir . $subdir . $filename;
        }

        return $this->copyImage($src, $mode);
    }

    private function copyImage(string $src, string $mode): ?string
    {
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        $subdir = ($mode === 'permanent') ? 'resume_images/' : 'temp/';
        $prefix = ($mode === 'permanent') ? 'profile_' : 'temp_';
        $filename = $prefix . uniqid() . '.' . $ext;
        $destination = $this->base_path . $this->uploads_dir . $subdir . $filename;

        // Create directory if needed
        $dir = dirname($destination);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (copy($src, $destination)) {
            return $this->uploads_dir . $subdir . $filename;
        }

        return null;
    }

    private function extractInfoFromResume(string $filePath, string $originalFilename): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $text = '';
        $name = '';
        $email = '';

        if ($ext === 'docx') {
            $text = $this->extractTextFromDOCX($filePath);
        } elseif ($ext === 'pdf') {
            $text = $this->extractTextFromPDF($filePath);
        }

        $name = $this->extractNameFromText($text) ?: $this->extractNameFromFilename($originalFilename);
        $email = $this->extractEmailFromText($text);

        return [
            'name' => $name,
            'email' => $email
        ];
    }

    private function extractTextFromPDF(string $pdfPath): string
    {
        $text = '';

        if (function_exists('shell_exec')) {
            $tempFile = tempnam(sys_get_temp_dir(), 'pdf_') . '.txt';
            $command = escapeshellcmd("pdftotext \"$pdfPath\" \"$tempFile\" 2>&1");
            @shell_exec($command);

            if (file_exists($tempFile)) {
                $text = file_get_contents($tempFile);
                unlink($tempFile);
            }
        }

        if (empty($text)) {
            $content = @file_get_contents($pdfPath);
            if ($content) {
                $text = preg_replace('/[\x00-\x1F]/', ' ', $content);
                $text = preg_replace('/\s+/', ' ', $text);
            }
        }

        return $text;
    }

    private function extractTextFromDOCX(string $docxPath): string
    {
        $text = '';

        if (extension_loaded('zip')) {
            $zip = new ZipArchive();
            if ($zip->open($docxPath) === TRUE) {
                if (($index = $zip->locateName('word/document.xml')) !== false) {
                    $data = $zip->getFromIndex($index);
                    $text = strip_tags(str_replace(['<w:p>', '<w:br/>'], ["\n", "\n"], $data));
                    $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
                }
                $zip->close();
            }
        }

        return trim($text);
    }

    private function extractNameFromText(string $text): string
    {
        $patterns = [
            '/Name\s*[:]\s*([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)/i',
            '/Full Name\s*[:]\s*([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $name = trim($matches[1]);
                if (preg_match('/^[A-Za-z\s\.\-]+$/', $name) && strlen($name) > 3) {
                    return ucwords(strtolower($name));
                }
            }
        }

        return '';
    }

    private function extractEmailFromText(string $text): string
    {
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $matches)) {
            $email = trim($matches[0]);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return strtolower($email);
            }
        }

        return '';
    }

    private function extractNameFromFilename(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);

        $name = preg_replace('/[0-9_\-\.]/', ' ', $name);
        $name = preg_replace('/\b(resume|cv|application|profile|pic|photo|image|candidate|untitled)\b/i', ' ', $name);
        $name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim($name);

        $words = explode(' ', $name);
        $validWords = array_filter($words, function ($word) {
            return strlen($word) > 1 && !is_numeric($word);
        });

        if (count($validWords) >= 2) {
            return ucwords(implode(' ', $validWords));
        }

        return 'Candidate';
    }

    private function handleResumeValidation(): void
    {
        header('Content-Type: application/json');

        if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['valid' => false, 'message' => 'Please select a resume file']);
            exit;
        }

        $file = $_FILES['resume'];
        $file_name = $file['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_ext, $this->allowed_extensions)) {
            echo json_encode([
                'valid' => false,
                'message' => "File type not allowed: .$file_ext. Allowed: PDF, DOC, DOCX, JPG, PNG"
            ]);
            exit;
        }

        if ($file['size'] > $this->max_file_size) {
            echo json_encode(['valid' => false, 'message' => "File is too large. Maximum: 5MB"]);
            exit;
        }

        try {
            $path = $this->uploadFile($file, 'temp/');
            $full_path = $this->base_path . $path;

            $image = $this->extractImageFromResume($full_path, 'temp');
            $extracted_info = $this->extractInfoFromResume($full_path, $file_name);

            echo json_encode([
                'valid' => true,
                'message' => $image ? '✅ Profile Picture Extracted' : '✅ Valid Resume',
                'extracted_image' => $image,
                'extracted_info' => [
                    'name' => $extracted_info['name'],
                    'email' => $extracted_info['email']
                ],
                'has_image' => !empty($image),
                'resume_name' => $file_name,
                'file_type' => $file_ext
            ]);

        } catch (Exception $e) {
            echo json_encode([
                'valid' => false,
                'message' => "Error: " . $e->getMessage()
            ]);
        }

        exit;
    }

    private function deleteFile(?string $path): void
    {
        if (empty($path)) {
            return;
        }

        $full_path = $this->base_path . $path;
        if (file_exists($full_path)) {
            @unlink($full_path);
        }
    }

    private function cleanPhoneNumber(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    private function validateCandidateData(array $data): void
    {
        if (empty($data['full_name']) || empty($data['email'])) {
            throw new Exception("Name and email are required");
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
    }

    private function checkDuplicateEmail(string $email, ?int $exclude_id = null): void
    {
        $sql = "SELECT id FROM candidates WHERE email = ?";
        $params = [trim($email)];

        if ($exclude_id) {
            $sql .= " AND id != ?";
            $params[] = $exclude_id;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        if ($stmt->fetch()) {
            throw new Exception("A candidate with this email already exists");
        }
    }

    public function getCandidates(string $search = '', string $status = ''): array
    {
        $sql = "SELECT * FROM candidates WHERE 1=1 AND (source IS NULL OR source != 'Online Registration')";
        $params = [];

        if ($search) {
            $sql .= " AND (full_name LIKE ? OR email LIKE ? OR job_title LIKE ?)";
            $params = array_fill(0, 3, "%$search%");
        }

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCandidate(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM candidates WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getStatusOptions(): array
    {
        return ['new', 'reviewed', 'shortlisted', 'interviewed', 'rejected', 'hired'];
    }

    public function getDisplayImage(array $candidate): ?string
    {
        if (empty($candidate['extracted_image_path'])) {
            return null;
        }

        $path = $candidate['extracted_image_path'];
        if (strpos($path, 'uploads/') !== 0) {
            $path = 'uploads/' . ltrim($path, '/');
        }

        if (file_exists($this->base_path . $path)) {
            return $path;
        }

        return null;
    }

    private function checkPin(): void
    {
        header('Content-Type: application/json');
        try {
            $pin = $_POST['pin'] ?? '';
            $email = $_SESSION['Email'];

            $stmt = $this->conn->prepare("SELECT resume_pin FROM logintbl WHERE Email = ?");
            if (!$stmt) {
                throw new Exception("Database error: prepare failed");
            }
            $stmt->execute([$email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Default PIN is 1234 if not set/found (though DB should have it)
            $validPin = $row['resume_pin'] ?? '1234';

            if ($pin === $validPin) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Incorrect PIN']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Initialize
try {
    $manager = new CandidateManager($conn);
    $formResult = $manager->handleRequests();
    $candidates = $manager->getCandidates($_GET['search'] ?? '', $_GET['status'] ?? '');
} catch (Exception $e) {
    $formResult = ['error' => 'An error occurred. Please try again.'];
    $candidates = [];
}

if (isset($_SESSION['formResult'])) {
    unset($_SESSION['formResult']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate | HR1</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <!-- PDF.js for Resume Parsing -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>
    <!-- Tesseract.js for Image OCR -->
    <script src='https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js'></script>
    <style>
        .profile-image-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #e5e7eb;
            margin: 10px auto;
        }

        .image-preview-container {
            min-height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            border: 2px dashed #d1d5db;
            background-color: #f9fafb;
            border-radius: 0.5rem;
            padding: 10px;
        }

        .preview-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
            text-align: center;
        }

        .validation-success {
            background-color: #d1fae5 !important;
            color: #065f46 !important;
            border: 1px solid #a7f3d0 !important;
        }

        .validation-warning {
            background-color: #fef3c7 !important;
            color: #92400e !important;
            border: 1px solid #fbbf24 !important;
        }

        .validation-error {
            background-color: #fee2e2 !important;
            color: #991b1b !important;
            border: 1px solid #fca5a5 !important;
        }

        .table-row-hover:hover {
            background-color: #f8fafc;
        }

        /* Hide scrollbar for Chrome, Safari and Opera */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for IE, Edge and Firefox */
        .no-scrollbar {
            -ms-overflow-style: none;
            /* IE and Edge */
            scrollbar-width: none;
            /* Firefox */
        }
    </style>
</head>

<body class="bg-gray-50 font-sans text-gray-800">
    <?php include '../Components/sidebar_admin.php'; ?>

    <div class="lg:ml-64">
        <div style="padding: 100px 30px 20px 30px;">
            <?php include '../Components/header_admin.php'; ?>
            <h1 class="text-3xl font-bold text-gray-800 mb-1">Candidate Tracking</h1>
            <p class="text-gray-600 mb-6">Manage and track candidate applications</p>

            <?php if (!empty($formResult['message'])): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center text-green-700">
                    <i class="fas fa-check-circle mr-3"></i> <?php echo htmlspecialchars($formResult['message']); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($formResult['error'])): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center text-red-700">
                    <i class="fas fa-exclamation-circle mr-3"></i> <?php echo htmlspecialchars($formResult['error']); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-sm p-4 mb-6 flex flex-col lg:flex-row justify-between gap-4">
                <form class="flex gap-3 flex-1 lg:max-w-xl">
                    <div class="relative flex-1">
                        <button type="submit"
                            class="absolute left-3 top-3 text-gray-400 hover:text-blue-500 bg-transparent border-0 cursor-pointer">
                            <i class="fas fa-search"></i>
                        </button>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                            placeholder="Search candidates..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <select name="status" onchange="this.form.submit()"
                        class="px-4 py-2 border border-gray-300 rounded-lg bg-white outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <?php foreach ($manager->getStatusOptions() as $option): ?>
                            <option value="<?php echo $option; ?>" <?php echo ($_GET['status'] ?? '') == $option ? 'selected' : ''; ?>>
                                <?php echo ucfirst($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <button type="button" onclick="openModal('candidateModal')"
                    class="relative z-10 px-6 py-3 bg-gray-900 text-white rounded-lg hover:bg-gray-800 shadow-sm whitespace-nowrap cursor-pointer">
                    <i class="fas fa-plus mr-2"></i> Add Candidate
                </button>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Candidate
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Position
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Experience
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Contact
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($candidates)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">No candidates found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($candidates as $candidate): ?>
                                    <?php
                                    $image = $manager->getDisplayImage($candidate);
                                    $initial = strtoupper(substr($candidate['full_name'], 0, 1));
                                    ?>
                                    <tr class="table-row-hover">
                                        <td class="px-6 py-4 whitespace-nowrap text-left">
                                            <div class="flex items-center justify-start gap-4">
                                                <div class="flex-shrink-0 h-10 w-10 relative">
                                                    <?php if ($image): ?>
                                                        <img class="h-10 w-10 rounded-full object-cover border border-gray-200"
                                                            src="<?php echo $image; ?>?v=<?php echo time(); ?>"
                                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                            title="Candidate photo">
                                                    <?php endif; ?>
                                                    <div
                                                        class="<?php echo $image ? 'hidden' : 'flex'; ?> h-10 w-10 rounded-full bg-gray-200 items-center justify-center text-gray-500 font-bold">
                                                        <?php echo $initial; ?>
                                                    </div>
                                                </div>
                                                <div class="text-left">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($candidate['full_name']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo htmlspecialchars($candidate['email']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="text-sm text-gray-900">
                                                <?php echo htmlspecialchars($candidate['job_title']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo htmlspecialchars($candidate['position']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm text-gray-500">
                                            <?php echo $candidate['experience_years']; ?> years
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 text-center">
                                            <?php echo htmlspecialchars($candidate['contact_number']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">
                                                <?php echo ucfirst($candidate['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm text-gray-500 whitespace-nowrap">
                                            <?php echo date('M d, Y', strtotime($candidate['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-3">
                                                <button
                                                    onclick='showPinModalForCandidate(<?php echo json_encode($candidate, JSON_HEX_APOS); ?>)'
                                                    class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 flex items-center justify-center transition-colors shadow-sm"
                                                    title="View Profile">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button
                                                    onclick='editCandidate(<?php echo json_encode($candidate, JSON_HEX_APOS); ?>)'
                                                    class="w-8 h-8 rounded-full bg-green-50 text-green-600 hover:bg-green-100 flex items-center justify-center transition-colors shadow-sm"
                                                    title="Edit Candidate">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" onsubmit="return confirm('Delete this candidate?')"
                                                    class="inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $candidate['id']; ?>">
                                                    <button
                                                        class="w-8 h-8 rounded-full bg-red-50 text-red-600 hover:bg-red-100 flex items-center justify-center transition-colors shadow-sm"
                                                        title="Delete Candidate">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="candidateModal" class="hidden fixed inset-0 z-[60] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div
                class="inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:align-middle sm:max-w-3xl sm:w-full">
                <div class="bg-gray-50 px-4 py-3 flex justify-between items-center border-b sticky top-0 z-10">
                    <h3 class="text-lg font-bold text-gray-900" id="modalTitle">Add Candidate</h3>
                    <button onclick="closeModal('candidateModal')"
                        class="text-gray-400 hover:text-gray-500 p-2 rounded-full hover:bg-gray-200 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <!-- Scrollable Content Wrapper -->
                <div class="max-h-[70vh] overflow-y-auto">
                    <form method="POST" enctype="multipart/form-data" id="candidateForm" class="p-6">
                        <!-- Form content remains the same, just wrapper changed -->
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="candidateId">
                        <input type="hidden" name="extracted_image_path" id="extractedImagePath">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700">Full Name *</label>
                                    <input type="text" name="full_name" id="fullName" required
                                        class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700">Job Type *</label>
                                    <select name="job_title" id="jobTitle" required
                                        class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3 bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="Full Time">Full Time</option>
                                        <option value="Part Time">Part Time</option>
                                        <option value="Contract">Contract</option>
                                        <option value="Freelance">Freelance</option>
                                        <option value="Internship">Internship</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700">Position *</label>
                                    <input type="text" name="position" id="position" required
                                        class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700">Experience (Years) *</label>
                                    <input type="number" name="experience_years" id="experienceYears" min="0" required
                                        class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700">Age *</label>
                                    <input type="number" name="age" id="age" min="18" required
                                        class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700">Email *</label>
                                    <input type="email" name="email" id="email" required
                                        class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700">Contact Number *</label>
                                    <input type="text" name="contact_number" id="contactNumber" required
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                        class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700">Source</label>
                                    <select name="source" id="source"
                                        class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="Direct Application">Direct Application</option>
                                        <option value="LinkedIn">LinkedIn</option>
                                        <option value="Referral">Referral</option>
                                        <option value="Job Fair">Job Fair</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div id="statusField" class="hidden">
                                    <label class="block text-sm font-bold text-gray-700">Status</label>
                                    <select name="status" id="status"
                                        class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3 bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <?php foreach ($manager->getStatusOptions() as $option): ?>
                                            <option value="<?php echo $option; ?>"><?php echo ucfirst($option); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700">Resume (PDF/DOC/Image)
                                        *</label>
                                    <div class="mt-1">
                                        <label
                                            class="cursor-pointer bg-blue-50 text-blue-700 font-semibold py-2 px-4 rounded-md border border-blue-200 hover:bg-blue-100 transition-colors block text-center overflow-hidden">
                                            <span id="fileName" class="block truncate px-2">Choose File</span>
                                            <input type="file" name="resume" id="resumeInput" class="hidden" required
                                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                        </label>
                                        <p class="text-xs text-gray-500 mt-1">Requirements: PDF, DOC, DOCX, JPG, PNG.
                                            Max
                                            5MB.</p>

                                        <div id="imagePreviewContainer" class="image-preview-container">
                                            <div id="validationStatus" class="text-center mb-2"></div>
                                            <img id="imagePreview" class="profile-image-preview" style="display: none;">
                                            <div id="previewText" class="preview-text">Upload file and validate</div>
                                        </div>

                                        <button type="button" id="validateBtn"
                                            class="mt-3 w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                                            <i class="fas fa-search mr-2"></i> Validate & Extract Profile
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-bold text-gray-700">Address</label>
                            <textarea name="address" id="address" rows="2"
                                class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Skills</label>
                                <textarea name="skills" id="skills" rows="2"
                                    class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Notes</label>
                                <textarea name="notes" id="notes" rows="2"
                                    class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                            <button type="button" onclick="closeModal('candidateModal')"
                                class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" id="submitBtn"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                Save Candidate
                            </button>
                        </div>
                    </form>
                </div> <!-- End Scrollable Wrapper -->
            </div>
        </div>
    </div>

    <div id="viewModal"
        class="hidden fixed inset-0 z-[60] flex items-start justify-center pt-24 px-4 pb-10 bg-gray-600 bg-opacity-75 backdrop-blur-sm overflow-y-auto no-scrollbar">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[85vh] overflow-y-auto relative no-scrollbar">
            <button onclick="closeModal('viewModal')"
                class="absolute top-4 right-4 z-10 w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:text-gray-600 hover:bg-gray-200 transition-colors">
                <i class="fas fa-times"></i>
            </button>
            <div id="viewContent" class="p-8"></div>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
            document.body.style.overflow = 'auto';
            if (id === 'candidateModal') {
                resetForm();
            }
        }

        function resetForm() {
            document.getElementById('candidateForm').reset();
            document.getElementById('fileName').textContent = "Choose File";
            document.getElementById('resumeInput').required = true;

            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('imagePreview').src = '';

            const container = document.getElementById('imagePreviewContainer');
            container.classList.add('border-dashed', 'border-2');
            container.classList.remove('shadow-sm', 'border-gray-200');

            document.getElementById('previewText').textContent = 'Upload file and validate';
            document.getElementById('extractedImagePath').value = '';
            document.getElementById('candidateId').value = '';
            document.getElementById('formAction').value = 'add';
            document.getElementById('modalTitle').textContent = 'Add Candidate';
            document.getElementById('statusField').classList.add('hidden');
            document.getElementById('validationStatus').innerHTML = '';

            document.getElementById('validateBtn').className = "mt-3 w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-md hover:bg-blue-700 transition-colors";
            document.getElementById('validateBtn').innerHTML = '<i class="fas fa-search mr-2"></i> Validate & Extract Profile';
            document.getElementById('validateBtn').disabled = false;
            document.getElementById('submitBtn').disabled = false;

            // Clear fetched highlights
            ['fullName', 'email', 'contactNumber', 'position'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.remove('bg-green-50', 'border-green-300');
            });
        }

        document.getElementById('resumeInput').addEventListener('change', function () {
            const file = this.files[0];
            document.getElementById('fileName').textContent = file ? file.name : "Choose File";

            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('imagePreview').src = '';

            const container = document.getElementById('imagePreviewContainer');
            container.classList.add('border-dashed', 'border-2');
            container.classList.remove('shadow-sm', 'border-gray-200');

            document.getElementById('previewText').textContent = 'Upload file and validate';
            document.getElementById('extractedImagePath').value = '';
            document.getElementById('validationStatus').innerHTML = '';

            document.getElementById('validateBtn').className = "mt-3 w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-md hover:bg-blue-700 transition-colors";
            document.getElementById('validateBtn').innerHTML = '<i class="fas fa-search mr-2"></i> Validate & Extract Profile';
            document.getElementById('validateBtn').disabled = false;
            document.getElementById('submitBtn').disabled = false;

            // Clear fetched highlights
            ['fullName', 'email', 'contactNumber', 'position'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.remove('bg-green-50', 'border-green-300');
            });
        });

        document.getElementById('validateBtn').addEventListener('click', async function () {
            const file = document.getElementById('resumeInput').files[0];
            if (!file) {
                alert("Please select a resume file first!");
                return;
            }

            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Validating...';
            this.disabled = true;
            this.className = "mt-3 w-full bg-blue-100 text-blue-700 font-bold py-2 px-4 rounded-md border border-blue-300";

            document.getElementById('previewText').textContent = 'Validating resume and extracting profile...';
            document.getElementById('imagePreview').style.display = 'none';

            // Start both tasks in parallel for speed
            const formData = new FormData();
            formData.append('resume', file);
            formData.append('action', 'validate_resume');

            try {
                // RUN IN PARALLEL: Server-side (Image) and Client-side (PDF Only - Fast)
                const [serverRes, ocrRes] = await Promise.allSettled([
                    fetch('', { method: 'POST', body: formData }).then(r => r.json()),
                    (async () => {
                        if (file.type === 'application/pdf') {
                            return await analyzePDF(file);
                        }
                        return null; // For images, we will do it in the background below
                    })()
                ]);

                const data = serverRes.status === 'fulfilled' ? serverRes.value : { valid: false, message: 'Server error' };
                const clientInfo = ocrRes.status === 'fulfilled' ? ocrRes.value : null;

                // BACKGROUND TASK for Images: Run OCR without blocking the UI
                if (file.type.startsWith('image/') && window.Tesseract) {
                    analyzeImage(file).then(info => {
                        if (info) {
                            console.log("Background OCR Finished:", info);
                            if (info.email && !document.getElementById('email').value) {
                                document.getElementById('email').value = info.email;
                                document.getElementById('email').classList.add('bg-green-50', 'border-green-300');
                            }
                            if (info.phone && !document.getElementById('contactNumber').value) {
                                document.getElementById('contactNumber').value = info.phone;
                                document.getElementById('contactNumber').classList.add('bg-green-50', 'border-green-300');
                            }
                            if (info.name && (!document.getElementById('fullName').value || /resume|candidate/i.test(document.getElementById('fullName').value))) {
                                document.getElementById('fullName').value = info.name;
                                document.getElementById('fullName').classList.add('bg-green-50', 'border-green-300');
                            }
                            if (info.position && !document.getElementById('position').value) {
                                document.getElementById('position').value = info.position;
                                document.getElementById('position').classList.add('bg-green-50', 'border-green-300');
                            }
                        }
                    });
                }

                const statusDiv = document.getElementById('validationStatus');

                if (data.valid) {
                    let bestName = '';
                    if (clientInfo && clientInfo.name && !/^(candidate|resume|updated resume)$/i.test(clientInfo.name)) {
                        bestName = clientInfo.name;
                    } else if (data.extracted_info?.name && !/^(candidate|resume|updated resume)$/i.test(data.extracted_info.name)) {
                        bestName = data.extracted_info.name;
                    }

                    if (data.has_image) {
                        this.className = "mt-3 w-full validation-success font-bold py-2 px-4 rounded-md border";
                        this.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Profile Detected!';

                        let imageMessage = '';
                        if (data.file_type === 'jpg' || data.file_type === 'jpeg' || data.file_type === 'png') {
                            imageMessage = '<div class="text-xs text-green-600 mt-1"><i class="fas fa-crop mr-1"></i> Profile cropped from image resume</div>';
                        } else {
                            imageMessage = '<div class="text-xs text-green-600 mt-1"><i class="fas fa-file-image mr-1"></i> Profile extracted from document</div>';
                        }

                        let extraInfoMsg = '';
                        if (bestName) {
                            extraInfoMsg += `<div class="text-xs text-green-600 mt-1">Found Name: ${bestName}</div>`;
                        }

                        const container = document.getElementById('imagePreviewContainer');
                        container.classList.remove('border-dashed', 'border-2');
                        container.classList.add('shadow-sm', 'border', 'border-gray-200');

                        statusDiv.innerHTML = `
                            <div class="flex flex-col items-center text-center justify-center mb-2 w-full">
                                <div class="flex items-center justify-center text-green-600 w-full">
                                    <i class="fas fa-check-circle text-lg mr-2"></i>
                                    <span class="font-bold">Profile Picture Extracted</span>
                                </div>
                                ${imageMessage}
                            </div>
                        `;

                        document.getElementById('extractedImagePath').value = data.extracted_image;

                        const img = document.getElementById('imagePreview');
                        img.crossOrigin = "anonymous";
                        img.src = data.extracted_image + '?t=' + Date.now();
                        // SHOW immediately so user doesn't see a blank space
                        img.className = "profile-image-preview block mx-auto";
                        img.style.display = 'block';

                        // temporary loading text
                        document.getElementById('previewText').textContent = 'Scanning for face...';
                        document.getElementById('previewText').className = "preview-text text-blue-600 font-semibold italic text-center mx-auto";

                        img.onload = function () {
                            detectAndCropFace(this);
                        };
                    } else {
                        // Restore dashed border
                        const container = document.getElementById('imagePreviewContainer');
                        container.classList.add('border-dashed', 'border-2');
                        container.classList.remove('shadow-sm', 'border-gray-200');

                        this.className = "mt-3 w-full validation-warning font-bold py-2 px-4 rounded-md border";
                        this.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> Valid Resume';

                        statusDiv.innerHTML = `
                            <div class="flex items-center justify-center text-yellow-600 mb-2">
                                <i class="fas fa-exclamation-triangle text-lg mr-2"></i>
                                <span class="font-bold">Valid Resume (No Profile)</span>
                            </div>
                        `;

                        document.getElementById('imagePreview').style.display = 'none';
                        document.getElementById('previewText').textContent = 'No profile picture found';
                        document.getElementById('previewText').className = "preview-text text-yellow-600";
                    }

                    const nameField = document.getElementById('fullName');
                    const currentVal = nameField.value.trim().toLowerCase();
                    const isGeneric = /^(candidate|updated resume|resume)$/i.test(currentVal);

                    if (bestName) {
                        if (!currentVal || isGeneric) {
                            nameField.value = bestName;
                        }
                    } else if (isGeneric) {
                        nameField.value = '';
                        nameField.placeholder = "Please enter full name";
                    }

                    // Prioritize Client Email if Server failed or has bad suffix
                    if (clientInfo && clientInfo.email && (!data.extracted_info?.email || data.extracted_info.email.endsWith('n'))) {
                        // Simple heuristic for the 'comn' bug
                        document.getElementById('email').value = clientInfo.email;
                    } else if (data.extracted_info?.email && !document.getElementById('email').value.trim()) {
                        document.getElementById('email').value = data.extracted_info.email;
                    }

                    if (data.resume_name) {
                        const fileInfo = document.createElement('div');
                        fileInfo.className = "text-xs text-gray-500 mt-1";
                        fileInfo.textContent = `File: ${data.resume_name} (${data.file_type.toUpperCase()})`;
                        statusDiv.appendChild(fileInfo);
                    }

                } else {
                    this.className = "mt-3 w-full validation-error font-bold py-2 px-4 rounded-md border";
                    this.innerHTML = '<i class="fas fa-times-circle mr-2"></i> Invalid Resume';

                    statusDiv.innerHTML = `
                        <div class="flex items-center justify-center text-red-600 mb-2">
                            <i class="fas fa-times-circle text-lg mr-2"></i>
                            <span class="font-bold">❌ ${data.message}</span>
                        </div>
                    `;

                    document.getElementById('imagePreview').style.display = 'none';
                    document.getElementById('previewText').textContent = 'Invalid resume file';
                    document.getElementById('previewText').className = "preview-text text-red-600";
                }
            } catch (error) {
                console.error('Validation error:', error);
                document.getElementById('validationStatus').innerHTML = `
                    <div class="flex items-center justify-center text-red-600 mb-2">
                        <i class="fas fa-exclamation-circle text-lg mr-2"></i>
                        <span class="font-bold">❌ Error: ${error.message}</span>
                    </div>
                `;
                this.innerHTML = originalText;
                this.className = "mt-3 w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-md hover:bg-blue-700 transition-colors";
            } finally {
                this.disabled = false;
            }
        });

        function editCandidate(candidate) {
            document.getElementById('modalTitle').textContent = 'Edit Candidate';
            document.getElementById('formAction').value = 'update';
            document.getElementById('candidateId').value = candidate.id;
            document.getElementById('fullName').value = candidate.full_name || '';
            document.getElementById('jobTitle').value = candidate.job_title || '';
            document.getElementById('position').value = candidate.position || '';
            document.getElementById('experienceYears').value = candidate.experience_years || '';
            document.getElementById('contactNumber').value = candidate.contact_number || '';
            document.getElementById('email').value = candidate.email || '';
            document.getElementById('age').value = candidate.age || '';
            document.getElementById('address').value = candidate.address || '';
            document.getElementById('source').value = candidate.source || 'Direct Application';
            document.getElementById('status').value = candidate.status || 'new';
            document.getElementById('skills').value = candidate.skills || '';
            document.getElementById('notes').value = candidate.notes || '';
            document.getElementById('statusField').classList.remove('hidden');
            document.getElementById('resumeInput').required = false;

            const img = document.getElementById('imagePreview');
            const previewText = document.getElementById('previewText');
            const statusDiv = document.getElementById('validationStatus');
            const validateBtn = document.getElementById('validateBtn');

            if (candidate.resume_path) {
                const filename = candidate.resume_path.split('/').pop() || "Uploaded file";
                document.getElementById('fileName').textContent = filename;
            }

            if (candidate.extracted_image_path) {
                let imagePath = candidate.extracted_image_path;
                if (imagePath.indexOf('uploads/') === -1) {
                    imagePath = 'uploads/' + imagePath.replace(/^\/+/, '');
                }

                document.getElementById('extractedImagePath').value = imagePath;
                img.src = imagePath + '?t=' + Date.now();
                img.style.display = 'block';

                // Remove dashed border for cleaner look
                const container = document.getElementById('imagePreviewContainer');
                container.classList.remove('border-dashed', 'border-2');
                container.classList.add('shadow-sm', 'border', 'border-gray-200');

                statusDiv.innerHTML = `
                    <div class="flex items-center justify-center text-green-600 mb-2">
                        <i class="fas fa-check-circle text-lg mr-2"></i>
                        <span class="font-bold">Profile Picture Extracted</span>
                    </div>
                `;

                validateBtn.className = "mt-3 w-full validation-success font-bold py-2 px-4 rounded-md border cursor-default";
                validateBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Profile Detected';
                validateBtn.disabled = true;

                img.onload = function () {
                    previewText.textContent = 'Profile picture extracted!';
                    previewText.className = "preview-text text-green-600 font-semibold";
                };
            } else {
                // Restore dashed border
                const container = document.getElementById('imagePreviewContainer');
                container.classList.add('border-dashed', 'border-2');
                container.classList.remove('shadow-sm', 'border-gray-200');

                img.style.display = 'none';
                statusDiv.innerHTML = `
                    <div class="flex items-center justify-center text-yellow-600 mb-2">
                        <i class="fas fa-exclamation-triangle text-lg mr-2"></i>
                        <span class="font-bold">Valid Resume (No Profile)</span>
                    </div>
                `;

                validateBtn.className = "mt-3 w-full validation-warning font-bold py-2 px-4 rounded-md border cursor-default";
                validateBtn.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> Valid Resume';
                validateBtn.disabled = true;

                previewText.textContent = 'No profile picture found';
                previewText.className = "preview-text text-yellow-600";
            }

            openModal('candidateModal');
        }

        function viewCandidate(candidate) {
            currentOpenCandidate = candidate;
            let imgHtml = '';

            if (candidate.extracted_image_path) {
                let imagePath = candidate.extracted_image_path;
                if (imagePath.indexOf('uploads/') === -1) {
                    imagePath = 'uploads/' + imagePath.replace(/^\/+/, '');
                }

                imgHtml = `
                    <img src="${imagePath}?t=${Date.now()}" 
                         class="w-32 h-32 rounded-xl mx-auto object-cover border-4 border-black shadow-lg"
                         onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="hidden w-32 h-32 rounded-xl bg-blue-100 flex items-center justify-center mx-auto border-4 border-black shadow-lg">
                        <span class="text-3xl font-bold text-blue-600">${candidate.full_name?.charAt(0) || '?'}</span>
                    </div>
                `;
            } else {
                const initial = candidate.full_name?.charAt(0)?.toUpperCase() || '?';
                imgHtml = `
                    <div class="w-32 h-32 rounded-xl bg-blue-100 flex items-center justify-center mx-auto border-4 border-black shadow-lg">
                        <span class="text-3xl font-bold text-blue-600">${initial}</span>
                    </div>
                `;
            }

            document.getElementById('viewContent').innerHTML = `
                <div class="flex flex-col md:flex-row gap-8">
                    <!-- Left Sidebar: Profile & Contact -->
                    <div class="w-full md:w-1/3 flex flex-col items-center text-center md:border-r border-gray-100 md:pr-6">
                        ${imgHtml}
                        <h2 class="text-xl font-bold text-gray-900 mt-4">${candidate.full_name || 'N/A'}</h2>
                        <p class="text-indigo-600 font-medium">${candidate.position || 'N/A'}</p>
                        
                        <div class="mt-3 mb-6">
                             <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold uppercase tracking-wide border border-green-200">
                                ${candidate.status || 'new'}
                            </span>
                        </div>

                        <div class="w-full space-y-4 text-left border-t border-gray-100 pt-6">
                            <div class="flex items-start gap-3 text-sm group">
                                <div class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-500 shrink-0 group-hover:bg-indigo-100 transition-colors">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <span class="block text-xs text-gray-400 uppercase tracking-wider font-semibold">Email</span>
                                    <span class="text-gray-700 font-medium break-all" title="${candidate.email}">${candidate.email || 'N/A'}</span>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 text-sm group">
                                 <div class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-500 shrink-0 group-hover:bg-indigo-100 transition-colors">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-400 uppercase tracking-wider font-semibold">Contact</span>
                                    <span class="text-gray-700 font-medium">${candidate.contact_number || 'N/A'}</span>
                                </div>
                            </div>
                             <div class="flex items-start gap-3 text-sm group">
                                 <div class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-500 shrink-0 group-hover:bg-indigo-100 transition-colors">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-400 uppercase tracking-wider font-semibold">Address</span>
                                    <span class="text-gray-700 font-medium">${candidate.address || 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                    <!-- View Resume Button -->
                        ${candidate.resume_path ? `
                        <div class="mt-6 w-full pt-6 border-t border-gray-100">
                             <button onclick="showPinModalForResume()" 
                                class="flex items-center justify-center w-full px-4 py-3 bg-gray-900 text-white rounded-xl hover:bg-gray-800 transition-all shadow-md group cursor-pointer">
                                <i class="fas fa-lock mr-2 group-hover:scale-110 transition-transform text-yellow-400"></i>
                                <span class="font-semibold">View Resume</span>
                            </button>
                        </div>
                        ` : ''}

                    </div>

                    <!-- Right Content: Details -->
                    <div class="w-full md:w-2/3 space-y-8">
                        <!-- Professional Info -->
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <i class="fas fa-briefcase text-indigo-500"></i> Professional Details
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 hover:border-indigo-100 transition-colors">
                                    <span class="text-xs text-gray-500 block mb-1">Job Title</span>
                                    <span class="font-bold text-gray-800 text-sm block truncate" title="${candidate.job_title}">${candidate.job_title || 'N/A'}</span>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 hover:border-indigo-100 transition-colors">
                                    <span class="text-xs text-gray-500 block mb-1">Experience</span>
                                    <span class="font-bold text-gray-800 text-sm block">${candidate.experience_years || '0'} Years</span>
                                </div>
                                 <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 hover:border-indigo-100 transition-colors">
                                    <span class="text-xs text-gray-500 block mb-1">Source</span>
                                    <span class="font-bold text-gray-800 text-sm block truncate">${candidate.source || 'N/A'}</span>
                                </div>
                                 <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 hover:border-indigo-100 transition-colors">
                                    <span class="text-xs text-gray-500 block mb-1">Date Applied</span>
                                    <span class="font-bold text-gray-800 text-sm block">${new Date(candidate.created_at).toLocaleDateString()}</span>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 hover:border-indigo-100 transition-colors">
                                    <span class="text-xs text-gray-500 block mb-1">Age</span>
                                    <span class="font-bold text-gray-800 text-sm block">${candidate.age || 'N/A'}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Skills -->
                        <div>
                             <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <i class="fas fa-star text-indigo-500"></i> Skills
                            </h3>
                             <div class="flex flex-wrap gap-2">
                                ${(candidate.skills && candidate.skills !== 'N/A')
                    ? candidate.skills.split(',').map(skill => `<span class="px-3 py-1.5 bg-indigo-50 text-indigo-700 text-xs rounded-lg font-semibold border border-indigo-100 hover:bg-indigo-100 transition-colors">${skill.trim()}</span>`).join('')
                    : '<span class="text-gray-400 text-sm italic">No skills listed</span>'
                }
                             </div>
                        </div>

                        <!-- Notes -->
                        <div>
                             <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <i class="fas fa-sticky-note text-indigo-500"></i> Notes
                            </h3>
                             <div class="text-sm text-gray-600 bg-yellow-50 p-5 rounded-xl border border-yellow-100 relative">
                                <i class="fas fa-quote-left absolute top-4 left-4 text-yellow-200 text-xl opacity-50"></i>
                                <p class="pl-6 italic relative z-10">
                                    ${candidate.notes || 'No notes added.'}
                                </p>
                             </div>
                        </div>
                    </div>
                </div>
            `;
            openModal('viewModal');
        }

        // Face Detection & Cropping using face-api.js
        async function detectAndCropFace(existingImg) {
            if (existingImg.dataset.processed === 'true' || !existingImg.src) return;

            const previewText = document.getElementById('previewText');

            function showImageFallback(img) {
                img.className = "profile-image-preview block mx-auto";
                img.style.display = 'block';
                img.dataset.processed = 'true';

                const container = document.getElementById('imagePreviewContainer');
                container.classList.remove('border-dashed', 'border-2');
                container.classList.add('shadow-sm', 'border-gray-200');

                const statusDiv = document.getElementById('validationStatus');
                statusDiv.innerHTML = `
                    <div class="flex items-center justify-center text-blue-600 mb-2 w-full">
                        <i class="fas fa-image text-lg mr-2"></i>
                        <span class="font-bold">Image Extracted</span>
                    </div>
                `;
                previewText.textContent = 'Profile picture extracted!';
                previewText.className = "preview-text text-blue-600 font-semibold text-center mx-auto";

                const btn = document.getElementById('validateBtn');
                btn.innerHTML = '<i class="fas fa-check mr-2"></i> Profile Detected!';
                btn.className = "mt-3 w-full validation-success font-bold py-2 px-4 rounded-md border";
            }

            try {
                // Models loading from public CDN (Official weights)
                if (!window.faceApiModelsLoaded) {
                    previewText.textContent = 'Loading AI models...';
                    const MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
                    // Load both for flexibility
                    await Promise.all([
                        faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
                        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL)
                    ]);
                    window.faceApiModelsLoaded = true;
                }

                previewText.textContent = 'AI Scanning for face...';

                // Ensure image is loaded and has source
                if (!existingImg.complete) {
                    await new Promise(res => existingImg.onload = res);
                }

                // Try SSD Mobilenet First (Much more accurate for small faces)
                let detections = await faceapi.detectAllFaces(existingImg, new faceapi.SsdMobilenetv1Options({
                    minConfidence: 0.2 // Very sensitive
                }));

                // Fallback to TinyFaceDetector if SSD fails
                if (detections.length === 0) {
                    detections = await faceapi.detectAllFaces(existingImg, new faceapi.TinyFaceDetectorOptions({
                        inputSize: 608, // Increased for resolution
                        scoreThreshold: 0.2
                    }));
                }

                if (detections.length === 0) {
                    console.log("No face detected by AI");
                    showImageFallback(existingImg);
                } else {
                    // Find biggest face (often the profile picture)
                    let bestDet = detections[0];
                    detections.forEach(det => {
                        if (det.box.width > bestDet.box.width) bestDet = det;
                    });

                    const box = bestDet.box;
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');

                    // Professional Portrait Padding
                    const padX = box.width * 0.25;
                    const padTop = box.height * 0.45;
                    const padBottom = box.height * 0.65;

                    const x = Math.max(0, box.x - padX);
                    const y = Math.max(0, box.y - padTop);
                    const w = Math.min(existingImg.naturalWidth - x, box.width + padX * 2);
                    const h = Math.min(existingImg.naturalHeight - y, box.height + padTop + padBottom);

                    canvas.width = w;
                    canvas.height = h;

                    ctx.drawImage(existingImg, x, y, w, h, 0, 0, w, h);

                    const newData = canvas.toDataURL('image/jpeg', 0.92);

                    // Update UI
                    existingImg.src = newData;
                    existingImg.dataset.processed = 'true';
                    existingImg.className = "profile-image-preview block mx-auto";
                    existingImg.style.display = 'block';

                    previewText.textContent = 'AI Refined: Profile found!';
                    previewText.className = "preview-text text-green-600 font-semibold text-center mx-auto";

                    saveCroppedFace(newData);

                    const btn = document.getElementById('validateBtn');
                    btn.innerHTML = '<i class="fas fa-magic mr-2"></i> AI Detected & Refined!';
                    btn.className = "mt-3 w-full validation-success font-bold py-2 px-4 rounded-md border";
                }
            } catch (e) {
                console.error("Face API Error:", e);
                showImageFallback(existingImg);
            }
        }

        async function saveCroppedFace(data) {
            const fd = new FormData();
            fd.append('action', 'save_cropped_image');
            fd.append('image_data', data);
            try {
                const res = await fetch('', {
                    method: 'POST',
                    body: fd
                });
                const json = await res.json();
                if (json.success && json.path) {
                    document.getElementById('extractedImagePath').value = json.path;
                    const btn = document.getElementById('validateBtn');
                    btn.innerHTML = '<i class="fas fa-magic mr-2"></i> Face Detected & Refined!';
                    btn.className = "mt-3 w-full validation-success font-bold py-2 px-4 rounded-md border";
                }
            } catch (e) {
                console.error(e);
            }
        }

        function extractInfoFromRawText(text, filename = '') {
            const emailMatch = text.match(/([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z]{2,6})\b/);
            const phoneMatch = text.match(/(?:\+?63|0)[\d\-\s]{9,13}/);

            // Heuristic for Position (Look for common patterns)
            let position = null;
            const posMatch = text.match(/(?:Applying for|Position|Job Title|Desired Position)\s*[:]\s*([A-Za-z\s]+)/i);
            if (posMatch) {
                position = posMatch[1].trim();
            }

            let name = null;
            const cleanText = text.replace(/[*_]/g, '');

            // Strategy 1: Look for "Name: ..."
            const nameMatch = cleanText.match(/(?:Name|Candidate|Candidate Name)\s*[:]\s*([A-Za-z\s\.]+)/i);
            if (nameMatch) {
                name = nameMatch[1].trim();
            } else {
                const lines = cleanText.split(/\n+/);

                // Strategy 1.5: Look for "Signature" block
                for (let i = 0; i < lines.length; i++) {
                    if (/applicant'?s?\s*signature|signature\s*over\s*printed\s*name|signed\s*by/i.test(lines[i])) {
                        if (i > 0) {
                            let candidateLine = lines[i - 1].trim();
                            if (!candidateLine && i > 1) candidateLine = lines[i - 2].trim();
                            if (candidateLine && candidateLine.split(' ').length >= 2 && !/[0-9@]/.test(candidateLine)) {
                                if (candidateLine.length < 50) {
                                    name = candidateLine;
                                    break;
                                }
                            }
                        }
                    }
                }

                if (!name) {
                    // Strategy 2: First significant non-garbage line
                    let potentialMixedName = null;
                    for (let i = 0; i < Math.min(100, lines.length); i++) {
                        let line = lines[i].trim();
                        if (line.length < 3 || /[<>]/.test(line)) continue;

                        if (line.split(/\s+/).length <= 6 &&
                            !/^(resume|curriculum|vitae|candidate|work|experience|education|contact|profile|summary|objective|skills|references)$/i.test(line) &&
                            !/philippines|college|university|school|bachelor|city|street|science|engineer|manager|developer|analyst|industry|energy|banking|finance/i.test(line)) {

                            if (!/[0-9@]/.test(line) && line.split(' ').length >= 2) {
                                if (line === line.toUpperCase()) {
                                    name = line;
                                    break;
                                } else if (/^[A-Z][a-z]+/.test(line) && !potentialMixedName && i < 15) {
                                    potentialMixedName = line;
                                }
                            }
                        }
                    }
                    if (!name && potentialMixedName) name = potentialMixedName;
                }
            }

            // Fallback: Filename
            if (!name && filename) {
                let cleanFn = filename.replace(/\.[^/.]+$/, "").replace(/[_-]/g, ' ');
                if (!/\d/.test(cleanFn) && cleanFn.includes(' ')) {
                    name = cleanFn.replace(/\b\w/g, l => l.toUpperCase());
                }
            }

            return {
                name: name,
                email: emailMatch ? emailMatch[0] : null,
                phone: phoneMatch ? phoneMatch[0].replace(/[^0-9+]/g, '') : null,
                position: position
            };
        }

        async function analyzeImage(file) {
            try {
                // Tesseract might take a moment
                const { data } = await Tesseract.recognize(file, 'eng');

                // Advanced Strategy: Use Line Height (Font Size) if available
                if (data.lines && data.lines.length > 0) {
                    // Calculate Median Height
                    const heights = data.lines.map(line => {
                        if (line.bbox) return line.bbox.y1 - line.bbox.y0;
                        return 0;
                    }).filter(h => h > 5).sort((a, b) => a - b);

                    const medianHeight = heights[Math.floor(heights.length / 2)] || 10;
                    const threshold = medianHeight * 1.3; // Lower threshold to catch more potential headers

                    // Construct "Big Text" (likely headers/names)
                    const bigLines = data.lines.filter(line => {
                        const h = (line.bbox) ? (line.bbox.y1 - line.bbox.y0) : 0;
                        return h >= threshold;
                    }).map(l => l.text);

                    const bigText = bigLines.join('\n');
                    console.log("OCR Big Text:", bigLines);

                    const infoFromBig = extractInfoFromRawText(bigText, file.name);
                    if (infoFromBig.name) {
                        // Merge with other info from full text
                        const infoFromFull = extractInfoFromRawText(data.text, file.name);
                        return {
                            name: infoFromBig.name,
                            email: infoFromFull.email || infoFromBig.email,
                            phone: infoFromFull.phone || infoFromBig.phone
                        };
                    }
                }

                return extractInfoFromRawText(data.text, file.name);
            } catch (e) {
                console.error("OCR Failed:", e);
                return extractInfoFromRawText('', file.name); // Fallback to filename on error
            }
        }

        async function analyzePDF(file) {
            try {
                const arrayBuffer = await file.arrayBuffer();
                const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer });
                const pdf = await loadingTask.promise;
                const page = await pdf.getPage(1);
                const textContent = await page.getTextContent();

                const items = textContent.items.filter(item => item.str.trim().length > 0);
                if (items.length === 0) return null;

                // 1. Find Max Font Size
                let maxSz = 0;
                items.forEach(item => {
                    const sz = Math.abs(item.transform[3]);
                    if (sz > maxSz) maxSz = sz;
                });

                // 2. Extract Name (First block of largest text)
                let nameParts = [];
                for (let item of items) {
                    const sz = Math.abs(item.transform[3]);
                    if (sz >= maxSz * 0.9) {
                        nameParts.push(item.str);
                    } else {
                        if (nameParts.length > 0) break;
                    }
                }

                let name = nameParts.join(' ').replace(/\s+/g, ' ').trim();
                name = name.replace(/^(Curriculum Vitae|Resume|CV|Bio|Candidate Profile|Candidate)\s*/i, '').trim();

                // 3. Fallback: Regex or Top Line Check if heuristic Name is bad
                let fullText = items.map(i => i.str).join(' ');

                if (name.length < 3 || /resume|curriculum|vitae|candidate/i.test(name)) {
                    // Try finding "Name: ..."
                    const nameMatch = fullText.match(/(?:Name|Candidate|Candidate Name)\s*[:]\s*([A-Za-z\s\.]+)/i);
                    if (nameMatch) {
                        name = nameMatch[1].trim();
                    } else if (items.length > 0) {
                        // Assume first significant text is name if it looks like a name (2+ words)
                        // Prioritize ALL CAPS names if available in top 5 items
                        let foundName = false;
                        for (let i = 0; i < Math.min(20, items.length); i++) {
                            let txt = items[i].str.trim();
                            if (txt.length > 3 && !/resume|curriculum|vitae|candidate/i.test(txt)) {
                                if (txt === txt.toUpperCase() && txt.split(' ').length >= 2) {
                                    name = txt;
                                    foundName = true;
                                    break;
                                }
                            }
                        }

                        if (!foundName) {
                            let possibleName = items[0].str.trim();
                            if (!/resume|curriculum|vitae|candidate/i.test(possibleName) && possibleName.split(' ').length >= 2) {
                                name = possibleName;
                            }
                        }
                    }
                }

                if (!name) {
                    // Last Resort: Filename
                    const fallback = extractInfoFromRawText('', file.name);
                    if (fallback.name) name = fallback.name;
                }

                const emailMatch = fullText.match(/([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z]{2,6})\b/);
                const phoneMatch = fullText.match(/(?:\+?63|0)[\d\-\s]{9,13}/);

                return {
                    name: name,
                    email: emailMatch ? emailMatch[0] : null,
                    phone: phoneMatch ? phoneMatch[0].replace(/[^0-9+]/g, '') : null
                };

            } catch (e) {
                console.error("PDF Parsing Error:", e);
                return extractInfoFromRawText('', file.name); // Fallback to filename
            }
        }
    </script>
    <!-- PIN Verification Modal -->
    <div id="pinModal"
        class="hidden fixed inset-0 z-[70] flex items-center justify-center p-4 bg-gray-900 bg-opacity-80 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all scale-100">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-center">
                <div id="pinModalImage"
                    class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4 backdrop-blur-sm overflow-hidden border-2 border-white/30">
                    <i class="fas fa-shield-alt text-3xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white">Security Check</h3>
                <p class="text-blue-100 text-sm mt-1">Enter your 4-digit PIN to access this profile</p>
            </div>

            <div class="p-6">
                <div class="mb-6">
                    <div class="flex justify-center gap-3" id="pinInputs">
                        <input type="password" maxlength="1"
                            class="pin-digit w-12 h-12 text-center text-2xl font-bold border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 outline-none transition-all"
                            autofocus>
                        <input type="password" maxlength="1"
                            class="pin-digit w-12 h-12 text-center text-2xl font-bold border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 outline-none transition-all">
                        <input type="password" maxlength="1"
                            class="pin-digit w-12 h-12 text-center text-2xl font-bold border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 outline-none transition-all">
                        <input type="password" maxlength="1"
                            class="pin-digit w-12 h-12 text-center text-2xl font-bold border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 outline-none transition-all">
                    </div>
                    <p id="pinError" class="text-red-500 text-center text-sm mt-3 font-medium hidden">
                        <i class="fas fa-exclamation-circle mr-1"></i> Incorrect PIN
                    </p>
                </div>

                <div class="flex gap-3">
                    <button onclick="closePinModal()"
                        class="flex-1 py-2.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 font-medium transition-colors">
                        Cancel
                    </button>
                    <button onclick="verifyPin()" id="verifyPinBtn" disabled
                        class="flex-1 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium transition-colors shadow-lg shadow-blue-600/30 disabled:opacity-50 disabled:cursor-not-allowed">
                        Verify
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let pendingCandidate = null;

        let currentOpenCandidate = null;
        let pinAction = 'view_profile'; // 'view_profile' or 'view_resume'

        function updatePinModalImage(candidate) {
            const imgContainer = document.getElementById('pinModalImage');
            let imageSrc = null;

            if (candidate && candidate.extracted_image_path) {
                let imagePath = candidate.extracted_image_path;
                if (imagePath.indexOf('uploads/') === -1) {
                    imagePath = 'uploads/' + imagePath.replace(/^\/+/, '');
                }
                imageSrc = imagePath;
            }

            if (imageSrc) {
                imgContainer.innerHTML = `<img src="${imageSrc + '?t=' + Date.now()}" class="w-full h-full object-cover">`;
            } else {
                const initial = candidate?.full_name?.charAt(0)?.toUpperCase() || '?';
                imgContainer.innerHTML = `<div class="w-full h-full bg-blue-500/20 flex items-center justify-center"><span class="text-3xl font-bold text-white">${initial}</span></div>`;
            }
        }

        function showPinModalForCandidate(candidate) {
            pendingCandidate = candidate;
            pinAction = 'view_profile';
            document.getElementById('pinModal').classList.remove('hidden');

            updatePinModalImage(candidate);

            // Focus first input
            setTimeout(() => {
                const firstInput = document.querySelector('.pin-digit');
                if (firstInput) firstInput.focus();
            }, 100);

            // Reset state
            document.querySelectorAll('.pin-digit').forEach(input => input.value = '');
            document.getElementById('pinError').classList.add('hidden');
            document.getElementById('verifyPinBtn').disabled = true;
        }

        function showPinModalForResume() {
            if (!currentOpenCandidate) return;
            pendingCandidate = currentOpenCandidate; // Set for image display
            pinAction = 'view_resume';
            document.getElementById('pinModal').classList.remove('hidden');

            updatePinModalImage(currentOpenCandidate);

            // Focus first input
            setTimeout(() => {
                const firstInput = document.querySelector('.pin-digit');
                if (firstInput) firstInput.focus();
            }, 100);

            // Reset state
            document.querySelectorAll('.pin-digit').forEach(input => input.value = '');
            document.getElementById('pinError').classList.add('hidden');
            document.getElementById('verifyPinBtn').disabled = true;
        }

        function closePinModal() {
            document.getElementById('pinModal').classList.add('hidden');
            pendingCandidate = null;
        }

        // PIN Input Logic
        const pinInputs = document.querySelectorAll('.pin-digit');
        pinInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                if (e.target.value.length === 1) {
                    if (index < pinInputs.length - 1) pinInputs[index + 1].focus();
                }
                checkPinComplete();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    pinInputs[index - 1].focus();
                }
                if (e.key === 'Enter') {
                    verifyPin();
                }
            });
        });

        function checkPinComplete() {
            const pin = Array.from(pinInputs).map(i => i.value).join('');
            document.getElementById('verifyPinBtn').disabled = pin.length !== 4;
        }

        async function verifyPin() {
            const pin = Array.from(pinInputs).map(i => i.value).join('');
            if (pin.length !== 4) return;

            const btn = document.getElementById('verifyPinBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'check_pin');
            formData.append('pin', pin);

            try {
                const res = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    const candidateRef = pendingCandidate;
                    const actionRef = pinAction;

                    closePinModal();

                    if (actionRef === 'view_profile' && candidateRef) {
                        viewCandidate(candidateRef);
                    } else if (actionRef === 'view_resume' && candidateRef) {
                        if (candidateRef.resume_path) {
                            window.open(candidateRef.resume_path, '_blank');
                        }
                    }
                } else {
                    document.getElementById('pinError').classList.remove('hidden');
                    document.getElementById('pinError').innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> Incorrect PIN';
                    // Shake effect
                    const modal = document.querySelector('#pinModal > div');
                    modal.classList.add('animate-shake');
                    setTimeout(() => modal.classList.remove('animate-shake'), 500);

                    // Clear inputs
                    pinInputs.forEach(i => i.value = '');
                    pinInputs[0].focus();
                }
            } catch (e) {
                console.error(e);
                document.getElementById('pinError').classList.remove('hidden');
                document.getElementById('pinError').innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> ' + (e.message || 'Verification failed');
                
                const modal = document.querySelector('#pinModal > div');
                modal.classList.add('animate-shake');
                setTimeout(() => modal.classList.remove('animate-shake'), 500);
            } finally {
                btn.innerHTML = 'Verify';
                btn.disabled = false;
            }
        }
    </script>
    <style>
        .animate-shake {
            animation: shake 0.5s cubic-bezier(.36, .07, .19, .97) both;
        }

        @keyframes shake {

            10%,
            90% {
                transform: translate3d(-1px, 0, 0);
            }

            20%,
            80% {
                transform: translate3d(2px, 0, 0);
            }

            30%,
            50%,
            70% {
                transform: translate3d(-4px, 0, 0);
            }

            40%,
            60% {
                transform: translate3d(4px, 0, 0);
            }
        }
    </style>
</body>

</html>