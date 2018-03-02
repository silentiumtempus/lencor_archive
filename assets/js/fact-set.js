$(document).ready(function () {
    if (!window.jQuery) {
    } else {
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
                    let $factoryEditForm = $factoryBlock.find('#factory_form_' + $factory);
                    /** Update factory **/
                    $factoryEditForm.on('submit', function (event) {
                        event.preventDefault();
                        $(this).off('submit');
                        updateFactory($factory, $factoryEditForm);
                    });
                    /** Factory editing cancellation **/
                    $factoryEditForm.on('click', '#factory_form_cancelButton', function () {
                        $.ajax({
                            url: Routing.generate('admin-factory-load', {factory: $factory}),
                            method: "POST",
                            data: null,
                            success: function (response) {
                                $('#factory_' + $factory).replaceWith(response);
                            }
                        });
                    });
                }
            });

            return false;
        });

        /** Perform setting update and show result **/

        function updateSetting($setting, $settingEditForm) {
            let $settingEditFormSerialized = $settingEditForm.serialize();
            $.ajax({
                url: Routing.generate('admin-setting-edit', {setting: $setting}),
                method: "POST",
                data: $settingEditFormSerialized,
                success: function (response) {
                    $('#setting_' + $setting).replaceWith(response);
                }
            })
        }

        /** Load setting edit form **/

        $(document).on('click', 'a[name="editSetting"]', function loadSettingEditForm() {
            let $setting = $(this).attr('id');
            $.ajax({
                url: Routing.generate('admin-setting-edit', {setting: $setting}),
                method: "POST",
                success: function (response) {
                    let $settingBlock = $('#setting_' + $setting);
                    $settingBlock.html(response);
                    let $settingEditForm = $settingBlock.find('#setting_form_' + $setting);
                    /** Update setting **/
                    $settingEditForm.on('submit', function (event) {
                        event.preventDefault();
                        $(this).off('submit');
                        updateSetting($setting, $settingEditForm);
                    });
                    /** Setting editing cancellation **/
                    $settingEditForm.on('click', '#setting_form_cancelButton', function () {
                        $.ajax({
                            url: Routing.generate('admin-setting-load', {setting: $setting}),
                            method: "POST",
                            data: null,
                            success: function (response) {
                                $('#setting_' + $setting).replaceWith(response);
                            }
                        });
                    });

                }
            });

            return false;
        });
    }
});