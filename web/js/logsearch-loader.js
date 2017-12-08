$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        let $path = $("#main-table").attr("data-path");
        let $logSearchForm = $('#entry_logs_search_form');

        $logSearchForm.on("submit", function (event) {
            event.preventDefault();
            logsLoader();

        });

        function logsLoader() {
            let $logSearchFormSerialized = $logSearchForm.serialize();
            $.ajax({
                url: $path,
                method: $logSearchForm.attr('method'),
                data: $logSearchFormSerialized,
                success: function (response) {
                    $('#logFiles').replaceWith($(response).find('#logFiles'));
                }
            });

            return true;
        }

        $(document).on("click", 'a[name="openLog"]', function(event) {
            event.preventDefault();
            $.ajax({
                url: "open-file",
                method: "POST",
                data: null,
                success: function (response) {

                }
            })
        })

    }
});