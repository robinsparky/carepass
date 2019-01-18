<?php
/**
 * This file could be used to catch submitted form data. When using a non-configuration
 * view to save form data, remember to use some kind of identifying field in your form.
 */
?>
<p style="text-align:center; font-weight:bold;">
<?php 
    $strStartDate = self::get_dashboard_widget_option(self::wid, 'starting_date');
    $strEndDate = self::get_dashboard_widget_option(self::wid, 'ending_date');
    $currentDate = new DateTime();
    $totalInprogress = 0;
    $totalCompleted = 0;
    $startDate = DateTime::createFromFormat( "Y-m-d", $strStartDate );
    if( false === $startDate ) {
        $startDate = DateTime::createFromFormat( "Y-m-d", "1970-01-01" );
    }
    $endDate = DateTime::createFromFormat( "Y-m-d", $strEndDate );
    if( false === $endDate ) {
        $endDate = $currentDate.add( new DateInterval("P12M") );
    }
    echo $startDate->format( "jS F Y" ) . " To " . $endDate->format( "jS F Y" ); 
?>
</p>
<div>
    <p>Members participating in Mentorship: 
    <?php
        $stats = self::getMentorshipStatistics( $startDate, $endDate );
        echo $stats 
        ?>
    </p>
</div>
<div>
    <table class="pass-statistics"><!DOCTYPE html>
    <thead>
        <tr><th><?php echo __("Webinar", CARE_TEXTDOMAIN) ?></th>
            <th><?php echo RecordUserWebinarProgress::PENDING ?></th>
            <th><?php echo RecordUserWebinarProgress::COMPLETED ?></th></tr>
    </thead>
    <tbody>
    <?php
        $stats = self::getWebinarStatistics( $startDate, $endDate );
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
        $stats = self::getCourseStatistics( $startDate, $endDate );
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