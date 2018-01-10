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
                    $('#logs').replaceWith($(response).find('#logs'));
                }
            });

            return false;
        }
        /** Load log file contents **/
        $(document).on("click", 'a[name="openLog"]', function(event) {
            event.preventDefault();
            let entryId = $(this).attr('id');
            let file = $(this).text();
            let parentFolder = $(this).parent('span').attr('id');
            $.ajax({
                url: "logging/open-file",
                method: "POST",
                data: {entryId: entryId, file: file, parentFolder: parentFolder},
                success: function (response) {
                    $('#logfile-content').html(response);

                }
            })
        });

        /** Load contents of subdirectory in logs **/
        $(document).on("click", 'a[name="openSubDir"]', function (event) {
            event.preventDefault();
            let $folder = $(this).attr('id');
            let $entryId = $(this).parents('ul').attr('id');
            let $parentFolder = $(this).parent('span').attr('id');
            let $path = Routing.generate('open-sub-dir', {entryId: $entryId});
            $.ajax({
                url: $path,
                method: "POST",
                data: {folder: $folder, entryId: $entryId, parentFolder: $parentFolder},
                success: function (response) {
                    $('#logs').html($(response));
                }
            })
        })
    }
});