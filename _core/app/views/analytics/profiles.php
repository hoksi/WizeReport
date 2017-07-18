<?php foreach($profiles as $item): ?>
    <a href="<?=site_url('analytics/report/' . $account_id . '/' .  $property_id . '/' . $item['id'])?>"><?=$account_name?> > <?=$property_name?> > <?=$item['name']?></a><br/>
<?php endforeach; ?>
