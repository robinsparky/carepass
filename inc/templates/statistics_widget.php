<?php
/**
 * This file could be used to catch submitted form data. When using a non-configuration
 * view to save form data, remember to use some kind of identifying field in your form.
 */
?>
<p>Reporting activity from:
<b><?php $startDate = self::get_dashboard_widget_option(self::wid, 'starting_date');
    $totalInprogress = 0;
    $totalCompleted = 0;
    $showDate = DateTime::createFromFormat("Y-m-d", $startDate);
    echo $showDate->format("jS F Y"); 
?>
</b> to the present.</p>
<div>
    <table class="pass-statistics"><!DOCTYPE html>
    <thead>
        <tr><th><?php echo __("Webinar", CARE_TEXTDOMAIN) ?></th>
            <th><?php echo RecordUserWebinarProgress::PENDING ?></th>
            <th><?php echo RecordUserWebinarProgress::COMPLETED ?></th></tr>
    </thead>
    <tbody>
    <?php
        $stats = self::getWebinarStatistics( $startDate );
        foreach($stats as $webinarName => $stat) { 
            $totalInprogress += $stat[RecordUserWebinarProgress::PENDING];
            $totalCompleted  += $stat[RecordUserWebinarProgress::COMPLETED];
            ?>
          <tr><td><?php echo $webinarName ?></td>
              <td><?php echo $stat[RecordUserWebinarProgress::PENDING]?></td>
              <td><?php echo $stat[RecordUserWebinarProgress::COMPLETED]?></td>
          </tr>
    <?php } ?>
    </tbody>
    <tfoot>
        <tr><td>Grand Totals</td><td><?php echo $totalInprogress?></td><td><?php echo $totalCompleted?></td></tr>
    </tfoot>
    </table>
</div>
<div>
    <table class="pass-statistics"><!DOCTYPE html>
    <caption style="caption-side:bottom; align:right;">To change the report date, hover over the widget title and click on the "Configure" link</caption>
    <thead>
        <tr><th><?php echo __("Course", CARE_TEXTDOMAIN) ?></th>
            <th><?php echo RecordUserCourseProgress::PENDING ?></th>
            <th><?php echo RecordUserCourseProgress::COMPLETED ?></th></tr>
    </thead>
    <tbody>
    <?php
        $totalInprogress = 0;
        $totalCompleted = 0;
        $stats = self::getCourseStatistics( $startDate );
        foreach($stats as $webinarName => $stat) { 
            $totalInprogress += $stat[RecordUserCourseProgress::PENDING];
            $totalCompleted  += $stat[RecordUserCourseProgress::COMPLETED];
            ?>
          <tr><td><?php echo $webinarName ?></td>
              <td><?php echo $stat[RecordUserCourseProgress::PENDING]?></td>
              <td><?php echo $stat[RecordUserCourseProgress::COMPLETED]?></td>
          </tr>
    <?php } ?>
    </tbody>
    <tfoot>
        <tr><td>Grand Totals</td><td><?php echo $totalInprogress?></td><td><?php echo $totalCompleted?></td></tr>
    </tfoot>
    </table>
</div>