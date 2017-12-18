$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        let $path = $("#log-search").attr("data-path");
        let $logSearchForm = $('#entry_logs_search_form');
        let $logsRowsCountForm = $('#logs_rows_count_form');

        $logSearchForm.on("submit", function (event) {
            event.preventDefault();
            logsLoader();

        });

        $logsRowsCountForm.on("submit", function (event) {
            event.preventDefault();
        });

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

            return true;
        }

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
        })
    }
});