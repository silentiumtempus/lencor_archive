$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        let $logsRowsCountForm = $('#logs_rows_count_form');
        $logsRowsCountForm.on("submit", function (event) {
            event.preventDefault();
            let $logsRowsCountFormSerialized = $logsRowsCountForm.serialize();
            $.ajax({
                url: "logging/open-file",
                method: "POST",
                data: $logsRowsCountFormSerialized,
                success: function (response) {
                    $('#logfile-content').html(response);
                }
            });
        });
    }
});
