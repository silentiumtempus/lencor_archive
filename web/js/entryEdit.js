$(document).ready(function () {
    if (window.jQuery) {
        let $searchByIdForm = $('#entry_search_by_id_form');

        $searchByIdForm.on("submit", function (event) {
            event.preventDefault();
        })

    }
});