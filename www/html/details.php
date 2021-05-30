<?php
require_once '../conf/const.php';
require_once MODEL_PATH . 'functions.php';
require_once MODEL_PATH . 'user.php';
require_once MODEL_PATH . 'item.php';

session_start();

if(is_logined() === false){
  redirect_to(LOGIN_URL);
}

$db = get_db_connect();

$user = get_login_user($db);

//トークンを取得
$token = get_post('token');

//該当の注文番号データ取得
$order_id = get_post('order_id');

//明細画面の履歴情報
$histories_data = get_histories_data($db, $user,$order_id);
// print_r($histories_data);

//管理者かどうかを判定
// function is_admin($user){
//   return $user['type'] === USER_TYPE_ADMIN;
// }
//ログインしているユーザー以外の注文については管理者以外閲覧できない(チェック)
if(is_valid_csrf_token($token) === true){
  
  if(is_admin($user) === true){
    $items = get_details($db, $order_id);

  } else {
    $items = get_details($db, $order_id, $user['user_id']);
  }
}else{
  set_error('不正な操作が行われました。');
  redirect_to(HISTORIES_URL);
}
include_once VIEW_PATH . '/details_view.php';