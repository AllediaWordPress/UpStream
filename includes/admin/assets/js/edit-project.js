(function ($, $data) {
    $('#upstream_mimlestone_data select').select2({
        allowClear: true
    });

    function initProject () {
        var $box = $(document.getElementById('post-body'));

        var groups = [
            '#_upstream_project_milestones',
            '#_upstream_project_tasks',
            '#_upstream_project_bugs',
            '#_upstream_project_files'
        ];

        $(groups).each(function (index, element) {

            var $group = $box.find(element);
            var $items = $group.find('.cmb-repeatable-grouping');

            // UI stuff
            $items.addClass('closed');
            hideFirstItemIfEmpty($group);
            hideFieldWrap($group);

            // add dynamic data into group row title
            replaceTitles($group);
            addAvatars($group);

            // permissions
            publishPermissions($group);
            deletePermissions($group);
            fileFieldPermissions($group);

            // when we do something
            $group
                .on('cmb2_add_row', function (evt) {
                    addRow($group);
                })
                .on('change cmb2_add_row cmb2_shift_rows_complete', function (evt) {
                    resetGroup($group);
                })
                .on('click button.cmb-remove-group-row', function (evt) {
                    if ($(evt.target).hasClass('cmb-remove-group-row')) {
                        $($group).each(function (i, e) {
                            var e = $(e);
                            var e_id = e.attr('id');

                            //resetGroup(e);

                            $(groups).each(function (i, e) {
                                var $g = $box.find(e);

                                resetGroup($g);

                                if ($g.attr('id') === '_upstream_project_tasks' || $g.attr('id') === '_upstream_project_bugs') {
                                    displayEndDate($g);
                                }
                            });

                            var $m = $('#_upstream_project_milestones');
                            displayMilestoneProgress($m);
                            displayMilestoneIcon($m);

                            var $t = $('#_upstream_project_tasks');
                            displayStatusColor($t);
                            displayMilestoneIcon($t);
                            displayProgress($t);

                            displayStatusColor($('#_upstream_project_bugs'));
                        });
                    }
                })
                .on('keyup', titleOnKeyUp);

            // milestone specific
            if ($group.attr('id') == '_upstream_project_milestones') {

                displayMilestoneProgress($group);
                displayMilestoneIcon($group);

                $group
                    .on('change cmb2_add_row cmb2_shift_rows_complete', function (evt) {
                        displayMilestoneProgress($group);
                        displayMilestoneIcon($group);
                    });

            }

            // task specific
            if ($group.attr('id') == '_upstream_project_tasks') {

                displayStatusColor($group);
                displayMilestoneIcon($group);
                displayProgress($group);
                displayEndDate($group);

                $group
                    .on('change cmb2_add_row cmb2_shift_rows_complete', function (evt) {
                        displayStatusColor($group);
                        displayMilestoneIcon($group);
                        displayProgress($group);
                        displayEndDate($group);
                    });
            }

            // bug specific
            if ($group.attr('id') == '_upstream_project_bugs') {

                displayStatusColor($group);
                displayEndDate($group);

                $group
                    .on('change cmb2_add_row cmb2_shift_rows_complete', function (evt) {
                        displayStatusColor($group);
                        displayEndDate($group);
                    });
            }

        });
    }

    function resetGroup ($group) {
        replaceTitles($group);
        addAvatars($group);
    }

    /*
     * Disable 'add new' button if permissions don't allow it.
     * Used in all groups.
     */
    function publishPermissions ($group) {
        if (!$group.find('.hidden').attr('data-publish')) {
            $group.find('.cmb-add-row button').prop('disabled', true).prop('title', 'You do not have permission for this');
        }
    };

    /*
     * Disable 'delete' button if permissions don't allow it.
     * Used in all groups.
     */
    function deletePermissions ($group) {
        $group.find('.cmb-repeatable-grouping').each(function () {
            var isOwner = $(this).find('[data-owner]').attr('data-owner');
            if (isOwner != 'true') {
                $(this).find('button.cmb-remove-group-row').prop('disabled', true).prop('title', 'You do not have permission for this');
            }
        });
    };

    /*
     * Disable 'upload file' button if permissions don't allow it.
     * Used in bugs and files.
     */
    function fileFieldPermissions ($group) {
        $group.find('.cmb-repeatable-grouping').each(function () {
            var file = $(this).find('.cmb-type-file');
            var disabled = $(file).find('[data-disabled]').attr('data-disabled');
            if (disabled == 'true') {
                $(this).on('click', '.cmb-attach-list li, .cmb2-media-status .img-status img, .cmb2-media-status .file-status > span', function () {
                    return false;
                });
                $(file).find('input.cmb2-upload-button').prop('disabled', true).prop('title', 'You do not have permission for this');
                $(file).find('.cmb2-remove-file-button').hide();
            }
        });
    };

    /*
     * Hides the row if there is only 1 and it is empty.
     *
     */
    function hideFirstItemIfEmpty ($group) {
        if ($group.attr('id') == '_upstream_project_milestones') {
            var $items = $group.find('.cmb-repeatable-grouping').first();
            $items.removeClass('closed');
            return;
        }

        if ($group.find('.hidden').attr('data-empty') == '1') {
            if ($group.find('.cmb-repeatable-grouping').length == 1) {
                $group.find('.cmb-repeatable-grouping').hide();
            }
        }
    };

    /*
     * Hide the field wrapping row if an input field has been hidden.
     * Via a filter such as add_filter( 'upstream_bug_metabox_fields', 'upstream_bugs_hide_field_for_role', 99, 3 );
     */
    function hideFieldWrap ($group) {
        $group.find('input, textarea, select').each(function () {
            if ($(this).hasClass('hidden')) {
                $(this).parents('.cmb-repeat-group-field').addClass('hidden');
            }
        });
    };

    /*
     * Displays the avatar in the title.
     * Used in all groups.
     */
    function addAvatars ($group) {

        $group.find('.cmb-repeatable-grouping').each(function () {
            var $this = $(this);
            var user_created = $this.find('[data-user_created_by]').attr('data-user_created_by');
            var av_created = $this.find('[data-avatar_created_by]').attr('data-avatar_created_by');

            // create the boxes to hold the images first
            $this.find('h3 span.title').prepend('<div class="av-created"></div><div class="av-assigned"></div>');

            if (av_created) {
                $this.find('.av-created').html('<img title="Created by: ' + user_created + '" src="' + av_created + '" height="25" width="25" />').show();
            } else {
                $this.find('.av-created').hide();
            }

            var assigneesWrapper = $this.find('.av-assigned');
            assigneesWrapper.html('');

            var assignees = $this.find('[data-assignees]').attr('data-assignees');
            if (assignees) {
                try {
                    var assigneesNames = [];

                    assignees = JSON.parse(assignees);
                    if (assignees
                        && assignees.data
                        && assignees.data.length > 0
                    ) {
                        var assignee = assignees.data[0];
                        assigneesWrapper.html('<img src="' + assignee.avatar + '" height="25" width="25" />');

                        if (assignees.data.length > 1) {
                            assigneesWrapper.append($('<span class="o-badge">+' + (assignees.data.length - 1) + '</span>'));
                        }

                        for (var assigneeIndex = 0; assigneeIndex < assignees.data.length; assigneeIndex++) {
                            assigneesNames.push(assignees.data[assigneeIndex].name);
                        }
                    }

                    assigneesWrapper.attr('title', $data.l.LB_ASSIGNED_TO + ': ' + assigneesNames.join(', ')).show();
                } catch (e) {
                    // Do nothing.
                }
            }
        });
    };

    /*
     * Displays the title in the title.
     * Used in all groups.
     */
    function replaceTitles ($group) {

        if ($group && $group.attr('id') == '_upstream_project_milestones') {
            $group.find('.cmb-group-title').each(function () {
                var $this = $(this);
                var title = $this.next().find('[id$="milestone"]').val();
                var start = $this.next().find('[id$="start_date"]').val();
                var end = $this.next().find('[id$="end_date"]').val();
                var dates = '<div class="dates">' + start + ' - ' + end + '</div>';
                if (title) {
                    $this.html('<span class="title">' + title + '</span>' + dates);
                }
            });

        } else {

            $group.find('.cmb-group-title').each(function () {
                var $this = $(this);
                var title = $this.next().find('[id$="title"]').val();
                var grouptitle = $group.find('[data-grouptitle]').data('grouptitle');
                if (!title) {
                    var $row = $this.parents('.cmb-row.cmb-repeatable-grouping');
                    var rowindex = $row.data('iterator');
                    var newtitle = grouptitle.replace('{#}', (rowindex + 1));
                    $this.html('<span class="title">' + newtitle + '</span>');
                } else {
                    $this.html('<span class="title">' + title + '</span>');
                }
                if (grouptitle == 'Task {#}')
                    displayProgress($group);
            });

        }
    };

    function titleOnKeyUp (evt) {
        var $group = $(evt.target).parents('.cmb2-postbox');
        replaceTitles($group);
        addAvatars($group);
    };

    /*
     * Displays the total milestone progress in the title.
     * Only used on the Milestones group.
     */
    function displayMilestoneProgress ($group) {
        $group.find('.cmb-repeatable-grouping').each(function () {
            var $this = $(this);
            var title = $this.find('.cmb-group-title .title').text();
            if (title) {
                var progress = $('ul.milestones li .title:contains(' + title + ')').next().next().text();
            } else {
                var progress = '0';
            }
            progress = progress ? progress : '0';
            $this.find('.progress').remove();
            $this.append('<span class="progress"><progress value="' + progress + '" max="100"></progress></span>');
        });
    };

    /*
     * Displays the milestone icon in the title.
     * Used in tasks and bugs.
     */
    function displayMilestoneIcon ($group) {
        $group.find('.cmb-repeatable-grouping').each(function () {
            var $this = $(this);
            var milestone = $this.find('[id$="milestone"]').val();

            if (milestone) {
                $this.find('.on-title.dashicons').remove();
                var color = $('ul.milestones .title:contains(' + milestone + ')').next().text();

                $this.find('button.cmb-remove-group-row.dashicons-before').after('<span style="color: ' + color + '" class="dashicons dashicons-flag on-title"></span> ');
            }
        });
    };

    /*
     * Displays the status in the title.
     * Used in bugs and tasks.
     */
    function displayStatusColor ($group) {
        $group.find('.cmb-group-title').each(function () {
            var $this = $(this);
            var status = $this.next().find('[id$="status"] option:selected').text();
            if (status) {
                var $parent = $this.parents('.cmb2-wrap.form-table');
                color = $parent.find('ul.statuses li .status:contains(' + status + ')').next().text();
                color = color ? color : 'transparent';
                $this.append('<span class="status" style="background: ' + color + '">' + status + '</span>');
            }
        });
    };

    /*
     * Displays the task end date in the title.
     */
    function displayEndDate ($group) {
        $group.find('.cmb-group-title').each(function () {
            var $this = $(this);
            var date = $this.next().find('[id$="end_date"], [id$="due_date"]').val();
            if (date) {
                $this.append('<span class="dates">End: ' + date + '</span>');
            }
        });
    };

    /*
     * Displays the currently selected progress in the title.
     * Only used on the Tasks group.
     */
    function displayProgress ($group) {
        $group.find('.cmb-repeatable-grouping').each(function () {
            var $this = $(this);
            var progress = $this.find('[id$="progress"]').val();
            progress = progress ? progress : '0';
            $this.find('.progress').remove();
            $this.append('<span class="progress"><progress value="' + progress + '" max="100"></progress></span>');
        });
    };

    var emptyClickEvent = function (e) {
        e.preventDefault();
        e.stopPropagation();
    };

    $(document).ready(function () {
        $('.postbox.cmb-row.cmb-repeatable-grouping[data-iterator] button.cmb-remove-group-row').each(function () {
            var self = $(this);

            if (self.attr('disabled') === 'disabled') {
                self.attr('data-disabled', 'disabled');
                self.on('click', emptyClickEvent);
            }
        });

        $('div[data-groupid]').on('click', 'button.cmb-remove-group-row', function (e) {
            var self = $(this);
            var groupWrapper = $(self.parents('div[data-groupid].cmb-nested.cmb-field-list.cmb-repeatable-group'));

            setTimeout(function () {
                $('.postbox.cmb-row.cmb-repeatable-grouping .cmb-remove-group-row[data-disabled]', groupWrapper).attr('disabled', 'disabled');
            }, 50);
        });
    });

    /*
     * When adding a new row
     *
     */
    function addRow ($group) {
        // if first item is hidden, then show it
        var first = $group.find('.cmb-nested .cmb-row')[0];
        if ($(first).is(':hidden')) {
            $(first).show();
            $(first).removeClass('closed');
            $(first).next().remove();
        }

        // enable all fields in this row and reset them
        var $row = $group.find('.cmb-repeatable-grouping').last();
        $row.addClass('is-new');
        $row.find('input, textarea, select').not(':button,:hidden').val('');
        $row.find(':input').prop({'disabled': false, 'readonly': false});
        $row.find('[data-user_assigned]').attr('data-user_assigned', '');
        $row.find('[data-user_created_by]').attr('data-user_created_by', '');
        $row.find('[data-avatar_assigned]').attr('data-avatar_assigned', '');
        $row.find('[data-avatar_created_by]').attr('data-avatar_created_by', '');
        $row.find('.up-o-tab').removeClass('nav-tab-active');
        $row.find('.up-o-tab[data-target=".up-c-tab-content-data"]').addClass('nav-tab-active');

        // Check if we have a defined default value in the attributes.
        $row.find('input, textarea, select').each(function () {
            var $self = $(this);
            var $wrapper = $self.closest('.cmb-row,.form-group');

            if ($wrapper.data('default')) {
                var defaultValue = $wrapper.data('default');

                // Is this element a select field?
                if (this.tagName === 'SELECT') {
                    defaultValue = defaultValue.split(',');

                    $.each(defaultValue, function (i, value) {
                        $self.find('option').each(function (i, option) {
                            if ($(option).prop('value') === value) {
                                $(option).prop('selected', true);
                            }
                        });
                    });
                } else if (this.tagName === 'INPUT' && $(this).prop('type') === 'radio') {
                    if ($self.prop('value') === defaultValue) {
                        $self.prop('checked', true);
                    }
                } else {
                    $self.val(defaultValue);
                }
            }
        });

        setTimeout(function () {
            $row.find('.up-o-tab[data-target=".up-c-tab-content-comments"]').remove();
            $row.find('.up-c-tabs-header').remove();
            $row.find('.cmb-row[data-fieldtype="comments"]').remove();
            // Recreate select2 fields.
            $row.find('.select2-container').remove();
            $row.find('.o-select2').select2();
        }, 25);

        $group.find('.cmb-add-row span').remove();

        window.wp.autosave.server.triggerSave();

        $('.cmb-remove-group-row[data-disabled]', $row).attr('data-disabled', null);
        $('.cmb-remove-group-row[data-disabled]', $group).each(function () {
            $(this).attr('disabled', 'disabled');
        });
    }

    /**
     * Add or remove users to "Assigned To" fields dynamically.
     */
    function toggleClientUsersFromAssignedToFields (e) {
        var self = $(this);
        var isChecked = self.is(':checked');

        var user_id = self.val();

        var fields = $('#post select.o-select2[name$="[assigned_to][]"]');
        var existentOptions = fields.find('option[value="' + user_id + '"]');

        if (isChecked) {
            if (existentOptions.length === 0) {
                var user_name = $('label', self.parent()).text().trim();

                var newOption = new Option(user_name, user_id, false, false);

                fields.append(newOption).trigger('change');
            }
        } else {
            if (existentOptions.length > 0) {
                existentOptions.remove();
                fields.trigger('change');
            }
        }
    }

    $('.cmb-row.cmb2-id--upstream-project-client-users').on('change', 'input[type="checkbox"]', toggleClientUsersFromAssignedToFields);

    /*
     * Shows a clients users dynamically via AJAX
     */
    function showClientUsers () {

        var $box = $(document.getElementById('_upstream_project_details'));
        var $ul = $box.find('.cmb2-id--upstream-project-client-users ul');

        getUsers = function (evt) {

            var $this = $(evt.target);
            var client_id = $this.val();

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    action: 'upstream_admin_ajax_get_clients_users',
                    client_id: client_id,
                    project_id: $('#post_ID').val()
                },
                success: function (response) {
                    $ul.empty();

                    if (typeof response.data === 'string' && response.data) {
                        $ul.append(response.data);
                    } else if (response.data.msg) {
                        $ul.append('<li>' + response.data.msg + '</li>');
                    }
                }
            });

            return false;

        };

        noUsers = function () {
            if ($ul.find('li').length == 0) {
                $ul.append('<li>' + upstream_project.l.MSG_NO_CLIENT_SELECTED + '</li>');
            }
        };

        noUsers();

        $box
            .on('keyup change', '#_upstream_project_client', function (evt) {
                getUsers(evt);
            });

    }

    // kick it all off
    initProject();
    showClientUsers();

    var showValidationError = function (errorMessage) {
        if ($('.has-error').length == 0 || errorMessage == '') {
            return;
        }

        var box = $('<div>')
            .addClass('upstream_validation_error_box')
            .text(errorMessage)
            .appendTo($('#submitdiv'));

        var icon = $('<span>')
            .addClass('dashicons dashicons-warning')
            .appendTo(box);

        window.setTimeout(function () {
            hideValidationError();
        }, 8000);
    };

    var hideValidationError = function () {
        var box = $('.upstream_validation_error_box');
        var removeBox = function () {
            box.remove();
        };

        box.fadeOut(400, removeBox);
    };

    /*
     * Deal with invalid fields to make sure they are visible when there is an error. If hidden, there will be an error:
     * "An invalid form control with name.... is not focusable."
     * It will happen on Chrome if the field is invalid and not visible.
     */
    $('form#post').find('input,textarea,select').on('invalid', function (e) {
        // Check if its container is closed and open it.
        if ($(this).closest('.postbox.closed')) {
            $(this).closest('.postbox.closed').removeClass('closed');
        }
    });

    $('form#post').on('submit', function (e) {
        var tasksWrapper = $('#_upstream_project_tasks_repeat');
        var tasks = $('.postbox.cmb-row.cmb-repeatable-grouping', tasksWrapper);

        for (var t = 0; t < tasks.length; t++) {
            var taskWrapper = $(tasks[t]);
            if (taskWrapper.css('display') !== 'none') {
                var taskTitleField = $('input.task-title', taskWrapper);
                if (taskTitleField.val().trim().length === 0) {
                    taskTitleField.addClass('has-error');

                    $(taskTitleField.parents('.postbox.cmb-row.cmb-repeatable-grouping')).removeClass('closed');
                    $(taskTitleField.parents('.postbox.cmb2-postbox')).removeClass('closed');

                    e.preventDefault();
                    e.stopPropagation();

                    taskTitleField.focus();
                    showValidationError(upstream_project.l.MSG_TITLE_CANT_BE_EMPTY);

                    return false;
                }
            }
        }

        $('input.task-title.has-error', tasksWrapper).removeClass('has-error');

        var wrapperMilestones = $('#_upstream_project_milestones_repeat, #_upstream_project_tasks_repeat, #_upstream_project_bugs_repeat');
        if (wrapperMilestones.length) {
            $('.postbox.cmb-row.cmb-repeatable-grouping .cmb-row *:disabled', wrapperMilestones).filter(function () {
                var self = $(this);
                if (['INPUT', 'SELECT', 'TEXTAREA'].indexOf(self.prop('tagName')) >= 0) {
                    $(this).prop({
                        'disabled': '',
                        'data-disabled': '',
                        'readonly': ''
                    });
                }
            });
        }

        // Check if Start/End dates intervals are valid.
        var stopSubmit = false;

        var validateIntervalBetweenDatesFields = function (startDateEl, endDateEl) {
            if (!startDateEl || !endDateEl) return true;

            var startDate = new Date(startDateEl.val());
            var endDate = new Date(endDateEl.val());

            if (!startDate
                || !endDate
                || !startDate.toJSON()
                || !endDate.toJSON()
            ) {
                return true;
            }

            return (+startDate) <= (+endDate);
        };

        var validateIntervalBetweenDatesFieldsInSection = function (section) {
            var isValid = true;

            if (section.length > 0) {
                var rowsList = $('> .postbox.cmb-row[data-iterator]', section);
                if (rowsList.length) {
                    rowsList.each(function () {
                        var row = $(this);

                        var startDateEl = $('[name$="[start_date]"]', row);
                        var endDateEl = $('[name$="[end_date]"]', row);

                        if (!validateIntervalBetweenDatesFields(startDateEl, endDateEl)) {
                            isValid = false;

                            startDateEl.addClass('has-error');
                            endDateEl.addClass('has-error');

                            row.removeClass('closed');
                            $('.up-o-tab', row).removeClass('nav-tab-active');
                            $('.up-o-tab.up-o-tab-data', row).addClass('nav-tab-active');

                            $('.up-o-tab-content', row).removeClass('is-active');
                            $('.up-o-tab-content.up-c-tab-content-data').addClass('is-active');

                            startDateEl.focus();

                            showValidationError(upstream_project.l.MSG_INVALID_INTERVAL_BETWEEN_DATE);

                            return false;
                        } else {
                            startDateEl.removeClass('has-error');
                            endDateEl.removeClass('has-error');
                        }
                    });
                }
            }

            return isValid;
        };

        // Check if Milestones section exists.
        if (!validateIntervalBetweenDatesFieldsInSection($('#_upstream_project_milestones_repeat'))
            || !validateIntervalBetweenDatesFieldsInSection($('#_upstream_project_tasks_repeat'))
        ) {
            stopSubmit = true;
        }

        if (stopSubmit) {
            e.preventDefault();
            e.stopPropagation();

            return false;
        }
    });

    var titleHasFocus = false;
    $(document)
        .on('before-autosave.update-post-slug', function () {
            titleHasFocus = document.activeElement && document.activeElement.id === 'title';
        })
        .on('after-autosave.update-post-slug', function (e, data) {
            if (!$('#edit-slug-box > *').length && !titleHasFocus) {
                $.post(ajaxurl, {
                        action: 'sample-permalink',
                        post_id: $('#post_ID').val(),
                        new_title: $('#title').val(),
                        samplepermalinknonce: $('#samplepermalinknonce').val()
                    },
                    function (data) {
                        if (data != '-1') {
                            $('#edit-slug-box').html(data);
                        }
                    }
                );
            }
        });
})(jQuery, upstream_project);

(function (window, document, $, upstream_project, undefined) {
    $(document).ready(function () {
        var newMessageLabel = $('#_upstream_project_discussions label[for="_upstream_project_new_message"]');
        var newMessageLabelText = newMessageLabel.text();

        function getCommentEditor (editor_id) {
            var TinyMceSingleton = window.tinyMCE ? window.tinyMCE : (window.tinymce ? window.tinymce : null);

            // The editor can be disabled by the user in his profile, so we return null.
            if (TinyMceSingleton === null) {
                return null;
            }

            var theEditor = TinyMceSingleton.get(editor_id);

            return theEditor;
        }

        function getCommentEditorTextarea (editor_id) {
            return $('#' + editor_id);
        }

        function getEditorContent (editor_id, asHtml) {
            asHtml = typeof asHtml === 'undefined' ? true : (asHtml ? true : false);

            var theEditor = getCommentEditor(editor_id);
            var content = '';

            var isEditorInVisualMode = theEditor ? !theEditor.isHidden() : false;
            if (isEditorInVisualMode) {
                if (asHtml) {
                    content = (theEditor.getContent() || '').trim();
                } else {
                    content = (theEditor.getContent({format: 'text'}) || '').trim();
                }
            } else {
                theEditor = getCommentEditorTextarea(editor_id);
                content = theEditor.val().trim();
            }

            return content;
        }

        function isEditorEmpty (editor_id) {
            var theEditor = getCommentEditor(editor_id),
                content;

            var isEditorInVisualMode = theEditor ? !theEditor.isHidden() : false;

            if (isEditorInVisualMode) {
                content = theEditor.getContent();
            } else {
                theEditor = getCommentEditorTextarea(editor_id);
                content = theEditor.val().trim();
            }

            // Check if maybe we have images in the content (those are not identified on the text format).
            // Replace images with placeholders
            content = content.replace(/<img.*\/>/g, '[image]');
            // Remove tags and special chars.
            content = content.replace(/<[^>]+>|&[a-z]+;/g, '');
            // Remove spaces.
            content = content.trim();

            return content === '';
        }

        function disableCommentArea (editor_id) {
            var theEditor = getCommentEditor(editor_id);

            if (theEditor) {
                theEditor.getDoc().designMode = 'off';

                var theEditorBody = theEditor.getBody();
                theEditorBody.setAttribute('contenteditable', 'false');
                theEditorBody.setAttribute('readonly', '1');
                theEditorBody.style.background = '#ECF0F1';
                theEditorBody.style.cursor = 'progress';
            }

            var theEditorTextarea = getCommentEditorTextarea(editor_id);
            theEditorTextarea.attr('disabled', 'disabled');
            theEditorTextarea.addClass('disabled');

            $('#wp-' + editor_id + '-wrap').css('cursor', 'progress');
            $('#insert-media-button').attr('disabled', 'disabled');
            $('button[data-action^="comment."]').attr('disabled', 'disabled');
            $('button[data-action^="comments."]').attr('disabled', 'disabled');
            $('button[data-editor="' + editor_id + '"]').attr('disabled', 'disabled');
        }

        function enableCommentArea (editor_id) {
            var theEditor = getCommentEditor(editor_id);

            if (theEditor) {
                theEditor.getDoc().designMode = 'on';

                var theEditorBody = theEditor.getBody();
                theEditorBody.setAttribute('contenteditable', 'true');
                theEditorBody.setAttribute('readonly', '0');
                theEditorBody.style.background = null;
                theEditorBody.style.cursor = null;
            }

            var theEditorTextarea = getCommentEditorTextarea(editor_id);
            theEditorTextarea.attr('disabled', null);
            theEditorTextarea.removeClass('disabled');

            $('#wp-' + editor_id + '-wrap').css('cursor', '');
            $('#insert-media-button').attr('disabled', null);
            $('button[data-action^="comment."]').attr('disabled', null);
            $('button[data-action^="comments."]').attr('disabled', null);
            $('button[data-editor="' + editor_id + '"]').attr('disabled', null);
        }

        function resetCommentEditorContent (editor_id) {
            var theEditor = getCommentEditor(editor_id);
            if (theEditor) {
                theEditor.setContent('');
            }

            var theEditorTextarea = getCommentEditorTextarea(editor_id);
            theEditorTextarea.val('');
        }

        function appendCommentHtmlToDiscussion (commentHtml, wrapper) {
            var comment = $(commentHtml);
            comment.hide();

            commentHtml = comment.html()
                .replace(/\\'/g, '\'')
                .replace(/\\"/g, '"');

            comment.html(commentHtml);

            comment.prependTo(wrapper);

            comment.slideDown();
        }

        function replyCancelButtonClickCallback (editor_id, wrapper) {
            if (editor_id === '_upstream_project_new_message') {
                $('label[for="_upstream_project_new_message"]').text(upstream_project.l.LB_ADD_NEW_COMMENT);
            }

            $('.button.u-to-be-removed', wrapper).remove();
            $('.button[data-action="comments.add_comment"]', wrapper).show();

            $('.o-comment[data-id]', wrapper).removeClass('is-mouse-over is-disabled is-being-replied');

            resetCommentEditorContent(editor_id);
        }

        function replySendButtonCallback (e) {
            e.preventDefault();

            var self = $(this);
            var parent = $(self.parent().parent());
            var commentsWrapper;

            var editor_id = $('textarea.wp-editor-area', parent).attr('id');

            if (isEditorEmpty(editor_id)) {
                setFocus(editor_id);
                return;
            }

            var commentContentHtml = getEditorContent(editor_id);

            var item_type, item_id, item_index;
            var itemsWrapper = $(self.parents('.cmb-nested.cmb-field-list[data-groupid]'));

            if (itemsWrapper.length > 0) {
                commentsWrapper = $('.c-comments', parent);
                var itemWrapper = $(self.parents('.cmb-row[data-iterator]'));

                item_index = itemWrapper.attr('data-iterator');

                var group_id = itemsWrapper.attr('data-groupid');
                var item_type_plural = group_id.replace('_upstream_project_', '');
                item_type = item_type_plural.substring(0, item_type_plural.length - 1);

                var prefix = group_id + '_' + item_index;
                item_id = $('#' + prefix + '_id').val();
            } else {
                item_type = 'project';
                commentsWrapper = $('.c-comments', self.parents('.cmb2-metabox'));
            }

            var errorCallback = function () {
                self.text(upstream_project.l.LB_SEND_REPLY);
                $('.button.u-to-be-removed', parent).attr('disabled', null);
                $('.o-comment.is-being-replied a[data-action="comment.reply"]', commentsWrapper).text(upstream_project.l.LB_REPLY);
            };

            var theCommentBeingReplied = $('.o-comment.is-being-replied', commentsWrapper);

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'upstream:project.add_comment_reply',
                    nonce: self.data('nonce'),
                    project_id: $('#post_ID').val(),
                    parent_id: self.attr('data-id'),
                    content: commentContentHtml,
                    item_type: item_type || null,
                    item_id: item_id || null
                },
                beforeSend: function () {
                    disableCommentArea(editor_id);
                    self.text(upstream_project.l.LB_REPLYING);
                    $('.button.u-to-be-removed', parent).attr('disabled', 'disabled');
                    $('a[data-action="comment.reply"]', theCommentBeingReplied).text(upstream_project.l.LB_REPLYING);
                },
                success: function (response) {
                    if (response.error) {
                        errorCallback();
                        console.error(response.error);
                        alert(response.error);
                    } else {
                        if (!response.success) {
                            errorCallback();
                            console.error('Something went wrong.');
                        } else {
                            resetCommentEditorContent(editor_id);
                            replyCancelButtonClickCallback(editor_id, parent);

                            appendCommentHtmlToDiscussion(response.comment_html, theCommentBeingReplied.find('.o-comment-replies').get(0));

                            $('a[data-action="comment.reply"]', theCommentBeingReplied).text(upstream_project.l.LB_REPLY);
                            $('.o-comment', commentsWrapper).removeClass('is-disabled is-mouse-over is-being-replied');
                        }
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    errorCallback();

                    var response = {
                        text_status: textStatus,
                        errorThrown: errorThrown
                    };

                    console.error(response);
                },
                complete: function () {
                    enableCommentArea(editor_id);
                }
            });
        }

        function setFocus (editor_id) {
            var theEditor = getCommentEditor(editor_id);
            var isEditorInVisualMode = theEditor ? !theEditor.isHidden() : false;
            if (isEditorInVisualMode) {
                theEditor.execCommand('mceFocus', false);
            } else {
                theEditor = getCommentEditorTextarea(editor_id);
                theEditor.focus();
            }
        }

        $('label[for="_upstream_project_new_message"]').on('click', function (e) {
            e.preventDefault();

            setFocus('_upstream_project_new_message');
        });

        $('.cmb2-wrap').on('click', '.c-comments .o-comment[data-id] a[data-action="comment.reply"]', function (e) {
            e.preventDefault();

            var self = $(this);
            var commentWrapper = $(self.parents('.o-comment[data-id]').get(0));
            var comment_id = commentWrapper.attr('data-id');

            commentWrapper.addClass('is-mouse-over is-being-replied');
            var parent = $(commentWrapper.parents('.c-comments').parent());

            $('.o-comment[data-id!="' + comment_id + '"]', parent).addClass('is-disabled');

            var editor_id = $('textarea.wp-editor-area', parent).attr('id');

            var addCommentBtn = $('.button[data-action="comments.add_comment"]', parent);
            addCommentBtn.hide();
            var controlsWrapper = $(addCommentBtn.parent());

            $('.button.u-to-be-removed', parent).remove();

            var cancelButton = $('<button></button>', {
                type: 'button',
                class: 'button button-secondary u-to-be-removed'
            })
                .text(upstream_project.l.LB_CANCEL);
            cancelButton.on('click', function (e) {
                e.preventDefault();

                replyCancelButtonClickCallback(editor_id, parent);
            });
            controlsWrapper.append(cancelButton);

            var sendButton = $('<button></button>', {
                type: 'button',
                class: 'button button-primary u-to-be-removed',
                'data-id': comment_id,
                'data-nonce': self.data('nonce')
            })
                .text(upstream_project.l.LB_SEND_REPLY)
                .css('margin-left', '10px');
            sendButton.on('click', replySendButtonCallback);
            controlsWrapper.append(sendButton);

            resetCommentEditorContent(editor_id);

            if (editor_id === '_upstream_project_new_message') {
                $('label[for="_upstream_project_new_message"]').text(upstream_project.l.LB_ADD_NEW_REPLY);
            }

            var finished = false;
            $('html, body').animate({
                scrollTop: $(editor_id === '_upstream_project_new_message' ? '#_upstream_project_discussions' : commentWrapper.parents('.postbox.cmb-row[data-iterator]')).offset().top
            }, {
                complete: function (e) {
                    if (!finished) {
                        setFocus(editor_id);
                        finished = true;
                    }
                }
            });
        });

        $('.cmb2-wrap').on('click', '.o-comment[data-id] a[data-action="comment.trash"]', function (e) {
            e.preventDefault();

            var self = $(this);

            var comment = $(self.parents('.o-comment[data-id]').get(0));
            if (!comment.length) {
                console.error('Comment wrapper not found.');
                return;
            }

            if (!confirm(upstream_project.l.MSG_ARE_YOU_SURE)) return;

            var errorCallback = function () {
                comment.removeClass('is-loading is-mouse-over is-being-removed');
                self.text(upstream_project.l.LB_DELETE);
            };

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'upstream:project.trash_comment',
                    nonce: self.data('nonce'),
                    project_id: $('#post_ID').val(),
                    comment_id: comment.attr('data-id')
                },
                beforeSend: function () {
                    comment.addClass('is-loading is-mouse-over is-being-removed');
                    self.text(upstream_project.l.LB_DELETING);
                },
                success: function (response) {
                    if (response.error) {
                        errorCallback();

                        console.error(response.error);
                        alert(response.error);
                    } else {
                        if (!response.success) {
                            console.error('Something went wrong.');

                            errorCallback();
                        } else {
                            comment.css('background-color', '#E74C3C');

                            comment.slideUp({
                                complete: function () {
                                    comment.remove();
                                }
                            });
                        }
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    errorCallback();

                    var response = {
                        text_status: textStatus,
                        errorThrown: errorThrown
                    };

                    console.error(response);
                }
            });
        });

        function sendNewCommentRequest (self, content, nonce, item_type, item_id, item_index, editor_id, commentsWrapper, item_title) {
            item_type = typeof item_type === 'undefined' ? 'project' : item_type;
            item_id = typeof item_id === 'undefined' ? null : item_id;
            item_index = typeof item_index === 'undefined' ? null : item_index;
            item_title = typeof item_title === 'undefined' ? null : item_title;

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'upstream:project.add_comment',
                    nonce: nonce,
                    project_id: $('#post_ID').val(),
                    item_type: item_type,
                    item_index: item_index,
                    item_id: item_id,
                    item_title: item_title,
                    content: content
                },
                beforeSend: function () {
                    disableCommentArea(editor_id);
                    self.text(upstream_project.l.LB_ADDING);
                    self.attr('disabled', 'disabled');
                },
                success: function (response) {
                    if (response.error) {
                        console.error(response.error);
                        alert(response.error);
                    } else {
                        if (!response.success) {
                            console.error('Something went wrong.');
                        } else {
                            resetCommentEditorContent(editor_id);

                            appendCommentHtmlToDiscussion(response.comment_html, commentsWrapper);
                        }
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    errorCallback();

                    var response = {
                        text_status: textStatus,
                        errorThrown: errorThrown
                    };

                    console.error(response);
                },
                complete: function () {
                    enableCommentArea(editor_id);
                    self.text(upstream_project.l.LB_ADD_COMMENT);
                    self.attr('disabled', null);
                }
            });
        }

        $('#_upstream_project_discussions .button[data-action="comments.add_comment"]').on('click', function (e) {
            e.preventDefault();

            var self = $(this);

            var editor_id = '_upstream_project_new_message';
            if (isEditorEmpty(editor_id)) {
                setFocus(editor_id);
                return;
            }

            var commentHtml = getEditorContent(editor_id);

            sendNewCommentRequest(
                self,
                commentHtml,
                self.attr('data-nonce'),
                'project',
                null,
                null,
                editor_id,
                $('#_upstream_project_discussions .c-comments')
            );
        });

        function sendToggleApprovalStateRequest (self, isApproved) {
            var comment = $(self.parents('.o-comment[data-id]').get(0));
            if (!comment.length) {
                console.error('Comment wrapper not found.');
                return;
            }

            var errorCallback = function () {
                comment
                    .removeClass('is-loading is-mouse-over is-being-' + (isApproved ? 'approved' : 'unapproved'))
                    .css('background-color', '');
                self.text(isApproved ? upstream_project.l.LB_APPROVE : upstream_project.l.LB_UNAPPROVE);
            };

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'upstream:project.' + (isApproved ? 'approve' : 'unapprove') + '_comment',
                    nonce: self.data('nonce'),
                    project_id: $('#post_ID').val(),
                    comment_id: comment.attr('data-id')
                },
                beforeSend: function () {
                    comment.addClass('is-loading is-mouse-over is-being-' + (isApproved ? 'approved' : 'unapproved'));
                    self.text(isApproved ? upstream_project.l.LB_APPROVING : upstream_project.l.LB_UNAPPROVING);
                },
                success: function (response) {
                    if (response.error) {
                        errorCallback();
                        console.error(response.error);
                        alert(response.error);
                    } else {
                        if (!response.success) {
                            errorCallback();
                            console.error('Something went wrong.');
                        } else {
                            comment.removeClass('s-status-' + (!isApproved ? 'approved' : 'unapproved') + ' is-being-' + (!isApproved ? 'approved' : 'unapproved'))
                                .addClass('s-status-' + (isApproved ? 'approved' : 'unapproved'));

                            var newComment = $(response.comment_html);
                            var newCommentBody = $('.o-comment__body', newComment);

                            $(comment.find('.o-comment__body').get(0)).replaceWith(newCommentBody);
                        }
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    errorCallback();

                    var response = {
                        text_status: textStatus,
                        errorThrown: errorThrown
                    };

                    console.error(response);
                },
                complete: function () {
                    comment.removeClass('is-loading is-mouse-over is-being-approved is-being-unapproved');
                }
            });
        }

        $('.cmb2-wrap').on('click', '.o-comment[data-id] a[data-action="comment.unapprove"]', function (e) {
            e.preventDefault();
            sendToggleApprovalStateRequest($(this), false);
        });

        $('.cmb2-wrap').on('click', '.o-comment[data-id] a[data-action="comment.approve"]', function (e) {
            e.preventDefault();
            sendToggleApprovalStateRequest($(this), true);
        });

        $('.cmb2-wrap').on('click', '.c-comments .o-comment[data-id] a[data-action="comment.go_to_reply"]', function (e) {
            e.preventDefault();

            var targetComment = $($(this).attr('href'));
            if (!targetComment.length) {
                console.error('Comment not found.');
                return;
            }

            var wrapper = $(targetComment.parents('.c-comments'));
            var wrapperOffset = wrapper.offset();

            if (!wrapperOffset) return;

            var offset = targetComment.offset() || null;
            if (!offset) return;

            var targetCommentOffsetTop = offset.top - wrapperOffset.top;

            wrapper.animate({
                scrollTop: targetCommentOffsetTop
            }, function () {
                targetComment.addClass('s-highlighted');
                setTimeout(function () {
                    targetComment.removeClass('s-highlighted');
                }, 750);
            });
        });

        $('.cmb2-wrap').on('click', '.up-o-tab[role="tab"][data-target]', function (e) {
            e.preventDefault();

            var self = $(this);
            var wrapper = $(self.parents('.cmb-row[data-iterator]'));

            $('.up-o-tab', wrapper).removeClass('nav-tab-active');
            self.addClass('nav-tab-active');

            var target = $('.up-o-tab-content' + self.attr('data-target'), wrapper);
            if (target.length > 0) {
                $('.up-o-tab-content', wrapper).removeClass('is-active');
                target.addClass('is-active');
            }
        });

        function fetchAllComments () {
            // Fix misplaced comments wrappers.
            $('.up-o-tab-content.up-c-tab-content-data .up-c-tab-content-comments').each(function () {
                var self = $(this);
                var tabsWrapper = $(self.parents('.up-c-tabs-content'));

                if (tabsWrapper.length > 0) {
                    tabsWrapper.append(self);
                }
            });

            $.ajax({
                type: 'GET',
                url: ajaxurl,
                data: {
                    action: 'upstream:project.get_all_items_comments',
                    nonce: $('#project_all_items_comments_nonce').val(),
                    project_id: $('#post_ID').val()
                },
                success: function (response) {
                    if (response.success) {
                        var itemsTypes = ['milestones', 'tasks', 'bugs', 'files'];
                        for (var itemTypeIndex = 0; itemTypeIndex < itemsTypes.length; itemTypeIndex++) {
                            var itemType = itemsTypes[itemTypeIndex];
                            var rowset = response.data[itemType];

                            $('input.hidden[type="text"][id^="_upstream_project_' + itemType + '_"][id$="_id"]').each(function () {
                                var wrapper = $($(this).parents('.up-c-tabs-content'));
                                if ($('up-c-tab-content-comments .c-comments', wrapper).length === 0) {
                                    $('.up-c-tab-content-comments', wrapper).append($('.c-comments', wrapper));
                                }
                            });

                            if (!rowset || rowset.length === 0) {
                                continue;
                            }

                            for (var item_id in rowset) {
                                var commentsList = rowset[item_id];
                                var itemEl = $('input.hidden[type="text"][id^="_upstream_project_' + itemType + '_"][id$="_id"][value="' + item_id + '"]');

                                var wrapper = $(itemEl.parents('.up-c-tabs-content'));
                                if ($('up-c-tab-content-comments .c-comments', wrapper).length === 0) {
                                    $('.up-c-tab-content-comments', wrapper).append($('.c-comments', wrapper));
                                }

                                var commentsWrapper = $('.up-c-tab-content-comments .c-comments', wrapper);
                                if (commentsList.length > 0) {
                                    for (var commentIndex = 0; commentIndex < commentsList.length; commentIndex++) {
                                        commentsWrapper.append($(commentsList[commentIndex]));
                                    }
                                }
                            }
                        }
                    }
                },
                error: function () {},
                complete: function () {}
            });
        }

        fetchAllComments();

        $('.cmb-row.cmb-type-comments[data-fieldtype="comments"]').each(function () {
            var self = $(this);

            var itemsWrapper = $(self.parents('.cmb-nested.cmb-field-list[data-groupid]'));
            var itemWrapper = $(self.parents('.postbox.cmb-row[data-iterator]'));

            var group_id = itemsWrapper.attr('data-groupid');

            var parent = $(self.parents('.up-c-tabs-content'));
            var wrapper = $('.up-c-tab-content-comments', parent);

            var prefix = group_id + '_' + itemWrapper.attr('data-iterator');

            var div = $('<div></div>');

            var addCommentButton = $('<button></button>', {
                'type': 'button',
                'class': 'button button-primary',
                'data-nonce': $('#' + prefix + '_comments_add_comment_nonce', parent).val(),
                'data-action': 'comments.add_comment'
            })
                .text(upstream_project.l.LB_ADD_COMMENT)
                .on('click', function (e) {
                    e.preventDefault();

                    var self = $(this);

                    var editor_id = prefix + '_comments_editor';
                    if (isEditorEmpty(editor_id)) {
                        setFocus(editor_id);
                        return;
                    }

                    var commentHtml = getEditorContent(editor_id);

                    var item_type_plural = group_id.replace('_upstream_project_', '');
                    var item_id = $('#' + prefix + '_id').val();
                    var item_title = '';

                    if (item_type_plural === 'milestones') {
                        item_title = $('#' + prefix + '_milestone').val();
                    } else {
                        item_title = $('#' + prefix + '_title').val();
                    }

                    sendNewCommentRequest(
                        self,
                        commentHtml,
                        self.attr('data-nonce'),
                        item_type_plural.substring(0, item_type_plural.length - 1),
                        item_id,
                        itemWrapper.attr('data-iterator'),
                        editor_id,
                        $('.c-comments', itemWrapper),
                        item_title
                    );
                });

            div.append(addCommentButton);
            wrapper.prepend(div);

            wrapper.prepend($('.cmb-td > div.wp-editor-wrap', self));
        });

        $('.up-o-filter-date').datepicker({
            dateFormat: 'yy-mm-dd',
            beforeShow: function (input, instance) {
                $('#ui-datepicker-div').addClass('cmb2-element');
            }
        });

        $('select.up-o-filter:not(.up-o-filter-date)').on('change', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var self = $(this);
            var filterColumn = self.attr('data-column');
            var filterValue = self.val();

            var metabox = $(self.parents('.cmb2-metabox').get(0));

            filterMetaboxTableBy(metabox, filterColumn, filterValue, 'contains');
        });

        $('.up-o-filter-date.up-o-filter').on('change', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var self = $(this);
            var filterColumn = self.attr('data-column');
            var filterValue = self.val();

            if (filterValue.length > 0) {
                filterValue = +new Date(filterValue);
            }

            var metabox = $(self.parents('.cmb2-metabox').get(0));

            filterMetaboxTableBy(metabox, filterColumn, filterValue, self.attr('data-compare-operator'));
        });

        function filterMetaboxTableBy (metabox, columnName, filterValue, operator) {
            var table = metabox.find('.cmb-nested.cmb-field-list.cmb-repeatable-group');
            var filtersWrapper = metabox.find('.up-c-filters');
            var filtersMap = [];

            var filters = $('[data-column]', filtersWrapper);
            filters.each(function () {
                var self = $(this);

                var value = self.val();
                if (typeof value === 'string') {
                    value = value.trim();
                } else if (value) {
                    value = value.filter(function (elValue) { return elValue.length > 0; });
                } else {
                    value = '';
                }

                filtersMap[self.attr('data-column')] = value.length > 0 ? value : null;
                filtersMap.push({
                    column: self.attr('data-column'),
                    value: value.length > 0 ? value : null,
                    comparator: self.attr('data-compare-operator') || 'exact'
                });
            });

            $('.cmb-row.postbox[data-empty-row]', table).remove();

            var filtersHasChanged = false;
            var rows = $('.cmb-row.postbox[data-iterator]', table);

            rows.removeClass('is-filtered');

            rows.each(function (trIndex) {
                var tr = $(this);
                var shouldDisplay = false;

                var filter, filterIndex, filterColumnValue, columnValue, comparator, theColumn;
                for (filterIndex = 0; filterIndex < filtersMap.length; filterIndex++) {
                    filter = filtersMap[filterIndex];
                    if (filter.value === null) {
                        continue;
                    }

                    filtersHasChanged = true;

                    theColumn = $('[name$="[' + filter.column + ']"]', tr);
                    if (theColumn.length === 0) {
                        theColumn = $('[name$="[' + filter.column + '][]"]', tr);
                    }

                    if (filter.comparator === 'contains' && theColumn.prop('tagName') === 'SELECT') {
                        columnValue = [];

                        var values = $('option[selected]', theColumn);
                        for (var valueIndex = 0; valueIndex < values.length; valueIndex++) {
                            columnValue.push($(values[valueIndex]).text());
                            columnValue.push($(values[valueIndex]).attr('value'));
                        }
                    } else {
                        columnValue = theColumn.val() || '';
                    }

                    if (theColumn.hasClass('hasDatepicker') && columnValue.length > 0) {
                        filter.value = +new Date(filter.value);
                        columnValue = +new Date(columnValue);
                    }

                    if (filter.comparator === 'contains') {
                        if (typeof filter.value === 'string') {
                            comparator = new RegExp(filter.value, 'i');
                            shouldDisplay = comparator.test(columnValue);
                        } else {
                            for (var valueIndex in filter.value) {
                                var theValue = filter.value[valueIndex];
                                theValue = theValue.length > 0 && theValue !== '__none__' ? theValue : '';

                                if (typeof columnValue === 'string') {
                                    if (theValue === columnValue) {
                                        shouldDisplay = true;
                                        break;
                                    } else if (theValue !== '') {
                                        comparator = new RegExp(theValue, 'i');
                                        if (comparator.test(columnValue)) {
                                            shouldDisplay = true;
                                            break;
                                        }
                                    }
                                } else {
                                    for (var columnValueIndex = 0; columnValueIndex < columnValue.length; columnValueIndex++) {
                                        var columnValueItem = columnValue[columnValueIndex];
                                        if (theValue === columnValue) {
                                            shouldDisplay = true;
                                            break;
                                        } else if (theValue !== '') {
                                            comparator = new RegExp(theValue, 'i');
                                            if (comparator.test(columnValue)) {
                                                shouldDisplay = true;
                                                break;
                                            }
                                        }
                                    }

                                    if (shouldDisplay) {
                                        break;
                                    }
                                }
                            }
                        }
                    } else if (filter.comparator === 'exact') {
                        if (typeof filter.value === 'string') {
                            if (filter.value === '__none__') {
                                shouldDisplay = !columnValue || columnValue === '__none__';
                            } else {
                                shouldDisplay = columnValue === filter.value;
                            }
                        } else {
                            for (var valueIndex in filter.value) {
                                if (filter.value[valueIndex] === '__none__') {
                                    shouldDisplay = !columnValue || columnValue === '__none__';
                                } else {
                                    shouldDisplay = columnValue === filter.value[valueIndex];
                                }

                                if (shouldDisplay) {
                                    break;
                                }
                            }
                        }
                    } else if (filter.comparator === '>') {
                        shouldDisplay = columnValue > filter.value;
                    } else if (filter.comparator === '>=') {
                        shouldDisplay = columnValue >= filter.value;
                    } else if (filter.comparator === '<') {
                        shouldDisplay = columnValue < filter.value;
                    } else if (filter.comparator === '<=') {
                        shouldDisplay = columnValue <= filter.value;
                    } else if (filter.value === '__none__') {
                        shouldDisplay = !columnValue || columnValue === '__none__';
                    } else {
                        if (typeof filter.value === 'string') {
                            shouldDisplay = columnValue.localeCompare(filter.value) === 0;
                        } else {
                            for (var valueIndex in filter.value) {
                                if (filter.value[valueIndex] === '__none__') {
                                    shouldDisplay = !columnValue || columnValue === '__none__';
                                } else {
                                    shouldDisplay = columnValue.localeCompare(filter.value[valueIndex]) === 0;
                                }

                                if (shouldDisplay) {
                                    break;
                                }
                            }
                        }
                    }

                    if (filtersHasChanged && !shouldDisplay) {
                        break;
                    }
                }

                if (shouldDisplay) {
                    tr.addClass('is-filtered').show();
                } else {
                    tr.hide();
                }
            });

            if (!filtersHasChanged) {
                rows.show();
            }

            var filteredRows = $('.cmb-row.postbox[data-iterator]:visible', table);
            if (filteredRows.length === 0) {
                $('.cmb-row:last-child', table).prepend($('<div class="postbox cmb-row cmb-repeatable-grouping" data-empty-row><p>' + upstream_project.l.MSG_NO_RESULTS + '</p></div>'));
            } else {
                $('.cmb-row.postbox:visible', table).addClass('is-filtered');
            }
        }

        $('.up-o-filter[data-trigger_on="keyup"]').on('keyup', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var self = $(this);
            var filterColumn = self.attr('data-column');
            var filterValue = self.val().trim();

            var metabox = $(self.parents('.cmb2-metabox').get(0));

            filterMetaboxTableBy(metabox, filterColumn, filterValue, 'contains');
        });

        $('.o-select2').select2();

        $('.cmb-add-group-row').on('click', function (e) {
            var self = $(this);

            setTimeout(function () {
                var wrapper = $(self.parents('div.cmb-repeatable-group[data-groupid]'));

                var dataWrapper = $('.up-c-tab-content-data', $('.postbox.cmb-row[data-iterator]', wrapper));
                dataWrapper = $(dataWrapper.get(dataWrapper.length - 1));

                $('input[type="text"],select', dataWrapper).val('').trigger('change');
                $('.select2.select2-container', dataWrapper).remove();
                $('select.o-select2', dataWrapper).select2();
            }, 100);
        });
    });
})(window, window.document, jQuery, upstream_project || {});
