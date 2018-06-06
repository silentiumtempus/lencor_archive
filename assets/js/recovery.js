$(document).ready(function () {
    if (window.jQuery) {
        $(document).on("click", "#findEntryFiles", findEntryFiles);

        /** Find entry entities files **/

        function findEntryFiles() {
            $.ajax({
                url: Routing.generate('recovery-find'),
                method: "POST",
                data: null,
                success: function (files) {
                    $('#container').html(files);
                }
            });

            return false;
        }
    }
});