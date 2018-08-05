$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        require("jquery-ui/ui/widgets/autocomplete");
        /** Do not touch this **/
        let deleted = 0;
        let $path;
        if (window.location.href.indexOf("deleted") > -1) {
            deleted = 1;
            $path = Routing.generate('admin-deleted-entries', {deleted: deleted}) + "/deleted";
        } else {
            $path = Routing.generate('entries', {deleted: deleted});
        }
        let $factory = $('#entry_search_form_factory');
        let searchForm = $("#entry_search_form");
        let createFolderBlock = $("#addFolder");
        let uploadFileBlock = $("#addFiles");
        let downloadFileBlock = $("#downloadFile");
        /** Seriously, it'a a bad idea  :) **/

        /** Archive entries main table factory->settings AJAX loader **/

        $(document).on("change", "#entry_search_form_factory", settingsLoadAction);

        function settingsLoadAction() {
            let data = {};
            data[$factory.attr('name')] = $factory.val();
            $.ajax({
                url: $path,
                method: searchForm.attr('method'),
                data: data,
                success: function (response) {
                    $('#entry_search_form_setting').replaceWith(
                        $(response).find('#entry_search_form_setting')
                    );
                }
            });

            return false;
        }

        /** Archive entries main table content AJAX loader **/

        $(document).on("submit", "#entry_search_form", searchAction);

        function searchAction() {
            $('#main-tbody').hide();
            $('#loading-spinner').show().css('display', 'contents');
            let fields = searchForm.serializeArray();
            console.log(deleted);
            $.ajax({
                url: $path,
                method: searchForm.attr('method'),
                data: fields,
                success: function (response) {
                    $('#loading-spinner').hide();
                    $('#main-tbody').replaceWith(
                        $(response).find('#main-tbody')
                    );
                }
            });

            return false;
        }

        /** Archive entries main table search form reset to default stdout AJAX loader **/

        $(document).on("click", "#entry_search_form_resetButton", resetAction);

        function resetAction() {
            searchForm.trigger('reset');
            $('#main-tbody').hide();
            $('#loading-spinner').show().css('display', 'contents');
            $.ajax({
                url: $path,
                method: searchForm.attr('method'),
                data: null,
                success: function (response) {
                    $('#loading-spinner').hide();
                    $('#main-tbody').replaceWith(
                        $(response).find('#main-tbody'));
                    $('#entry_search_form_setting').attr('disabled', 'disabled');
                    $('#addFolder').hide();
                }
            });

            return false;
        }

        /** Archive entries content loader **/

        $(document).on("click", "a[name='entryId']", openEntryContents);

        function openEntryContents() {
            let entryId = $(this).parent().attr("id");
            let entryRow = $('#entry_' + entryId);
            let contentPlace = $('#entryContent_' + entryId);
            if ($(contentPlace).is(":hidden")) {
                $.ajax({
                    url: Routing.generate('entries_view'),
                    method: searchForm.attr('method'),
                    data: {entryId: entryId, deleted: deleted},
                    success: function (response) {
                        contentPlace.html($(response));
                        loadLastUpdateInfo(entryId, null);
                        let folderId = contentPlace.find('#rootEntry').children('td').attr('id');
                        openFolder(folderId);
                        contentPlace.show().css('display', 'table-cell');
                        entryRow.find('#up').css('display', 'inline-flex');
                        entryRow.find('#down').hide();
                    }
                });
            }
            else {
                $(contentPlace).hide();
                entryRow.find('#down').css('display', 'inline-flex');
                entryRow.find('#up').hide();
            }

            return false;
        }

        /** Archive entries content navigation **/

        $(document).on("click", "a[name='openFolder']", function () {
            let folderId = $(this).attr("id");
            openFolder(folderId);

            return false;
        });

        function openFolder(folderId) {
            let folderRow = $('#folder_' + folderId);
            let folderContent = $('#folderContent_' + folderId);
            let fileContent = $('#fileContent_' + folderId);
            if ($(folderContent).is(":hidden")) {
                console.log(deleted);
                $.ajax({
                    url: Routing.generate('entries_view_folders'),
                    method: "POST",
                    data: {folderId: folderId, deleted: deleted},
                    success: function (response) {
                        folderContent.html(response);
                        loadLastUpdateInfo(null, folderId);
                    }
                });
                $.ajax({
                    url: Routing.generate('entries_view_files'),
                    method: "POST",
                    data: {folderId: folderId, deleted: deleted},
                    success: function (response) {
                        fileContent.html(response);
                    }
                });
                folderContent.show();
                fileContent.show();
                folderRow.find('#up').css('display', 'inline-flex');
                folderRow.find('#down').hide();
            }
            else {
                folderContent.hide();
                fileContent.hide();
                folderRow.find('#down').css('display', 'inline-flex');
                folderRow.find('#up').hide();
            }

            return false;
        }

        /** Archive entries folder creation with form loader **/

        $(document).on("click", 'a[name="addFolder"]', createFolder);

        function createFolder() {
            let folderId = $(this).parent().attr("id");
            /** Load folder creation form **/
            $.ajax({
                url: Routing.generate('entries_new_folder'),
                method: searchForm.attr('method'),
                data: {folderId: folderId},
                success: function (loadFormResponse) {
                    createFolderBlock.html($(loadFormResponse));
                    createFolderBlock.show();
                    let $folderAddForm = createFolderBlock.find('#folder_add_form');
                    $folderAddForm.on("submit", function createFolder(event) {
                        event.preventDefault();
                        let folderSerialized = $folderAddForm.serialize();
                        /** Submit new folder **/
                        $.ajax({
                            url: Routing.generate('entries_new_folder'),
                            method: $folderAddForm.attr('method'),
                            data: folderSerialized,
                            success: function () {
                                createFolderBlock.hide();
                                if ($folderAddForm.find('select').length) {
                                    folderId = $folderAddForm.find('select[id="folder_add_form_parentFolder"]').val();
                                } else {
                                    folderId = $folderAddForm.attr('folderid');
                                }
                                let folderContent = $('#folderContent_' + folderId);
                                /** Reload folder view order **/
                                if (folderContent.is(':hidden')) {
                                    openFolder(folderId);
                                } else {
                                    $.ajax({
                                        url: Routing.generate('entries_view_folder'),
                                        method: searchForm.attr('method'),
                                        data: {folderId: folderId, deleted: deleted},
                                        success: function (reloadResponse) {
                                            folderContent.hide();
                                            folderContent.html(reloadResponse);
                                            folderContent.show();
                                            loadLastUpdateInfo(null, folderId);
                                        }
                                    });
                                }
                                loadLastUpdateInfo(null, folderId);
                                /** Load flash messages **/
                                loadFlashMessages();
                            }

                        });
                    });
                }
            });

            return false;
        }

        /** Archive entries file upload with form loader **/

        $(document).on("click", 'a[name="addFiles"]', uploadFile);

        function uploadFile() {
            let folderId = $(this).parent().attr("id");
            /** Load file upload form **/
            $.ajax({
                url: Routing.generate('entries_new_file'),
                method: searchForm.attr('method'),
                data: {folderId: folderId},
                success: function (loadFormResponse) {
                    uploadFileBlock.html($(loadFormResponse));
                    uploadFileBlock.show();
                    let $fileAddForm = uploadFileBlock.find('#file_add_form');
                    $fileAddForm.on("submit", function uploadFile(event) {
                        event.preventDefault();
                        let fileSerialized = new FormData($(this)[0]);
                        /** Submit new file **/
                        $.ajax({
                            url: Routing.generate('entries_new_file'),
                            method: $fileAddForm.attr('method'),
                            data: fileSerialized,
                            processData: false,
                            contentType: false,
                            success: function () {
                                uploadFileBlock.hide();
                                if ($fileAddForm.find('select').length) {
                                    folderId = $fileAddForm.find('select[id="file_add_form_parentFolder"]').val();
                                } else {
                                    folderId = $fileAddForm.attr('folderid');
                                }
                                let folderContent = $('#folderContent_' + folderId);
                                let fileContent = $('#fileContent_' + folderId);
                                if (folderContent.is(':hidden')) {
                                    openFolder(folderId);
                                } else {
                                    /** Reload folder view order **/
                                    $.ajax({
                                        url: Routing.generate('entries_view_files'),
                                        method: searchForm.attr('method'),
                                        data: {folderId: folderId, deleted: deleted},
                                        success: function (reloadResponse) {
                                            fileContent.hide();
                                            fileContent.html(reloadResponse);
                                            fileContent.show();
                                        }
                                    });
                                }
                                loadLastUpdateInfo(null, folderId);
                                /** Load flash messages **/
                                loadFlashMessagesSummary(true);
                            }
                        });
                    });
                }
            });

            return false;
        }

        /** Archive entries file download with md5 verification **/

        $(document).on("click", 'a[name="downloadFile"]', downloadFile);

        function downloadFile() {
            let fileId = $(this).parent().attr("id");
            /** Load file download block **/
            $.ajax({
                url: Routing.generate('entries_download_file', {file: fileId}),
                method: "POST",
                data: null,
                success: function (downloadBlockResponse) {
                    downloadFileBlock.html($(downloadBlockResponse));
                    downloadFileBlock.show();
                }
            });
            reloadFileInfo(fileId);

            return false;
        }

        /** Common function to reload file rowon file state change **/

        function reloadFileInfo(fileId) {
            let fileInfo = $('#file_' + fileId);
            $.ajax({
                url: Routing.generate('entries_reload_file', {file: fileId}),
                method: "POST",
                data: null,
                success: function (reloadFileInfo) {
                    fileInfo.replaceWith(reloadFileInfo);
                }
            });

            return false;
        }

        /** Last entry update information load & refresh **/

        function loadLastUpdateInfo(entryId, folderId) {
            if (entryId !== null) {
                $('#update-info-spinner').show().css('display', 'contents');
                $.ajax({
                    url: Routing.generate('entries_last_update_info'),
                    method: "POST",
                    data: {entryId: entryId},
                    success: function (reloadLastUpdateInfo) {
                        $('#update-info-spinner').hide();
                        $($('#entryContent_' + entryId).find('#last-update')).html(reloadLastUpdateInfo);
                    }
                });
            }
            else if (folderId !== null) {
                $('#update-info-spinner').show().css('display', 'contents');
                $.ajax({
                        url: Routing.generate('entries_get_folder_entryId'),
                        method: "POST",
                        data: {folderId: folderId},
                        success: function (entryId) {
                            $.ajax({
                                url: Routing.generate('entries_last_update_info'),
                                method: "POST",
                                data: {folderId: folderId},
                                success: function (reloadLastUpdateInfo) {
                                    $('#update-info-spinner').hide();
                                    $($('#entryContent_' + entryId).find('#last-update')).html(reloadLastUpdateInfo);
                                }
                            });
                        }
                    }
                );
            }

            return false;
        }

        /** Archive entries file removal action **/

        $(document).on("click", 'a[name="removeFile"]', removeFile);

        function removeFile() {
            let fileId = $(this).parent().attr("id");
            $.ajax({
                url: Routing.generate('entries_remove_file'),
                method: "POST",
                data: {fileId: fileId},
                success: function (fileRemoval) {
                    $('#file_' + fileId).replaceWith(fileRemoval);
                }
            });

            return false;
        }

        /** Archive entries file restore action **/

        $(document).on("click", 'a[name="restoreFile"]', restoreFile);

        function restoreFile() {
            let fileId = $(this).parent().attr("id");
            $.ajax({
                url: Routing.generate('entries_restore_file'),
                method: "POST",
                data: {fileId: fileId},
                success: function (fileRestoration) {
                    $('#file_' + fileId).replaceWith(fileRestoration);
                }
            });

            return false;
        }

        /** Archive entries file request action **/

        $(document).on("click", 'a[name="requestFile"]', requestFile);

        function requestFile() {
            let fileId = $(this).parent().attr("id");
            $.ajax({
                url: Routing.generate('entries_request_file'),
                method: "POST",
                data: {fileId: fileId},
                success: function (fileRequest) {
                    $('#file_' + fileId).replaceWith(fileRequest);
                }
            });

            return false;
        }

        /** Archive entries file undelete action **/

        $(document).on("click", 'a[name="undeleteFile"]', unDeleteFile)

        function unDeleteFile() {
            let fileId = $(this).parent().attr("id");
            $.ajax({
                url: Routing.generate('entries_undelete_file'),
                method: "POST",
                data: {fileId: fileId},
                success: function (fileUnDeleted) {
                    $('#file_' + fileId).replaceWith(fileUnDeleted);
                    loadFlashMessages();
                }
            });

            return false;
        }

        /** Archive entries file rename action **/

        $(document).on("click", 'a[name="renameFile"]', renameFile);

        function renameFile() {
            let fileId = $(this).parent().attr("id");
            let $formPlace = $('#file_' + fileId).children('span').first();
            $.ajax({
                url: Routing.generate('entries_rename_file', {file: fileId}),
                method: "POST",
                data: null,
                success: function (renameForm) {
                    $formPlace.html(renameForm);
                }
            });

            return false;
        }

        /** Update folder **/
        $(document).on("submit", 'form[name="file_rename_form"]', function () {
            $(this).off('submit');
            let fileId = $(this).parent().attr('id');
            updateFile(fileId, $(this));
            loadFlashMessages();

            return false;
        });

        /** File editing cancellation **/
        $(document).on('click', '#file_rename_form_cancelButton', function () {
            let fileId = $(this).parents('span').attr('id');
            $.ajax({
                url: Routing.generate('entries_reload_file', {file: fileId}),
                method: "POST",
                data: null,
                success: function (file) {
                    $($('#file_' + fileId).children('span').first()).replaceWith($(file).children('span').first());
                }
            });
        });

        /** Perform entry file update and show result **/

        function updateFile(fileId, $fileRenameForm) {
            let $fileRenameFormSerialized = $fileRenameForm.serialize();
            $.ajax({
                url: Routing.generate('entries_rename_file', {file: fileId}),
                method: "POST",
                data: $fileRenameFormSerialized,
                success: function (file) {
                    if ($(file).filter('#file_' + fileId).length) {
                        $($('#file_' + fileId).children('span').first()).replaceWith($(file).children('span').first());
                    }
                }
            });

            return false;
        }

        /** Confirm file delete **/

        $(document).on('click', 'a[name="deleteFile"]', deleteFile);

        function deleteFile() {
            let fileId = $(this).parent().attr("id");
            $.ajax({
                url: Routing.generate('entries_delete_file', {file: fileId}),
                method: "POST",
                data: null,
                success: function (file) {
                    if (file === '1') {
                        $('#file_'+fileId).remove();
                    }
                    loadFlashMessages();
                }

            });

            return false;
        }

        /** Archive entries folder removal action **/

        $(document).on("click", 'a[name="removeFolder"]', removeFolder);

        function removeFolder() {
            let folderId = $(this).parent().attr("id");
            let folderContent = $('#folderContent_' + folderId);
            $.ajax({
                url: Routing.generate('entries_remove_folder'),
                method: "POST",
                data: {folderId: folderId},
                success: function (folderRemoval) {
                    $('#folder_' + folderId).replaceWith(folderRemoval);
                    folderContent.html('');
                    folderContent.hide();
                    loadLastUpdateInfo(null, folderId);
                }
            });

            return false;
        }

        /** Archive entries folder restore action **/

        $(document).on("click", 'a[name="restoreFolder"]', restoreFolder);

        function restoreFolder() {
            let folderId = $(this).parent().attr("id");
            let $folderEntry = null;
            $.ajax({
                url: Routing.generate('entries_restore_folder'),
                method: "POST",
                data: {folderId: folderId},
                success: function (folderRestoration) {
                    $.ajax({
                        url: Routing.generate('entries_reload_folder'),
                        method: "POST",
                        data: {foldersArray: folderRestoration},
                        success: function (folderReload) {
                            jQuery.each(folderRestoration, function (index, value) {
                                $folderEntry = $('#folder_' + value);
                                let $temp = $(folderReload).filter('#folder_' + value);
                                $($folderEntry.children('ul').first()).replaceWith($temp.children('ul').first());
                                $folderEntry.removeClass('marked');
                            });
                        }
                    });
                    loadLastUpdateInfo(null, folderId);
                }
            });

            return false;
        }

        /** Archive entries folder request action **/

        $(document).on("click", 'a[name="requestFolder"]', requestFolder);

        function requestFolder() {
            let folderId = $(this).parent().attr("id");
            $.ajax({
                url: Routing.generate('entries_request_folder'),
                method: "POST",
                data: {folderId: folderId},
                success: function (folderRequest) {
                    $($('#folder_' + folderId).children('ul').first()).replaceWith($(folderRequest).children('ul').first());
                    loadLastUpdateInfo(null, folderId);
                }
            });

            return false;
        }

        /** Archive entries folder rename action **/

        $(document).on("click", 'a[name="renameFolder"]', renameFolder);

        function renameFolder() {
            let folderId = $(this).parent().attr('id');
            let formPlace = $('#folder_' + folderId).find('span').first();
            $.ajax({
                url: Routing.generate('entries_rename_folder', {folder: folderId}),
                method: "POST",
                data: null,
                success: function (renameForm) {
                    formPlace.html($(renameForm));
                }
            });

            return false;
        }

        /** Update folder **/

        $(document).on("submit", 'form[name="folder_rename_form"]', function () {
            $(this).off('submit');
            let folderId = $(this).parent().attr('id');
            updateFolder(folderId, $(this));
            loadFlashMessages();

            return false;
        });

        /** Folder editing cancellation **/

        $(document).on('click', '#folder_rename_form_cancelButton', function () {
            let folderId = $(this).parents('span').attr('id');
            $.ajax({
                url: Routing.generate('entries_reload_folder', {folder: folderId}),
                method: "POST",
                data: null,
                success: function (folder) {
                    $($('#folder_' + folderId).children('ul').first()).replaceWith($(folder).children('ul').first());
                }
            });
        });

        /** Perform entry folder update and show result **/

        function updateFolder(folderId, $folderRenameForm) {
            let $folderRenameFormSerialized = $folderRenameForm.serialize();
            $.ajax({
                url: Routing.generate('entries_rename_folder', {folder: folderId}),
                method: "POST",
                data: $folderRenameFormSerialized,
                success: function (folder) {
                    if ($(folder).filter('#folder_' + folderId).length) {
                        $($('#folder_' + folderId).children('ul').first()).replaceWith($(folder).children('ul').first());
                    }
                }
            });

            return false;
        }

        /** Archive entry removal action **/

        $(document).on("click", 'a[name="removeEntry"]', removeEntry);

        function removeEntry() {
            let entryId = $(this).parent().attr("id");
            $.ajax({
                url: Routing.generate('entries_remove_entry'),
                method: "POST",
                data: {entryId: entryId},
                success: function (entryRemoval) {
                    $('#entry_' + entryId).replaceWith(entryRemoval);
                    if ($('#entryContent_' + entryId).is(':visible')) {
                        loadLastUpdateInfo(entryId, null);
                    }
                }
            });

            return false;
        }

        /** Archive entry restore action **/

        $(document).on("click", 'a[name="restoreEntry"]', restoreEntry);

        function restoreEntry() {
            let entryId = $(this).parent().attr("id");
            $.ajax({
                url: Routing.generate('entries_restore_entry'),
                method: "POST",
                data: {entryId: entryId},
                success: function (entryRestoration) {
                    $('#entry_' + entryId).replaceWith(entryRestoration);
                    if ($('#entryContent_' + entryId).is(':visible')) {
                        loadLastUpdateInfo(entryId, null);
                    }
                }
            });

            return false;
        }

        /** Archive entry request action **/

        $(document).on("click", 'a[name="requestEntry"]', requestEntry);

        function requestEntry() {
            let entryId = $(this).parent().attr("id");
            $.ajax({
                url: Routing.generate('entries_request_entry'),
                method: "POST",
                data: {entryId: entryId},
                success: function (entryRequest) {
                    $('#entry_' + entryId).replaceWith(entryRequest);
                    if ($('#entryContent_' + entryId).is(':visible')) {
                        loadLastUpdateInfo(entryId, null);
                    }
                }
            });

            return false;
        }

        /** Showing requesters for deleted items **/

        $(document).on("click", 'a[name="isRequested"]', function (event) {
            let type = $(this).parent().attr("id");
            let element = $(this);
            showRequesters(type, element, event);

            return false;
        });

        function showRequesters(type, element, event) {
            let func;
            let $blockDuplicate = $('.requesters-block');
            if ($blockDuplicate.length) {
                $blockDuplicate.remove();
            }
            let requestersBlock = $.parseHTML('<span></span>');
            $(requestersBlock).addClass('requesters-block text-left non-opaque');
            switch (type) {
                case 'file' :
                    func = 'file';
                    break;
                case 'folder' :
                    func = 'folder';
                    break;
                case 'entry' :
                    func = 'entry';
                    let x = event.pageX - 20;
                    let y = event.pageY - 10;
                    $(requestersBlock).css('left', x);
                    $(requestersBlock).css('top', y);
                    break;
            }
            let spinner = $.parseHTML('<i> </i>');
            $(spinner).addClass('fa fa-spinner fa-pulse fa-2x fa-fw non-opaque margin-auto');
            $(spinner).appendTo(requestersBlock);
            $(requestersBlock).prependTo(element.parent());
            $.ajax({
                url: Routing.generate('show_requesters'),
                method: "POST",
                data: {type: type, id: element.attr('id')},
                success: function (requesters) {
                    $(requestersBlock).empty();
                    $(requesters).appendTo(requestersBlock);
                    return false;
                }
            });

            $(document).on('mouseleave', 'a[name="isRequested"]', function () {
                $(requestersBlock).remove();

                return false;
            });

            return false;
        }

        /** Popup window close **/

        $(document).on("click", "a[name='closeForm']", function closeForm(event) {
            event.preventDefault();
            $(this).parent().hide();
        });

        /** Flash messages loader **/

        function loadFlashMessages($redirect) {
            let $flashMessages = $('#flash-messages');
            $.ajax({
                url: Routing.generate('flash_messages'),
                method: "POST",
                success: function (reloadFlashMessages) {
                    if ($flashMessages.length > 0) {
                        $flashMessages.fadeOut('fast');
                        if ($redirect === false) {
                            let id = window.setTimeout(null, 0);
                            while (id--) {
                                window.clearTimeout(id);
                            }
                        }
                    }
                    $flashMessages.html($(reloadFlashMessages).filter('#flash-messages').children());
                    $flashMessages.fadeIn("slow");
                    setTimeout(hideFlashMessages, 7000);

                    return false;
                }
            });

            return false;
        }

        /** Close flash message manually **/

        $(document).on('click', '#close-alert', function () {
            $(this).parent().fadeOut("slow");
        });


        /** Flash messages summary loader **/

        function loadFlashMessagesSummary(clear) {
            let $flashMessages = $('#flash-messages');
            $.ajax({
                url: Routing.generate('flash_messages_summary'),
                method: "POST",
                success: function (reloadFlashMessages) {
                    $flashMessages.html($(reloadFlashMessages).filter('#flash-messages').children());
                    $flashMessages.fadeIn("slow");
                    if (clear) {
                        clearFlashMessages();
                    }
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

        /** Flash messages clear for batch file upload to prevent them from overflowing the page **/

        function clearFlashMessages() {
            $.ajax({
                url: Routing.generate('flash_messages_clear'),
                method: "POST"
            });

            return false;
        }

        /** Search form auto completion hints loader **/

        $('#entry_search_form_archiveNumber,' +
            '#entry_search_form_registerNumber, ' +
            '#entry_search_form_contractNumber, ' +
            '#entry_search_form_fullConclusionName'
        ).each(function (i, input) {
            input = $(input);
            input.autocomplete({
                minLength: 1,
                showNoSuggestionNotice: true,
                noSuggestionNotice: 'No result',
                source: function (request, response) {
                    let $fields = searchForm.serializeArray();
                    // @TODO: refactor this regex
                    let $field = /\[(.+)/.exec(input.attr('name'))[0].replace(/[^\w\s]/gi, '');
                    $.ajax({
                        url: Routing.generate('entries_search_hints', {field: $field}),
                        method: "POST",
                        data: $fields,
                        success: function (hints) {
                            response(hints);

                        }
                    });
                },
            });
        });
    }
});
