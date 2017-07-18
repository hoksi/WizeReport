<?php foreach($account as $item): ?>
    <a href="<?=site_url('analytics/report/' . $item['id'])?>"><?=$item['name']?></a><br/>
<?php endforeach; ?>
