$(document).ready(function () {
    if (window.jQuery) {

        $(document).on("click", "#sys-info", loadData('sys-info'));
        $(document).on("click", "#app-config", loadData('app-config'));
        $(document).on("click", "#php-settings", loadData('php-settings'));
        $(document).on("folder-permissions", loadData('folder-permissions'));

        function loadData(target) {

            return null;
        }

    }
});
