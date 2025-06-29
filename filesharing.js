(function() {
    'use strict';
    
    console.log('🔗 Module FileSharing - Chargement...');
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFileSharing);
    } else {
        initFileSharing();
    }
    
    function initFileSharing() {
        if (typeof fileSharingActive === 'undefined' || !fileSharingActive) {
            console.log('🔗 Module FileSharing - Désactivé');
            return;
        }
        
        console.log('🔗 Module FileSharing - Initialisation...');
        
        window.showShareFileDialog = showShareFileDialog;
        window.createShareDialog = createShareDialog;
        window.createFileShare = createFileShare;
        window.showShareResult = showShareResult;
        
        observeButtonsContainer();
        
        console.log('🔗 Module FileSharing - Initialisé avec succès');
    }
    
    function showShareFileDialog(directory) {
        console.log('🔗 showShareFileDialog appelée pour:', directory);
        
        fetch('./modules/FileSharing/api.php?action=get_user_files&parent=/')
        .then(response => {
            console.log('🔗 Réponse API FileSharing:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('🔗 Données FileSharing:', data);
            if (data.error || data.content === 'empty') {
                showAlert('Aucun fichier disponible pour le partage');
                return;
            }

            const files = data.content.filter(item => item.type !== 'folder');
            
            if (files.length === 0) {
                showAlert('Aucun fichier disponible pour le partage');
                return;
            }

            createShareDialog(files, directory);
        })
        .catch(error => {
            console.error('🔗 Erreur API FileSharing:', error);
            showAlert('Erreur lors de la récupération des fichiers');
        });
    }
    
    function createShareDialog(files, directory) {
        console.log('🔗 createShareDialog appelée avec', files.length, 'fichiers');
        
        const overlay = document.createElement('div');
        overlay.classList.add('overlay');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            backdrop-filter: blur(5px);
        `;

        const popup = document.createElement('div');
        popup.classList.add('popup');
        popup.style.cssText = `
            background: var(--surface-color);
            border-radius: 16px;
            padding: 32px;
            max-width: 800px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
            position: relative;
        `;

        const title = document.createElement('h2');
        title.textContent = '🔗 Partager des fichiers';
        title.style.cssText = `
            margin: 0 0 24px 0;
            color: var(--font-color);
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 16px;
        `;
        popup.appendChild(title);

        const fileLabel = document.createElement('label');
        fileLabel.textContent = 'Sélectionnez les fichiers à partager :';
        fileLabel.style.cssText = `
            display: block;
            margin-bottom: 16px;
            font-weight: 600;
            color: var(--font-color);
            font-size: 16px;
        `;
        popup.appendChild(fileLabel);

        const fileContainer = document.createElement('div');
        fileContainer.style.cssText = `
            max-height: 300px;
            overflow-y: auto;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            background: var(--surface-color);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        `;

        const selectAllContainer = document.createElement('div');
        selectAllContainer.style.cssText = `
            display: flex;
            align-items: center;
            padding: 12px;
            margin-bottom: 12px;
            background: var(--accent-color);
            border-radius: 8px;
            color: white;
            font-weight: 600;
        `;

        const selectAllCheckbox = document.createElement('input');
        selectAllCheckbox.type = 'checkbox';
        selectAllCheckbox.id = 'select-all';
        selectAllCheckbox.style.cssText = `
            margin-right: 12px;
            transform: scale(1.3);
            cursor: pointer;
        `;

        const selectAllLabel = document.createElement('label');
        selectAllLabel.textContent = 'Sélectionner tous les fichiers';
        selectAllLabel.htmlFor = 'select-all';
        selectAllLabel.style.cursor = 'pointer';

        selectAllContainer.appendChild(selectAllCheckbox);
        selectAllContainer.appendChild(selectAllLabel);
        fileContainer.appendChild(selectAllContainer);

        files.forEach(file => {
            const fileItem = document.createElement('div');
            fileItem.style.cssText = `
                display: flex;
                align-items: center;
                margin-bottom: 8px;
                padding: 12px;
                border-radius: 8px;
                background: var(--surface-color);
                border: 1px solid var(--border-color);
                transition: all 0.2s ease;
                cursor: pointer;
            `;

            fileItem.addEventListener('mouseenter', () => {
                fileItem.style.background = 'var(--hover-color)';
                fileItem.style.transform = 'translateX(4px)';
            });

            fileItem.addEventListener('mouseleave', () => {
                fileItem.style.background = 'var(--surface-color)';
                fileItem.style.transform = 'translateX(0)';
            });

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.value = file.name;
            checkbox.className = 'file-checkbox';
            checkbox.style.cssText = `
                margin-right: 16px;
                transform: scale(1.2);
                cursor: pointer;
                accent-color: var(--accent-color);
            `;

            const fileIcon = document.createElement('span');
            fileIcon.textContent = '📄';
            fileIcon.style.cssText = `
                margin-right: 12px;
                font-size: 18px;
            `;

            const fileName = document.createElement('span');
            fileName.textContent = file.name;
            fileName.style.cssText = `
                color: var(--font-color);
                flex: 1;
                font-weight: 500;
                font-size: 14px;
            `;

            const filePath = document.createElement('span');
            filePath.textContent = file.parent || '/';
            filePath.style.cssText = `
                color: var(--text-muted);
                font-size: 12px;
                margin-left: 8px;
                opacity: 0.7;
            `;

            fileItem.appendChild(checkbox);
            fileItem.appendChild(fileIcon);
            fileItem.appendChild(fileName);
            fileItem.appendChild(filePath);
            fileContainer.appendChild(fileItem);

            fileItem.addEventListener('click', (e) => {
                if (e.target !== checkbox) {
                    checkbox.checked = !checkbox.checked;
                    updateSelectAllState();
                }
            });
        });
        popup.appendChild(fileContainer);

        function updateSelectAllState() {
            const checkboxes = fileContainer.querySelectorAll('.file-checkbox');
            const checkedBoxes = fileContainer.querySelectorAll('.file-checkbox:checked');
            selectAllCheckbox.checked = checkedBoxes.length === checkboxes.length;
            selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < checkboxes.length;
        }

        selectAllCheckbox.addEventListener('change', () => {
            const checkboxes = fileContainer.querySelectorAll('.file-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });

        fileContainer.addEventListener('change', updateSelectAllState);

        const durationLabel = document.createElement('label');
        durationLabel.textContent = 'Durée de validité :';
        durationLabel.style.cssText = `
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--font-color);
            font-size: 16px;
        `;
        popup.appendChild(durationLabel);

        const durationSelect = document.createElement('select');
        durationSelect.style.cssText = `
            width: 100%;
            padding: 14px;
            margin-bottom: 20px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            background: var(--surface-color);
            color: var(--font-color);
            font-size: 14px;
            cursor: pointer;
            transition: border-color 0.2s ease;
        `;
        
        durationSelect.addEventListener('focus', () => {
            durationSelect.style.borderColor = 'var(--accent-color)';
        });
        
        durationSelect.addEventListener('blur', () => {
            durationSelect.style.borderColor = 'var(--border-color)';
        });
        
        const durations = [
            { value: '1', text: '1 jour' },
            { value: '3', text: '3 jours' },
            { value: '7', text: '1 semaine' },
            { value: '14', text: '2 semaines' },
            { value: '30', text: '1 mois' },
            { value: '90', text: '3 mois' }
        ];
        
        durations.forEach(duration => {
            const option = document.createElement('option');
            option.value = duration.value;
            option.textContent = duration.text;
            if (duration.value === '7') option.selected = true;
            durationSelect.appendChild(option);
        });
        popup.appendChild(durationSelect);

        const maxDownloadsLabel = document.createElement('label');
        maxDownloadsLabel.textContent = 'Nombre maximum de téléchargements (0 = illimité) :';
        maxDownloadsLabel.style.cssText = `
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--font-color);
            font-size: 16px;
        `;
        popup.appendChild(maxDownloadsLabel);

        const maxDownloadsInput = document.createElement('input');
        maxDownloadsInput.type = 'number';
        maxDownloadsInput.value = '0';
        maxDownloadsInput.min = '0';
        maxDownloadsInput.max = '1000';
        maxDownloadsInput.style.cssText = `
            width: 100%;
            padding: 14px;
            margin-bottom: 20px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            background: var(--surface-color);
            color: var(--font-color);
            font-size: 14px;
            transition: border-color 0.2s ease;
        `;
        
        maxDownloadsInput.addEventListener('focus', () => {
            maxDownloadsInput.style.borderColor = 'var(--accent-color)';
        });
        
        maxDownloadsInput.addEventListener('blur', () => {
            maxDownloadsInput.style.borderColor = 'var(--border-color)';
        });
        
        popup.appendChild(maxDownloadsInput);

        const passwordLabel = document.createElement('label');
        passwordLabel.textContent = 'Mot de passe (optionnel) :';
        passwordLabel.style.cssText = `
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--font-color);
            font-size: 16px;
        `;
        popup.appendChild(passwordLabel);

        const passwordInput = document.createElement('input');
        passwordInput.type = 'password';
        passwordInput.placeholder = 'Laissez vide pour aucun mot de passe';
        passwordInput.style.cssText = `
            width: 100%;
            padding: 14px;
            margin-bottom: 24px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            background: var(--surface-color);
            color: var(--font-color);
            font-size: 14px;
            transition: border-color 0.2s ease;
        `;
        
        passwordInput.addEventListener('focus', () => {
            passwordInput.style.borderColor = 'var(--accent-color)';
        });
        
        passwordInput.addEventListener('blur', () => {
            passwordInput.style.borderColor = 'var(--border-color)';
        });
        
        popup.appendChild(passwordInput);

        const buttonContainer = document.createElement('div');
        buttonContainer.style.cssText = `
            display: flex;
            gap: 16px;
            justify-content: flex-end;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        `;

        const shareButton = document.createElement('button');
        shareButton.textContent = '🔗 Créer le partage';
        shareButton.style.cssText = `
            padding: 14px 28px;
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        `;
        
        shareButton.addEventListener('mouseenter', () => {
            shareButton.style.transform = 'translateY(-2px)';
            shareButton.style.boxShadow = '0 6px 16px rgba(0, 123, 255, 0.4)';
        });
        
        shareButton.addEventListener('mouseleave', () => {
            shareButton.style.transform = 'translateY(0)';
            shareButton.style.boxShadow = '0 4px 12px rgba(0, 123, 255, 0.3)';
        });
        
        shareButton.addEventListener('click', function () {
            const selectedFiles = Array.from(fileContainer.querySelectorAll('input[type="checkbox"]:checked'))
                .map(checkbox => checkbox.value);
            
            if (selectedFiles.length === 0) {
                showAlert('Veuillez sélectionner au moins un fichier');
                return;
            }
            
            const duration = durationSelect.value;
            const maxDownloads = maxDownloadsInput.value;
            const password = passwordInput.value;
            
            createFileShare(selectedFiles, directory, duration, maxDownloads, password);
            closePopup(overlay);
        });
        buttonContainer.appendChild(shareButton);

        const cancelButton = document.createElement('button');
        cancelButton.textContent = 'Annuler';
        cancelButton.style.cssText = `
            padding: 14px 28px;
            background: var(--danger-color);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        `;
        
        cancelButton.addEventListener('mouseenter', () => {
            cancelButton.style.transform = 'translateY(-2px)';
            cancelButton.style.boxShadow = '0 6px 16px rgba(220, 53, 69, 0.4)';
        });
        
        cancelButton.addEventListener('mouseleave', () => {
            cancelButton.style.transform = 'translateY(0)';
            cancelButton.style.boxShadow = '0 4px 12px rgba(220, 53, 69, 0.3)';
        });
        
        cancelButton.addEventListener('click', function () {
            closePopup(overlay);
        });
        buttonContainer.appendChild(cancelButton);

        popup.appendChild(buttonContainer);

        overlay.appendChild(popup);
        document.body.appendChild(overlay);
        
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closePopup(overlay);
            }
        });
    }
    
    function createFileShare(selectedFiles, directory, duration, maxDownloads, password) {
        console.log('🔗 createFileShare appelée avec:', selectedFiles, directory, duration, maxDownloads);
        
        const filePaths = selectedFiles.map(fileName => {
            return directory === '/' ? '/' + fileName : directory + '/' + fileName;
        });
        
        const shareData = {
            files: filePaths,
            duration: parseInt(duration),
            maxDownloads: parseInt(maxDownloads),
            password: password || null
        };

        console.log('🔗 Données de partage:', shareData);

        fetch('./modules/FileSharing/api.php?action=create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': '<^3i{~i5ln4(h#`s*$d]-d|;xx.s{tt#$~&2$jd{fzo|epmk+~k[;9[d/+7*b-q'
            },
            body: JSON.stringify(shareData)
        })
        .then(response => response.json())
        .then(data => {
            console.log('🔗 Réponse création partage:', data);
            if (data.success) {
                showShareResult(data.share_url, selectedFiles);
            } else {
                showAlert(data.error || 'Erreur lors de la création du partage');
            }
        })
        .catch(error => {
            console.error('🔗 Erreur création partage:', error);
            showAlert('Erreur lors de la création du partage');
        });
    }
    
    function showShareResult(shareUrl, fileNames) {
        console.log('🔗 showShareResult appelée avec URL:', shareUrl);
        
        const overlay = document.createElement('div');
        overlay.classList.add('overlay');

        const popup = document.createElement('div');
        popup.classList.add('popup');
        popup.style.maxWidth = '700px';

        const title = document.createElement('h2');
        title.textContent = '✅ Partage créé avec succès !';
        title.style.color = 'var(--success-color)';
        popup.appendChild(title);

        const fileInfo = document.createElement('p');
        fileInfo.textContent = `Fichiers partagés : ${fileNames.join(', ')}`;
        fileInfo.style.marginBottom = '15px';
        fileInfo.style.color = 'var(--font-color)';
        popup.appendChild(fileInfo);

        const urlLabel = document.createElement('label');
        urlLabel.textContent = 'Lien de partage :';
        urlLabel.style.display = 'block';
        urlLabel.style.marginBottom = '5px';
        urlLabel.style.fontWeight = 'bold';
        urlLabel.style.color = 'var(--font-color)';
        popup.appendChild(urlLabel);

        const urlContainer = document.createElement('div');
        urlContainer.style.display = 'flex';
        urlContainer.style.gap = '10px';
        urlContainer.style.marginBottom = '20px';

        const urlInput = document.createElement('input');
        urlInput.type = 'text';
        urlInput.value = shareUrl;
        urlInput.readOnly = true;
        urlInput.style.flex = '1';
        urlInput.style.padding = '12px';
        urlInput.style.border = '1px solid var(--border-color)';
        urlInput.style.borderRadius = '8px';
        urlInput.style.backgroundColor = 'var(--surface-color)';
        urlInput.style.color = 'var(--font-color)';
        urlContainer.appendChild(urlInput);

        const copyButton = document.createElement('button');
        copyButton.textContent = '📋 Copier';
        copyButton.style.padding = '12px 16px';
        copyButton.style.backgroundColor = 'var(--accent-color)';
        copyButton.style.color = 'white';
        copyButton.style.border = 'none';
        copyButton.style.borderRadius = '8px';
        copyButton.style.cursor = 'pointer';
        copyButton.addEventListener('click', function () {
            urlInput.select();
            document.execCommand('copy');
            copyButton.textContent = '✅ Copié !';
            setTimeout(() => {
                copyButton.textContent = '📋 Copier';
            }, 2000);
        });
        urlContainer.appendChild(copyButton);

        popup.appendChild(urlContainer);

        const closeButton = document.createElement('button');
        closeButton.textContent = 'Fermer';
        closeButton.style.padding = '12px 24px';
        closeButton.style.backgroundColor = 'var(--text-muted)';
        closeButton.style.color = 'white';
        closeButton.style.border = 'none';
        closeButton.style.borderRadius = '8px';
        closeButton.style.cursor = 'pointer';
        closeButton.addEventListener('click', function () {
            closePopup(overlay);
        });
        popup.appendChild(closeButton);

        overlay.appendChild(popup);
        document.body.appendChild(overlay);
    }
    
    function closePopup(overlay) {
        try {
            document.body.removeChild(overlay);
        } catch (error) {
            console.error('🔗 Erreur lors de la fermeture de la popup:', error);
        }
    }
    
    function showAlert(message, duration = 3000) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--surface-color);
            color: var(--font-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 16px 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            max-width: 400px;
            word-wrap: break-word;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            line-height: 1.4;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        
        let icon = 'ℹ️';
        if (message.includes('Erreur') || message.includes('erreur')) {
            icon = '❌';
            notification.style.borderLeft = '4px solid var(--danger-color)';
        } else if (message.includes('succès') || message.includes('créé')) {
            icon = '✅';
            notification.style.borderLeft = '4px solid var(--success-color)';
        } else if (message.includes('Aucun fichier')) {
            icon = '📁';
            notification.style.borderLeft = '4px solid var(--warning-color)';
        }
        
        notification.innerHTML = `${icon} ${message}`;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, duration);
        
        notification.style.cursor = 'pointer';
        notification.addEventListener('click', () => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        });
    }
    
    function observeButtonsContainer() {
        const buttonsContainer = document.getElementById('buttons');
        if (buttonsContainer) {
            const hasButtons = buttonsContainer.querySelector('.button');
            if (hasButtons && !buttonsContainer.querySelector('.share-file-button')) {
                const currentDirectory = typeof Sparent !== 'undefined' ? Sparent : '/';
                addShareButton(currentDirectory);
            }
            
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        const hasButtons = buttonsContainer.querySelector('.button');
                        if (hasButtons && !buttonsContainer.querySelector('.share-file-button')) {
                            const currentDirectory = typeof Sparent !== 'undefined' ? Sparent : '/';
                            addShareButton(currentDirectory);
                        }
                    }
                });
            });
            
            observer.observe(buttonsContainer, { childList: true });
            console.log('🔗 Observateur de boutons configuré');
        }
    }
    
    function addShareButton(directory) {
        try {
            const fileList = document.getElementById('buttons');
            if (!fileList) return;
            
            const existingButton = fileList.querySelector('.share-file-button');
            if (existingButton) return;
            
            const shareFileButton = document.createElement('button');
            shareFileButton.textContent = '🔗 Partager un fichier';
            shareFileButton.classList.add('share-file-button', 'button');
            shareFileButton.addEventListener('click', function () {
                showShareFileDialog(directory);
            });
            fileList.appendChild(shareFileButton);
            
            console.log('🔗 Bouton de partage ajouté pour:', directory);
        } catch (error) {
            console.error('🔗 Erreur lors de l\'ajout du bouton de partage:', error);
        }
    }
    
})(); 