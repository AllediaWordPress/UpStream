<?php

$display_options = $report->getDisplayOptions();

if (!empty($display_options['show_display_fields_box'])):

    ?>
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel" data-section="report-parameters-display-fields">
            <div class="x_title">
                <h2>
                    <?php echo esc_html(__('Include Fields')); ?>
                </h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">

                <div class="row">

                    <div class="col-lg-12">
                        <div class="form-group">
                            <select class="form-control" name="upstream_report__display_fields[]" multiple>
                                <?php foreach ($display_fields as $field_name => $title): ?>
                                    <option selected value="<?php echo $field_name; ?>"><?php echo esc_html($title); ?></option>
                                <?php endforeach; ?>
                            </select>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php

endif;
