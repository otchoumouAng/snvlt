$(function() {
    // Setup - add a text input to each footer cell
    $('#datatable_admin_suivi thead tr')
        .clone(true)
        .addClass('filters')
        .appendTo('#datatable_admin_suivi thead');

    var table = $('#datatable_admin_suivi').DataTable({
        "orderCellsTop": true,
        "fixedHeader": true,
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
        initComplete: function () {
            var api = this.api();

            // For each column
            api
                .columns()
                .eq(0)
                .each(function (colIdx) {
                    // Set the header cell to contain the input element
                    var cell = $('.filters th').eq(
                        $(api.column(colIdx).header()).index()
                    );
                    var title = $(cell).text();
                    $(cell).html('<input type="text" class="form-control" placeholder="' + title + '" />');

                    // On every keypress in this input
                    $(
                        'input',
                        $('.filters th').eq($(api.column(colIdx).header()).index())
                    )
                        .off('keyup change')
                        .on('keyup change', function (e) {
                            e.stopPropagation();

                            // Get the search value
                            $(this).attr('title', $(this).val());
                            var regexr = '({search})'; //$(this).parents('th').find('select').val();

                            var cursorPosition = this.selectionStart;
                            // Search the column for that value
                            api
                                .column(colIdx)
                                .search(
                                    this.value != ''
                                        ? regexr.replace('{search}', '(((' + this.value + ')))')
                                        : '',
                                    this.value != '',
                                    this.value == ''
                                )
                                .draw();

                            $(this)
                                .focus()[0]
                                .setSelectionRange(cursorPosition, cursorPosition);
                        });
                });
        },
    });

    $('#datatable_admin_suivi tbody').on('dblclick', 'tr', function () {
        var transactionId = $(this).data('id');
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
                detailsHtml += '<dt class="col-sm-4">Avis de recette:</dt><dd class="col-sm-8">' + data.identifiant + '</dd>';
                detailsHtml += '<dt class="col-sm-4">Service:</dt><dd class="col-sm-8">' + data.service + '</dd>';
                detailsHtml += '<dt class="col-sm-4">Montant demandé:</dt><dd class="col-sm-8">' + data.montant_fcfa + ' FCFA</dd>';
                detailsHtml += '<dt class="col-sm-4">Montant payé:</dt><dd class="col-sm-8">' + (data.paid_amount ? data.paid_amount + ' FCFA' : 'N/A') + '</dd>';
                detailsHtml += '<dt class="col-sm-4">Statut:</dt><dd class="col-sm-8">' + data.statut + '</dd>';
                detailsHtml += '<dt class="col-sm-4">Date de paiement:</dt><dd class="col-sm-8">' + data.paid_at + '</dd>';
                detailsHtml += '<dt class="col-sm-4">Référence de paiement:</dt><dd class="col-sm-8">' + data.tresorpay_receipt_reference + '</dd>';
                detailsHtml += '<dt class="col-sm-4">Numéro de paiement:</dt><dd class="col-sm-8">' + data.payer_phone + '</dd>';
                detailsHtml += '<dt class="col-sm-4">Société:</dt><dd class="col-sm-8">' + (data.company ? data.company : 'N/A') + '</dd>';
                detailsHtml += '<dt class="col-sm-4">Demandeur:</dt><dd class="col-sm-8">' + data.client_nom + ' ' + data.client_prenom + '</dd>';
                detailsHtml += '<dt class="col-sm-4">Téléphone du demandeur:</dt><dd class="col-sm-8">' + data.telephone + '</dd>';
                detailsHtml += '<dt class="col-sm-4">Créé le:</dt><dd class="col-sm-8">' + data.created_at + '</dd>';
                detailsHtml += '<dt class="col-sm-4">Créé par:</dt><dd class="col-sm-8">' + data.created_by + '</dd>';
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