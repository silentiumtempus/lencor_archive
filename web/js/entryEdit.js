$(document).ready(function () {
    if (window.jQuery) {
        let $searchByIdForm = $('#entry_search_by_id_form');

        $searchByIdForm.on("submit", function (event) {
            event.preventDefault();
            let $entryId = event.currentTarget[0].value;
            let $searchByIdFormSerialized = $searchByIdForm.serialize();
            $.ajax({
                url: Routing.generate('admin-entries'),
                data: $searchByIdFormSerialized,
                method: "POST",
                success: function (response) {
                    $('#entryEdit').html(response);
                }
            });
        });
    }
});