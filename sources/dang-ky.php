<?php
include './smtp/index.php';
if (isset($_POST['dang-ky']) and $_SESSION['token']   == $_POST['_token']) {
    token();
    $row_tv = $d->num_rows("select * from #_thanhvien where email ='" . addslashes($_POST['email']) . "' or dien_thoai ='" . addslashes($_POST['dien_thoai']) . "'  ");
    if ($row_tv == 0) {
        $data['ho_ten']             =   addslashes(replaceHTMLCharacter($_POST['ho_ten']));
        $data['email']              =   addslashes(replaceHTMLCharacter($_POST['email']));
        $data['md5_email']          =   addslashes(MD5($_POST['email']));
        $data['mat_khau']           =   MD5($_POST['mat_khau']);
        $data['loai']               =   (int)$_POST['loai'];
        $data['ngay_tao']           =   date('Y-m-d', time());
        $data['token']              =   $_SESSION['token'];

        $d->reset();
        $d->setTable('#_thanhvien');
        if ($idthanhvien = $d->insert($data)) {
            $dem = strlen($idthanhvien);
            $chuoi = '';
            if ($dem < 5) {
                for ($i = 0; $i < (5 - $dem); $i++) {
                    $chuoi .= '0';
                }
            }
            if ($data['loai'] == 1) {
                $data_tv['ma_thanhvien'] = 'CTV-' . $chuoi . $idthanhvien;
                $data_tv['token_gioithieu'] = MD5($data_tv['ma_thanhvien'] . time());
            } else {
                $data_tv['ma_thanhvien'] = $chuoi . $idthanhvien;
            }

            $d->reset();
            $d->setTable('#_thanhvien');
            $d->setWhere('id', $idthanhvien);
            if ($d->update($data_tv)) {
                $noidung = '
                <p>Xin chào ' . $data['ho_ten'] . ',</p>
                <br>
                <p>Cảm ơn quý khách đã đăng ký tài khoản thành viên tại ' . _web_page . '.</p>
                <p>Quý khách nhận được email này bởi vì quý khách đã vừa tạo tài khoản thành viên của ' . _ten_cong_ty . '. Nếu quý khách không thực hiện, vui lòng liên hệ với chúng tôi. <br>Vui lòng truy cập vào liên kết bên dưới để xác nhận email và hoàn thành quy trình đăng ký:</p>
                <p><a href="' . URLPATH . '?com=' . $com . '&verificationid=' . $data['token'] . '&confirmid=' . $data['md5_email'] . '">' . URLPATH . '?com=' . $com . '&verificationid=' . $data['token'] . '&confirmid=' . $data['md5_email'] . '</a></p>
                <p><b>Trân trọng!</b></p>
                <p><i>Vui lòng không trả lời thư này!</i></p>';
                if (sendmail("Xác nhận đăng ký thành viên tại " . _web_page, $noidung, $email, $data['email'], _ten_cong_ty)) {
                    $thongbao_tt        =   'Đăng ký thành công';
                    $thongbao_icon      =   'success';
                    $thongbao_content   =   'Vui lòng kiểm tra mail để xác nhận đăng ký!';
                    $thongbao_url       =  URLPATH;
                } else {
                    $d->reset();
                    $d->setTable('#_thanhvien');
                    $d->setWhere('id', $idthanhvien);
                    $d->delete();
                    $thongbao_tt        =   'Đã sảy ra lỗi';
                    $thongbao_icon      =   'error';
                    $thongbao_content   =   'Gửi email xác nhận thất bại!';
                    $thongbao_url       =  '';
                }
            }
        }
    } else {
        $thongbao_tt    =   'Đã sảy ra lỗi';
        $thongbao_icon  =   'error';
        $thongbao_content =  'Email đã tồn tại';
        $thongbao_url       =  $url_page;
    }
}
if (isset($_GET['verificationid'])) {
    $thanhvien = $d->simple_fetch("select * from #_thanhvien where md5_email = '" . addslashes($_GET['confirmid']) . "' and token ='" . addslashes($_GET['verificationid']) . "' ");
    $data['token']     =  '';
    if ($thanhvien['loai'] == 0) {
        $data['trang_thai']         =  1;
    } else {
        $data['trang_thai']         =  2;
    }
    $d->reset();
    $d->setTable('#_thanhvien');
    $d->setWhere('id', $thanhvien['id']);
    if ($d->update($data)) {
        if ($thanhvien['loai'] == 0) {
            $thongbao_tt        =   'Hoàn tất đăng ký';
            $thongbao_icon      =   'success';
            $thongbao_content   =   '';
            $thongbao_url       =   URLPATH . $d->getCate(22, 'alias') . ".html";
        } else {
            $thongbao_tt        =   'Hoàn tất đăng ký! Tài khoản CTV của bạn sẽ hoạt động sau khi được admin duyệt';
            $thongbao_icon      =   'success';
            $thongbao_content   =   '';
            $thongbao_url       =   URLPATH . $d->getCate(22, 'alias') . ".html";
        }
    } else {
        $thongbao_tt        =   'Đã sảy ra lỗi';
        $thongbao_icon      =   'error';
        $thongbao_content   =   '';
        $thongbao_url       =   URLPATH . "dang-ky.html";
    }
}
?>

<div class="wrapper-main">
    <div class="account-area mt-70 mb-70">
        <div class="container">
            <div class="row">
                <div class="col-md-10 m-auto">
                    <div class="login_wrap widget-taber-content background-white">
                        <div class="p-5 padding_eight_all bg-white">
                            <div class="heading_s1">
                                <h1 class="mb-5"><?= $d->getTxt(63) ?></h1>
                                <p class="mb-30"><?= $d->getTxt(64) ?> <a href="<?= URLPATH . $d->getCate(21, 'alias') ?>.html"><?= $d->getCate(21, 'ten') ?></a></p>
                            </div>
                            <form method="post" action id="form-dangky">
                                <input type="hidden" value="<?= $_SESSION['token'] ?>" name="_token" />
                                <div class="form-group">
                                    <input type="text" required="" name="ho_ten" placeholder="<?= $d->getTxt(29) ?>">
                                </div>
                                <div class="form-group">
                                    <input type="text" required="" name="email" placeholder="Email">
                                </div>
                                <div class="form-group">
                                    <input required="" type="password" id="matkhau" name="mat_khau" placeholder="<?= $d->getTxt(66) ?>">
                                </div>
                                <div class="form-group">
                                    <input required="" type="password" name="mat_khau2" placeholder="<?= $d->getTxt(67) ?>">
                                </div>
                                <div class="login_footer form-group mb-50">
                                    <div class="chek-form">
                                        <div class="custome-checkbox">
                                            <input class="form-check-input" required type="checkbox" name="xacnhan" id="exampleCheckbox12" value="1">
                                            <label class="form-check-label" for="exampleCheckbox12"><span><?= $d->getTxt(70) ?></span></label>
                                        </div>
                                    </div>
                                    <a href="<?= URLLANG . $d->getCate(142, 'alias') ?>.html"><i class="fi-rs-book-alt mr-5 text-muted"></i><?= $d->getTxt(72) ?></a>
                                </div>
                                <div class="form-group mb-30">
                                    <button type="submit" class="btn btn-danger btn-fill-out btn-block hover-up font-weight-bold" name="dang-ky"><?= $d->getTxt(73) ?></button>
                                </div>
                                <p class="font-xs text-muted"><strong><?= $d->getTxt(134) ?>:</strong><?= $d->getTxt(71) ?></p>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    <?php if (isset($thongbao_tt) and $thongbao_tt != "") { ?>
        swal({
            title: '<?= $thongbao_tt ?>',
            text: '<?= $thongbao_content ?>',
            icon: '<?= $thongbao_icon ?>',
            button: false,
            timer: 2000
        }).then((value) => {
            window.location = "<?= $thongbao_url ?>";
        });
    <?php } ?>
    $(document).ready(function() {
        //Khi bàn phím được nhấn và thả ra thì sẽ chạy phương thức này
        $("#form-dangky").validate({
            rules: {
                ho_ten: "required",
                xacnhan: "required",
                email: {
                    required: true,
                    email: true
                },
                mat_khau: {
                    required: true,
                    minlength: 6,
                },
                mat_khau2: {
                    required: true,
                    minlength: 6,
                    equalTo: "#matkhau"
                }
            },
            messages: {
                ho_ten: "Vui lòng nhập họ tên",
                xacnhan: "Vui lòng xác nhận đồng ý với các điều khoản",
                email: {
                    required: "Vui lòng nhập email",
                    email: "Vui lòng nhập đúng định dạng mail"
                },
                mat_khau: {
                    required: "Vui lòng nhập mật khẩu",
                    minlength: 'Mật khẩu tối thiểu 6 ký tự'
                },
                mat_khau2: {
                    required: 'Vui lòng xác nhận lại mật khẩu',
                    minlength: 'Mật khẩu tối thiểu 6 ký tự',
                    equalTo: "Nhập lại mật khẩu không đúng"
                }
            }
        });
    });
</script>