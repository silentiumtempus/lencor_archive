$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        //let $path = $('#factories').attr('data-path');


        /** Load settings list for selected factory **/
        $(document).on('click', 'a[name="openFactory"]', function loadSettings() {
            let $factoryId = $(this).attr('id');
            $.ajax({
                url: Routing.generate('admin-settings'),
                method: "POST",
                data: {factoryId: $factoryId},
                success: function (response) {
                    $('#settings').html(response);
                }
            });
        });

        /** Load factory edit form **/
        $(document).on('click', 'a[name="editFactory"]', function loadFactoryEditForm() {
            let $factory = $(this).attr('id');
            $.ajax({
                url: Routing.generate('admin-factory-edit', {factory: $factory}),
                method: "POST",
                success: function (response) {
                    $('#factory_' + $factory).html(response);
                    let $factoryEditForm = $('#factory_form');
                    /** Update factory **/
                    $(document).on('submit', $factoryEditForm, function updateFactory(event) {
                        event.preventDefault();
                        let $factoryEditFormSerialized = $factoryEditForm.serialize();
                        //alert($factory);
                        $.ajax({
                            url: Routing.generate('admin-factory-edit', {factory: $factory}),
                            method: "POST",
                            data: $factoryEditFormSerialized,
                            success: function (response) {
                                $('#factory_' + $factory).replaceWith(response);
                                $factoryEditFormSerialized = null;
                                response = null;
                                $factory = null;
                            }
                        });
                    });
                    /** Factory editing cancellation **/
                    $(document).on('click', '#factory_form_cancelButton', function cancelFactoryEdit() {
                    });
                }
            });
            return false;
        });
    }
});