<!DOCTYPE html>
<html lang="ja">
<head>
  <?php include VIEW_PATH . 'templates/head.php'; ?>
  <title>購入履歴</title>
  <link rel="stylesheet" href="<?php print(STYLESHEET_PATH . 'admin.css'); ?>">
</head>
<body>
  <?php include VIEW_PATH . 'templates/header_logined.php'; ?>
  <div class="container">
    <h1>購入履歴</h1>
    <?php if(count($items) > 0){ ?>
      <table class="table table-bordered">
        <thead class="thead-light">
          <tr>
            <th>注文番号</th>
            <th>購入日時</th>
            <th>合計金額</th>
            <th>購入明細</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($items as $item){ ?>
          <tr>
            <td><?php print $item['order_id']; ?></td>
            <td><?php print $item['created']; ?></td>
            <td><?php print $item['total_price']; ?>円</td>
            <td>
              <form method = "post" action="details.php">
                <!-- 各行の末尾に「購入明細表示」ボタンを表示 -->
                <input type="submit" value="明細を確認" class="btn btn-secondary">
                
                <input type="hidden" name="order_id" value="<?php print $item['order_id']; ?>" >
                <input type="hidden" name="created" value="<?php print $item['created']; ?>" >
                <input type="hidden" name="total_price" value="<?php print $item['total_price']; ?>" >
                <input type="hidden" name ="token" value ="<?php print $token; ?>">
              </form>
            </td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    <?php } else { ?>
      <p>購入履歴はありません。</p>
    <?php } ?> 
  </div>
</body>
</html>