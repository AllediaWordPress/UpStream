(function ($) {
    // Add a custom class to the Extensions menu item for custom styling.
    var menu_found = false,
        interval,
        limit = 20,
        i = 0;

    interval = window.setInterval(
        function () {
            if (menu_found || limit === i) {
                window.clearInterval(interval);
                return;
            }

            i++;

            $('#toplevel_page_upstream_general ul.wp-submenu li').each(function () {
                if ($(this).find('a').length > 0) {
                    if ($(this).find('a').attr('href') === 'admin.php?page=upstream_extensions') {
                        // Check if the current menu links to the extensions page.
                        $(this).addClass('upstream-extensions');
                        menu_found = true;
                    }
                }
            });
        },
        500
    );

    // Mailchimp subscription form
    var attempts = 0,
        checkExist;

    checkExist = setInterval(function() {
        ++attempts;

        if (attempts >= 20) {
            clearInterval(checkExist);
            return;
        }

        if ($('#upstream_subscription_ad').length > 0) {
            clearInterval(checkExist);

            $('#upstream_subscription_ad form').on('submit', function (e) {
                if ($(this).find('#mce-EMAIL').val().trim() === '') {
                    e.preventDefault();
                    e.stopPropagation();

                    $(this).find('#mce-EMAIL').addClass('error');
                    $(this).find('#mce-EMAIL').focus();
                }
            });

            $('#upstream_subscription_ad form #mce-EMAIL').on('blur', function () {
                 if ($(this).val().trim() !== '') {
                     $(this).removeClass('error');
                 }
            });
        }
    }, 500); // check every 100ms
})(jQuery);
