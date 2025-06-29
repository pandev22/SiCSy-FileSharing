<?php
session_start();

error_log("🔗 FileSharing View - Début du script");
error_log("🔗 FileSharing View - Share ID: " . ($_GET['id'] ?? 'none'));

try {
    $pdo = new PDO('mysql:host=localhost;dbname=sicsy;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log("🔗 FileSharing View - Connexion DB réussie");
} catch (PDOException $e) {
    error_log("🔗 FileSharing View - Erreur DB: " . $e->getMessage());
    die('Erreur de connexion à la base de données');
}

$shareId = $_GET['id'] ?? '';

if (empty($shareId)) {
    error_log("🔗 FileSharing View - Share ID manquant");
    http_response_code(404);
    die('Partage non trouvé');
}

try {
    error_log("🔗 FileSharing View - Recherche du partage: $shareId");
    
    $stmt = $pdo->prepare("
        SELECT * FROM file_shares 
        WHERE share_id = ? 
        AND (expires_at IS NULL OR expires_at > NOW())
        AND (max_downloads = 0 OR downloads_count < max_downloads)
        AND is_active = 1
    ");
    $stmt->execute([$shareId]);
    $share = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("🔗 FileSharing View - Résultat requête: " . ($share ? "trouvé" : "non trouvé"));
    
    if (!$share) {
        error_log("🔗 FileSharing View - Partage non trouvé ou expiré");
        http_response_code(404);
        die('Partage expiré ou non trouvé');
    }
    
    error_log("🔗 FileSharing View - Partage trouvé, décodage des fichiers");
    $files = json_decode($share['files'], true);
    $hasPassword = !empty($share['password']);
    
    error_log("🔗 FileSharing View - Fichiers: " . json_encode($files));
    error_log("🔗 FileSharing View - Mot de passe: " . ($hasPassword ? "oui" : "non"));
    
} catch (PDOException $e) {
    error_log("🔗 FileSharing View - Erreur SQL: " . $e->getMessage());
    die('Erreur lors de la récupération du partage');
}

$passwordVerified = false;
if ($hasPassword) {
    if (isset($_POST['password'])) {
        if (password_verify($_POST['password'], $share['password'])) {
            $passwordVerified = true;
        } else {
            $passwordError = 'Mot de passe incorrect';
        }
    }
} else {
    $passwordVerified = true;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partage de fichiers - SICSY</title>
    <style>
        :root {
            --bg-color: #1a1a1a;
            --surface-color: #2d2d2d;
            --border-color: #404040;
            --font-color: #ffffff;
            --accent-color: #007bff;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --text-muted: #6c757d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--font-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            flex: 1;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: var(--accent-color);
        }

        .header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .card {
            background-color: var(--surface-color);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }

        .file-list {
            list-style: none;
        }

        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background-color: var(--bg-color);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .file-info {
            flex: 1;
        }

        .file-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .file-size {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .download-btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .download-btn:hover {
            background-color: #0056b3;
        }

        .password-form {
            text-align: center;
        }

        .password-input {
            width: 100%;
            max-width: 300px;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background-color: var(--bg-color);
            color: var(--font-color);
            font-size: 1rem;
        }

        .password-btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }

        .password-btn:hover {
            background-color: #0056b3;
        }

        .error-message {
            color: var(--danger-color);
            margin: 10px 0;
            text-align: center;
        }

        .share-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            text-align: center;
            padding: 15px;
            background-color: var(--bg-color);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .info-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: var(--text-muted);
            border-top: 1px solid var(--border-color);
        }

        @media (max-width: 600px) {
            .container {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .file-item {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔗 Partage de fichiers</h1>
            <p>Fichiers partagés via SICSY</p>
        </div>

        <?php if ($hasPassword && !$passwordVerified): ?>
            <div class="card">
                <h2>🔒 Accès protégé par mot de passe</h2>
                <form method="POST" class="password-form">
                    <input type="password" name="password" placeholder="Entrez le mot de passe" class="password-input" required>
                    <button type="submit" class="password-btn">Déverrouiller</button>
                    <?php if (isset($passwordError)): ?>
                        <div class="error-message"><?php echo htmlspecialchars($passwordError); ?></div>
                    <?php endif; ?>
                </form>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="share-info">
                    <div class="info-item">
                        <div class="info-label">Fichiers partagés</div>
                        <div class="info-value"><?php echo count($files); ?></div>
                    </div>
                    <?php if ($share['expires_at']): ?>
                        <div class="info-item">
                            <div class="info-label">Expire le</div>
                            <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($share['expires_at'])); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($share['max_downloads'] > 0): ?>
                        <div class="info-item">
                            <div class="info-label">Téléchargements</div>
                            <div class="info-value"><?php echo $share['downloads_count']; ?> / <?php echo $share['max_downloads']; ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <h2>📁 Fichiers disponibles</h2>
                <ul class="file-list">
                    <?php foreach ($files as $filePath): ?>
                        <?php
                        $fileName = basename($filePath);
                        $parent = dirname($filePath);
                        if ($parent === '.') $parent = '/';
                        
                        $parent = str_replace('\\', '/', $parent);
                        
                        error_log("🔗 FileSharing View - Recherche fichier: nom='$fileName', parent='$parent'");
                        error_log("🔗 FileSharing View - Chemin original: '$filePath'");
                        
                        $stmt = $pdo->prepare("SELECT * FROM files WHERE name = ? AND parent = ?");
                        $stmt->execute([$fileName, $parent]);
                        $file = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        error_log("🔗 FileSharing View - Fichier trouvé: " . ($file ? "oui" : "non"));
                        if ($file) {
                            error_log("🔗 FileSharing View - DB parent: '{$file['parent']}', DB name: '{$file['name']}'");
                        }
                        ?>
                        <li class="file-item">
                            <div class="file-info">
                                <div class="file-name"><?php echo htmlspecialchars($fileName); ?></div>
                                <div class="file-size">
                                    <?php if ($file): ?>
                                        <?php echo number_format(strlen($file['content'])) . ' octets'; ?>
                                    <?php else: ?>
                                        Fichier non trouvé
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($file): ?>
                                <a href="./api.php?action=download&id=<?php echo urlencode($shareId); ?>&file=<?php echo urlencode($filePath); ?>" class="download-btn">
                                    📥 Télécharger
                                </a>
                            <?php else: ?>
                                <span style="color: var(--danger-color);">Indisponible</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>Powered by SICSY - Système de partage de fichiers sécurisé</p>
    </div>
</body>
</html> 