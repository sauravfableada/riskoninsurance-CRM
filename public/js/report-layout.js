(function (window, $) {
    'use strict';

    window.compactReportLayout = function (config) {
        if (window.Chart) {
            window.Chart.defaults.font.size = 10;
            window.Chart.defaults.plugins.legend.labels.boxWidth = 14;
            window.Chart.defaults.plugins.legend.labels.padding = 10;
        }

        var $chart = $(config.chart);
        var $table = $(config.table);
        if (!$chart.length || !$table.length) return;

        var $topCard = $chart.closest('.card');
        $table.addClass('compact-report-table');
        var $search = $(config.search).closest('.input-group').detach();
        var $export = $(config.export).detach();
        var $controls = $('<div class="compact-report-controls"></div>').append($search, $export);
        var $topBar = $('<div class="compact-report-topbar"></div>')
            .append($('<h4 class="fw-bold mb-0"></h4>').text(config.title))
            .append($controls);

        var $header = $topCard.children('.card-header').first();
        if ($header.length) {
            $header.attr('class', 'card-header bg-white border-0 px-3 pt-3 pb-2').empty().append($topBar);
        } else {
            $topCard.children('.card-body').children('.d-flex').first().replaceWith($topBar);
        }

        var $filterPanel = $(config.filterAnchor).closest('.rounded');
        var $filterShell = $filterPanel.parent();
        $filterPanel.detach();
        $filterPanel.addClass('compact-report-filter-panel');

        var $chartBlock = config.chartRow ? $chart.closest('.row') : $chart.closest('.chart-container');
        if (!$chartBlock.length) $chartBlock = $chart.parent();
        $chartBlock.addClass('compact-report-chart');
        if (config.chartRow) $chartBlock.addClass('compact-report-chart-with-metrics');
        $filterPanel.insertBefore($chartBlock);
        if ($filterShell.length && !$filterShell.children().length) $filterShell.remove();

        $table.closest('.card').children('.card-header').remove();
    };
})(window, window.jQuery);
