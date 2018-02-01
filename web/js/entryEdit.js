$(document).ready(function () {
    if (window.jQuery) {
        let $searchByIdForm = $('#entry_search_by_id_form');

        $searchByIdForm.on("submit", function (event) {
            event.preventDefault();
            let $entryId = event.currentTarget[0].value;
            $.ajax({
                url: Routing.generate('admin-entries', {entryId: $entryId}),
                method: "POST",
                success: function (response) {
                    $('#entryEdit').html(response.find('#entryEdit'));
                }
            })

        })

    }
});