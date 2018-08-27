$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        let $method = 'POST';
        $(document).on('click', 'a[name="serializeUsers"]', serializeUsers);

        function serializeUsers() {
            $.ajax({
                url: Routing.generate('admin-serialize-users'),
                method: $method,
                data: null,
                success: function (response) {
                    console.log(response);
                }
            });

            return false;
        }
    }
});