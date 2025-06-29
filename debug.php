<?php
session_start();

function displaySection($title, $content, $status = 'info') {
    $statusClass = $status === 'success' ? 'success' : ($status === 'error' ? 'error' : 'info');
    echo "<div class='section $statusClass'>";
    echo "<h3>$title</h3>";
    echo "<div class='content'>$content</div>";
    echo "</div>";
}

function testDatabase() {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=sicsy;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return ['success' => true, 'message' => 'Connexion réussie'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
    }
}

function checkFileSharesTable($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'file_shares'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            return ['exists' => false, 'message' => 'Table file_shares n\'existe pas'];
        }
        
        $stmt = $pdo->query("DESCRIBE file_shares");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $structure = "<table><tr><th>Colonne</th><th>Type</th><th>Null</th></tr>";
        foreach ($columns as $column) {
            $structure .= "<tr><td>{$column['Field']}</td><td>{$column['Type']}</td><td>{$column['Null']}</td></tr>";
        }
        $structure .= "</table>";
        
        return ['exists' => true, 'structure' => $structure];
    } catch (PDOException $e) {
        return ['exists' => false, 'message' => 'Erreur: ' . $e->getMessage()];
    }
}

function installTable($pdo) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS file_shares (
            id INT AUTO_INCREMENT PRIMARY KEY,
            share_id VARCHAR(32) NOT NULL UNIQUE,
            user_id VARCHAR(200) NOT NULL,
            files TEXT NOT NULL,
            duration INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            downloads_count INT DEFAULT 0,
            max_downloads INT DEFAULT 0,
            password VARCHAR(255),
            is_active TINYINT(1) DEFAULT 1
        )";
        
        $pdo->exec($sql);
        return ['success' => true, 'message' => 'Table file_shares créée avec succès'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
    }
}

function testCreateShare($pdo) {
    try {
        $shareId = bin2hex(random_bytes(16));
        $testFiles = json_encode(['/test-file.txt']);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));
        
        $stmt = $pdo->prepare("
            INSERT INTO file_shares (share_id, user_id, files, duration, expires_at, max_downloads, password, created_at, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 1)
        ");
        
        $stmt->execute([$shareId, 'test_user', $testFiles, 1, $expiresAt, 10, null]);
        
        $stmt = $pdo->prepare("DELETE FROM file_shares WHERE share_id = ?");
        $stmt->execute([$shareId]);
        
        return ['success' => true, 'message' => 'Test de création de partage réussi'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
    }
}

$action = $_GET['action'] ?? '';
$dbResult = testDatabase();

if ($action === 'install' && $dbResult['success']) {
    $pdo = new PDO('mysql:host=localhost;dbname=sicsy;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $installResult = installTable($pdo);
}

if ($action === 'test' && $dbResult['success']) {
    $pdo = new PDO('mysql:host=localhost;dbname=sicsy;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $testResult = testCreateShare($pdo);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module FileSharing - Debug & Installation</title>
    <style>
        :root {
            --bg-color: #1a1a1a;
            --surface-color: #2d2d2d;
            --border-color: #404040;
            --font-color: #ffffff;
            --accent-color: #007bff;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
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
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background-color: var(--surface-color);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .header h1 {
            color: var(--accent-color);
            margin-bottom: 10px;
        }

        .steps {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }

        .section {
            background-color: var(--surface-color);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border-color);
        }

        .section h3 {
            margin-bottom: 15px;
            color: var(--accent-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section.success {
            border-color: var(--success-color);
        }

        .section.error {
            border-color: var(--danger-color);
        }

        .section.warning {
            border-color: var(--warning-color);
        }

        .content {
            margin-bottom: 15px;
        }

        .content pre {
            background-color: var(--bg-color);
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.9rem;
        }

        .content table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .content table th,
        .content table td {
            padding: 8px;
            text-align: left;
            border: 1px solid var(--border-color);
        }

        .content table th {
            background-color: var(--bg-color);
            font-weight: bold;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--accent-color);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 5px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .btn.success {
            background-color: var(--success-color);
        }

        .btn.danger {
            background-color: var(--danger-color);
        }

        .btn.warning {
            background-color: var(--warning-color);
            color: #000;
        }

        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status.success {
            background-color: var(--success-color);
            color: white;
        }

        .status.error {
            background-color: var(--danger-color);
            color: white;
        }

        .status.warning {
            background-color: var(--warning-color);
            color: #000;
        }

        .icon {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔗 Module FileSharing - Debug & Installation</h1>
            <p>Page de diagnostic et d'installation du module de partage de fichiers</p>
        </div>

        <div class="steps">
            <!-- Étape 1: Connexion Base de Données -->
            <div class="section <?php echo $dbResult['success'] ? 'success' : 'error'; ?>">
                <h3>
                    <span class="icon"><?php echo $dbResult['success'] ? '✅' : '❌'; ?></span>
                    Étape 1: Connexion Base de Données
                </h3>
                <div class="content">
                    <p><strong>Statut:</strong> 
                        <span class="status <?php echo $dbResult['success'] ? 'success' : 'error'; ?>">
                            <?php echo $dbResult['success'] ? 'SUCCÈS' : 'ÉCHEC'; ?>
                        </span>
                    </p>
                    <p><?php echo $dbResult['message']; ?></p>
                </div>
            </div>

            <?php if ($dbResult['success']): ?>
                <?php
                $pdo = new PDO('mysql:host=localhost;dbname=sicsy;charset=utf8', 'root', '');
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $tableCheck = checkFileSharesTable($pdo);
                ?>

                <!-- Étape 2: Vérification Table file_shares -->
                <div class="section <?php echo $tableCheck['exists'] ? 'success' : 'warning'; ?>">
                    <h3>
                        <span class="icon"><?php echo $tableCheck['exists'] ? '✅' : '⚠️'; ?></span>
                        Étape 2: Table file_shares
                    </h3>
                    <div class="content">
                        <p><strong>Statut:</strong> 
                            <span class="status <?php echo $tableCheck['exists'] ? 'success' : 'warning'; ?>">
                                <?php echo $tableCheck['exists'] ? 'EXISTE' : 'MANQUANTE'; ?>
                            </span>
                        </p>
                        
                        <?php if ($tableCheck['exists']): ?>
                            <p>La table file_shares existe et a la bonne structure.</p>
                            <?php echo $tableCheck['structure']; ?>
                        <?php else: ?>
                            <p><?php echo $tableCheck['message']; ?></p>
                            <a href="?action=install" class="btn success">🔧 Installer la table</a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($installResult)): ?>
                    <div class="section <?php echo $installResult['success'] ? 'success' : 'error'; ?>">
                        <h3>
                            <span class="icon"><?php echo $installResult['success'] ? '✅' : '❌'; ?></span>
                            Installation Table
                        </h3>
                        <div class="content">
                            <p><?php echo $installResult['message']; ?></p>
                            <?php if ($installResult['success']): ?>
                                <a href="?" class="btn">🔄 Recharger la page</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Étape 3: Test de Fonctionnalité -->
                <div class="section info">
                    <h3>
                        <span class="icon">🧪</span>
                        Étape 3: Test de Fonctionnalité
                    </h3>
                    <div class="content">
                        <p>Testez la création d'un partage de test pour vérifier que tout fonctionne.</p>
                        <a href="?action=test" class="btn warning">🧪 Lancer le test</a>
                    </div>
                </div>

                <?php if (isset($testResult)): ?>
                    <div class="section <?php echo $testResult['success'] ? 'success' : 'error'; ?>">
                        <h3>
                            <span class="icon"><?php echo $testResult['success'] ? '✅' : '❌'; ?></span>
                            Résultat du Test
                        </h3>
                        <div class="content">
                            <p><?php echo $testResult['message']; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Étape 4: Debug des Partages -->
                <div class="section info">
                    <h3>
                        <span class="icon">🔍</span>
                        Étape 4: Debug des Partages
                    </h3>
                    <div class="content">
                        <?php
                        if ($tableCheck['exists']) {
                            try {
                                $stmt = $pdo->query("SELECT * FROM file_shares ORDER BY created_at DESC LIMIT 5");
                                $shares = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (count($shares) > 0) {
                                    echo "<p><strong>Derniers partages:</strong></p>";
                                    echo "<table>";
                                    echo "<tr><th>ID</th><th>Utilisateur</th><th>Fichiers</th><th>Expire</th><th>Downloads</th></tr>";
                                    
                                    foreach ($shares as $share) {
                                        $files = json_decode($share['files'], true);
                                        $fileCount = count($files);
                                        $expires = date('d/m/Y H:i', strtotime($share['expires_at']));
                                        
                                        echo "<tr>";
                                        echo "<td>" . substr($share['share_id'], 0, 8) . "...</td>";
                                        echo "<td>{$share['user_id']}</td>";
                                        echo "<td>$fileCount fichier(s)</td>";
                                        echo "<td>$expires</td>";
                                        echo "<td>{$share['downloads_count']}/{$share['max_downloads']}</td>";
                                        echo "</tr>";
                                    }
                                    echo "</table>";
                                } else {
                                    echo "<p>Aucun partage trouvé.</p>";
                                }
                            } catch (PDOException $e) {
                                echo "<p class='error'>Erreur: " . $e->getMessage() . "</p>";
                            }
                        }
                        ?>
                    </div>
                </div>

                <!-- Étape 5: Vérification des Fichiers -->
                <div class="section info">
                    <h3>
                        <span class="icon">📁</span>
                        Étape 5: Vérification des Fichiers
                    </h3>
                    <div class="content">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM files");
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            echo "<p><strong>Nombre de fichiers dans la base:</strong> {$result['count']}</p>";
                            
                            if ($result['count'] > 0) {
                                $stmt = $pdo->query("SELECT * FROM files LIMIT 5");
                                $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                echo "<table>";
                                echo "<tr><th>Nom</th><th>Parent</th><th>Type</th><th>Taille</th></tr>";
                                
                                foreach ($files as $file) {
                                    $size = strlen($file['content']);
                                    echo "<tr>";
                                    echo "<td>{$file['name']}</td>";
                                    echo "<td>{$file['parent']}</td>";
                                    echo "<td>{$file['type']}</td>";
                                    echo "<td>" . number_format($size) . " octets</td>";
                                    echo "</tr>";
                                }
                                echo "</table>";
                            }
                        } catch (PDOException $e) {
                            echo "<p class='error'>Erreur: " . $e->getMessage() . "</p>";
                        }
                        ?>
                    </div>
                </div>

            <?php endif; ?>
        </div>

        <div class="section info">
            <h3>
                <span class="icon">🔗</span>
                Liens Utiles
            </h3>
            <div class="content">
                <a href="../index.php" class="btn">🏠 Retour à l'accueil</a>
                <a href="view.php?id=test" class="btn">👁️ Tester une page de partage</a>
                <a href="api.php?action=get_user_files&parent=/" class="btn">📋 Tester l'API</a>
            </div>
        </div>
    </div>
</body>
</html>