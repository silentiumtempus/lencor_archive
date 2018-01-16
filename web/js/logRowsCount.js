$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        let $logsRowsCountForm = $('#logs_rows_count_form');
        $logsRowsCountForm.on("submit", function (event) {
            event.preventDefault();
            let $text = $('#logfile-text');
            $text.hide();
            $('#loading-spinner').show().css('display', 'contents');
            let $logsRowsCountFormSerialized = $logsRowsCountForm.serialize();
            $.ajax({
                url: Routing.generate('logging-open-file'),
                method: "POST",
                data: $logsRowsCountFormSerialized,
                success: function (response) {
                    $('#loading-spinner').hide();
                    $text.replaceWith($(response).find('#logfile-text'));
                    $text.show();
                }
            });
        });
    }
});
