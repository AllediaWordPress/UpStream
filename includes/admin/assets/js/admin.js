(function ($) {
    $(function () {
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

        $('.upstream-milestone-assigned-to select').select2({
            allowClear: true
        });
    });
})(jQuery);
