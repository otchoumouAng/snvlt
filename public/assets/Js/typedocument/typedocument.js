document.addEventListener('DOMContentLoaded', function() {
    let selectedTypeDocumentId = null;
    let table;
    let preloadedForms = {};

    /**
     * Initialise le composant DataTable pour afficher les types de documents.
     */
    function initializeDataTable() {
    // Charger les formulaires préchargés
    preloadedForms = {
        new: document.getElementById('template-form-new').innerHTML,
        edit: document.getElementById('template-form-edit').innerHTML,
        read: document.getElementById('template-form-read').innerHTML
    };

    table = new DataTable('#typeDocumentsTable', {
        responsive: true,
        processing: true,
        serverSide: false,
        ajax: {
            url: '/admin/type_documents/data',
            dataSrc: 'data',
            error: function(xhr, error, thrown) {
                console.error('DataTables AJAX error:', error, thrown);
                const tableBody = document.querySelector('#typeDocumentsTable tbody');
                if (tableBody) {
                    tableBody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Erreur de chargement des données. Veuillez rafraîchir la page.</td></tr>';
                }
            }
        },
        columns: [{
            data: null,
            title: '',
            orderable: false,
            render: function(data, type, row) {
                let iconClass = row.desactivate ? 'mdi-file-document-off' : 'mdi-file-document';
                let iconColor = row.desactivate ? 'text-muted' : 'text-primary';
                return `<i class="mdi ${iconClass} ${iconColor}" style="font-size: 24px;"></i>`;
            }
        }, {
            data: 'designation',
            title: 'Désignation',
            render: function(data, type, row) {
                return row.desactivate ? `<span style="text-decoration: line-through; color: #6c757d;">${data}</span>` : data;
            }
        }, {
            data: 'desactivate',
            title: 'Statut',
            render: function(data, type, row) {
                return data ? '<span class="badge bg-secondary">Désactivé</span>' : '<span class="badge bg-success">Actif</span>';
            }
        }],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json'
        },
        createdRow: function(row, data, dataIndex) {
            if (data.desactivate) {
                row.classList.add('table-secondary');
            }
            row.id = data.DT_RowId;
            row.classList.add('type-document-row');
            row.style.cursor = 'pointer';
            
            // Ajouter les événements directement sur la ligne
            row.addEventListener('click', function() {
                selectTypeDocument(data.id, this);
            });
            
            row.addEventListener('dblclick', function() {
                showTypeDocumentForm(data.id, 'read');
            });
        }
    });
}

    /**
     * Affiche le formulaire dans la modale en utilisant les templates préchargés
     * @param {number|null} id - L'ID du type de document (null pour une création).
     * @param {string} mode - Le mode d'ouverture ('new', 'edit', 'read').
     */
    function showTypeDocumentForm(id, mode) {
        const modalBody = document.getElementById('typeDocumentModalBody');
        const modalTitle = document.getElementById('typeDocumentModalTitle');
        
        // Utiliser le template préchargé
        let formHtml = preloadedForms[mode];
        
        // Si ce n'est pas un nouveau document, charger les données
        if (id && mode !== 'new') {
            // Trouver la ligne dans la table
            const row = document.getElementById('row_' + id);
            if (row) {
                const rowData = table.row(row).data();
                
                // Mettre à jour les valeurs du formulaire
                formHtml = formHtml.replace(
                    'value=""', 
                    `value="${rowData.designation || ''}"`
                );
                
                // Mettre à jour le statut
                if (rowData.desactivate) {
                    formHtml = formHtml.replace(
                        '<option value="0" selected>Actif</option>',
                        '<option value="0">Actif</option>'
                    );
                    formHtml = formHtml.replace(
                        '<option value="1">Désactivé</option>',
                        '<option value="1" selected>Désactivé</option>'
                    );
                }
                
                // Ajouter l'ID caché si en mode édition
                if (mode === 'edit') {
                    // Vérifier si le champ hidden existe déjà
                    if (!formHtml.includes('name="id"')) {
                        formHtml = formHtml.replace(
                            '<form id="typeDocumentForm" method="post">',
                            '<form id="typeDocumentForm" method="post">\n<input type="hidden" name="id" value="' + id + '">'
                        );
                    } else {
                        // Mettre à jour la valeur si le champ existe déjà
                        formHtml = formHtml.replace(
                            'name="id" value=""',
                            'name="id" value="' + id + '"'
                        );
                    }
                    
                    // Changer le texte du bouton de "Créer" à "Modifier"
                    formHtml = formHtml.replace(
                        /<button type="submit" class="btn btn-primary">([^<]*)<\/button>/,
                        '<button type="submit" class="btn btn-primary">Modifier</button>'
                    );
                }
            }
    }
    
    modalBody.innerHTML = formHtml;
    
    const titles = {
        'new': 'Nouveau Type de Document',
        'edit': 'Modifier le Type de Document',
        'read': 'Détails du Type de Document'
    };
    modalTitle.textContent = titles[mode] || 'Formulaire';
    
    // Ajouter l'écouteur d'événement pour la soumission du formulaire
    if (mode !== 'read') {
        const form = modalBody.querySelector('#typeDocumentForm');
        if (form) {
            form.addEventListener('submit', handleFormSubmit);
        }
    }
    
    // Afficher la modale
    const modal = new bootstrap.Modal(document.getElementById('typeDocumentModal'));
    modal.show();
}

    /**
     * Gère la soumission du formulaire d'ajout/modification via AJAX.
     * @param {Event} event - L'événement de soumission du formulaire.
     */
    function handleFormSubmit(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    // Debug: Afficher les données dans la console
    console.log('Données du formulaire:', data);

    apiService.saveTypeDocument(data)
        .then(result => {
            if (result.success) {
                const message = data.id ? 
                    'Type de document modifié avec succès' : 
                    'Type de document créé avec succès';
                
                Notification.success(message);
                
                const modalInstance = bootstrap.Modal.getInstance(document.getElementById('typeDocumentModal'));
                if (modalInstance) {
                    modalInstance.hide();
                }
                
                // Mettre à jour la table sans rechargement complet
                if (data.id) {
                    // Modification d'un élément existant
                    const row = document.getElementById('row_' + data.id);
                    if (row) {
                        const rowNode = table.row(row);
                        const rowData = rowNode.data();
                        
                        // Mettre à jour les données
                        rowData.designation = result.typeDocument.designation;
                        rowData.desactivate = result.typeDocument.desactivate;
                        
                        // Mettre à jour la ligne dans DataTable
                        rowNode.data(rowData).draw(false);
                        
                        // Mettre à jour l'apparence
                        if (rowData.desactivate) {
                            row.classList.add('table-secondary');
                        } else {
                            row.classList.remove('table-secondary');
                        }
                    }
                } else {
                    // Nouvel élément - recharger seulement la première page
                    table.ajax.reload(null, false);
                }
            } else {
                Notification.error(result.message || "Une erreur s'est produite.");
            }
        })
        .catch(error => {
            Notification.error("Erreur critique lors de l'enregistrement.");
            console.error("Save Error:", error);
        });
}

    /**
     * Gère la sélection visuelle d'une ligne et active les boutons contextuels.
     * @param {number} id - L'ID de l'élément sélectionné.
     * @param {HTMLElement} trElement - L'élément TR de la ligne cliquée.
     */
    
    function selectTypeDocument(id, trElement) {
        selectedTypeDocumentId = id;
        document.getElementById('btnEditTypeDocument').disabled = false;
        
        // Gère la classe 'selected' pour le feedback visuel
        const allRows = document.querySelectorAll('#typeDocumentsTable tr');
        allRows.forEach(row => row.classList.remove('selected'));
        trElement.classList.add('selected');
        
        // Ajouter une bordure pour mieux visualiser la sélection
        trElement.style.border = '2px solid #007bff';
        
        // Réinitialiser le style des autres lignes
        allRows.forEach(row => {
            if (row !== trElement) {
                row.style.border = '';
            }
        });
    }

    // --- Initialisation et Écouteurs d'Événements ---

    initializeDataTable();

    document.getElementById('btnAddTypeDocument').addEventListener('click', () => {
        showTypeDocumentForm(null, 'new');
    });
    
    document.getElementById('btnEditTypeDocument').addEventListener('click', () => {
        if (selectedTypeDocumentId) {
            showTypeDocumentForm(selectedTypeDocumentId, 'edit');
        }
    });
    
    // Nettoyage lorsque la modale est fermée
    document.getElementById('typeDocumentModal').addEventListener('hidden.bs.modal', function () {
        // Désélectionne la ligne visuellement
        $('#typeDocumentsTable tr.selected').removeClass('selected');

        // Réinitialise l'état
        document.getElementById('btnEditTypeDocument').disabled = true;
        selectedTypeDocumentId = null;
    });
});