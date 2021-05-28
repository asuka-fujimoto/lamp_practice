<!DOCTYPE html>
<html lang="ja">
<head>
  <?php include VIEW_PATH . 'templates/head.php'; ?>
  <title>購入明細</title>
  <link rel="stylesheet" href="<?php print(STYLESHEET_PATH . 'admin.css'); ?>">
</head>
<body>
  <?php 
  include VIEW_PATH . 'templates/header_logined.php'; 
  ?>

  <div class="container">
    <h1>購入明細</h1>
    <?php if(count($items) === 0){ ?>
      <p>該当する明細はありません</p>
    <?php } else { ?>
      <?php foreach($items as $item){ ?>
        <p>注文番号：<?php print h($item['order_id']);?></p>
        <p>購入日時：<?php print h($item['created']);?></p>
        <p>合計金額：<?php print h($item['small_price']);?>円</p>
    <?php } ?>
    
    <table class="table table-bordered">
    <thead class="thead-light">
        <tr>
        <th>商品名</th>
        <th>購入価格</th>
        <th>購入数</th>
        <th>小計</th>
        </tr>
    </thead>
    <tbody>
        
        <tr>
        <td><?php print h($item['name']) ;?></td>
        <td><?php print h($item['price']) ;?>円</td>
        <td><?php print h($item['amount']) ;?>個</td>
        <td><?php print $item['small_price'] ;?>円</td>
        </tr>
        <?php } ?>
    </tbody>
    </table>
    <a href="histories.php">購入履歴へ戻る</a>
  </div>
</body>
</html>