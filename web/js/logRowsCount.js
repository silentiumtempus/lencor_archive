$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        let $logsRowsCountForm = $('#logs_rows_count_form');
        let $path = $("#log-search").attr("data-path");
        $logsRowsCountForm.on("submit", function (event) {
            event.preventDefault();
            let rowsCount = $('#log_rows_count_form_rowsCount').val();
            let entryId = $('#log_rows_count_form_entryId').attr('value');
            let file = $('#log_rows_count_form_file').attr('value');
            let $logsRowsCountFormSerialized = $logsRowsCountForm.serialize();
            $.ajax({
                url: "logging/open-file",
                method: "POST",
                data: $logsRowsCountFormSerialized,
                success: function (response) {
                    //alert('OK');
                    $('#logfile-content').html(response);
                }
            });
        });
    }
});
