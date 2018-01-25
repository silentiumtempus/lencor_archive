$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        //let $path = $('#factories').attr('data-path');
        let $factoryEditFormsArray = [];
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

        /** Perform factory name update and show result **/
        function updateFactory($factory, $factoryEditForm) {
            let $factoryEditFormSerialized = $factoryEditForm.serialize();
            $.ajax({
                url: Routing.generate('admin-factory-edit', {factory: $factory}),
                method: "POST",
                data: $factoryEditFormSerialized,
                success: function (response) {
                    $('#factory_' + $factory).replaceWith(response);
                }
            });
            return false;
        }

        /** Load factory edit form **/
        $(document).on('click', 'a[name="editFactory"]', function loadFactoryEditForm() {
            let $factory = $(this).attr('id');
            $.ajax({
                url: Routing.generate('admin-factory-edit', {factory: $factory}),
                method: "POST",
                success: function (response) {
                    let $factoryBlock = $('#factory_' + $factory);
                    $factoryBlock.html(response);
                    $factoryEditFormsArray[$factory] = $factoryBlock.find('#factory_form_' + $factory);
                    /** Update factory **/
                    $factoryEditFormsArray[$factory].on('submit', function (event) {
                        event.preventDefault();
                        $(this).off('submit');
                        updateFactory($factory, $factoryEditFormsArray[$factory]);
                        return false;
                    });
                    /** Factory editing cancellation **/
                    $(document).on('click', '#factory_form_cancelButton', function cancelFactoryEdit() {
                    });
                    return false;
                }
            });
            return false;
        });
    }
});