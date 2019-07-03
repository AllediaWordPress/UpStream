jQuery(function ($) {
    // Highlight the extensions submenu.
    var allex = new Allex('upstream');
    allex.highlight_submenu('admin.php?page=upstream_extensions');

    window.upstream_reset_capabilities = function (event) {
        var $btn = $(event.target);
        var label = $btn.text();
        var buttonSlug = $btn.data('slug');

        if (!confirm(upstreamAdmin.MSG_CONFIRM_RESET_CAPABILITIES)) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                action: 'upstream_admin_reset_capabilities',
                nonce: $btn.data('nonce'),
                role: buttonSlug
            },
            beforeSend: function () {
                $btn.text(upstreamAdmin.LB_RESETTING);
                $btn.prop('disabled', true);
            },
            error: function (response) {
                $msg = $('<span>' + upstreamAdmin.MSG_CAPABILITIES_ERROR + '</span>');
                $msg.addClass('upstream_float_error');

                $btn.after($msg);

                window.setTimeout(function () {
                    $msg.fadeOut();
                }, 4000);
            },
            success: function (response) {
                $msg = $('<span class="allex-success-message">' + upstreamAdmin.MSG_CAPABILITIES_RESETED + '</span>');
                $msg.addClass('upstream_float_success');

                $btn.parent().append($msg);

                window.setTimeout(function () {
                    $msg.fadeOut();
                }, 4000);
            },
            complete: function (jqXHR, textStatus) {
                if (textStatus !== 'success') {

                }

                $btn.text(label);
                $btn.prop('disabled', false);
            }
        });
    };

    window.upstream_refresh_projects_meta = function (event) {
        var $btn = $(event.target);
        var label = $btn.text();

        if (!confirm(upstreamAdmin.MSG_CONFIRM_REFRESH_PROJECTS_META)) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                action: 'upstream_admin_refresh_projects_meta',
                nonce: $btn.data('nonce')
            },
            beforeSend: function () {
                $btn.text(upstreamAdmin.LB_REFRESHING);
                $btn.prop('disabled', true);
            },
            error: function (response) {
                $msg = $('<span>' + upstreamAdmin.MSG_PROJECTS_META_ERROR + '</span>');
                $msg.addClass('upstream_float_error');

                $btn.after($msg);

                window.setTimeout(function () {
                    $msg.fadeOut();
                }, 4000);
            },
            success: function (response) {
                $msg = $('<span class="allex-success-message">' + upstreamAdmin.MSG_PROJECTS_SUCCESS + '</span>');
                $msg.addClass('upstream_float_success');

                $btn.parent().append($msg);

                window.setTimeout(function () {
                    $msg.fadeOut();
                }, 4000);
            },
            complete: function (jqXHR, textStatus) {
                if (textStatus !== 'success') {

                }

                $btn.text(label);
                $btn.prop('disabled', false);
            }
        });
    };

    window.upstream_cleanup_update_cache = function (event) {
        var $btn = $(event.target);
        var label = $btn.text();

        if (!confirm(upstreamAdmin.MSG_CONFIRM_CLEANUP_UPDATE_CACHE)) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                action: 'upstream_admin_cleanup_update_cache',
                nonce: $btn.data('nonce')
            },
            beforeSend: function () {
                $btn.text(upstreamAdmin.LB_REFRESHING);
                $btn.prop('disabled', true);
            },
            error: function (response) {
                $msg = $('<span>' + upstreamAdmin.MSG_CLEANUP_UPDATE_DATA_ERROR + '</span>');
                $msg.addClass('upstream_float_error');

                $btn.after($msg);

                window.setTimeout(function () {
                    $msg.fadeOut();
                }, 4000);
            },
            success: function (response) {
                $msg = $('<span class="allex-success-message">' + upstreamAdmin.MSG_PROJECTS_SUCCESS + '</span>');
                $msg.addClass('upstream_float_success');

                $btn.parent().append($msg);

                window.setTimeout(function () {
                    $msg.fadeOut();
                }, 4000);
            },
            complete: function (jqXHR, textStatus) {
                if (textStatus !== 'success') {

                }

                $btn.text(label);
                $btn.prop('disabled', false);
            }
        });
    };

    $("#_upstream_milestone_start_date").datepicker({
        numberOfMonths: 2,
        dateFormat: 'yy-mm-dd',
        onSelect: function(selected) {
          $("#_upstream_milestone_end_date").datepicker("option","minDate", selected)
        }
    });
    $("#_upstream_milestone_end_date").datepicker({
        numberOfMonths: 2,
        dateFormat: 'yy-mm-dd',
        onSelect: function(selected) {
           $("#_upstream_milestone_start_date").datepicker("option","maxDate", selected)
        }
    }); 


    $('.o-datepicker').datepicker({
        todayBtn: 'linked',
        clearBtn: true,
        autoclose: true,
        keyboardNavigation: false,
        format: upstreamAdmin.datepickerDateFormat
    }).on('change', function (e) {
        var self = $(this);

        var value = self.datepicker('getDate');
        /*
        if (value) {
          value /= 1000;
        }
        */

        if (value) {
            value = (+new Date(value)) / 1000;
        }

        var hiddenField = $('#' + self.attr('id') + '_timestamp');
        if (hiddenField.length > 0) {
            hiddenField.val(value);
        }
    });

    $('.color-field').wpColorPicker();

    window.upstream_migrate_milestones = function (event) {
        $('.upstream-option-subpanel').remove();

        /**
         * Prepare the terminal for output the migration data.
         */
        var $fieldWrapper = $('.cmb2-id-migrate-milestones .cmb-td');
        var $migrationWrapper = $('<div>').addClass('upstream-option-subpanel');
        var $btn = $(event.target);
        var promptString = '';

        $fieldWrapper.append($migrationWrapper);

        /**
         * Initialize the terminal.
         */
        var terminal = $($migrationWrapper).terminal(
            function (command) {
                error('Please, just wait until the process finish.');
            },
            {
                greetings: '======================================================\nUpStream - Legacy Milestone Migration\n======================================================',
                name: 'upstream_milestone_migration',
                prompt: promptString
            }
        ).disable();

        var echo = function (text) {
            terminal.echo(promptString + text);
        };

        var error = function (text) {
            terminal.error(text);
        };

        $btn.prop('disabled', true);

        var call = function (args) {
            var data = args.data;
            data.action = args.action;
            data.nonce = $btn.data('nonce');

            $.ajax({
                url: ajaxurl,
                type: args.type,
                data: data,
                beforeSend: function () {
                    if (typeof args.before !== 'undefined') {
                        args.before();
                    }
                },
                error: function (response) {
                    error('Sorry, something is wrong...');
                    error(response.status + ' ' + response.statusText);
                },
                success: function (response) {
                    if (typeof args.after !== 'undefined') {
                        args.after(response);
                    }
                }
            });
        };

        var migrateProject = function (projects, i) {
            var project = projects[i];

            call({
                action: 'upstream_admin_migrate_milestones_for_project',
                type: 'post',
                data: {
                    projectId: project.id
                },
                before: function () {
                    echo('[' + (i + 1) + '] Migrating milestones for ' + project.title);
                },
                after: function (response) {
                    response = JSON.parse(response);

                    if (typeof response.success === 'undefined' || !response.success) {
                        error('Error found while migrating the milestones for the project ' + project.title + '\n');
                    } else {
                        echo('Success\n');
                    }

                    // Call the migration for the next project, if exists.
                    if (projects.length > i + 1) {
                        migrateProject(projects, i + 1);
                    } else {
                        echo('\nDone.');

                        $btn.prop('disabled', false);
                    }
                }
            });
        };

        // Check how many projects with milestones we have
        call({
            action: 'upstream_admin_migrate_milestones_get_projects',
            type: 'get',
            data: {},
            before: function () {
                echo('Please, wait... we are looking for legacy milestones on all projects');
            },
            after: function (response) {
                response = JSON.parse(response);

                if (response.length > 0) {
                    echo(response.length + ' projects with legacy milestones found:\n');
                    var data;

                    // Display the found projects
                    for (var i = 0; i < response.length; i++) {
                        data = response[i];

                        echo('  ' + (i + 1) + '. ' + data.id + ': ' + data.title + ' (' + data.count + ')');
                    }

                    echo('');
                    echo('Starting the migration process for each project\n');

                    // Trigger the migration for each project
                    migrateProject(response, 0);
                } else {
                    echo('\nDone. No legacy milestones found.');
                }
            }
        });
    };
});

// Sortable
jQuery(document).ready(function($) {
    if( $("#_upstream_project_milestones_repeat").length ) {
        $("#_upstream_project_milestones_repeat").sortable({
            stop: function( event, ui ) {
                var idx = 1;
                $("#_upstream_project_milestones_repeat > .cmb-repeatable-grouping").each(function() {
                    $(this).attr("idx", idx);
                    $.ajax({
                        type: 'POST',
                        url: ajaxurl,
                        data: {
                            action: 'upstream.milestone-edit.editmenuorder',
                            post_id: $("#_upstream_project_milestones_" + $(this).attr("data-iterator") + "_id").val(),
                            item_val: idx
                        },
                        success: function (response) {
                            console.log(response);
                        }
                    });
                    idx++;
                });
            }
        });
    }
    if( $("#_upstream_project_tasks_repeat").length ) {
        $("#_upstream_project_tasks_repeat").sortable();
    }
    if( $("#_upstream_project_bugs_repeat").length ) {
        $("#_upstream_project_bugs_repeat").sortable();
    }
    if( $("#post_type").val() == 'upst_milestone' ) {
        $("#post_type").parent().find("#normal-sortables").css("display", "none");
    }
});