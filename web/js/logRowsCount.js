$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        let $logsRowsCountForm = $('#logs_rows_count_form');

        $logsRowsCountForm.on("submit", function (event) {
            event.preventDefault();

        });
    }
});
