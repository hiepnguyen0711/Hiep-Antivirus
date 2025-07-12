<?php
require_once './library/vendor/autoload.php';

use \Firebase\JWT\JWT;

$key = "205049";

$link = explode("?", $_SERVER['REQUEST_URI']);
if ($link[1] != '') {
    $vari = explode("&", $link[1]);
    $search = array();
    foreach ($vari as $item) {
        $str = explode("=", $item);
        $search["$str[0]"] = $str[1];
    }
}
if (isset($search) and $search['url'] != '') {
    $url_back = URLLANG . $search['url'] . '.html';
} else {
    $url_back = URLLANG;
}


if (isset($_POST['login'])) {
    if ($_SESSION['token']  == $_POST['_token']) {
        $md5_email  = MD5($_POST['email']);
        $matkhau    = MD5($_POST['password']);

        echo $row_tv     = $d->num_rows("select * from #_thanhvien where md5_email ='" . $md5_email . "' and  mat_khau ='" . $matkhau . "' ");

        if ($row_tv > 0) {

            $thanhvien = $d->simple_fetch("select * from #_thanhvien where md5_email = '" . $md5_email . "' and mat_khau='" . $matkhau . "' ");
            if ($thanhvien['trang_thai'] == 1) {

                $_SESSION['thanhvien']['id_login'] = $thanhvien['id'];
                $_SESSION['thanhvien']['thanhvien_login'] = $thanhvien['ho_ten'];
                $_SESSION['thanhvien']['type'] = $thanhvien['loai'];
                if (isset($_POST['save'])) {

                    if ($thanhvien['token'] == "") {
                        $token  =   chuoird(6);
                        $data_tv['token'] =  $token;
                        $d->reset();
                        $d->setTable('#_thanhvien');
                        $d->setWhere('id', $thanhvien['id']);
                        $d->update($data_tv);
                    } else {
                        $token  =   $thanhvien['token'];
                    }
                    $payload['id_login']        =   $thanhvien['id'];
                    $payload['user_login']      =   $thanhvien['ho_ten'];
                    $payload['type']            =   $thanhvien['loai'];
                    $payload['token']           =   $token;
                    $jwt = JWT::encode($payload, $key);
                    setrawcookie("keyId", urlencode($jwt), time() + (60 * 60 * 24 * 365), "/", NULL, FALSE, TRUE);
                }

                // var_dump($url_back);
                // die();

                $thongbao_tt    =   'Đăng nhập thành công';
                $thongbao_icon  =   'success';
                $thongbao_content =  'Chào mừng thành viên ' . $thanhvien['ho_ten'];
                $thongbao_url       =  $url_back;
            } else {
                $thongbao_tt    =   'Đã sảy ra lỗi!';
                $thongbao_icon  =   'error';
                $thongbao_content =  'Tài khoản chưa được xác nhận';
                $thongbao_url       =  URLLANG;
            }
        } else {
            $thongbao_tt    =   'Đã sảy ra lỗi!';
            $thongbao_icon  =   'error';
            $thongbao_content =  'Email hoặc mật khẩu không đúng';
            $thongbao_url       =  $url_page;
        }
    } else {
        $thongbao_tt        =   'Đã sảy ra lỗi!';
        $thongbao_icon      =   'error';
        $thongbao_content   =   'Erore token';
        $thongbao_url       =   $url_page;
    }
    //token();

}
?>
<div class="wrapper-main">
    <!-- account-area-start -->
    <div class="account-area mt-70 mb-70">
        <div class="container">
            <div class="row">
                <div class="col-md-10 m-auto">
                    <div class="login_wrap widget-taber-content background-white">
                        <div class="p-5 padding_eight_all bg-white">
                            <div class="heading_s1">
                                <h1 class="mb-5"><?= $row['ten'] ?></h1>
                                <p class="mb-30"><?= $d->getTxt(61) ?> <a href="<?= $d->getCate(22, 'alias') ?>.html">Đăng ký</a></p>
                            </div>
                            <form method="POST" action="" id="form-dangnhap">
                                <input type="hidden" value="<?= $_SESSION['token'] ?>" name="_token" />
                                <div class="form-group">
                                    <input type="text" required="" name="email" placeholder="<?= $d->getTxt(31) ?> *">
                                </div>
                                <div class="form-group">
                                    <input required="" type="password" name="password" placeholder="<?= $d->getTxt(66) ?> *">
                                </div>
                                <div class="login_footer form-group mb-50">
                                    <div class="chek-form">
                                        <div class="custome-checkbox">
                                            <input class="form-check-input" type="checkbox" name="save" id="exampleCheckbox1" value="1">
                                            <label class="form-check-label" for="exampleCheckbox1"><span><?= $d->getTxt(87) ?></span></label>
                                        </div>
                                    </div>
                                    <a href="<?= URLLANG ?>quen-mat-khau.html"><?= $d->getCate(23, 'ten') ?>Quên mật khẩu ?</a>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-danger btn-heading btn-block hover-up" name="login">Log in</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        //Khi bàn phím được nhấn và thả ra thì sẽ chạy phương thức này
        $("#form-dangnhap").validate({
            rules: {
                email: {
                    required: true,
                    email: true
                },
                password: {
                    required: true
                }
            },
            messages: {
                email: {
                    required: "Vui lòng nhập email",
                    email: "Vui lòng nhập đúng định dạng mail"
                },
                password: {
                    required: "Vui lòng nhập mật khẩu"
                }
            }
        });
    });
</script>