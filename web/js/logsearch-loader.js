$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        let $logSearchForm = $('#entry_logs_search_form');

        $logSearchForm.on("submit", function (event) {
            event.preventDefault();
            logsLoader();

        });

        function logsLoader() {
            return true;
        }

    }
});