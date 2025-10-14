(function($) {
    "use strict";

    $(document).ready(function() {
        $('#transactions-table').DataTable({
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            language: {
                search: "Filtrer:",
                lengthMenu: "Afficher _MENU_ éléments",
                info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
                infoEmpty: "Affichage de 0 à 0 sur 0 éléments",
                infoFiltered: "(filtré de _MAX_ éléments au total)",
                paginate: {
                    first: "Premier",
                    last: "Dernier",
                    next: "Suivant",
                    previous: "Précédent"
                }
            }
        });
    });
})(jQuery);