$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        let $path = $("#log-search").attr("data-path");
        let $logSearchForm = $('#entry_logs_search_form');

        $logSearchForm.on("submit", function (event) {
            event.preventDefault();
            $('#logfile-content').hide();
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
                    $('#logs').replaceWith($(response).find('#logs'));
                }
            });

            return false;
        }
        /** Load log file contents **/

        $(document).on("click", 'a[name="openLog"]', function(event) {
            event.preventDefault();
            $('#logfile-content').hide();
            $('#loading-spinner').show().css('display', 'block');
            let entryId = $(this).attr('id');
            let file = $(this).text();
            let parentFolder = $(this).parent('span').attr('id');
            $.ajax({
                url: Routing.generate('logging-open-file'),
                method: "POST",
                data: {entryId: entryId, file: file, parentFolder: parentFolder},
                success: function (response) {
                    $('#loading-spinner').hide();
                    $('#logfile-block').html(response);
                    $('#logfile-content').show();

                }
            })
        });

        /** Load contents of subdirectory in logs **/

        $(document).on("click", 'a[name="openSubDir"]', function (event) {
            event.preventDefault();
            $('#logfile-content').hide();
            let $folder = $(this).attr('id');
            let $entryId = $(this).parents('ul').attr('id');
            let $parentFolder = $(this).parent('span').attr('id');
            $.ajax({
                url: Routing.generate('logging-open-sub-dir', {entryId: $entryId}),
                method: "POST",
                data: {folder: $folder, entryId: $entryId, parentFolder: $parentFolder},
                success: function (response) {
                    $('#logs').html($(response));
                }
            })
        })
    }
});