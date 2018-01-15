$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        let $logsRowsCountForm = $('#logs_rows_count_form');
        $logsRowsCountForm.on("submit", function (event) {
            event.preventDefault();
            $('#logfile-content').hide();
            $('#loading-spinner').show().css('display', 'contents');
            let $logsRowsCountFormSerialized = $logsRowsCountForm.serialize();
            $.ajax({
                url: Routing.generate('logging-open-file'),
                method: "POST",
                data: $logsRowsCountFormSerialized,
                success: function (response) {
                    $('#loading-spinner').hide();
                    $('#logfile-content').html(response);
                    $('#logfile-content').show();
                }
            });
        });
    }
});
