<?php foreach($property as $item): ?>
    <a href="<?=site_url('analytics/report/' . $account_id . '/' . $item['id'])?>"><?=$account_name?> > <?=$item['name']?></a><br/>
<?php endforeach; ?>
