$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        /** Do not touch this **/
        let $path = Routing.generate('entries');
        let $factory = $('#entry_search_form_factory');
        let searchForm = $("#entry_search_form");
        let resetButton = $("#entry_search_form_resetButton");
        let createFolderBlock = $("#addFolder");
        let uploadFileBlock = $("#addFiles");
        let downloadFileBlock = $("#downloadFile");

        /** Seriously, it'a a bad idea  :) **/

        /** Archive entries main table factory->settings AJAX loader **/

        $factory.on("change", function settingsLoadAction() {
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
        });

        /** Archive entries main table content AJAX loader **/

        searchForm.on("submit", function searchAction(event) {
            event.preventDefault();
            $('#main-tbody').hide();
            $('#loading-spinner').show().css('display', 'contents');
            let fields = searchForm.serializeArray();
            let values = {};
            jQuery.each(fields, function (i, field) {
                values[field.name] = field.value;
            });
            $.ajax({
                url: $path,
                method: searchForm.attr('method'),
                data: values,
                success: function (response) {
                    $('#loading-spinner').hide();
                    $('#main-tbody').replaceWith(
                        $(response).find('#main-tbody')
                    );
                }
            });

            return false;
        });

        /** Archive entries main table search form reset to default stdout AJAX loader **/

        resetButton.on("click", function resetAction() {
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
        });

        /** Archive entries content loader **/

        $(document).on("click", "a[name='entryId']", openEntryContents);

        function openEntryContents() {
            let entryId = $(this).parent().attr("id");
            let entryRow = $('#entry_' + entryId);
            let contentPlace = $('#entryContent_' + entryId);
            if ($(contentPlace).is(":hidden")) {
                $.ajax({
                    url: "view",
                    method: searchForm.attr('method'),
                    data: {entryId: entryId},
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
                $.ajax({
                    url: "view_folders",
                    method: "POST",
                    data: {folderId: folderId},
                    success: function (response) {
                        folderContent.html(response);
                        loadLastUpdateInfo(null, folderId);
                    }
                });
                $.ajax({
                    url: "view_files",
                    method: "POST",
                    data: {folderId: folderId},
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
                url: "new_folder",
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
                            url: "new_folder",
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
                                        url: "view_folders",
                                        method: searchForm.attr('method'),
                                        data: {folderId: folderId},
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
                url: "new_file",
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
                            url: "new_file",
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
                                        url: "view_files",
                                        method: searchForm.attr('method'),
                                        data: {folderId: folderId},
                                        success: function (reloadResponse) {
                                            fileContent.hide();
                                            fileContent.html(reloadResponse);
                                            fileContent.show();
                                        }
                                    });
                                }
                                loadLastUpdateInfo(null, folderId);
                                /** Load flash messages **/
                                loadFlashMessagesSummary();
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
                url: "download_file",
                method: "POST",
                data: {fileId: fileId},
                success: function (downloadBlockResponse) {
                    downloadFileBlock.html($(downloadBlockResponse));
                    downloadFileBlock.show();
                }
            });
            let fileInfo = $('#file_' + fileId);
            $.ajax({
                url: "reload_file",
                method: "POST",
                data: {fileId: fileId},
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
                    url: "last_update_info",
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
                        url: "get_folder_entryId",
                        method: "POST",
                        data: {folderId: folderId},
                        success: function (entryId) {
                            $.ajax({
                                url: "last_update_info",
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
                url: "remove_file",
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
                url: "restore_file",
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
                url: "request_file",
                method: "POST",
                data: {fileId: fileId},
                success: function (fileRequest) {
                    $('#file_' + fileId).replaceWith(fileRequest);
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
                url: "remove_folder",
                method: "POST",
                data: {folderId: folderId},
                success: function (folderRemoval) {
                    $('#folder_' + folderId).replaceWith(folderRemoval);
                    folderContent.html('');
                    folderContent.hide();
                }
            });

            return false;
        }

        /** Archive entries folder restore action **/

        $(document).on("click", 'a[name="restoreFolder"]', restoreFolder);

        function restoreFolder() {
            let folderId = $(this).parent().attr("id");
            $.ajax({
                url: "restore_folder",
                method: "POST",
                data: {folderId: folderId},
                success: function (folderRestoration) {
                    $('#folder_' + folderId).replaceWith(folderRestoration);
                }
            });

            return false;
        }

        /** Archive entry removal action **/

        $(document).on("click", 'a[name="removeEntry"]', removeEntry);

        function removeEntry() {
            let entryId = $(this).parent().attr("id");
            $.ajax({
                url: "remove_entry",
                method: "POST",
                data: {entryId: entryId},
                success: function (entryRemoval) {
                    $('#entry_' + entryId).replaceWith(entryRemoval);
                }
            });

            return false;
        }

        /** Archive entry restore action **/

        $(document).on("click", 'a[name="restoreEntry"]', restoreEntry);

        function restoreEntry() {
            let entryId = $(this).parent().attr("id");
            $.ajax({
                url: "restore_entry",
                method: "POST",
                data: {entryId: entryId},
                success: function (entryRestoration) {
                    $('#entry_' + entryId).replaceWith(entryRestoration);

                }
            });

            return false;
        }

        /** Showing requesters for deleted items **/

        $(document).on("click", 'a[name="isRequested"]', function () {
            let type = $(this).parent("span").attr("id");
            let id = $(this).attr("id");
            showRequesters(type, id);
        });

        function showRequesters(type, id)
        {
            let func;
            switch (type) {
                case 'file' : func = 'file';
                break;
                case 'folder' : func = 'folder';
                break;
                case 'entry' : func = 'entry';
                break;
            }
            /*$.ajax({
                url: Routing.generate("show_requesters"),
                method: "POST",
                data: {}
            }); */

            return false;
        }

        /** Popup window close **/

        $(document).on("click", "a[name='closeForm']", function closeForm(event) {
            event.preventDefault();
            $(this).parent().hide();
        });

        /** Flash messages loader **/

        function loadFlashMessages() {
            $.ajax({
                url: "flash_messages",
                method: "POST",
                success: function (reloadFlashMessages) {
                    $('#flash-messages').replaceWith(
                        $(reloadFlashMessages));
                }
            });

            return false;
        }

        /** Flash messages summary loader **/

        function loadFlashMessagesSummary() {
            $.ajax({
                url: "flash_messages_summary",
                method: "POST",
                success: function (reloadFlashMessages) {
                    $('#flash-messages').replaceWith(
                        $(reloadFlashMessages));
                }
            });

            return false;
        }
    }
});
