<?php
require_once MODEL_PATH . 'functions.php';
require_once MODEL_PATH . 'db.php';

// DB利用

function get_item($db, $item_id){
  $sql = "
    SELECT
      item_id, 
      name,
      stock,
      price,
      image,
      status
    FROM
      items
    WHERE
      item_id = ?
  ";

  // // SQL文を実行する準備
  // $statement = $db->prepare($sql);
  // // SQL文のプレースホルダに値をバインド
  // $statement->bindValue(1, $item_id, PDO::PARAM_INT);
  // // SQLを実行
  // $statement->execute($params);

  return fetch_query($db, $sql, [$item_id]);

}

function get_items($db, $is_open = false){
  $sql = '
    SELECT
      item_id, 
      name,
      stock,
      price,
      image,
      status
    FROM
      items
  ';
  if($is_open === true){
    $sql .= '
      WHERE status = 1
    ';
  }

  return fetch_all_query($db, $sql);
}

function get_all_items($db){
  return get_items($db);
}

function get_open_items($db){
  return get_items($db, true);
}

function regist_item($db, $name, $price, $stock, $status, $image){
  $filename = get_upload_filename($image);
  if(validate_item($name, $price, $stock, $filename, $status) === false){
    return false;
  }
  return regist_item_transaction($db, $name, $price, $stock, $status, $image, $filename);
}

function regist_item_transaction($db, $name, $price, $stock, $status, $image, $filename){
  $db->beginTransaction();
  if(insert_item($db, $name, $price, $stock, $filename, $status) 
    && save_image($image, $filename)){
    $db->commit();
    return true;
  }
  $db->rollback();
  return false;
  
}

function insert_item($db, $name, $price, $stock, $filename, $status){
  $status_value = PERMITTED_ITEM_STATUSES[$status];
  $sql = "
    INSERT INTO
      items(
        name,
        price,
        stock,
        image,
        status
      )
    VALUES(?, ?, ?, ?, ?);
  ";

  // // SQL文を実行する準備
  // $statement = $db->prepare($sql);
  // // SQL文のプレースホルダに値をバインド
  // $statement->bindValue(1, $name,         PDO::PARAM_STR);
  // $statement->bindValue(2, $price,        PDO::PARAM_INT);
  // $statement->bindValue(3, $stock,        PDO::PARAM_INT);
  // $statement->bindValue(4, $filename,     PDO::PARAM_STR);
  // $statement->bindValue(5, $status_value, PDO::PARAM_INT);
  // // SQLを実行
  return execute_query($db, $sql,[$name, $price, $stock, $filename, $status_value]);
}

function update_item_status($db, $item_id, $status){
  $sql = "
    UPDATE
      items
    SET
      status = ?
    WHERE
      item_id = ?
    LIMIT 1
  ";
  
  // // SQL文を実行する準備
  // $statement = $db->prepare($sql);
  // // SQL文のプレースホルダに値をバインド
  // $statement->bindValue(1, $status,   PDO::PARAM_INT);
  // $statement->bindValue(2, $item_id,  PDO::PARAM_INT);

  return execute_query($db, $sql,[$status, $item_id]);
}

function update_item_stock($db, $item_id, $stock){
  $sql = "
    UPDATE
      items
    SET
      stock = ?
    WHERE
      item_id = ?
    LIMIT 1
  ";
  
  // // SQL文を実行する準備
  // $statement = $db->prepare($sql);
  // // SQL文のプレースホルダに値をバインド
  // $statement->bindValue(1, $stock,   PDO::PARAM_INT);
  // $statement->bindValue(2, $item_id, PDO::PARAM_INT);

  return execute_query($db, $sql,[$stock, $item_id]);
}

function destroy_item($db, $item_id){
  $item = get_item($db, $item_id);
  if($item === false){
    return false;
  }
  $db->beginTransaction();
  if(delete_item($db, $item['item_id'])
    && delete_image($item['image'])){
    $db->commit();
    return true;
  }
  $db->rollback();
  return false;
}

function delete_item($db, $item_id){
  $sql = "
    DELETE FROM
      items
    WHERE
      item_id = ?
    LIMIT 1
  ";
  
  // // SQL文を実行する準備
  // $statement = $db->prepare($sql);
  // // SQL文のプレースホルダに値をバインド
  // $statement->bindValue(1, $item_id, PDO::PARAM_INT);

  return execute_query($db, $sql,[$item_id]);
}

//購入履歴を取得する関数
function get_histories($db, $user){
  $params = array();
  
  //購入履歴のデータ取り出す
  //購入明細の値段と個数から合計金額を計算
  //JOINで履歴と明細のテーブル結合
  $sql = '
    SELECT
      histories.order_id,
      histories.user_id,
      histories.created,
      SUM(details.price * details.amount) AS total_price
      FROM
      histories
    JOIN
      details
    ON
      details.order_id = histories.order_id
  ';
    //現在ログイン中の一般ユーザー(type2)の購入履歴を表示する
    if($user['type'] === USER_TYPE_NORMAL){
      //取り出す条件
      $sql .= '
        WHERE
          histories.user_id = ?
      '; 
// $params=Array ( [0] => 1 ) $user=Array ( [user_id] => 1 [name] => sampleuser [password] => password [type] => 2 )
      $params[] = $user['user_id'];
    // print_r($params);
    }
    //注文番号のグループ化を行う
    $sql .= '
      GROUP BY
        order_id
    ';

    return fetch_all_query($db, $sql, $params);
}

//購入明細を取得する関数
function get_details($db, $order_id, $user_id = 0){
  //注文番号取得
  $params = array($order_id);

  $sql = "
    SELECT
      details.order_id,
      items.name,
      details.price,
      details.amount,
      details.price * details.amount AS small_price
    FROM
      details
    JOIN
      items
    ON
      details.item_id = items.item_id
    WHERE
      details.order_id = ?
  ";
//existsを使い副問い合わせを行う
  if($user_id !== 0){
    $sql .= '
      AND
        exists( SELECT * FROM histories WHERE order_id=? AND user_id = ?)
    ';
    $params[] = $order_id;
    $params[] = $user_id;
  }

  return fetch_all_query($db, $sql, $params);
}

// 非DB

function is_open($item){
  return $item['status'] === 1;
}

function validate_item($name, $price, $stock, $filename, $status){
  $is_valid_item_name = is_valid_item_name($name);
  $is_valid_item_price = is_valid_item_price($price);
  $is_valid_item_stock = is_valid_item_stock($stock);
  $is_valid_item_filename = is_valid_item_filename($filename);
  $is_valid_item_status = is_valid_item_status($status);

  return $is_valid_item_name
    && $is_valid_item_price
    && $is_valid_item_stock
    && $is_valid_item_filename
    && $is_valid_item_status;
}

function is_valid_item_name($name){
  $is_valid = true;
  if(is_valid_length($name, ITEM_NAME_LENGTH_MIN, ITEM_NAME_LENGTH_MAX) === false){
    set_error('商品名は'. ITEM_NAME_LENGTH_MIN . '文字以上、' . ITEM_NAME_LENGTH_MAX . '文字以内にしてください。');
    $is_valid = false;
  }
  return $is_valid;
}

function is_valid_item_price($price){
  $is_valid = true;
  if(is_positive_integer($price) === false){
    set_error('価格は0以上の整数で入力してください。');
    $is_valid = false;
  }
  return $is_valid;
}

function is_valid_item_stock($stock){
  $is_valid = true;
  if(is_positive_integer($stock) === false){
    set_error('在庫数は0以上の整数で入力してください。');
    $is_valid = false;
  }
  return $is_valid;
}

function is_valid_item_filename($filename){
  $is_valid = true;
  if($filename === ''){
    $is_valid = false;
  }
  return $is_valid;
}

function is_valid_item_status($status){
  $is_valid = true;
  if(isset(PERMITTED_ITEM_STATUSES[$status]) === false){
    $is_valid = false;
  }
  return $is_valid;
}