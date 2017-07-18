<?php foreach($report_list as $key => $item): ?>
    <a href="<?=site_url('analytics/report/' . $account_id . '/' .  $property_id . '/' . $profile_id . '/' . $key)?>"><?=$account_name?> > <?=$property_name?> > <?=$profile_name?> > <?=$item?></a><br/>
<?php endforeach; ?>
