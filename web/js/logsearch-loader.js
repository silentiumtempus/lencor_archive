$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        let $path = $("#main-table").attr("data-path");
        let $logSearchForm = $('#entry_logs_search_form');
        let $entryIdInputField = $('#archive_entry_log_search_form_id');

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
                }
            });

            return true;
        }

    }
});