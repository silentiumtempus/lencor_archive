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
            if ($formType === 'edit') {
                $path = Routing.generate('admin-entries', {entryId: $entryFormDiv.find('table').attr('id')});
            }
            $data['submit'] = false;
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
                    let $redirect = false;
                    loadFlashMessages($redirect);
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
                    let $redirect = false;
                    loadFlashMessages($redirect);
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
                    let $redirect = false;
                    /** Redirect after submit if requested **/
                    if ($button.attr('id') === 'entry_form_submitAndOpenButton' && !isNaN(response) && response !== '') {
                        $redirect = true;
                        setTimeout(function () {
                            window.location.replace(Routing.generate('entries', {entry: response}));
                        }, 5000);
                    }
                    /** Flash messages loader **/
                    loadFlashMessages($redirect);
                }
            });

            return false;
        });

        /** Flash messages loader **/

        function loadFlashMessages($redirect) {
            let $flashMessages = $('#flash-messages');
            $.ajax({
                url: Routing.generate('flash_messages'),
                method: "POST",
                success: function (reloadFlashMessages) {
                    $flashMessages.html($(reloadFlashMessages).filter('#flash-messages').children());
                    if ($flashMessages.length > 0) {
                        $flashMessages.fadeOut('fast');
                        if ($redirect === false) {
                            let id = window.setTimeout(null, 0);
                            while (id--) {
                                window.clearTimeout(id);
                            }
                        }
                    }
                    $flashMessages.fadeIn("slow");
                    setTimeout(hideFlashMessages, 14000);

                    return false;
                }
            });

            return false;
        }

        /** Close flash message manually **/

        $(document).on('click', '#close-alert', function () {
            let $fm = $('#flash-messages');
            $(this).parent().fadeOut("slow").promise().done(function () {
                (this).remove();
                if ($fm.length === 1) {
                    $fm.hide();
                }
            });
        });

        /** Flash messages hiding **/

        function hideFlashMessages() {
            let $flashMessages = $('#flash-messages');
            $flashMessages.fadeOut("slow");

            return true;

        }
    }
});