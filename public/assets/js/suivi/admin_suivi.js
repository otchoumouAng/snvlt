$(function() {
    var table = $('#datatable_admin_suivi').DataTable({
        "order": [[ 5, "desc" ]],
        "scrollX": true,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tout"]],
        "language": {
            "lengthMenu": "Afficher _MENU_ enregistrements par page",
            "zeroRecords": "Aucun enregistrement trouvé",
            "info": "Affichage de la page _PAGE_ sur _PAGES_",
            "infoEmpty": "Aucun enregistrement disponible",
            "infoFiltered": "(filtré sur un total de _MAX_ enregistrements)",
            "search": "Rechercher :",
            "paginate": {
                "first": "Premier",
                "last": "Dernier",
                "next": "Suivant",
                "previous": "Précédent"
            }
        },
    });

    // Link external filters
    $('#filter_avis').on('keyup change', function () {
        table.column(0).search(this.value).draw();
    });

    $('#filter_service').on('keyup change', function () {
        table.column(1).search(this.value).draw();
    });

    $('#filter_statut').on('change', function () {
        table.column(6).search(this.value).draw();
    });

    $('#filter_societe').on('keyup change', function () {
        table.column(7).search(this.value).draw();
    });

    $('#datatable_admin_suivi tbody').on('dblclick', 'tr', function () {
        var transactionId = $(this).data('id');
        if (!transactionId) {
            return;
        }
        var modal = $('#transactionDetailsModal');
        var modalLoader = modal.find('#modal-loader');
        var modalContent = modal.find('#modal-content-details');

        modalContent.hide();
        modalLoader.show();
        modal.modal('show');

        $.ajax({
            url: '/api/transaction/' + transactionId,
            method: 'GET',
            success: function (data) {
                var detailsHtml = '<dl class="row">';
                detailsHtml += '<dt class="col-sm-4"><i class="ph ph-file-text"></i> Avis de recette:</dt><dd class="col-sm-8">' + data.identifiant + '</dd>';
                detailsHtml += '<dt class="col-sm-4"><i class="ph ph-wrench"></i> Service:</dt><dd class="col-sm-8">' + data.service + '</dd>';
                detailsHtml += '<dt class="col-sm-4"><i class="ph ph-money"></i> Montant demandé:</dt><dd class="col-sm-8">' + data.montant_fcfa + ' FCFA</dd>';
                detailsHtml += '<dt class="col-sm-4"><i class="ph ph-wallet"></i> Montant payé:</dt><dd class="col-sm-8">' + (data.paid_amount ? data.paid_amount + ' FCFA' : 'N/A') + '</dd>';
                detailsHtml += '<dt class="col-sm-4"><i class="ph ph-info"></i> Statut:</dt><dd class="col-sm-8">' + data.statut + '</dd>';
                detailsHtml += '<dt class="col-sm-4"><i class="ph ph-calendar"></i> Date de paiement:</dt><dd class="col-sm-8">' + data.paid_at + '</dd>';
                detailsHtml += '<dt class="col-sm-4"><i class="ph ph-receipt"></i> Référence de paiement:</dt><dd class="col-sm-8">' + data.tresorpay_receipt_reference + '</dd>';
                detailsHtml += '<dt class="col-sm-4"><i class="ph ph-phone"></i> Numéro de paiement:</dt><dd class="col-sm-8">' + data.payer_phone + '</dd>';
                detailsHtml += '<dt class="col-sm-4"><i class="ph ph-buildings"></i> Société:</dt><dd class="col-sm-8">' + (data.company ? data.company : 'N/A') + '</dd>';
                detailsHtml += '<dt class="col-sm-4"><i class="ph ph-user"></i> Demandeur:</dt><dd class="col-sm-8">' + data.client_nom + ' ' + data.client_prenom + '</dd>';
                detailsHtml += '<dt class="col-sm-4"><i class="ph ph-phone-call"></i> Téléphone du demandeur:</dt><dd class="col-sm-8">' + data.telephone + '</dd>';
                detailsHtml += '<dt class="col-sm-4"><i class="ph ph-clock"></i> Créé le:</dt><dd class="col-sm-8">' + data.created_at + '</dd>';
                detailsHtml += '<dt class="col-sm-4"><i class="ph ph-user-circle"></i> Créé par:</dt><dd class="col-sm-8">' + data.created_by + '</dd>';
                detailsHtml += '</dl>';

                modalContent.html(detailsHtml);
                modalLoader.hide();
                modalContent.show();
            },
            error: function () {
                modalContent.html('<p class="text-danger">Impossible de charger les détails de la transaction.</p>');
                modalLoader.hide();
                modalContent.show();
            }
        });
    });
});