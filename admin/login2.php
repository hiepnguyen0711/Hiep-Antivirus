<?php
if (!isset($_SESSION)) {
    session_start();
}

@define('_template', '/templates/');
@define('_source', '/sources/');
@define('_lib', '/lib/');

include "lib/config.php";
include "lib/function.php";
include "lib/class.php";
global $d;
$d = new func_index($config['database']);

if (isset($_COOKIE['key_ad']) and $_COOKIE['key_ad'] != '0') {
    $login = $d->o_fet("select * from #_user where token='" . addslashes($_COOKIE['key_ad']) . "' ");
    //print_r($login);
    if (count($login) > 0) {
        $_SESSION['id_user']     =     $login[0]['id'];
        $_SESSION['user_admin']     = $login[0]['tai_khoan'];
        $_SESSION['user_hash']     =    $login[0]['user_hash'];
        $_SESSION['quyen'] = $login[0]['quyen_han'];
        $_SESSION['name'] = $login[0]['ho_ten'];
        $_SESSION['is_admin'] = $login[0]['is_admin'];
        $d->location("index.php");
    }
}

if (isset($_POST['login'])) {
    $user_hash = sha1($d->clean(addslashes($_POST['input-username'])));
    $pass_hash = sha1($d->clean(addslashes($_POST['input-password'])));

    $login = $d->o_fet("select * from #_user where user_hash = '$user_hash' and pass_hash = '$pass_hash' and quyen_han>=1");
    if (count($login) > 0) {
        $_SESSION['id_user'] = $login[0]['id'];
        $_SESSION['user_admin'] = $login[0]['tai_khoan'];
        $_SESSION['user_hash'] = $user_hash;
        $_SESSION['quyen'] = @$login[0]['quyen_han'];
        $_SESSION['name'] = @$login[0]['ho_ten'];
        $_SESSION['is_admin'] = $login[0]['is_admin'];
        if (isset($_POST['checkbox'])) {
            if ($login[0]['token'] == '') {
                $key_login = md5(time() . $login[0]['id']);
                setrawcookie('key_ad', $key_login, time() + (86400 * 30 * 365), '/', NULL, NULL, TRUE);
                $data_tv['token'] = $key_login;
                $d->reset();
                $d->setTable('#_user');
                $d->setWhere('id', $login[0]['id']);
                $d->update($data_tv);
            } else {
                $key_login = $login[0]['token'];
                setrawcookie('key_ad', $key_login, time() + (86400 * 30 * 365), '/', NULL, NULL, TRUE);
            }
        }
        $d->location("index.php");
    } else {
        $err = 'Tài khoản hoặc mật khẩu chưa đúng.';
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Đăng nhập</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Bootstrap 3.3.7 -->
    <link rel="stylesheet" href="public/plugin/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="public/plugin/font-awesome/css/font-awesome.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="public/plugin/Ionicons/css/ionicons.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="public/css/AdminLTE.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700&display=swap" rel="stylesheet">
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <!-- /.login-logo -->
        <div class="login-box-body">
            <p class="login-box-msg"><b>ĐĂNG NHẬP HỆ THỐNG QUẢN TRỊ</b></p>
            <p class="text-center">
                <font class='err' style="color: red"><?= @$err ?></font>
            </p>
            <form action="" method="post">
                <div class="form-group has-feedback ">
                    <input type="text" id="input-username" name="input-username" class="form-control" placeholder="Tên đăng nhập">
                    <span class="glyphicon glyphicon-user form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <input id="input-password" name="input-password" type="password" class="form-control" placeholder="Password">
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="checkbox"> Ghi nhớ đăng nhập
                    </label>
                </div>
                <div class="form-group">
                    <button id="forget-btn" name="login" type="submit" onclick="return kiem_tra_login()" class="btn btn-primary btn-block btn-flat"><i class="glyphicon glyphicon-log-in"></i> Đăng nhập</button>
                </div>
            </form>
        </div>
        <!-- /.login-box-body -->
    </div>
    <!-- /.login-box -->

    <script src="public/plugin/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap 3.3.7 -->
    <script src="public/plugin/bootstrap/js/bootstrap.min.js"></script>
    <script>
        function kiem_tra_login() {
            if ($("#input-username").val() == '') {
                $("#input-username").focus();
                $(".err").text("Chưa nhập tên tài khoản");
                return false;
            } else if ($("#input-password").val() == '') {
                $("#input-password").focus();
                $(".err").text("Chưa nhập mật khẩu");
                return false;
            } else return true;
        }
    </script>
</body>

</html>