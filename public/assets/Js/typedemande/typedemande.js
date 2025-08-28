document.addEventListener('DOMContentLoaded', function() {
    let selectedTypeDemandeId = null;
    let selectedTypeDemandeName = null;
    let table;
    let preloadedForms = {};

    /**
     * Initialise le composant DataTable pour afficher les types de demandes.
     */
    function initializeDataTable() {
        // Charger les formulaires préchargés
        preloadedForms = {
            new: document.getElementById('template-form-new').innerHTML,
            edit: document.getElementById('template-form-edit').innerHTML,
            read: document.getElementById('template-form-read').innerHTML
        };

        table = new DataTable('#typeDemandesTable', {
            responsive: true,
            processing: true,
            serverSide: false,
            ajax: {
                url: '/admin/type_demandes/data',
                dataSrc: 'data',
                error: function(xhr, error, thrown) {
                    console.error('DataTables AJAX error:', error, thrown);
                    const tableBody = document.querySelector('#typeDemandesTable tbody');
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
                row.id = 'row_' + data.id;
                row.classList.add('type-demande-row');
                row.style.cursor = 'pointer';
                
                // Ajouter les événements directement sur la ligne
                row.addEventListener('click', function() {
                    selectTypeDemande(data.id, data.designation, this);
                });
                
                row.addEventListener('dblclick', function() {
                    showTypeDemandeForm(data.id, 'read');
                });
            }
        });
    }

    /**
     * Affiche le formulaire dans la modale en utilisant les templates préchargés
     * @param {number|null} id - L'ID du type de demande (null pour une création).
     * @param {string} mode - Le mode d'ouverture ('new', 'edit', 'read').
     */
    function showTypeDemandeForm(id, mode) {
        const modalBody = document.getElementById('typeDemandeModalBody');
        const modalTitle = document.getElementById('typeDemandeModalTitle');
        
        // Utiliser le template préchargé
        let formHtml = preloadedForms[mode];
        
        // Si ce n'est pas une nouvelle demande, charger les données
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
                            '<form id="typeDemandeForm" method="post">',
                            '<form id="typeDemandeForm" method="post">\n<input type="hidden" name="id" value="' + id + '">'
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
                
                // En mode lecture, charger les documents associés
                if (mode === 'read') {
                    // Charger les documents associés après le rendu du formulaire
                    setTimeout(() => {
                        loadDocumentsForReadMode(id);
                    }, 100);
                }
            }
        }
        
        modalBody.innerHTML = formHtml;
        
        const titles = {
            'new': 'Nouveau Type de Demande',
            'edit': 'Modifier le Type de Demande',
            'read': 'Détails du Type de Demande'
        };
        modalTitle.textContent = titles[mode] || 'Formulaire';
        
        // Ajouter l'écouteur d'événement pour la soumission du formulaire
        if (mode !== 'read') {
            const form = modalBody.querySelector('#typeDemandeForm');
            if (form) {
                form.addEventListener('submit', handleFormSubmit);
            }
        }
        
        // Afficher la modale
        const modal = new bootstrap.Modal(document.getElementById('typeDemandeModal'));
        modal.show();
    }

    /**
     * Charge les documents associés pour l'affichage en mode lecture
     * @param {number} typeDemandeId - ID du type de demande
     */
    function loadDocumentsForReadMode(typeDemandeId) {
        apiService.get(`/admin/type_demande/${typeDemandeId}/documents`)
            .then(documents => {
                const tbody = document.getElementById('documentsReadOnlyList');
                tbody.innerHTML = '';
                
                if (documents.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="2" class="text-center">Aucun document associé</td></tr>';
                    return;
                }
                
                documents.forEach(doc => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${doc.designation}</td>
                        <td>${doc.description || ''}</td>
                    `;
                    tbody.appendChild(row);
                });
            })
            .catch(error => {
                console.error('Erreur lors du chargement des documents:', error);
                const tbody = document.getElementById('documentsReadOnlyList');
                tbody.innerHTML = '<tr><td colspan="2" class="text-center text-danger">Erreur de chargement des documents</td></tr>';
            });
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

        apiService.saveTypeDemande(data)
            .then(result => {
                if (result.success) {
                    const message = data.id ? 
                        'Type de demande modifié avec succès' : 
                        'Type de demande créé avec succès';
                    
                    Notification.success(message);
                    
                    const modalInstance = bootstrap.Modal.getInstance(document.getElementById('typeDemandeModal'));
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
                            rowData.designation = result.typeDemande.designation;
                            rowData.desactivate = result.typeDemande.desactivate;
                            
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
     * @param {string} name - Le nom du type de demande sélectionné.
     * @param {HTMLElement} trElement - L'élément TR de la ligne cliquée.
     */
    function selectTypeDemande(id, name, trElement) {
        selectedTypeDemandeId = id;
        selectedTypeDemandeName = name;
        document.getElementById('btnEditTypeDemande').disabled = false;
        document.getElementById('btnEditDocuments').disabled = false;
        
        // Gère la classe 'selected' pour le feedback visuel
        const allRows = document.querySelectorAll('#typeDemandesTable tr');
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

    document.getElementById('btnAddTypeDemande').addEventListener('click', () => {
        showTypeDemandeForm(null, 'new');
    });
    
    document.getElementById('btnEditTypeDemande').addEventListener('click', () => {
        if (selectedTypeDemandeId) {
            showTypeDemandeForm(selectedTypeDemandeId, 'edit');
        }
    });
    
    document.getElementById('btnEditDocuments').addEventListener('click', () => {
        if (selectedTypeDemandeId) {
            // Vérifier que documentsModal est bien initialisé
            if (window.documentsModal && typeof window.documentsModal.open === 'function') {
                window.documentsModal.open(selectedTypeDemandeId, selectedTypeDemandeName);
            } else {
                console.error('documentsModal n\'est pas correctement initialisé');
                Notification.error('Erreur lors de l\'ouverture de l\'éditeur de documents');
            }
        }
    });
    
    // Nettoyage lorsque la modale est fermée
    document.getElementById('typeDemandeModal').addEventListener('hidden.bs.modal', function () {
        // Désélectionne la ligne visuellement
        $('#typeDemandesTable tr.selected').removeClass('selected');

        // Réinitialise l'état
        document.getElementById('btnEditTypeDemande').disabled = true;
        document.getElementById('btnEditDocuments').disabled = true;
        selectedTypeDemandeId = null;
        selectedTypeDemandeName = null;
    });
});