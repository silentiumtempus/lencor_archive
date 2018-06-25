$(document).ready(function () {
    if (window.jQuery) {
        let $factory = $('#entry_form_factory');
        let $entryForm = $('#entry_form');
        let $factoryAddForm = $('#factory_form');
        let $settingAddForm = $('#setting_form');
        let $entryFormDiv = $('#entryForm');
        let $path = Routing.generate('entries-new');
        let $formType = $entryFormDiv.attr('class');
        /** Archive entries new entry page factory->settings AJAX loader **/
        function settingsLoader() {
            $factory = $($entryFormDiv).find('#entry_form_factory');
            let $data = {};
            $data[$factory.attr('name')] = $factory.val();
            if ($formType === 'new_entry') {
                $path = Routing.generate('entries-new');
            } else if ($formType === 'edit') {
                $path = Routing.generate('admin-entries', {entryId: $entryFormDiv.find('table').attr('id')});
            }
            $.ajax({
                url: $path,
                method: 'POST',
                data: $data,
                success: function (response) {
                    $('#entry_form_setting').replaceWith(
                        $(response).find('#entry_form_setting')
                    );
                }
            });

            return false;
        }

        /** Load settings list when other factory was selected **/

        $(document).on("change", '#entry_form_factory', function () {
            settingsLoader();

            return false;
        });

        /** Archive new factory submission **/

        $factoryAddForm.on("submit", function (event) {
            event.preventDefault();
            let factorySerialized = $factoryAddForm.serialize();
            $.ajax({
                url: Routing.generate('entries-new'),
                method: $factoryAddForm.attr('method'),
                data: factorySerialized,
                success: function (response) {
                    $('#flash-messages').replaceWith(
                        $(response).find('#flash-messages'));
                    $.ajax({
                        url: $path,
                        method: $entryForm.attr('method'),
                        data: null,
                        success: function (response) {
                            $('#setting_form_factory').replaceWith(
                                $(response).find('#setting_form_factory'));
                            $('#entry_form_factory').replaceWith(
                                $(response).find('#entry_form_factory'));
                        },
                    });
                }
            });

            return false;
        });

        /** Archive new setting submission **/

        $settingAddForm.on("submit", function (event) {
            event.preventDefault();
            let settingSerialized = $settingAddForm.serialize();
            $.ajax({
                url: $path,
                method: $settingAddForm.attr('method'),
                data: settingSerialized,
                success: function (response) {
                    $('#flash-messages').replaceWith(
                        $(response).find('#flash-messages'));
                    $.ajax({
                        url: $path,
                        method: $settingAddForm.attr('method'),
                        data: null,
                        success: function () {
                            let $changedFactory = $settingAddForm.find('select[id="setting_form_factory"]').val();
                            let $selectedFactoryInAddForm = $entryForm.find('select[id="entry_form_factory"]').val();
                            if ($changedFactory === $selectedFactoryInAddForm) {
                                settingsLoader();
                            }
                        }
                    });
                }
            });

            return false;
        });

        /** Archive new entry submission **/

        $entryFormDiv.on("submit", $entryForm, function (event) {
            let $button = $(document.activeElement);
            let $entryId;
            event.preventDefault();
            let entrySerialized = $('#entry_form').serialize();
            if ($formType === 'new_entry') {
                $path = Routing.generate('entries-new');
                $entryId = null;
            } else if ($formType === 'edit') {
                $entryId = $entryFormDiv.find('table').attr('id');
                $path = Routing.generate('admin-entries', {entryId: $entryId});
            }
            $.ajax({
                url: $path,
                method: 'POST',
                data: entrySerialized,
                success: function (response) {
                    if ($formType === 'new_entry') {
                        $entryId = response;
                    }
                    /** Flash messages loader **/
                    loadFlashMessages();
                    if ($button.attr('id') === 'entry_form_submitAndOpenButton' && !isNaN(response)) {
                        setTimeout(function() {document.location.href = Routing.generate('entries', {entry: response})}, 5000);
                    }
                }
            });
            $path = Routing.generate('entries-new');

            return false;
        });

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
    }
});