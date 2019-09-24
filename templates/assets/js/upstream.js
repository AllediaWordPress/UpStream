/**
 * Resize function without multiple trigger
 *
 * Usage:
 * $(window).smartresize(function(){
 *     // code here
 * });
 */
(function ($, sr) {
    // debouncing function from John Hann
    // http://unscriptable.com/index.php/2009/03/20/debouncing-javascript-methods/
    var debounce = function (func, threshold, execAsap) {
        var timeout;

        return function debounced () {
            var obj = this, args = arguments;

            function delayed () {
                if (!execAsap)
                    func.apply(obj, args);
                timeout = null;
            }

            if (timeout)
                clearTimeout(timeout);
            else if (execAsap)
                func.apply(obj, args);

            timeout = setTimeout(delayed, threshold || 100);
        };
    };

    // smartresize
    jQuery.fn[sr] = function (fn) { return fn ? this.bind('resize', debounce(fn)) : this.trigger(sr); };

})(jQuery, 'smartresize');

// Sidebar
jQuery(document).ready(function ($) {
    if (typeof $.fn.datepicker.dates === 'undefined') {
        $.fn.datepicker.dates = {'en': []};
    }

    $.fn.datepicker.dates['en'] = {
        days: [
            upstream.langs.LB_SUNDAY,
            upstream.langs.LB_MONDAY,
            upstream.langs.LB_TUESDAY,
            upstream.langs.LB_WEDNESDAY,
            upstream.langs.LB_THURSDAY,
            upstream.langs.LB_FRIDAY,
            upstream.langs.LB_SATURDAY
        ],
        daysShort: [
            upstream.langs.LB_SUN,
            upstream.langs.LB_MON,
            upstream.langs.LB_TUE,
            upstream.langs.LB_WED,
            upstream.langs.LB_THU,
            upstream.langs.LB_FRI,
            upstream.langs.LB_SAT
        ],
        daysMin: [
            upstream.langs.LB_SU,
            upstream.langs.LB_MO,
            upstream.langs.LB_TU,
            upstream.langs.LB_WE,
            upstream.langs.LB_TH,
            upstream.langs.LB_FR,
            upstream.langs.LB_SA
        ],
        months: [
            upstream.langs.LB_JANUARY,
            upstream.langs.LB_FEBRUARY,
            upstream.langs.LB_MARCH,
            upstream.langs.LB_APRIL,
            upstream.langs.LB_MAY,
            upstream.langs.LB_JUNE,
            upstream.langs.LB_JULY,
            upstream.langs.LB_AUGUST,
            upstream.langs.LB_SEPTEMBER,
            upstream.langs.LB_OCTOBER,
            upstream.langs.LB_NOVEMBER,
            upstream.langs.LB_DECEMBER
        ],
        monthsShort: [
            upstream.langs.LB_JAN,
            upstream.langs.LB_FEB,
            upstream.langs.LB_MAR,
            upstream.langs.LB_APR,
            upstream.langs.LB_MAY,
            upstream.langs.LB_JUN,
            upstream.langs.LB_JUL,
            upstream.langs.LB_AUG,
            upstream.langs.LB_SEP,
            upstream.langs.LB_OCT,
            upstream.langs.LB_NOV,
            upstream.langs.LB_DEC
        ],
        today: upstream.langs.LB_TODAY,
        clear: upstream.langs.LB_CLEAR,
        format: 'mm/dd/yyyy',
        titleFormat: 'MM yyyy', /* Leverages same syntax as 'format' */
        weekStart: 0
    };

    $('[data-toggle="tooltip"]').tooltip();

    // TODO: This is some kind of easy fix, maybe we can improve this
    var setContentHeight = function () {
        // reset height
        $('.right_col').css('min-height', $(window).height());

        var bodyHeight = $('body').outerHeight(),
            footerHeight = $('body').hasClass('footer_fixed') ? -10 : $('footer').height(),
            leftColHeight = $('.left_col').eq(1).height() + $('.sidebar-footer').height(),
            contentHeight = bodyHeight < leftColHeight ? leftColHeight : bodyHeight;

        // normalize content
        contentHeight -= $('.nav_menu').height() + footerHeight;

        $('.right_col').css('min-height', contentHeight);
    };
    window.setContentHeight = setContentHeight;

    $('#sidebar-menu').find('a').on('click', function (ev) {

        var $li = $(this).parent();

        if ($li.is('.active')) {
            $li.removeClass('active active-sm');
            $('ul:first', $li).slideUp(function () {
                setContentHeight();
            });
        } else {
            // prevent closing menu if we are on child menu
            if (!$li.parent().is('.child_menu')) {
                $('#sidebar-menu').find('li').removeClass('active active-sm');
                $('#sidebar-menu').find('li ul').slideUp();
            }

            $li.addClass('active');

            $('ul:first', $li).slideDown(function () {
                setContentHeight();
            });
        }
    });

    // toggle small or large menu
    $('#menu_toggle').on('click', function () {
        if ($('body').hasClass('nav-md')) {
            $('#sidebar-menu').find('li.active ul').hide();
            $('#sidebar-menu').find('li.active').addClass('active-sm').removeClass('active');
        } else {
            $('#sidebar-menu').find('li.active-sm ul').show();
            $('#sidebar-menu').find('li.active-sm').addClass('active').removeClass('active-sm');
        }

        $('body').toggleClass('nav-md nav-sm');

        setContentHeight();
    });

    // check active menu
    $('#sidebar-menu').find('a[href="' + window.location.href.split('?')[0] + '"]').parent('li').addClass('current-page');

    $('#sidebar-menu').find('a').filter(function () {
        return this.href == window.location.href.split('?')[0];
    }).parent('li').addClass('current-page').parents('ul').slideDown(function () {
        setContentHeight();
    }).parent().addClass('active');

    // recompute content when resizing
    $(window).smartresize(function () {
        setContentHeight();
    });

    setContentHeight();

    // fixed sidebar
    if ($.fn.mCustomScrollbar) {
        $('.menu_fixed').mCustomScrollbar({
            autoHideScrollbar: true,
            theme: 'minimal',
            mouseWheel: {preventDefault: true}
        });
    }

    $(window).trigger('upstream-sidebar');
});
// /Sidebar

// Panel toolbox
jQuery(document).ready(function ($) {
    $('.collapse-link').on('click', function () {
        var $boxPanel = $(this).closest('.x_panel'),
            $icon = $(this).find('i'),
            $boxContent = $boxPanel.find('.x_content');

        // fix for some div with hardcoded fix class
        if ($boxPanel.attr('style')) {
            $boxContent.slideToggle(200, function () {
                $boxPanel.removeAttr('style');
            });
        } else {
            $boxContent.slideToggle(200);
            $boxPanel.css('height', 'auto');
        }

        $icon.toggleClass('fa-chevron-up fa-chevron-down');

        var state = $icon.hasClass('fa-chevron-up') ? 'opened' : 'closed',
            section = $boxPanel.data('section');

        // Store the current slider state.
        $.ajax({
            url: upstream.ajaxurl,
            type: 'post',
            data: {
                action: 'upstream_collapse_update',
                nonce: upstream.security,
                section: section,
                state: state
            }
        });
    });

    $('.close-link').click(function () {
        var $boxPanel = $(this).closest('.x_panel');

        $boxPanel.remove();
    });

    $('#project-dashboard.sortable').sortable({
        placeholder: 'ui-state-highlight',
        cancel: 'input,textarea,button,select,option,.ui-state-disabled,.navbar-right',
        handle: '.x_title i.sortable_handler',
        update: function (event, ui) {
            var rows = $('#project-dashboard').sortable('toArray');

            // Store the current panel order
            $.ajax({
                url: upstream.ajaxurl,
                type: 'post',
                data: {
                    action: 'upstream_panel_order_update',
                    nonce: upstream.security,
                    rows: rows
                }
            });
        }
    });
    $('#project-dashboard.sortable .x_title').disableSelection();
});

// Instantiate NProgress lib.
(function (window, document, $, NProgress, undefined) {
    if (!NProgress) return;

    NProgress.start();

    $(window).on('load', function () {
        NProgress.done();
    });
})(window, window.document, jQuery, NProgress || null);

(function (window, document, $, undefined) {
    $(document).ready(function () {
        $('.c-discussion').on('click', '.o-comment[data-id] a[data-action="comment.go_to_reply"]', function (e) {
            e.preventDefault();

            var targetComment = $($(this).attr('href'));
            if (targetComment.length === 0) {
                console.error('Comment not found.');
                return;
            }

            var wrapper = $(targetComment.parents('.c-discussion'));

            wrapper.animate({
                scrollTop: targetComment.get(0).offsetTop
            }, function () {
                targetComment.addClass('s-highlighted');
                setTimeout(function () {
                    targetComment.removeClass('s-highlighted');
                }, 750);
            });
        });

        function highlighComment (comment_selector) {
            var comment = $(comment_selector);
            if (comment.length === 0) return;

            var wrapper = $($(comment.parents('.c-comments')).get(0));
            if (wrapper.length === 0) return;

            var SCROLL_DURATION = 250;
            var HIGHLIGH_CLASS = 's-highlighted';

            var scrollElToOffset = function (subject, offset, onSuccessCallback) {
                subject.animate({
                    scrollTop: offset,
                    duration: SCROLL_DURATION
                }, onSuccessCallback);
            };

            var doHighlighComment = function (comment) {
                comment.addClass(HIGHLIGH_CLASS);
                setTimeout(function () {
                    comment.removeClass(HIGHLIGH_CLASS);
                }, 750);
            };

            var wrapperDataType = wrapper.attr('data-type');
            if (wrapperDataType === 'project') {
                var bodyHasScrolled = false;
                scrollElToOffset($('html, body'), comment.offset().top, function () {
                    if (bodyHasScrolled) return;

                    bodyHasScrolled = true;

                    scrollElToOffset(wrapper, comment.get(0).offsetTop, function () {
                        doHighlighComment(comment);
                    });
                });
            } else {
                var tr = $($(wrapper.parents('tr')).get(0));

                $('> td[data-name]', tr).trigger('click');

                var bodyHasScrolled = false;
                scrollElToOffset($('html, body'), tr.offset().top, function () {
                    if (bodyHasScrolled) return;

                    bodyHasScrolled = true;

                    comment = $('tr.child ' + comment_selector, tr.parent());
                    wrapper = $($(comment.parents('.c-comments')).get(0));

                    scrollElToOffset(wrapper, comment.get(0).offsetTop - comment.height(), function () {
                        doHighlighComment(comment);
                    });
                });
            }
        }

        setTimeout(function () {
            var commentIdRegexp = new RegExp(/^\#comment\-\d+/i);
            if (commentIdRegexp.test(window.location.hash)) {
                highlighComment(window.location.hash);
            }
        }, 250);
    });
})(window, window.document, jQuery || {});

(function (window, document, $, $data, TableExport, undefined) {
    $(document).ready(function () {
        $('.o-data-table tr[data-id] a[data-toggle="up-modal"]').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var self = $(this);
            var tr = self.parents('tr[data-id]');

            var modal = new Modal({
                el: self.attr('data-up-target')
            });

            modal.on('show', function (modal, e) {
                $('[data-column]', tr).each(function () {
                    var columnEl = $(this);
                    var columnName = $(this).attr('data-column');

                    if (['notes', 'description', 'comments'].indexOf(columnName) >= 0) {
                        $('[data-column="' + columnName + '"]', modal.el).html(columnEl.html());
                    } else {
                        $('[data-column="' + columnName + '"]', modal.el).text(columnEl.text());
                    }
                });
            });

            modal.on('hidden', function (modal, e) {
                $('[data-column]', modal.el).html('');
            });

            modal.show();
        });

        function createOrderDirectionEl (direction) {
            var span = $('<span></span>', {
                class: 'pull-right o-order-direction'
            });

            span.append($('<i></i>', {
                class: 'fa fa-sort' + (!direction ? '' : (direction === 'ASC' ? '-asc' : '-desc'))
            }));

            return span;
        }

        function orderTable (columnName, direction, table) {
            var trs = $('> tbody > tr[data-id]', table);

            if (trs.length === 0) return;

            var data = [];

            var tr, columnValue;

            var data = [];
            trs.each(function (trIndex) {
                var tr = $(this);

                // Check if the column has an specific value for ordering.
                if ($('[data-column="' + columnName + '"]', tr).attr('data-order')) {
                    columnValue = $('[data-column="' + columnName + '"]', tr).attr('data-order');
                } else {
                    // Fallback to the value.
                    columnValue = $('[data-column="' + columnName + '"]', tr).attr('data-value') || '';
                }

                data.push({
                    index: trIndex,
                    value: columnValue.toUpperCase(),
                    children: $('> tbody > tr[data-parent="' + tr.attr('data-id') + '"]', table).clone()
                });
            });

            data.sort(function (a, b) {
                var comparison = a.value.localeCompare(b.value);

                if (direction === 'DESC' && comparison !== 0) {
                    comparison *= -1;
                }

                return comparison;
            });

            $('> tbody > tr', table).remove();

            $.each(data, function (trNewIndex) {
                var tr = $(trs.get(this.index), table);

                tr.removeClass('t-row-odd t-row-even');

                if ((trNewIndex + 1) % 2 === 0) {
                    tr.addClass('t-row-even');
                } else {
                    tr.addClass('t-row-odd');
                }

                $('> tbody', table).append(tr);

                if (this.children.length > 0) {
                    $('> tbody', table).append(this.children);
                }
            });

            table.attr('data-order-dir', direction)
                .attr('data-ordered-by', columnName);

            var th = $('thead tr th[data-column="' + columnName + '"]', table);
            $('.o-order-direction', th).remove();

            th.append(createOrderDirectionEl(direction));
        }

        $('.o-data-table').on('click', 'thead th.is-orderable[role="button"]', function (e) {
            e.preventDefault();

            var self = $(this);
            var wrapper = $(self.parent());
            var table = wrapper.parents('table');
            var column = self.attr('data-column');

            $('.o-order-direction', wrapper).remove();
            $('th.is-orderable[role="button"]', wrapper).append(createOrderDirectionEl(null));
            $('.o-order-direction', self).remove();

            if (self.hasClass('is-ordered')) {
                var orderDir = (self.attr('data-order-dir') || 'DESC').toUpperCase();
                var newOrderDir = orderDir === 'DESC' ? 'ASC' : 'DESC';
            } else {
                $('.is-ordered', wrapper).removeClass('is-ordered');
                $('th[data-order-dir]', wrapper).attr('data-order-dir', null);

                var newOrderDir = 'ASC';
            }

            self.attr('data-order-dir', newOrderDir);
            self.append(createOrderDirectionEl(newOrderDir));
            self.addClass('is-ordered');

            orderTable(column, newOrderDir, $(self.parents('table.o-data-table')));

            // Store the current ordering data to persist after page load.
            $.ajax({
                url: upstream.ajaxurl,
                type: 'post',
                data: {
                    action: 'upstream_ordering_update',
                    nonce: upstream.security,
                    column: column,
                    orderDir: newOrderDir,
                    tableId: table.attr('id')
                }
            });
        });

        function sortTable (columnName, columnValue, filtersWrapper) {
            var table = $(filtersWrapper.attr('data-target'));
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

            $('tbody tr[data-empty-row]', table).remove();

            var filtersHasChanged = false;
            var trs = $('tbody tr[data-id]', table);

            trs.removeClass('is-filtered');

            trs.each(function (trIndex) {
                var tr = $(this);
                var shouldDisplay = false;

                var filter, filterIndex, filterColumnValue, columnValue, comparator, dataWrapper;
                for (filterIndex = 0; filterIndex < filtersMap.length; filterIndex++) {
                    filter = filtersMap[filterIndex];
                    if (filter.value === null) {
                        continue;
                    }

                    filtersHasChanged = true;

                    dataWrapper = $('[data-column="' + filter.column + '"]', tr);
                    if (dataWrapper.length === 0) {
                        dataWrapper = $('[data-column="' + filter.column + '"]', $('tbody tr[data-parent="' + tr.attr('data-id') + '"]'));
                        if (dataWrapper.length === 0) {
                            continue;
                        } else {
                            dataWrapper = $('[data-value]', dataWrapper);
                        }
                    }

                    columnValue = dataWrapper.attr('data-value');

                    if (filter.comparator === 'contains') {
                        if (typeof filter.value === 'string') {
                            comparator = new RegExp(filter.value, 'i');
                            shouldDisplay = comparator.test(columnValue);
                        } else {
                            for (var valueIndex in filter.value) {
                                comparator = new RegExp(filter.value[valueIndex], 'i');
                                if (comparator.test(columnValue)) {
                                    shouldDisplay = true;
                                    break;
                                }
                            }
                        }
                    } else if (filter.comparator === 'exact') {
                        if (typeof filter.value === 'string') {
                            shouldDisplay = shouldDisplay = columnValue === filter.value;
                        } else {
                            for (var valueIndex in filter.value) {
                                if (filter.value[valueIndex] === '__none__') {
                                    shouldDisplay = !columnValue || columnValue === '__none__';
                                } else {
                                    shouldDisplay = columnValue === filter.value[valueIndex] || columnValue.split(',').indexOf(filter.value[valueIndex]) >= 0;
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
                trs.show();
            }

            var filteredRows = $('tbody tr[data-id]:visible', table);
            if (filteredRows.length === 0) {
                var thsCount = $('thead th', table).length;
                var tr = $('<tr data-empty-row><td colspan="' + thsCount + '" class="text-center">No results</td></tr>');
                var tBody = $('tbody', table);

                if (tBody.children('[data-empty-row]').length === 0) {
                    tBody.append(tr);
                }
            } else {
                $('tbody tr[data-id]:visible', table).addClass('is-filtered');
            }

            $(table).parent().find('.sub_count').html(filteredRows.length);
            displayValue = filteredRows.length > 0 ? 'inline' : 'none';
            $(table).parent().find('.sub_count').parent().find("span.p_count").css("display", displayValue);

            $('#pro_count').html(filteredRows.length);
            $('#pro_count').parent().find("span.p_count").css("display", displayValue);
        }

        var filterDataTable = function (e, self) {
            if (typeof e === 'object' && e != null) {
                e.preventDefault();
            }

            if (typeof self === 'undefined') {
                self = $(this);
            } else {
                self = $(self);
            }

            var filterColumn = self.attr('data-column');
            var filterValue = self.val() || [];

            sortTable(filterColumn, filterValue || '', $(self.parents('form').get(0)));
        };

        $('.c-data-table .c-data-table__filters .o-select2').on('change', filterDataTable);

        // Force refresh the data tables, to apply default filters
        var statusFilter = $('.c-data-table__filters #projects-filters .o-select2[data-column="status"]')[0];
        if (typeof statusFilter !== 'undefined') {
            filterDataTable(null, statusFilter);
        }
        statusFilter = $('.c-data-table__filters #tasks-filters .o-select2[data-column="status"]')[0];
        if (typeof statusFilter !== 'undefined') {
            filterDataTable(null, statusFilter);
        }
        statusFilter = $('.c-data-table__filters #bugs-filters .o-select2[data-column="status"]')[0];
        if (typeof statusFilter !== 'undefined') {
            filterDataTable(null, statusFilter);
        }

        $('.c-data-table .c-data-table__filters input[type="search"]').on('keyup', function (e) {
            e.preventDefault();

            var self = $(this);
            var filterColumn = self.attr('data-column');
            //var value = self.val().trim();
            var value = self.val();

            var wrapper = $(self.parents('.c-data-table__filters'));
            $('.form-control[data-column="' + filterColumn + '"]', wrapper).val(value);

            sortTable(filterColumn, value, $(self.parents('form').get(0)));
        });

        // Expand rows in tables.
        $('.o-data-table').on('click', 'tr.is-expandable > td:first-child', function (e) {
            var self = $(this);
            var tr = $(self.parents('tr[data-id]').get(0));
            var table = $(tr.parents('.o-data-table').get(0));
            var trChild = $('tr[data-parent="' + tr.attr('data-id') + '"]', table);

            if (trChild.length > 0) {
                if (!tr.hasClass('is-expanded')) {
                    tr.addClass('is-expanded')
                        .attr('aria-expanded', 'true');
                    trChild.show();

                    $('.fa.fa-angle-right', self)
                        .removeClass('fa-angle-right')
                        .addClass('fa-angle-down');
                } else {
                    tr.removeClass('is-expanded')
                        .attr('aria-expanded', 'false');
                    trChild.hide();

                    $('.fa.fa-angle-down', self)
                        .removeClass('fa-angle-down')
                        .addClass('fa-angle-right');
                }
            }
        });

        $('.o-datepicker').datepicker({
            todayBtn: 'linked',
            clearBtn: true,
            autoclose: true,
            keyboardNavigation: false,
            format: $data.datepickerDateFormat
        }).on('change', function (e) {
            var self = $(this);

            var value = self.datepicker('getDate');

            if (value) {
                value = ((+new Date(value)) / 1000) - (60 * (new Date()).getTimezoneOffset());
            }

            var hiddenField = $('#' + self.attr('id') + '_timestamp');
            if (hiddenField.length > 0) {
                hiddenField.val(value);
            }
        });

        $('.c-data-table .c-data-table__filters .o-datepicker').on('change', function () {
            var self = $(this);

            var filterColumn = self.attr('data-column');
            var wrapper = $(self.parents('.c-data-table__filters'));
            var value = self.val();

            var hiddenField = $('#' + self.attr('id') + '_timestamp');
            if (hiddenField.length > 0) {
                sortTable(hiddenField.attr('data-column'), value, $(self.parents('form').get(0)));
            }
        });

        function cloneTableForExport (table) {
            var clonedTable = table.clone();
            $('thead tr', clonedTable).prepend($('<th>#</th>'));

            $('tbody tr:not(.is-filtered)', clonedTable).remove();

            var visibleIndex = 0;
            $('tbody tr[data-id]', clonedTable).each(function () {
                visibleIndex++;

                $(this).prepend($('<td>' + visibleIndex + '</td>'));
            });

            return clonedTable;
        }

        function getCurrentDate () {
            var now = new Date();
            var currentMonth = now.getMonth() + 1;
            var currentDate = [
                now.getFullYear(),
                (currentMonth < 10 ? '0' + currentMonth : currentMonth),
                now.getDate()
            ];

            var fileName = currentDate.join('');

            return fileName;
        }

        function isExportedDataFormatValid (format) {
            var allowedFormats = ['txt', 'csv'];

            return allowedFormats.indexOf(format) >= 0;
        }

        function exportTableData (table, targetFormat, charset) {
            if (!isExportedDataFormatValid(targetFormat)) {
                return false;
            }

            charset = (typeof charset === 'undefined'
                || !charset
                    ? 'utf-8'
                    : charset
            );

            var clonedTable = cloneTableForExport(table);

            $('> thead th[data-type="file"]', clonedTable).each(function () {
                var th = $(this);

                $('> tbody > tr > td[data-column="' + th.attr('data-column') + '"]', clonedTable).each(function () {
                    var td = $(this);

                    var a = $('a', td);
                    if (a.length > 0) {
                        td.text(a.attr('href'));
                    }
                });
            });

            var data = clonedTable.tableExport({
                trimWhitespace: true
            }).getExportData();

            var tableId = table.attr('id');

            var exportedData = data[tableId][targetFormat];

            var dataBlob = new Blob([exportedData.data], {
                type: exportedData.mimeType + ';charset=' + charset
            });

            var fileName = getCurrentDate() + '-' + exportedData.filename + exportedData.fileExtension;

            saveAs(dataBlob, fileName);

            return true;
        }

        $('.c-data-table [data-action="export"]').on('click', function (e) {
            e.preventDefault();

            var self = $(this);
            var fileType = (self.attr('data-type') || '').toLowerCase();

            if (!isExportedDataFormatValid(fileType)) {
                console.error('Invalid mime type "' + fileType + '"');
                return;
            }

            var cTable = $(self.parents('.c-data-table'));
            var table = $('.o-data-table', cTable);
            if (cTable.length > 0
                && table.length > 0) {
                exportTableData(table, fileType);
            }
        });

        $('.c-data-table select.form-control:not([multiple])').select2({
            allowClear: true
        });

        $('.c-data-table select.form-control[multiple]').select2({
            allowClear: false
        });

        $('.c-data-table').each(function () {
            var self = $(this);

            var table = $('.o-data-table', self);
            var order_by = table.attr('data-ordered-by') || '';
            var order_dir = table.attr('data-order-dir') || 'DESC';

            if (order_by.length > 0) {
                orderTable(order_by, order_dir, table);
            }
        });

        (function () {
            function generateContrastColor (baseColor) {
                var d = 0;

                // Counting the perceptive luminance - human eye favors green color.
                var a = 1 - (0.299 * baseColor.r + 0.587 * baseColor.g + 0.114 * baseColor.b) / 255;
                if (a >= 0.4) {
                    // Base color is dark, so we'll use white
                    d = 255;
                }

                var newColor = {
                    r: d,
                    g: d,
                    b: d
                };

                return newColor;
            }

            function hexToRGB (hexColor) {
                var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hexColor);
                var rgb = result ? {
                    r: parseInt(result[1], 16),
                    g: parseInt(result[2], 16),
                    b: parseInt(result[3], 16)
                } : null;

                var color = new Color(rgb);

                return color;
            }

            function componentToHex (c) {
                var hex = c.toString(16);
                return hex.length == 1 ? '0' + hex : hex;
            }

            function rgbToHex (r, g, b) {
                return '#' + componentToHex(r) + componentToHex(g) + componentToHex(b);
            }

            $('.up-o-label').each(function () {
                var self = $(this);

                var bgColor = self.css('background-color');
                if (bgColor) {
                    bgColor = bgColor.replace(/rgb\(|\)/ig, '').replace(/\s+/g, '').split(',');
                    if (bgColor.length === 3) {
                        bgColor = {
                            r: bgColor[0],
                            g: bgColor[1],
                            b: bgColor[2]
                        };

                        var constrastColor = generateContrastColor(bgColor);
                        var contrastColorHex = rgbToHex(constrastColor.r, constrastColor.g, constrastColor.b);

                        self.css('color', contrastColorHex);
                    }
                }
            });
        })();
    });
})(window, window.document, jQuery || {}, upstream || {}, TableExport);
