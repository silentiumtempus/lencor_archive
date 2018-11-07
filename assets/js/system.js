$(document).ready(function () {
    if (window.jQuery) {
        $(document).on('click', '#info', function () {
            loadData('info');

            return false;
        });
        $(document).on('click', '#config-info', function () {
            loadData('config-info');

            return false;
        });
        $(document).on('click', '#php-config-info', function () {
            loadData('php-config-info');

            return false;
        });
        $(document).on('click', '#permissions-info', function () {
            loadData('permissions-info');

            return false;
        });

        function loadData(target) {
            $.ajax({
                url: Routing.generate('system-' + target),
                method: "POST",
                data: null,
                success: function (response) {
                    $('#system-content').html(response);
                    alert(response);
                }
            });

            return false;
        }

    }
});
