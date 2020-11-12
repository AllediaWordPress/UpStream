(function (window, document, $, ajaxurl, l, undefined) {
    'use strict';

    if (!$) {
        console.error(l['ERR_JQUERY_NOT_FOUND']);
        return;
    }

    if (!$('#titlewrap').length) {
        return;
    }

    // Make the Client Name field required.
    (function () {
        var titleWrap = $('#titlewrap');
        var titleLabel = $('#title-prompt-text', titlewrap);

        titleLabel.text(titleLabel.text() + ' *');

        $('#title', titlewrap).attr('required', 'required');
    })();

    var removeUserCallback = function (e) {
        e.preventDefault();

        var self = $(this);
        var table = $('#table-users');

        if (table.hasClass('is-removing')) {
            self.trigger('blur');
            return;
        }

        var row = $(this).parents('tr[data-id]');
        var tbody = $('tbody', table);
        if (row.length) {
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'upstream:client.remove_user',
                    client: $('#post_ID').val(),
                    user: row.data('id')
                },
                beforeSend: function (jqXHR, settings) {
                    self.addClass('up-spinner').html('<div></div>');
                    table.addClass('is-removing');
                    $('#_upstream_client_users a.thickbox').addClass('disabled').addClass('up-u-no-events');
                    $('#publish').addClass('disabled').addClass('up-u-no-events');
                    $('#delete-action a').addClass('disabled').addClass('up-u-no-events');
                },
                success: function (response, textStatus, jqXHR) {
                    if (!response.success) {
                        alert(response.err);
                    } else {
                        row.remove();

                        if ($('tr', tbody).length === 0) {
                            tbody.append('<tr data-empty><td colspan="7">' + l['MSG_NO_ASSIGNED_USERS'] + '</td></tr>');
                        }
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    self.removeClass('up-spinner').html('<span class="dashicons dashicons-trash"></span>');
                },
                complete: function (jqXHR, settings) {
                    table.removeClass('is-removing');
                    $('#_upstream_client_users a.thickbox').removeClass('disabled').removeClass('up-u-no-events');
                    $('#publish').removeClass('disabled').removeClass('up-u-no-events');
                    $('#delete-action a').removeClass('disabled').removeClass('up-u-no-events');
                }
            });
        }
    };

    function updateAddExistentUserButtonLabel () {
        var table = $('#table-add-existent-users');
        var wrapper = $(table.parent().parent());
        var button = $('[data-type="submit"]', wrapper);

        var selectedItemsCount = $('tbody tr[data-id] td input[type="checkbox"]:checked', table).length;
        if (selectedItemsCount > 0) {
            button.attr('disabled', null);
            button.text(l[selectedItemsCount > 1 ? 'MSG_ADD_MULTIPLE_USERS' : 'MSG_ADD_ONE_USER'].replace('\%d', selectedItemsCount));
        } else {
            button.attr('disabled', 'disabled');
            button.text(l['MSG_NO_USER_SELECTED']);

            $('thead input[type="checkbox"]', table).prop('checked', false);
        }
    }

    $('#table-add-existent-users thead input[type="checkbox"]').on('click', function (e) {
        var wrapper = $($(this).parents('table'));

        $('tbody tr[data-id] td input[type="checkbox"]', wrapper).prop('checked', this.checked);

        updateAddExistentUserButtonLabel();
    });

    $('#table-add-existent-users').on('click', 'tbody tr[data-id] td input[type="checkbox"]', updateAddExistentUserButtonLabel);

    var onClickAddExistentUserAnchorCallback = function (e) {
        var table = $('#table-add-existent-users');
        var wrapper = $(table.parent().parent());
        var tbody = $('tbody', table);

        $('[data-type="submit"]', wrapper).remove();

        var addSelectedUsers = function (e) {
            e.preventDefault();

            var self = $(this);

            var table = $('#table-add-existent-users');
            var usersIdsList = [];
            var selectedCheckboxes = $('tbody input[type="checkbox"]:checked', table);
            if (selectedCheckboxes.length > 0) {
                for (var i = 0; i < selectedCheckboxes.length; i++) {
                    usersIdsList.push(selectedCheckboxes.get(i).value);
                }

                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'upstream:client.add_existent_users',
                        client: $('#post_ID').val(),
                        users: usersIdsList
                    },
                    beforeSend: function (jqXHR, settings) {
                        self.attr('disabled', 'disabled');
                        self.text(l['LB_ADDING_USERS']);
                        $('input', table).attr('disabled', 'disabled');
                    },
                    success: function (response, textStatus, jqXHR) {
                        if (!response.success) {

                        } else {
                            $('#TB_closeWindowButton').trigger('click');

                            var table = $('#table-users');
                            $('tr[data-empty]', table).remove();

                            for (var userIndex = 0; userIndex < response.data.length; userIndex++) {
                                var user = response.data[userIndex];

                                var tr = $('<tr data-id="' + user.id + '"></tr>');
                                tr.append('<td>' + user.name + '</td>');
                                tr.append('<td>' + user.email + '</td>');
                                tr.append('<td>' + user.assigned_by + '</td>');
                                tr.append('<td class="text-center">' + user.assigned_at + '</td>');
                                tr.append('<td class="text-center"><a href="#" data-remove-user class="up-u-color-red"><span class="dashicons dashicons-trash"></span></a></td>');

                                $('tbody', table).append(tr);
                            }
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.error(errorThrown);
                    },
                    complete: function (jqXHR, textStatus) {
                        $('input', table).attr('disabled', null);
                    }
                });
            }
        };

        $.ajax({
            type: 'GET',
            url: ajaxurl,
            data: {
                action: 'upstream:client.fetch_unassigned_users',
                client: $('#post_ID').val()
            },
            beforeSend: function (jqXHR, settings) {
                tbody.html('<tr data-loading><td colspan="4">Fetching users...</td></tr>');
            },
            success: function (response, textStatus, jqXHR) {
                tbody.html('');

                if (!response.success) {
                } else {
                    if (!response.data.length) {
                        tbody.append($('<tr><td colspan="3">' + l['MSG_NO_USERS_FOUND'] + '</td></tr>'));
                    } else {
                        var wrapper = $($('#table-add-existent-users').parent().parent());
                        $('div.submit', wrapper).append($('<button type="button" data-type="submit" disabled="disabled" class="button button-primary">' + l['MSG_NO_USER_SELECTED'] + '</button>'));

                        $('[data-type="submit"]', $('div.submit', wrapper)).on('click', addSelectedUsers);

                        response.data.map(function (user) {
                            var tr = $('<tr data-id="' + user.id + '"></tr>');

                            tr.append($('<td class="text-center"><input type="checkbox" value="' + user.id + '" /></td>'));
                            tr.append($('<td>' + user.name + '</td>'));
                            tr.append($('<td>' + user.email + '</td>'));

                            tbody.append(tr);

                            return user;
                        });
                    }
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                tbody.html('');
                console.error(errorThrown);
            },
            complete: function (jqXHR, textStatus) {}
        });
    };

    $('#add-existent-user').on('click', onClickAddExistentUserAnchorCallback);

    $(document).ready(function () {
        $('#table-users').on('click', 'a[data-remove-user]', removeUserCallback);

        var permissionsSubmitEventHasBeenAttached = false;

        function permissionsSubmitEvent (e) {
            e.preventDefault();

            var self = $(this);
            var wrapper = $('#form-user-permissions');
            var table = $('table', wrapper);

            var permissionsIds = [];
            $('tbody input[type="checkbox"]:checked').each(function () {
                permissionsIds.push($(this).val());
            });

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'upstream:client.update_user_permissions',
                    client: $('#post_ID').val(),
                    user: self.attr('data-id'),
                    permissions: permissionsIds
                },
                beforeSend: function (jqXHR, settings) {
                    $('input[type="checkbox"]', table).attr('disabled', 'disabled');
                    self.text(self.attr('data-loading-label'));
                    self.attr('disabled', 'disabled');
                },
                success: function (response, textStatus, jqXHR) {
                    if (!response.success) {
                        self.attr('disabled', null);
                        console.error(response.err);
                    } else {
                        self.attr('data-id', null);
                        $('#TB_closeWindowButton').trigger('click');
                    }

                    self.text(self.attr('data-label'));
                    $('input[type="checkbox"]', table).attr('disabled', null);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error(errorThrown);
                }
            });

            return false;
        };

        $('#table-users').on('click', 'tr[data-id] > td:first-child a', function (e) {
            var self = $(this);
            var wrapper = $('#form-user-permissions');
            var table = $('table', wrapper);
            var submitButton = $('button[type="submit"]', wrapper);
            var user_id = self.parents('tr[data-id]').attr('data-id');
            submitButton.text(self.attr('data-label'));
            submitButton.attr('data-id', user_id);

            if (!permissionsSubmitEventHasBeenAttached) {
                submitButton.on('click', permissionsSubmitEvent);
                permissionsSubmitEventHasBeenAttached = true;
            }

            $('thead input[type="checkbox"]', table).prop('checked', false);
            $('input[type="checkbox"]', table).attr('disabled', null);

            table.on('click', 'thead > tr:first-child > th:first-child input[type="checkbox"]', function () {
                var self = $(this);
                var shouldCheckAll = self.is(':checked');

                $('tbody input[name="permissions[]"]', table).prop('checked', shouldCheckAll);
            });

            $.ajax({
                type: 'GET',
                url: ajaxurl,
                data: {
                    action: 'upstream:client.fetch_user_permissions',
                    client: $('#post_ID').val(),
                    user: user_id
                },
                beforeSend: function (jqXHR, settings) {
                    $('tbody', table).html('<tr><td colspan="2">' + l['MSG_FETCHING_DATA'] + '</td></tr>');
                    submitButton.attr('disabled', 'disabled');
                },
                success: function (response, textStatus, jqXHR) {
                    $('tbody', table).html('');

                    if (!response.success) {
                        console.error(response.err);
                    } else {
                        if (!response.data.length) {
                            $('tbody', table).append($('<tr><td colspan="2">' + l['MSG_NO_DATA_FOUND'] + '</td></tr>'));
                        } else {
                            submitButton.attr('disabled', null);

                            for (var permissionIndex = 0; permissionIndex < response.data.length; permissionIndex++) {
                                var permission = response.data[permissionIndex];

                                var tr = $('<tr></tr>');
                                tr.append($(
                                    '<td>' +
                                    '<input type="checkbox" id="' + permission.key + '" name="permissions[]" value="' + permission.key + '" ' + (permission.value ? 'checked="checked"' : '') + ' />' +
                                    '</td>'
                                ));

                                tr.append($(
                                    '<td>' +
                                    '<label for="' + permission.key + '">' + permission.title + '</label>' +
                                    (permission.description.length > 0
                                            ? '<p class="description">' + permission.description + '</p>'
                                            : ''
                                    ) +
                                    '</td>'
                                ));

                                $('tbody', table).append(tr);
                            }
                        }
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error(errorThrown);
                }
            });
        });
    });
})(window, window.document, jQuery || null, ajaxurl, upstreamMetaboxClientLangStrings);
