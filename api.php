<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

error_log("🔗 FileSharing API - Action: " . ($_GET['action'] ?? 'none') . " - Method: " . $_SERVER['REQUEST_METHOD']);

$public_actions = ['get_user_files', 'view', 'download'];

$action = $_GET['action'] ?? '';
$is_public = in_array($action, $public_actions);

if (!$is_public) {
    session_start();
    if (!isset($_SESSION['username'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Session non valide']);
        exit;
    }
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=sicsy;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("🔗 FileSharing API - Erreur DB: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de connexion à la base de données']);
    exit;
}

function getUserFiles($pdo, $parent = '/') {
    error_log("🔗 FileSharing API - getUserFiles appelée pour parent: $parent");
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM files WHERE type = 'files' ORDER BY name");
        $stmt->execute();
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("🔗 FileSharing API - Fichiers trouvés: " . count($files));
        
        if (empty($files)) {
            return ['content' => 'empty'];
        }
        
        $cleanFiles = [];
        foreach ($files as $file) {
            error_log("🔗 FileSharing API - Vérification fichier: " . $file['name'] . " dans " . $file['parent']);
            
            if (!empty($file['name']) && $file['name'] !== 'on' && $file['name'] !== 'Fichier non trouvé') {
                error_log("🔗 FileSharing API - Fichier accepté: " . $file['name']);
                $cleanFiles[] = [
                    'name' => $file['name'],
                    'parent' => $file['parent'],
                    'type' => 'files',
                    'size' => $file['size'] ?? 0
                ];
            } else {
                error_log("🔗 FileSharing API - Fichier ignoré (nom invalide): " . $file['name']);
            }
        }
        
        error_log("🔗 FileSharing API - Fichiers valides après filtrage: " . count($cleanFiles));
        
        if (empty($cleanFiles)) {
            return ['content' => 'empty'];
        }
        
        return ['content' => $cleanFiles];
    } catch (PDOException $e) {
        error_log("🔗 FileSharing API - Erreur getUserFiles: " . $e->getMessage());
        return ['error' => 'Erreur lors de la récupération des fichiers'];
    }
}

switch ($action) {
    case 'get_user_files':
        error_log("🔗 FileSharing API - Action get_user_files");
        $parent = $_GET['parent'] ?? '/';
        error_log("🔗 FileSharing API - Parent demandé: $parent");
        $result = getUserFiles($pdo, $parent);
        error_log("🔗 FileSharing API - Résultat: " . json_encode($result));
        echo json_encode($result);
        break;
        
    case 'create':
        error_log("🔗 FileSharing API - Action create");
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        error_log("🔗 FileSharing API - Données reçues: " . json_encode($input));
        
        if (!$input) {
            echo json_encode(['error' => 'Données invalides']);
            break;
        }
        
        $files = $input['files'] ?? [];
        $duration = $input['duration'] ?? 7;
        $maxDownloads = $input['maxDownloads'] ?? 0;
        $password = $input['password'] ?? null;
        
        if (!empty($password)) {
            $password = password_hash($password, PASSWORD_DEFAULT);
        }
        
        if (empty($files)) {
            echo json_encode(['error' => 'Aucun fichier sélectionné']);
            break;
        }
        
        try {
            $shareId = bin2hex(random_bytes(16));
            
            $expiresAt = $duration > 0 ? date('Y-m-d H:i:s', strtotime("+$duration days")) : null;
            
            $stmt = $pdo->prepare("
                INSERT INTO file_shares (share_id, user_id, files, duration, expires_at, max_downloads, password, created_at, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 1)
            ");
            
            $filesJson = json_encode($files);
            $stmt->execute([$shareId, $_SESSION['username'], $filesJson, $duration, $expiresAt, $maxDownloads, $password]);
            
            $shareUrl = "http://" . $_SERVER['HTTP_HOST'] . "/sicsy/modules/FileSharing/view.php?id=" . $shareId;
            
            try {
                $logStmt = $pdo->prepare("INSERT INTO logs (IP, path, content, type, user) VALUES (?, ?, ?, ?, ?)");
                $ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
                $path = '/FileSharing';
                $content = 'Création partage: ' . implode(', ', array_map('basename', $files)) . ' (ID: ' . $shareId . ')';
                $type = 'createShare';
                $user = $_SESSION['username'];
                
                $logStmt->execute([$ip, $path, $content, $type, $user]);
                error_log("🔗 FileSharing API - Log créé pour le partage: $shareId");
            } catch (PDOException $e) {
                error_log("🔗 FileSharing API - Erreur création log: " . $e->getMessage());
            }
            
            error_log("🔗 FileSharing API - Partage créé: $shareId");
            echo json_encode([
                'success' => true,
                'share_id' => $shareId,
                'share_url' => $shareUrl
            ]);
            
        } catch (PDOException $e) {
            error_log("🔗 FileSharing API - Erreur création partage: " . $e->getMessage());
            echo json_encode(['error' => 'Erreur lors de la création du partage']);
        }
        break;
        
    case 'view':
        error_log("🔗 FileSharing API - Action view");
        $shareId = $_GET['id'] ?? '';
        
        if (empty($shareId)) {
            http_response_code(404);
            echo json_encode(['error' => 'Partage non trouvé']);
            break;
        }
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM file_shares 
                WHERE share_id = ? 
                AND (expires_at IS NULL OR expires_at > NOW())
                AND (max_downloads = 0 OR downloads_count < max_downloads)
                AND is_active = 1
            ");
            $stmt->execute([$shareId]);
            $share = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$share) {
                http_response_code(404);
                echo json_encode(['error' => 'Partage expiré ou non trouvé']);
                break;
            }
            
            $files = json_decode($share['files'], true);
            echo json_encode([
                'share' => $share,
                'files' => $files,
                'has_password' => !empty($share['password'])
            ]);
            
        } catch (PDOException $e) {
            error_log("🔗 FileSharing API - Erreur view: " . $e->getMessage());
            echo json_encode(['error' => 'Erreur lors de la récupération du partage']);
        }
        break;
        
    case 'download':
        error_log("🔗 FileSharing API - Action download");
        $shareId = $_GET['id'] ?? '';
        $fileName = $_GET['file'] ?? '';
        
        if (empty($shareId) || empty($fileName)) {
            http_response_code(400);
            echo json_encode(['error' => 'Paramètres manquants']);
            break;
        }
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM file_shares 
                WHERE share_id = ? 
                AND (expires_at IS NULL OR expires_at > NOW())
                AND (max_downloads = 0 OR downloads_count < max_downloads)
                AND is_active = 1
            ");
            $stmt->execute([$shareId]);
            $share = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$share) {
                http_response_code(404);
                echo json_encode(['error' => 'Partage expiré ou non trouvé']);
                break;
            }
            
            $files = json_decode($share['files'], true);
            if (!in_array($fileName, $files)) {
                http_response_code(403);
                echo json_encode(['error' => 'Fichier non autorisé']);
                break;
            }
            
            $stmt = $pdo->prepare("UPDATE file_shares SET downloads_count = downloads_count + 1 WHERE share_id = ?");
            $stmt->execute([$shareId]);
            
            try {
                $logStmt = $pdo->prepare("INSERT INTO logs (IP, path, content, type, user) VALUES (?, ?, ?, ?, ?)");
                $ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
                $path = '/FileSharing';
                $content = 'Téléchargement: ' . basename($fileName) . ' (Partage: ' . $shareId . ')';
                $type = 'downloadShare';
                $user = $share['user_id']; // Utilisateur qui a créé le partage
                
                $logStmt->execute([$ip, $path, $content, $type, $user]);
                error_log("🔗 FileSharing API - Log créé pour le téléchargement: " . basename($fileName));
            } catch (PDOException $e) {
                error_log("🔗 FileSharing API - Erreur création log téléchargement: " . $e->getMessage());
            }
            
            $fileName = basename($fileName);
            $parent = dirname($fileName);
            if ($parent === '.') $parent = '/';
            
            $parent = str_replace('\\', '/', $parent);
            
            $stmt = $pdo->prepare("SELECT * FROM files WHERE name = ? AND parent = ?");
            $stmt->execute([$fileName, $parent]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$file) {
                http_response_code(404);
                echo json_encode(['error' => 'Fichier non trouvé']);
                break;
            }
            
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
            header('Content-Length: ' . strlen($file['content']));
            echo $file['content'];
            
        } catch (PDOException $e) {
            error_log("🔗 FileSharing API - Erreur download: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors du téléchargement']);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Action non reconnue']);
        break;
}
?> 
