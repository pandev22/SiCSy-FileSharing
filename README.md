# 🔗 Module FileSharing pour SICSY

Module de partage de fichiers avec durée limitée et protection par mot de passe.

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

Ce code permet de charger correctement le fichier **filesharing.js** indispensable au bon fonctionnement du module FileSharing.

## 📋 Fonctionnalités

- ✅ Partage de fichiers avec lien unique
- ✅ Durée limitée (1 jour à 3 mois)
- ✅ Protection par mot de passe (optionnel)
- ✅ Limite de téléchargements
- ✅ Interface moderne avec thème DarkModern
- ✅ Intégration automatique

## 🎯 Utilisation

1. **Cliquez sur "🔗 Partager un fichier"** dans l'interface SICSY
2. **Sélectionnez les fichiers** à partager
3. **Configurez** : durée, téléchargements max, mot de passe
4. **Créez le partage** et copiez le lien

## 🐛 Problèmes courants

- **Erreur 403** : Vérifiez que le module est activé
- **Aucun fichier** : Vérifiez les logs PHP
- **Lien invalide** : Vérifiez la base de données

## 🔒 Sécurité

- Sessions validées
- Mots de passe hashés
- Validation des données
- Protection CSRF

---

**Module FileSharing** - Installation simple, utilisation intuitive ! 🔗 