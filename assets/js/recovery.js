$(document).ready(function () {
    if (window.jQuery) {

        /** Find entry entities files **/

        $(document).on("click", "#findEntryFiles", findEntryFiles);

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

        /** Restore entries from files **/

        $(document).on("click", "#recoverDatabase", restoreEntriesFromFiles);

        function restoreEntriesFromFiles() {
            $.ajax({
                url: Routing.generate('recovery-exec'),
                method: "POST",
                data: null,
                success: function(result) {
                    loadFlashMessages();
                }
            });

            return false;
        }

        /** Flash messages loader **/

        function loadFlashMessages() {
            let $flashMessages = $('#flash-messages');
            $.ajax({
                url: Routing.generate('flash_messages'),
                method: "POST",
                success: function (reloadFlashMessages) {
                    $flashMessages.html($(reloadFlashMessages).filter('#flash-messages').children());
                    $flashMessages.fadeIn("slow");
                    //setTimeout(hideFlashMessages, 7000);
                }
            });

            return false;
        }

        /** Flash messages hiding **/

        function hideFlashMessages() {
            let $flashMessages = $('#flash-messages');
            $flashMessages.fadeOut("slow");

        }

        /** Close flash message manually **/

        $(document).on('click', '#close-alert', function () {
            $(this).parent().fadeOut("slow");
        });
    }
});