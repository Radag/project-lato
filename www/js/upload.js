function setUploadForm(inputId, config) 
{
    var uploadTemplate = $('<li>' +
        '<div class="attached-file">' +
            '<a href="" target="_blank" class="file-name"></a>' +
            '<div class="flex-spacer"></div>' +
            '<a href="" class="remove-attachment"><i class="material-icons">delete</i></a>' +
        '</div>' +
        '<div class="progress upload-file-progress">' +
            '<div class="determinate" style="width: 0%"></div>' +
        '</div> ' +
    '</li>');

    $(document).bind('dragover', function (e) {
        var dropZone = $(".dropzone-area"),
            timeout = window.dropZoneTimeout;
                    
        if (timeout) {
            clearTimeout(timeout);
        } else {
            $("#message-form-dropzone").addClass('active');
            dropZone.addClass('active');
        }
        window.dropZoneTimeout = setTimeout(function () {
            window.dropZoneTimeout = null;
            dropZone.removeClass('active');
        }, 100);
    });

    var uploadsFiles = {};
    var options = {
        dataType: 'json',
        url: config.links.upload,
        dropZone: false,
        add: function(e, data) {
            if(config.uploadBlock) {
                data.uploadId = Math.random().toString(36).substr(2, 10);
                var attachTemplate = uploadTemplate.clone();
                attachTemplate.addClass(data.uploadId);
                attachTemplate.find(".file-name").text(data.files[0].name);
                $(config.uploadBlock).append(attachTemplate).removeClass('hide');
                $(config.submitButton).prop('disabled', true);
                attachTemplate.find('.remove-attachment').on('click', function(event) {
                    event.preventDefault();
                    var id = $(this).parents('.attached-file').data('id');
                    if(id !== undefined) {
                        var link = config.links.delete.replace('XXXX', id);
                        $.get( link, function( data ) {
                            if(data.deleted) {
                                var values = $(config.inputWithIds).val();
                                $(config.inputWithIds).val(values.replace("_" + id, ""));
                                attachTemplate.remove();
                            }
                        });
                    } else {
                        data.jqXHR.abort();
                        attachTemplate.remove();
                    }
                }); 
            }            
            data.submit();
        },
        progress: function (e, data) {
            if(config.uploadBlock) {
                var progress = parseInt(data.loaded / data.total * 100, 10);
                uploadsFiles[data.uploadId] = false;
                $("." + data.uploadId).find('.determinate').css('width', progress + '%');
            }            
        },
        done: function (e, data) {
            if(config.uploadBlock) {
                uploadsFiles[data.uploadId] = true;
                var allDone = true;
                for(var i in uploadsFiles) {
                    if(!uploadsFiles[i]) {
                        allDone = false;
                    }
                }
                if(allDone) {
                    $(config.submitButton).prop('disabled', false);
                }
                var uploadedFile = data.result;
                var attachTemplate = $("." + data.uploadId);
                $(config.uploadBlock).find('div.error').remove();
                if(uploadedFile.error) {
                    attachTemplate.remove();
                    $(config.uploadBlock).append("<div class='error'>" + uploadedFile.error + "</div>");
                } else {
                    attachTemplate.find(".progress").hide();
                    attachTemplate.find(".file-name").html(uploadedFile.file.name + " <span>" + uploadedFile.file.type + "</span>");
                    setAttachmentType(uploadedFile.file.type, attachTemplate);
                    attachTemplate.find(".attached-file a").attr('href', uploadedFile.file.fullPath);
                    $(config.uploadBlock).append(attachTemplate);
                    $(config.inputWithIds).val($(config.inputWithIds).val() + "_" + uploadedFile.file.id);
                    attachTemplate.find(".attached-file").data('id', uploadedFile.file.id);
                }
            }
            if(config.after) {
                config.after(data.result);
            }
        }
    };
    
    if(config.dropZone) {
        options.dropZone = config.dropZone;
    }
    $(inputId).fileupload(options);
        
    function setAttachmentType(type, attachTemplate)
    {
        switch(type) {
            case 1:
            attachTemplate.find(".attachment-icon").addClass('image');
            attachTemplate.find(".attachment-icon .material-icons").text('image');
            break;
            case 3:
            attachTemplate.find(".attachment-icon").addClass('document');
            attachTemplate.find(".attachment-icon .material-icons").text('insert_drive_file');
            break;
            case 4:
            attachTemplate.find(".attachment-icon").addClass('spreadsheet');
            attachTemplate.find(".attachment-icon .material-icons").text('equalizer');
            break;
            case 5:
            attachTemplate.find(".attachment-icon").addClass('presentation');
            attachTemplate.find(".attachment-icon .material-icons").text('videocam');
            break;
            case 6:
            attachTemplate.find(".attachment-icon").addClass('presentation');
            attachTemplate.find(".attachment-icon .material-icons").text('description');
            break;
            default:
            attachTemplate.find(".attachment-icon").addClass('unknown');
            attachTemplate.find(".attachment-icon .material-icons").text('help_outline');
        }
    }
}

