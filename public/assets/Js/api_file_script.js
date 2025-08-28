
document.addEventListener('DOMContentLoaded', () => {
    const uploadZone = document.getElementById('upload-zone');
    const fileInput = document.getElementById('file-input');
    const browseBtn = document.getElementById('browse-btn');
    const progressContainer = document.getElementById('progress-container');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');

    // Gestion du clic sur le bouton "Parcourir"
    browseBtn.addEventListener('click', () => fileInput.click());

    // Gestion de la sélection de fichier
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length) {
            handleFiles(e.target.files);
        }
    });

    // Gestion du glisser-déposer
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        uploadZone.classList.add('drag-over');
    }

    function unhighlight() {
        uploadZone.classList.remove('drag-over');
    }

    uploadZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    });

    // Traitement des fichiers
    function handleFiles(files) {
        const file = files[0];
        
        // Validation du fichier
        if (!file.name.endsWith('.csv')) {
            showError('Seuls les fichiers CSV sont acceptés');
            return;
        }

        if (file.size > 4 * 1024 * 1024) {
            showError('Le fichier ne doit pas dépasser 4 Mo');
            return;
        }

        // Affichage du fichier sélectionné
        showFileInfo(file);

        // Simulation de l'upload (remplacer par un vrai appel API)
        simulateUpload(file);
    }

    function showFileInfo(file) {
        uploadZone.innerHTML = `
            <svg class="upload-icon success-icon" viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
            </svg>
            <h3>Fichier prêt !</h3>
            <div class="file-info">
                <span class="file-name">${file.name}</span>
                <span class="file-size">${formatFileSize(file.size)}</span>
            </div>
            <button id="remove-btn" class="remove-btn">Changer de fichier</button>
        `;

        uploadZone.classList.add('upload-success');
        progressContainer.classList.remove('hidden');

        // Gestion du bouton "Changer"
        document.getElementById('remove-btn').addEventListener('click', resetUploader);
    }

    function showError(message) {
        uploadZone.innerHTML = `
            <svg class="upload-icon error-icon" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
            </svg>
            <h3>${message}</h3>
            <button id="retry-btn" class="browse-btn">Réessayer</button>
        `;

        uploadZone.classList.add('upload-error');

        document.getElementById('retry-btn').addEventListener('click', resetUploader);
    }

    /*function resetUploader() {
        uploadZone.innerHTML = `
            <svg class="upload-icon" viewBox="0 0 24 24">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            <h3>Glissez-déposez votre fichier CSV ici</h3>
            <p class="subtext">Taille maximale : 4 Mo</p>
            <div class="file-requirements">
                <span>Formats acceptés : .csv</span>
            </div>
            <button id="browse-btn" class="browse-btn">Parcourir les fichiers</button>
        `;

        uploadZone.classList.remove('drag-over', 'upload-success', 'upload-error');
        progressContainer.classList.add('hidden');
        progressBar.style.width = '0%';
        progressText.textContent = '0%';
        fileInput.value = '';

        // Réattacher les événements
        document.getElementById('browse-btn').addEventListener('click', () => fileInput.click());
    }*/

    function resetUploader() {
    const fileInput = document.getElementById('file-input');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const progressContainer = document.getElementById('progress-container');
    const uploadZone = document.getElementById('upload-zone');

    if (fileInput) fileInput.value = '';
    if (progressBar) progressBar.style.width = '0%';
    if (progressText) progressText.textContent = '0%';
    if (progressContainer) progressContainer.classList.add('hidden');
    if (uploadZone) uploadZone.classList.remove('drag-over', 'upload-success', 'upload-error');
}


    function simulateUpload(file) {
        let progress = 0;
        const interval = setInterval(() => {
            progress += Math.random() * 10;
            if (progress >= 100) {
                progress = 100;
                clearInterval(interval);
            }
            
            progressBar.style.width = `${progress}%`;
            progressText.textContent = `${Math.round(progress)}%`;
        }, 200);
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});