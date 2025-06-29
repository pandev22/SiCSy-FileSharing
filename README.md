# 🔗 Module FileSharing pour SICSY

Module de partage de fichiers avec durée limitée et protection par mot de passe.

## ⚠️Ce code fonctionne seulement avec le thème [**DarkModern**](https://github.com/pandev22/SiCSy-DarkModern)

## 🚀 Installation rapide

### 1. Installation automatique
1. **Téléchargez le module** dans `modules/FileSharing/`
2. **Allez sur** : `http://votre-site.com/sicsy/modules/FileSharing/debug.php`
3. **Supprimez debug.php** une fois l'installation terminée
4. **Activez le module** dans Admin > Gérer les modules

### 2. Ajout de code

Pour que FileSharing fonctionne correctement, il est nécessaire d’ajouter le code suivant à la toute fin du fichier **cloud_script.js** (`main/cloud_script.js`)

```
    getFiles(Sparent)

    if (typeof fileSharingActive !== 'undefined' && fileSharingActive) {
        const script = document.createElement('script');
        script.src = './modules/FileSharing/filesharing.js';
        document.head.appendChild(script);
    }
```

Ajoute le code suivant dans le fichier **index.php**  juste avant la balise de fermeture `</html>`  afin de faire apparaître le bouton.

```
<script>
    var Sparent = "<?php echo $_SESSION['parent']; ?>";
    var fileSharingActive = <?php echo json_encode($fileShareActive); ?>;
</script>
```

Ce code permet de charger correctement le fichier **filesharing.js** indispensable au bon fonctionnement du module FileSharing.

## ⚠️Ce code fonctionne seulement avec le thème [**DarkModern**](https://github.com/pandev22/SiCSy-DarkModern)

---

**Module FileSharing** - Installation simple, utilisation intuitive ! 🔗 
