$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        /** Load settings list for selected factory **/

        $(document).on('click', 'a[name="openFactory"]', function loadSettings() {
            let factoryId = $(this).attr('id');
            $.ajax({
                url: Routing.generate('admin-settings', {factory: factoryId}),
                method: "POST",
                data: null,
                success: function (response) {
                    $('#settings').html(response);
                }
            });

            return false;
        });

        /** Load factory edit form **/

        $(document).on('click', 'a[name="editFactory"]', renameFactory);

        function renameFactory() {
            let factoryId = $(this).attr('id');
            $.ajax({
                url: Routing.generate('admin-factory-edit', {factory: factoryId}),
                method: "POST",
                success: function (response) {
                    let $factoryBlock = $('#factory_' + factoryId).children('span').first();
                    $factoryBlock.html(response);
                }
            });

            return false;
        }

        /** Update factory **/

        $(document).on("submit", 'form[name="factory_form"]', function () {
            $(this).off('submit');
            let factoryId = $(this).parent().attr('id');
            updateFactory(factoryId, $(this));
            loadFlashMessages();

            return false;
        });

        /** Factory editing cancellation **/

        $(document).on('click', '#factory_form_cancelButton', cancelFactoryUpdate);

        function cancelFactoryUpdate() {
            let factoryId = $(this).parents('span').attr('id');
            $.ajax({
                url: Routing.generate('admin-factory-load', {factory: factoryId}),
                method: "POST",
                data: null,
                success: function (factory) {
                    let $factoryBlock = $('#factory_' + factoryId).children('span').first();
                    $factoryBlock.replaceWith($(factory).find('span').first());
                }
            });

            return false;
        }


        /** Perform factory name update and show result **/

        function updateFactory(factoryId, $factoryEditForm) {
            let $factoryEditFormSerialized = $factoryEditForm.serialize();
            $.ajax({
                url: Routing.generate('admin-factory-edit', {factory: factoryId}),
                method: "POST",
                data: $factoryEditFormSerialized,
                success: function (factory) {
                    if ($(factory).filter('#factory_' + factoryId).length) {
                        let $factoryBlock = $('#factory_' + factoryId).children('span').first();
                        $factoryBlock.replaceWith($(factory).find('span').first());
                    }
                }
            });

            return false;
        }


        /** Load setting edit form **/

        $(document).on('click', 'a[name="editSetting"]', renameSetting);

        function renameSetting() {
            let settingId = $(this).attr('id');
            $.ajax({
                url: Routing.generate('admin-setting-edit', {setting: settingId}),
                method: "POST",
                success: function (settingForm) {
                    let $settingBlock = $('#setting_' + settingId).children('span').first();
                    $settingBlock.html(settingForm);
                }
            });

            return false;
        }

        /** Update setting **/

        $(document).on("submit", 'form[name="setting_form"]', function () {
            $(this).off('submit');
            let settingId = $(this).parent().attr('id');
            updateSetting(settingId, $(this));
            loadFlashMessages();

            return false;
        });

        /** Setting editing cancellation **/

        $(document).on('click', '#setting_form_cancelButton', function () {
            let settingId = $(this).parents('span').attr('id');
            $.ajax({
                url: Routing.generate('admin-setting-load', {setting: settingId}),
                method: "POST",
                data: null,
                success: function (setting) {
                    let $settingBlock = $('#setting_' + settingId).children('span').first();
                    $settingBlock.replaceWith($(setting).find('span').first());
                }
            });

            return false;
        });


        /** Perform setting update and show result **/

        function updateSetting(settingId, $settingEditForm) {
            let $settingEditFormSerialized = $settingEditForm.serialize();
            $.ajax({
                url: Routing.generate('admin-setting-edit', {setting: settingId}),
                method: "POST",
                data: $settingEditFormSerialized,
                success: function (setting) {
                    if ($(setting).filter('#setting_' + settingId).length) {
                        let $settingBlock = $('#setting_' + settingId).children('span').first();
                        $settingBlock.replaceWith($(setting).find('span').first());
                    }
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
                    setTimeout(hideFlashMessages, 7000);
                }
            });

            return false;
        }

        /** Flash messages hiding **/

        function hideFlashMessages() {
            let $flashMessages = $('#flash-messages');
            $flashMessages.fadeOut("slow");

            return false;
        }

    }
});