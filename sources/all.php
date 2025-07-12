<?php

if (isset($_POST['btn-dknt'])) {
    if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
        $secret = _secretkey;
        $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $_POST['g-recaptcha-response']);
        $responseData = json_decode($verifyResponse);
        if ($responseData->success) {
            $d->reset();
            $data = "";
            $data = array(
                'ho_ten' => $d->clear($_POST['ten']),
                'sdt' => $d->clear($_POST['dienthoai']),
                'soluong' => $d->clear($_POST['soluong']),
                'thoigian' => $d->clear($_POST['thoigian']),
                'tieu_de' => "Đăng ký nhận tin",
            );
            // var_dump($data);
            // die();
            $d->setTable('#_lienhe');
            if ($d->insert($data)) {
                $thongbao_tt    =   $d->getTxt(15);
                $thongbao_icon  =   'success';
                $thongbao_content =  $d->getTxt(16);
                $thongbao_url       = URLLANG;
            } else {
                $thongbao_tt    =   $d->getTxt(17);
                $thongbao_icon  =   'error';
                $thongbao_content =  $d->getTxt(17);
                $thongbao_url       = URLLANG;
            }
        } else {
            $thongbao_tt    =   $d->getTxt(17);
            $thongbao_icon  =   'error';
            $thongbao_content =  $d->getTxt(17);
            $thongbao_url       = URLLANG;
        }
    } else {
        $thongbao_tt    =   $d->getTxt(17);
        $thongbao_icon  =   'error';
        $thongbao_content =  $d->getTxt(17);
        $thongbao_url       = URLLANG;
    }
}

if (isset($_POST['lienhe'])) {
    if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
        $secret = _secretkey;
        $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $_POST['g-recaptcha-response']);
        $responseData = json_decode($verifyResponse);
        if ($responseData->success) {
            $noidung    =   validate_content($_POST['noi_dung']);
            $ho_ten     =   validate_content($_POST['ho_ten']);
            $dien_thoai =   validate_content($_POST['dien_thoai']);
            $email      =   validate_content($_POST['email']);
            $dia_chi    =   validate_content($_POST['dia_chi']);
            $ngay    =   validate_content($_POST['ngay']);
            $data['ho_ten']     =   $ho_ten;
            $data['email']      =   $email;
            $data['sdt']        =   $dien_thoai;
            $data['dia_chi']    =   $dia_chi;
            $data['noi_dung']   =   $noidung;
            $data['ngay_lienhe']   =   $ngay;
            $d->reset();
            $d->setTable('#_lienhe');
            if ($d->insert($data)) {
                $thongbao_tt    =   $d->getTxt(15);
                $thongbao_icon  =   'success';
                $thongbao_content =  $d->getTxt(16);
                $thongbao_url       = URLLANG;
            }
        } else {
            $thongbao_tt    =   $d->getTxt(17);
            $thongbao_icon  =   'error';
            $thongbao_content =  $d->getTxt(17);
            $thongbao_url       = URLLANG;
        }
    } else {
        $thongbao_tt    =   $d->getTxt(17);
        $thongbao_icon  =   'error';
        $thongbao_content =  $d->getTxt(17);
        $thongbao_url       = URLLANG;
    }
}


if (isset($_POST['dat_lich'])) {
    if ($_POST['pnvn_token'] == $_SESSION['token']) {
        $ho_ten     =   validate_content($_POST['ho_ten']);
        $so_khach     =   validate_content($_POST['so_khach']);
        $email     =   validate_content($_POST['email']);
        $ngay_dat_lich     =   validate_content($_POST['ngay_dat_lich']);
        $gio_dat_lich     =   validate_content($_POST['gio_dat_lich']);
        $dien_thoai =   validate_content($_POST['dien_thoai']);
        // $ngay_dat_lich_timestamp     =   validate_content($_POST['ngay_dat_lich_timestamp']);

        $data = [];
        $data = array(
            "ho_ten" => $ho_ten,
            "so_khach" => $so_khach,
            "email" => $email,
            "ngay_dat_lich" => $ngay_dat_lich,
            "gio_dat_lich" => $gio_dat_lich,
            "sdt" => $dien_thoai,
            "ngay_dat_lich_timestamp" => strtotime($ngay_dat_lich),
            "tieu_de" => "Đặt Lịch",
        );

        $d->reset();
        $d->setTable('#_lienhe');
        if ($d->insert($data)) {
            $thongbao_tt    =   $d->getTxt(15);
            $thongbao_icon  =   'success';
            $thongbao_content =  $d->getTxt(16);
            $thongbao_url       = URLLANG;
            token();
        } else {
            $thongbao_tt    =   $d->getTxt(17);
            $thongbao_icon  =   'error';
            $thongbao_content =  $d->getTxt(17);
            $thongbao_url       = URLLANG;
        }
    } else {
        $thongbao_tt    =   $d->getTxt(17);
        $thongbao_icon  =   'error';
        $thongbao_content =  $d->getTxt(17);
        $thongbao_url       = URLLANG;
    }
}


if (isset($_POST['lienhe_nocaptcha'])) {
    if ($_POST['pnvn_token'] == $_SESSION['token']) {
        $ho_ten     =   validate_content($d->clear(addslashes($_POST['ho_ten'])));
        $email     =   validate_content($d->clear(addslashes($_POST['email'])));
        $dien_thoai     =   validate_content($d->clear(addslashes($_POST['dien_thoai'])));
        $noi_dung     =   validate_content($d->clear(addslashes($_POST['noi_dung'])));
        $tieu_de     =   validate_content($d->clear(addslashes($_POST['tieu_de'])));

        $data = [];
        $data = array(
            "ho_ten" => $ho_ten,
            "email" => $email,
            "sdt" => $dien_thoai,
            "noi_dung" => $noi_dung,
            "tieu_de" => $tieu_de,
        );

        $d->reset();
        $d->setTable('#_lienhe');
        if ($d->insert($data)) {
            $thongbao_tt    =   $d->getTxt(15);
            $thongbao_icon  =   'success';
            $thongbao_content =  $d->getTxt(16);
            $thongbao_url       = URLLANG;
            token();
        } else {
            $thongbao_tt    =   $d->getTxt(17);
            $thongbao_icon  =   'error';
            $thongbao_content =  $d->getTxt(17);
            $thongbao_url       = URLLANG;
        }
    } else {
        $thongbao_tt    =   $d->getTxt(17);
        $thongbao_icon  =   'error';
        $thongbao_content =  $d->getTxt(17);
        $thongbao_url       = URLLANG;
    }
}

if (isset($_POST['lienhe_nocaptcha_footer'])) {
    if ($_POST['pnvn_token'] == $_SESSION['token']) {
        $ho_ten     =   'Trống';
        $email     =   validate_content($_POST['email']);
        $dien_thoai     =   'Trống';
        $noi_dung     =   'Trống';

        $data = [];
        $data = array(
            "ho_ten" => $ho_ten,
            "email" => $email,
            "sdt" => $dien_thoai,
            "noi_dung" => $noi_dung,
            "tieu_de" => "Đăng ký nhận thông tin (form dưới footer)",
        );

        $d->reset();
        $d->setTable('#_lienhe');
        if ($d->insert($data)) {
            $thongbao_tt    =   $d->getTxt(15);
            $thongbao_icon  =   'success';
            $thongbao_content =  $d->getTxt(16);
            $thongbao_url       = URLLANG;
            token();
        } else {
            $thongbao_tt    =   $d->getTxt(17);
            $thongbao_icon  =   'error';
            $thongbao_content =  $d->getTxt(17);
            $thongbao_url       = URLLANG;
        }
    } else {
        $thongbao_tt    =   $d->getTxt(17);
        $thongbao_icon  =   'error';
        $thongbao_content =  $d->getTxt(17);
        $thongbao_url       = URLLANG;
    }
}


if (isset($_POST['gui_thong_tin_khieu_nai'])) {
    if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
        $secret = _secretkey;
        $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $_POST['g-recaptcha-response']);
        $responseData = json_decode($verifyResponse);
        if ($responseData->success) {
            $ho_ten     =   validate_content($_POST['ho_ten']);
            $dien_thoai =   validate_content($_POST['dien_thoai']);
            $email      =   'Trống';
            $dia_chi    =   validate_content($_POST['dia_chi']);
            $ma_van_don    =   validate_content($_POST['ma_van_don']);
            $noi_dung    =   validate_content($_POST['noi_dung']);
            $loai_khieu_nai    =   validate_content($_POST['loai_khieu_nai']);

            $data['ho_ten']     =   $ho_ten;
            $data['email']      =   $email;
            $data['sdt']        =   $dien_thoai;
            $data['dia_chi']    =   $dia_chi;
            $data['noi_dung']   =   $noi_dung;
            $data['ma_van_don']   =   $ma_van_don;
            $data['loai_khieu_nai']   =   $loai_khieu_nai;
            $data['tieu_de']   =   'GỬI THÔNG TIN KHIẾU NẠI';
            $data['loai_lienhe']   =   1;
            $d->reset();
            $d->setTable('#_lienhe');
            if ($d->insert($data)) {
                $thongbao_tt    =   $d->getTxt(15);
                $thongbao_icon  =   'success';
                $thongbao_content =  $d->getTxt(16);
                $thongbao_url       = URLLANG;
            }
        } else {
            $thongbao_tt    =   $d->getTxt(17);
            $thongbao_icon  =   'error';
            $thongbao_content =  $d->getTxt(17);
            $thongbao_url       = URLLANG;
        }
    } else {
        $thongbao_tt    =   $d->getTxt(17);
        $thongbao_icon  =   'error';
        $thongbao_content =  $d->getTxt(17);
        $thongbao_url       = URLLANG;
    }
}
