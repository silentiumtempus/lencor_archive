$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        //let $path = $('#factories').attr('data-path');
        let $path = Routing.generate('admin-settings');

        /** Load settings list for selected factory **/
        $(document).on('click', 'a[name="factory"]', function loadSettings() {
            let $factoryId = $(this).attr('id');
            $.ajax({
                url: $path,
                method: "POST",
                data: {factoryId: $factoryId},
                success: function (response) {
                    $('#settings').html(response);
                }
            })
        });
    }
});