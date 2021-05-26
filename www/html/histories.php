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

$token = get_csrf_token();

$items = get_histories($db, $user);
//ソート順は新しいものが上にくるようにする
$items = array_reverse($items);

include_once VIEW_PATH . '/histories_view.php';