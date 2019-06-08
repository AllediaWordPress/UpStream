(function (global, $) {
    $(function () {
        var loaderString = '<div class="lds-dual-ring"></div>';

        /**
         * The framework constructor.
         *
         * @param plugin_name
         *
         * @constructor
         */
        var Framework = function (plugin_name) {
            this.plugin_name = plugin_name;
        };

        /**
         * Add a class to the addons menu allowing to highlight it using CSS.
         *
         * @param string href
         */
        Framework.prototype.highlight_submenu = function (href) {
            // Add a custom class to the Extensions menu item for custom styling.
            var menu_found = false,
                interval,
                limit = 20,
                i = 0,
                self = this;

            interval = global.setInterval(
                function () {
                    if (menu_found || limit === i) {
                        global.clearInterval(interval);
                        return;
                    }

                    i++;

                    $('#adminmenu ul.wp-submenu li').each(function () {
                        if ($(this).find('a').length > 0) {
                            if ($(this).find('a').attr('href') === href) {
                                // Check if the current menu links to the extensions page.
                                $(this).addClass('allex-highlight');
                                $(this).addClass(self.plugin_name);
                                menu_found = true;
                            }
                        }
                    });
                },
                500
            );
        };

        // Check if we already have the framework.
        if (!global.Allex) {
            global.Allex = Framework;
        }
    });
})(window, jQuery);
