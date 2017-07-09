(function(window, document, $, ajaxurl, undefined) {
  'use strict';

  if (!$) {
    console.error('UpStream requires jQuery.');
  }

  if (!$('#titlewrap').length) {
    return;
  }

  // Make the Client Name field required.
  (function() {
    var titleWrap = $('#titlewrap');
    var titleLabel = $('#title-prompt-text', titlewrap);

    titleLabel.text(titleLabel.text() + ' *');

    $('#title', titlewrap).attr('required', 'required');
  })();

  var removeUserCallback = function(e) {
    e.preventDefault();

    var row = $(this).parents('tr[data-id]');
    var tbody = $(row.parent());
    if (row.length) {
      $.ajax({
        type: 'POST',
        url : ajaxurl,
        data: {
          action: 'upstream:client.remove_user',
          client: $('#post_ID').val(),
          user  : row.data('id')
        },
        beforeSend: function(jqXHR, settings) {},
        success: function(response, textStatus, jqXHR) {
          if (!response.success) {
            alert(response.err);
          } else {
            row.remove();

            if ($('tr', tbody).length === 0) {
              // @todo : lang support
              tbody.append('<tr data-empty><td colspan="7">There\'s no users assigned yet.</td></tr>');
            }
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {},
        complete: function(jqXHR, settings) {}
      });
    }
  };

  function updateAddExistentUserButtonLabel() {
    var table = $('#table-add-existent-users');
    var wrapper = $(table.parent().parent());
    var button = $('[data-type="submit"]', wrapper);

    var selectedItemsCount = $('tbody tr[data-id] td input[type="checkbox"]:checked', table).length;
    if (selectedItemsCount > 0) {
      button.attr('disabled', null);
      button.text('Add '+ selectedItemsCount +' user'+ (selectedItemsCount > 1 ? 's' : ''));
    } else {
      button.attr('disabled', 'disabled');
      button.text('No user selected');

      $('thead input[type="checkbox"]', table).prop('checked', false);
    }
  }

  $('#table-add-existent-users thead input[type="checkbox"]').on('click', function(e) {
    var wrapper = $($(this).parents('table'));

    $('tbody tr[data-id] td input[type="checkbox"]', wrapper).prop('checked', this.checked);

    updateAddExistentUserButtonLabel();
  });

  $('#table-add-existent-users').on('click', 'tbody tr[data-id] td input[type="checkbox"]', updateAddExistentUserButtonLabel);

  var onClickAddExistentUserAnchorCallback = function(e) {
    var table = $('#table-add-existent-users');
    var wrapper = $(table.parent().parent());
    var tbody = $('tbody', table);

    $('[data-type="submit"]', wrapper).remove();

    var addSelectedUsers = function(e) {
      e.preventDefault();

      var table = $('#table-add-existent-users');
      var usersIdsList = [];
      var selectedCheckboxes = $('tbody input[type="checkbox"]:checked', table);
      if (selectedCheckboxes.length > 0) {
        for (var i = 0; i < selectedCheckboxes.length; i++) {
          usersIdsList.push(selectedCheckboxes.get(i).value);
        }

        $.ajax({
          type: 'POST',
          url : ajaxurl,
          data: {
            action: 'upstream:client.add_existent_users',
            client: $('#post_ID').val(),
            users : usersIdsList
          },
          beforeSend: function(jqXHR, settings) {},
          success   : function(response, textStatus, jqXHR) {
            if (!response.success) {

            } else {
              $('#TB_closeWindowButton').trigger('click');

              var table = $('#table-users');
              $('tr[data-empty]', table).remove();

              for (var userIndex = 0; userIndex < response.data.length; userIndex++) {
                var user = response.data[userIndex];

                var tr = $('<tr data-id="'+ user.id +'"></tr>');
                tr.append('<td>'+ user.name +'</td>');
                tr.append('<td>'+ user.username +'</td>');
                tr.append('<td>'+ user.email +'</td>');
                tr.append('<td class="text-center">'+ user.assigned_at +'</td>');
                tr.append('<td>'+ user.assigned_by +'</td>');
                tr.append('<td class="text-center"><a href="#" data-remove-user><span class="dashicons dashicons-trash"></span></a></td>');

                $('tbody', table).append(tr);
              }
            }
          },
          error     : function(jqXHR, textStatus, errorThrown) {
            console.error(errorThrown);
          },
          complete  : function(jqXHR, textStatus) {}
        });
      }
    };

    $.ajax({
      type: 'GET',
      url : ajaxurl,
      data: {
        action: 'upstream:client.fetch_unassigned_users',
        client: $('#post_ID').val()
      },
      beforeSend: function(jqXHR, settings) {
        tbody.html('<tr data-loading><td colspan="4">Fetching users...</td></tr>');
      },
      success   : function(response, textStatus, jqXHR) {
        tbody.html('');

        if (!response.success) {
        } else {
          if (!response.data.length) {
            tbody.append($('<tr><td colspan="4">No users found.</td></tr>'));
          } else {
            var wrapper = $($('#table-add-existent-users').parent().parent());
            $('div.submit', wrapper).append($('<button type="button" data-type="submit" disabled="disabled" class="button button-primary">No user selected</button>'));

            $('[data-type="submit"]', $('div.submit', wrapper)).on('click', addSelectedUsers);

            response.data.map(function(user) {
              var tr = $('<tr data-id="'+ user.id +'"></tr>');

              tr.append($('<td class="text-center"><input type="checkbox" value="'+ user.id +'" /></td>'));
              tr.append($('<td>'+ user.name +'</td>'));
              tr.append($('<td>'+ user.username +'</td>'));
              tr.append($('<td>'+ user.email +'</td>'));

              tbody.append(tr);

              return user;
            });
          }
        }
      },
      error     : function(jqXHR, textStatus, errorThrown) {
        tbody.html('');
        console.error(errorThrown);
      },
      complete  : function(jqXHR, textStatus) {}
    });
  }

  $('#add-existent-user').on('click', onClickAddExistentUserAnchorCallback);

  $(document).ready(function() {
    $('#table-users').on('click', 'a[data-remove-user]', removeUserCallback);

    $('#form-add-new-user button[type="submit"]').on('click', function(e) {
      e.preventDefault();

      var self = $(this);
      var form = $('#form-add-new-user');
      var hasError = false;

      var usernameField = $('[name="username"]', form);
      var usernameFieldValue = usernameField.val();
      // Check if username is potentially valid.
      if (usernameFieldValue.length < 3 || usernameFieldValue.length > 60 || !/^[a-z]+[a-z0-9\-\_]+$/i.test(usernameFieldValue)) {
        usernameField.focus();
        return;
      }

      var passwordField = $('[name="password"]', form);
      if (passwordField.val().length < 6) {
        passwordField.focus();
        return;
      }

      var inputsList = $('input', form);
      for (var inputIndex = 0; inputIndex < inputsList.length; inputIndex++) {
        var input = $(inputsList[inputIndex]);
        if (input.attr('required')) {
          var value = input.val() || "";
          if (value.trim().length === 0) {
            input.focus();
            hasError = true;
            break;
          }
        }
      }

      if (!hasError) {
        $.ajax({
          type: 'POST',
          url : ajaxurl,
          data: {
            action      : 'upstream:client.add_new_user',
            client      : $('#post_ID').val(),
            username    : usernameField.val(),
            email       : $('[name="email"]', form).val(),
            password    : passwordField.val(),
            first_name  : $('[name="first_name"]', form).val(),
            last_name   : $('[name="last_name"]', form).val(),
            notification: $('[name="notification"]', form).is(':checked'),
          },
          beforeSend: function(jqXHR, settings) {
            $('.error-message', form).remove();
          },
          success   : function(response, textStatus, jqXHR) {
            if (!response.success) {
              form.prepend($('<p class="error-message">' + response.err + '</p>'));
            } else {
              $('#TB_closeWindowButton').trigger('click');
              // @todo: reset form
              // @todo: close modal
              // @todo: append user to users-table

              var tr = $('<tr data-id="'+ response.data.id +'"></tr>');
              tr.append('<td>'+ response.data.name +'</td>');
              tr.append('<td>'+ response.data.username +'</td>');
              tr.append('<td>'+ response.data.email +'</td>');
              tr.append('<td>'+ response.data.assigned_at +'</td>');
              tr.append('<td>'+ response.data.assigned_by +'</td>');
              tr.append('<td class="text-center"><a href="#" data-remove-user><span class="dashicons dashicons-trash"></span></a></td>');

              var table = $('#table-users');
              $('tr[data-empty]', table).remove();

              $('tbody', table).append(tr);
            }
          },
          error     : function(jqXHR, textStatus, errorThrown) {},
          complete  : function(jqXHR, textStatus) {}
        });
      }
    });
  });
})(window, window.document, jQuery || null, ajaxurl);
