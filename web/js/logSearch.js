$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        let $path = $("#log-search").attr("data-path");
        let $logSearchForm = $('#entry_logs_search_form');

        $logSearchForm.on("submit", function (event) {
            event.preventDefault();
            logsLoader();

        });
        /** Load log files and folders **/
        function logsLoader() {
            let $logSearchFormSerialized = $logSearchForm.serialize();
            $.ajax({
                url: $path,
                method: $logSearchForm.attr('method'),
                data: $logSearchFormSerialized,
                success: function (response) {
                    $('#log-files').replaceWith($(response).find('#log-files'));
                }
            });

            return false;
        }
        /** Load log file contents **/
        $(document).on("click", 'a[name="openLog"]', function(event) {
            event.preventDefault();
            let entryId = $(this).attr("id");
            let file = $(this).text();
            $.ajax({
                url: "logging/open-file",
                method: "POST",
                data: {entryId: entryId, file: file},
                success: function (response) {
                    $('#logfile-content').html(response);

                }
            })
        });

        $(document).on("clock", 'a[name="openSubDir"]', function (event) {
            event.preventDefault();
        })
    }
});