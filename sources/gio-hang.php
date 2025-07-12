<?php
// dd($_SESSION['cart']);
if (isset($_POST['dat_hang'])) {
    if (empty($_SESSION['cart'])) {
        header('location: ' . URLLANG);
        return false;
    }
    $ma_dh = 'DH-' . chuoird(5);
    token();
    $data['ma_dh']              =   $ma_dh;
    $data['ho_ten']             =   addslashes($_POST['ho_ten']);
    $data['dien_thoai']         =   addslashes($_POST['dien_thoai']);
    $data['email']              =   addslashes($_POST['email']);
    $data['dia_chi']              =   addslashes($_POST['dia_chi']);
    $data['phuong']             =   addslashes($_POST['code_xa']);
    $data['quan']               =   addslashes($_POST['code_huyen']);
    $data['thanh_pho']          =   addslashes($_POST['code_tinh']);
    //$data['phi_vanchuyen']      =   $_SESSION['nhahang']['phi_ship'];
    $data['phuong_thuc_van_chuyen']          =   addslashes($_POST['vanhiep_phuong_thuc_van_chuyen']);
    $data['phuong_thuc_thanh_toan']          =   addslashes($_POST['vanhiep_phuong_thuc_thanh_toan']);
    $data['ngay_dathang']       =   date('Y-m-d', time());
    $data['tinhtrang_donhang']  =   1;
    if (KiemTraDangNhap()) {
        $data['id_thanhvien']  =   $_SESSION['user_id'];
    }
    // lấy phí vận chuyển
    $phi_van_chuyen_data = $d->simple_fetch("select * from #_phuong_thuc_van_chuyen where id = {$data['phuong_thuc_van_chuyen']} ");
    $data['phi_vanchuyen'] = $phi_van_chuyen_data['gia'];
    // dd($data);
    // die();
    $d->reset();
    $d->setTable('#_dathang');
    if ($id_dh = $d->insert($data)) {
        $_SESSION['ma_dh'] =  $ma_dh;
        foreach ($_SESSION['cart'] as $key => $value) {
            $row_sp = $d->simple_fetch("select * from #_sanpham where id_code = '" . $value['productid'] . "' $where_lang");
            if ($row_sp['khuyen_mai']) {
                $gia = $row_sp['khuyen_mai'];
            } else {
                $gia = $row_sp['gia'];
            }
            $tong = $tong + ($gia * $row_sp['soluong']);
            $data_ct['id_dh']       = $id_dh;
            $data_ct['ma_dh']       = $ma_dh;
            $data_ct['ten_sp']      = $row_sp['ten'];
            $data_ct['gia_ban']     = $gia;
            $data_ct['so_luong']    = $value['soluong'];
            $data_ct['id_sp']       = $row_sp['id_code'];
            $data_ct['hinh_sp']     = $row_sp['hinh_anh'];

            // 
            $data_ct['left_eye']     = $value['left_eye'];
            $data_ct['right_eye']     = $value['right_eye'];
            // 
            $d->reset();
            $d->setTable('#_dathang_chitiet');
            $d->insert($data_ct);
        }
        unset($_SESSION['cart']);
        unset($_SESSION['nhahang']);
        $thongbao_tt        =   'Đặt hàng thành công';
        $thongbao_icon      =   'success';
        $thongbao_content   =   '';
        $thongbao_url       =  URLLANG . 'thanhcong.html?success=' . $ma_dh;
    }
}
$link = explode("?", $_SERVER['REQUEST_URI']);
if ($link[1] != '') {
    $vari = explode("&", $link[1]);
    $search = array();
    foreach ($vari as $item) {
        $str = explode("=", $item);
        $search["$str[0]"] = $str[1];
    }
}
if (isset($search['delete'])) {
    unset($_SESSION['cart'][$search['delete']]);
    if (count($_SESSION['cart']) == 0) {
        unset($_SESSION['cart']);
    } else {
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
    $d->redirect(URLLANG . $com . ".html");
}
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $soluongsp = 0;
    foreach ($_SESSION['cart'] as $key => $value) {
        $soluongsp = $soluongsp + $value['soluong'];
    }
}
//session_destroy();
if (isset($_SESSION['cart'])) {
    $cart_products = $_SESSION['cart'];
} else {
    $cart_products = "";
}
// var_dump($cart_products);
$giohang_c = $d->getCate(121);
// dd($_SESSION['cart']);
if (!empty($cart_products)) {
?>

    <section class="vanhiep-gio-hang p-0">
        <div class="vanhiep-gio-hang-content ">
            <div class="row gx-0 gy-4 flex-column-reverse flex-lg-row">
                <div class="col-12 col-lg-7">
                    <div class="vanhiep-gio-hang-content-item vanhiep-gio-hang-content-item-left ps-lg-5 pt-3 pt-lg-4">
                        <div class="container">
                            <!-- title start -->
                            <div class="vanhiep-gio-hang-ten-cong-ty">
                                <a href="<?= URLLANG ?>"><?= _ten_cong_ty ?></a>
                            </div>
                            <!-- title end -->
                            <!-- breadcrumb start -->
                            <div class="vanhiep-gio-hang-breadcrumb my-2">
                                <ul class="vanhiep-gio-hang-breadcrumb-ul d-flex align-items-center">
                                    <li class="active">
                                        <a href="<?= URLLANG . $giohang_c['alias'] . '.html' ?>">Giỏ hàng</a>
                                    </li>
                                    <li class="mx-2">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </li>
                                    <li>
                                        Thông tin giỏ hàng
                                    </li>
                                </ul>
                            </div>
                            <!-- breadcrumb end -->

                            <!-- thông tin thanh toán start -->
                            <div class="vanhiep-gio-hang-thong-tin-thanh-toan pb-3">
                                <!-- title start -->
                                <div class="vanhiep-gio-hang-thong-tin-thanh-toan-title mb-3">
                                    Thông tin thanh toán
                                </div>
                                <!-- title end -->

                                <!-- form thanh toán start -->
                                <div class="vanhiep-gio-hang-thong-tin-thanh-toan-form">
                                    <form action="" method="post">
                                        <div class="row gx-4 gy-3">
                                            <!-- họ tên start -->
                                            <div class="col-12">
                                                <div class="vanhiep-gio-hang-thong-tin-thanh-toan-form-item">
                                                    <div class="form-floating ">
                                                        <?php
                                                        if (KiemTraDangNhap()) { ?>
                                                            <input type="text" name="ho_ten" value="<?= $tai_khoan['ho_ten'] ?>" required class="form-control" id="floatingName" placeholder="Họ tên">
                                                        <?php
                                                        } else {
                                                        ?>
                                                            <input type="text" name="ho_ten" required class="form-control" id="floatingName" placeholder="Họ tên">
                                                        <?php } ?>
                                                        <label for="floatingName">Họ tên</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- họ tên end -->

                                            <!-- email start -->
                                            <div class="col-12 col-lg-8">
                                                <div class="vanhiep-gio-hang-thong-tin-thanh-toan-form-item">
                                                    <div class="form-floating ">
                                                        <?php
                                                        if (KiemTraDangNhap()) { ?>
                                                            <input type="email" name="email" value="<?= $tai_khoan['email'] ?>" required class="form-control" id="floatingEmail" placeholder="Email">
                                                        <?php
                                                        } else {
                                                        ?>
                                                            <input type="email" name="email" required class="form-control" id="floatingEmail" placeholder="Email">
                                                        <?php } ?>
                                                        <label for="floatingEmail">Email</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- email end -->

                                            <!-- số điện thoại start -->
                                            <div class="col-12 col-lg-4">
                                                <div class="vanhiep-gio-hang-thong-tin-thanh-toan-form-item">
                                                    <div class="form-floating ">
                                                        <?php
                                                        if (KiemTraDangNhap()) { ?>
                                                            <input type="number" name="dien_thoai" value="<?= $tai_khoan['dien_thoai'] ?>" required class="form-control" id="floatingPhone" placeholder="Số điện thoại">
                                                        <?php
                                                        } else {
                                                        ?>
                                                            <input type="number" name="dien_thoai" required class="form-control" id="floatingPhone" placeholder="Số điện thoại">
                                                        <?php } ?>
                                                        <label for="floatingPhone">Số điện thoại</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- số điện thoại end -->

                                            <!-- địa chỉ start -->
                                            <div class="col-12">
                                                <div class="vanhiep-gio-hang-thong-tin-thanh-toan-form-item">
                                                    <div class="form-floating ">
                                                        <input type="text" name="dia_chi" required class="form-control" id="floatingAddress" placeholder="Địa chỉ">
                                                        <label for="floatingAddress">Địa chỉ</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- địa chỉ end -->

                                            <!-- tỉnh start -->
                                            <div class="col-12 col-lg-4">
                                                <div class="vanhiep-gio-hang-thong-tin-thanh-toan-form-item">
                                                    <div class="form-floating">
                                                        <select class="form-control" required name="code_tinh" id="code_tinh" onchange="get_huyen('code_tinh', 'code_huyen')">
                                                            <option value=""><?= $d->gettxt(125) ?></option>
                                                            <?php foreach ($d->getTinh('code,ten') as $key => $value) { ?>
                                                                <option value="<?= $value['code'] ?>"><?= $value['ten'] ?></option>
                                                            <?php } ?>
                                                        </select>
                                                        <label for="code_tinh">Tỉnh</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- tỉnh start -->

                                            <!-- quận/huyện start -->
                                            <div class="col-12 col-lg-4">
                                                <div class="vanhiep-gio-hang-thong-tin-thanh-toan-form-item">
                                                    <div class="form-floating">
                                                        <select class="form-control" required id="code_huyen" name="code_huyen" onchange="get_xa('code_huyen', 'code_xa')">
                                                            <option value="">Chọn quận/huyện</option>
                                                        </select>
                                                        <label for="code_huyen">Quận/Huyện</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- quận huyện end -->

                                            <!-- xã/phường start -->
                                            <div class="col-12 col-lg-4">
                                                <div class="vanhiep-gio-hang-thong-tin-thanh-toan-form-item">
                                                    <div class="form-floating">
                                                        <select class="form-control" required id="code_xa" name="code_xa">
                                                            <option value="">Chọn phường/xã</option>
                                                        </select>
                                                        <label for="code_xa">Phường/Xã</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- xã phường end -->

                                            <!-- Phương thức vận chuyển -->
                                            <div class="col-12">
                                                <div class="vh-ptvc-form">
                                                    <?php
                                                    // Lấy phương thức vận chuyển
                                                    $phuong_thuc_van_chuyen_list = $d->o_fet("select * from #_phuong_thuc_van_chuyen order by id desc");
                                                    foreach ($phuong_thuc_van_chuyen_list as $key => $v) {
                                                        if ($key == 0) {
                                                            $checked = "checked";
                                                        } else {
                                                            $checked = "";
                                                        }
                                                    ?>
                                                        <div class="form-check py-3">
                                                            <div class="row px-3">
                                                                <div class="col-9">
                                                                    <div class="vh-ptvc-col-item">
                                                                        <input class="form-check-input vanhiep_phuong_thuc_van_chuyen" value="<?= $v['id'] ?>" data-value="<?= $v['gia'] ?>" <?= $checked ?> type="radio" name="vanhiep_phuong_thuc_van_chuyen" id="vh-ptvc-radio-<?= $key ?>">
                                                                        <label class="form-check-label" for="vh-ptvc-radio-<?= $key ?>"><?= $v['ten'] ?></label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-3">
                                                                    <div class="vh-ptvc-col-item vh-ptvc-col-item-price">
                                                                        <label for="vh-ptvc-radio-<?= $key ?>"><?= number_format($v['gia']) ?> <span class='vh-type-price'><sup>đ</sup></span></label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <!-- phương thức vận chuyển end -->

                                            <!-- phương thức thanh toán start -->
                                            <div class="col-12">
                                                <div class="vanhiep-gio-hang-thong-tin-thanh-toan-form-item">
                                                    <!-- title start -->
                                                    <div class="vanhiep-gio-hang-thong-tin-thanh-toan-title mb-3">
                                                        Phương Thức thanh toán
                                                    </div>
                                                    <!-- title end -->

                                                    <!-- content start -->
                                                    <div class="vanhiep-phuong-thuc-thanh-toan">
                                                        <div class="accordion" id="accordionExample">
                                                            <?php
                                                            // lấy danh sách phương thực thanh toán
                                                            $phuong_thuc_thanh_toan_list = $d->o_fet("select * from #_phuong_thuc_thanh_toan order by id desc");
                                                            foreach ($phuong_thuc_thanh_toan_list as $key => $v) {
                                                                if ($key == 0) {
                                                                    $collapsed = "";
                                                                    $show = "show";
                                                                    $checked = "checked";
                                                                } else {
                                                                    $collapsed = "collapsed";
                                                                    $show = "";
                                                                    $checked = "";
                                                                }
                                                            ?>
                                                                <!-- item start -->
                                                                <div class="accordion-item vh-pttt-item">
                                                                    <h2 class="accordion-header" id="headingOne">
                                                                        <button class="accordion-button <?= $collapsed ?>" type="button" data-bs-toggle="collapse" data-bs-target="#vh-collapse-<?= $key ?>" aria-expanded="true" aria-controls="vh-collapse-<?= $key ?>">
                                                                            <div class="form-check ">
                                                                                <div class="d-flex align-items-center">
                                                                                    <input class="form-check-input me-2" <?= $checked ?> value="<?= $v['id'] ?>" type="radio" name="vanhiep_phuong_thuc_thanh_toan" id="vh-pttt-<?= $key ?>">
                                                                                    <label class="form-check-label d-inline-flex align-items-center" for="vh-pttt-<?= $key ?>">
                                                                                        <img src="<?= Img($v['hinh_anh']) ?>" alt="icon" class="me-2 vh-gio-hang-pttt-icon"> <?= $v['ten'] ?>
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                        </button>
                                                                    </h2>
                                                                    <div id="vh-collapse-<?= $key ?>" class="accordion-collapse collapse <?= $show ?>" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                                                        <div class="accordion-body p-3">
                                                                            <?= $v['noi_dung'] ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <!-- item end -->
                                                            <?php } ?>


                                                        </div>
                                                    </div>
                                                    <!-- content end -->
                                                </div>
                                            </div>

                                            <!-- phương thức thanh toán end -->

                                            <!-- nút đặt hàng start -->
                                            <div class="vh-btn-dang-hang-form">
                                                <div class="d-flex justify-content-between align-items-center flex-column-reverse flex-lg-row">
                                                    <div class="vh-quay-lai mt-2 mt-lg-0">
                                                        <a href="<?= URLLANG ?>" class="d-inline-flex py-3 px-3 text-center">Quay lại</a>
                                                    </div>
                                                    <button type="submit" class="btn vh-btn-dat-hang py-3 px-3" name="dat_hang">Đặt hàng</button>

                                                </div>
                                            </div>
                                            <!-- nút đặt hàng end -->

                                        </div>
                                    </form>
                                </div>
                                <!-- form thanh toán end -->

                                <!-- bản quyền start -->
                                <div class="vh-copyright text-center py-2 mt-3">
                                    Powered by PhươngNamVina
                                </div>
                                <!-- bản quyền end -->
                            </div>
                            <!-- thông tin thanh toán end -->
                        </div>
                    </div>
                </div>
                <!-- thông tin giỏ hàng start -->
                <div class="col-12 col-lg-5">
                    <div class="vanhiep-gio-hang-content-item vanhiep-gio-hang-content-item-right pe-lg-5 pt-3 pt-lg-4">
                        <div class="container">
                            <!--  -->
                            <!-- Giỏ hàng nội dung start -->
                            <div class="ha-cart-product-content">
                                <?php
                                $total_cost_no_fax = 0;
                                foreach ($cart_products as $key => $v) {
                                    // lấy thông tin sản phẩm
                                    $product = $d->simple_fetch("select * from #_sanpham where id_code = {$v['productid']}");
                                ?>
                                    <!-- item start -->
                                    <div class="ha-cart-product-item mb-4" data-pid="<?= $v['productid'] ?>" data-code="<?= $v['code'] ?>">
                                        <div class="row align-items-start">
                                            <!-- hình -->
                                            <div class="col-2">
                                                <div class="ha-cart-product-item-img-box">
                                                    <a href="<?= Img($product['hinh_anh']) ?>" data-fancybox="images"> <img class="cart-product-item-img" src="<?= Img($product['hinh_anh']) ?>" alt="Product Image">
                                                    </a>
                                                    <span class="ha-cart-product-item-quantity ha-cart-product-item-quantity-key-<?= $v['code'] ?> "><?= $v['soluong'] ?></span>
                                                    <!-- nút xoát -->
                                                    <span class="ha-cart-product-delete">
                                                        <a style="color: red;" href="<?= URLLANG . $com ?>.html?delete=<?= $key ?>">
                                                            <i class="fa-solid fa-circle-xmark"></i>
                                                        </a>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- tên sản phẩm, size và màu -->
                                            <div class="col-7 px-2">
                                                <div class="row gy-0">
                                                    <!-- tên sản phẩm -->
                                                    <div class="col-12">
                                                        <div class="ha-product-item-name">
                                                            <span class="catchuoi2"><?= $product['ten'] ?></span>
                                                        </div>
                                                    </div>

                                                    <!-- độ cận start -->
                                                    <div class="col-12">
                                                        <div class="do-can-gio-hang-wrapper d-flex flex-nowrap gap-3">
                                                            <small class="gio-hang-do-can-mat-trai-box">
                                                                <span class="gio-hang-do-can-label">Left Eye: </span>
                                                                <span><?php echo (!empty($v['left_eye'])) ? $v['left_eye'] : "Not selected"; ?></span>
                                                            </small>
                                                            <small class="gio-hang-do-can-mat-right-box">
                                                                <span class="gio-hang-do-can-label">Right Eye: </span>
                                                                <span><?php echo (!empty($v['right_eye'])) ? $v['right_eye'] : "Not selected"; ?></span>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <!-- độ cận end -->

                                                    <?php /*
                                                <!-- màu và kích thước -->
                                                <div class="col-12">
                                                    <div class="ha-product-item-size-color">
                                                        <!-- màu  -->
                                                        <div class="ha-item-color me-1">
                                                            <?php
                                                            // lấy màu
                                                            $color = $d->simple_fetch("select mo_ta from #_sanpham_chitiet where id_sp = {$v['productid']} and id_thuoctinh = 10 and id = {$v['mau']}");
                                                            ?>
                                                            <div class="ha-item-color-bg" style="background-color: <?= $color['mo_ta'] ?>;"></div>
                                                        </div>
                                                        <!-- size -->
                                                        <?php
                                                        // lấy size
                                                        $size = $d->simple_fetch("select ten from #_sanpham_chitiet where id_sp = {$v['productid']} and id_thuoctinh = 5 and id = {$v['size']}");
                                                        ?>
                                                        <div class="ha-item-size">
                                                            - Size: <span class="ha-item-size-number"><?= $size['ten'] ?></span>
                                                        </div>
                                                    </div>
                                                </div> */ ?>

                                                    <!-- nút tăng giảm số lượng start -->

                                                    <div class="vh-btn-tang-giam-box d-flex align-items-center">
                                                        <div class="vh-btn-giam qty-button qty-down me-2">
                                                            <i class="fa-solid fa-minus"></i>
                                                        </div>
                                                        <input type="hidden" class="num_sl" data-pid="<?= $v['productid'] ?>" value="<?= $v['soluong'] ?>" data-code="<?= $v['code'] ?>">

                                                        <div class="vh-btn-tang qty-button qty-up">
                                                            <i class="fa-solid fa-plus"></i>
                                                        </div>
                                                    </div>

                                                    <!-- nút tăng giảm số lượng end -->
                                                </div>
                                            </div>
                                            <div class="col-3 ms-auto vh-gio-hang-item-price vh-gio-hang-item-price-key-<?= $v['code'] ?>">
                                                <?php
                                                // lấy giá tiền sản phẩm
                                                $price = $d->simple_fetch("select gia,khuyen_mai from #_sanpham where id_code = {$v['productid']}");
                                                $cost = 0;
                                                if ($price['khuyen_mai'] != 0) {
                                                    $cost = $price['khuyen_mai'];
                                                } else {
                                                    $cost = $price['gia'];
                                                }
                                                echo number_format($cost * $v['soluong']) . "<span class='vh-type-price'><sup>đ</sup></span>";
                                                $total_cost_no_fax = $total_cost_no_fax + ($cost * $v['soluong']);
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- item end -->
                                <?php }
                                ?>

                            </div>
                            <!-- giỏ hàng nội dung end -->

                            <!-- line -->
                            <div class="shipping-line my-4"></div>

                            <!-- mã giảm giá start -->
                            <?php /* <div class="vh-ma-giam-gia-box py-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="vh-ma-giam-gia-box-item">
                                        <div class="form-floating ">
                                            <input type="text" name="ma_giam_gia" class="form-control vh-mgg" id="floatingMaGiamGia" placeholder="name@example.com">
                                            <label for="floatingMaGiamGia">Mã giảm giá</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="vh-ma-giam-gia-box-item">
                                        <button type="button" class="btn vh-btn-ma-giam-gia">Sử dụng</button>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center vh-mgg-thong-bao pt-2">
                                Mã giảm giá sai hoặc hết hạn sử dụng
                            </div>
                        </div> */ ?>
                            <!-- mã giảm giá end -->

                            <!-- tổng thanh toán -->
                            <div class="ha-cart-total-price">
                                <table class="ha-cart-total-price-table">
                                    <tbody>
                                        <tr>
                                            <td>Tạm tính</td>
                                            <td class="ha-text-bold"><span id="vh-tam-tinh-price"><?= number_format($total_cost_no_fax) ?> <span class='vh-type-price'><sup>đ</sup></span></span></td>
                                        </tr>
                                        <!-- Thêm data-shipping để lưu giá trị phí vận chuyển -->
                                        <tr>
                                            <td>Shipping</td>
                                            <td class="cost-shipping" data-shipping="0">0 <span class='vh-type-price'><sup>đ</sup></span></td>
                                        </tr>
                                        <tr>
                                            <?php
                                            // tính tax
                                            $tax = (0 / 100) * $total_cost_no_fax;
                                            ?>
                                            <td>Thuế</td>
                                            <td class="ha-text-bold"><?= number_format($tax) ?> <span class='vh-type-price'><sup>đ</sup></span></td>
                                        </tr>
                                        <tr>
                                            <?php

                                            ?>
                                            <td>Mã giảm</td>
                                            <td class="ha-text-bold">
                                                <span id="vh-giam-gia">
                                                    <?= number_format($tax) ?> <span class='vh-type-price'><sup>đ</sup></span>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <?php
                                            // Tổng tiền
                                            $total_cost = $total_cost_no_fax + $tax;
                                            ?>
                                            <td class="ha-text-bold">Tổng tiền</td>
                                            <td class="ha-text-bold total-price-text" data-total="<?= $total_cost_no_fax ?>"><?= number_format($total_cost) ?> <span class='vh-type-price'><sup>đ</sup></span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!--  -->
                        </div>
                    </div>
                </div>
                <!-- thông tin giỏ hàng end -->
            </div>
        </div>
    </section>
<?php } else { ?>
    <section data-scroll-section>
        <div class="container">
            <div class="well text-center py-5" style="font-size: 18px;">
                <p><?= $d->gettxt(42) ?></p>
                <a href="<?= URLLANG ?>"><?= $d->gettxt(43) ?></a>
            </div>
        </div>

    </section>
<?php } ?>


<script type="text/javascript" src="http://ajax.aspnetcdn.com/ajax/jquery.validate/1.13.1/jquery.validate.min.js"></script>
<script>
    <?php if (isset($_SESSION['cart'])) { ?>
        <?php if (!$_SESSION['nhahang']['ma_sale']) { ?>

            // function check_sale() {
            //     var ma_sale = $('#ma_sale').val();
            //     $.ajax({
            //         url: "sources/ajax/ajax.php",
            //         type: "post",
            //         dataType: "text",
            //         data: {
            //             do: 'check_sale',
            //             ma_sale: ma_sale,
            //             tong_dong: <?= $tong ?>,
            //             phi_ship: $('#phi_ship').val()
            //         },
            //         success: function(result) {
            //             if (result === '0') {
            //                 window.location.href = "<?= URLPATH . $com ?>.html";
            //             } else {
            //                 swal({
            //                     title: '',
            //                     text: 'Mã khuyến mãi không hợp lệ',
            //                     icon: 'error',
            //                     button: false,
            //                     timer: 2000
            //                 });
            //             }
            //         }
            //     });
            // }
        <?php } ?>
        $(document).ready(function() {
            $("#form-diachi").validate({
                rules: {
                    ho_ten: "required",
                    dien_thoai: "required",
                    email: {
                        email: true
                    },
                    id_countries: "required",
                    id_states: "required",
                    id_cities: "required",
                    dia_chi: "required"
                },
                messages: {
                    ho_ten: "Vui lòng nhập họ tên",
                    dien_thoai: "Vui lòng nhập số diện thoại",
                    email: {
                        email: "Vui lòng nhập đúng định dạng mail"
                    },
                    id_countries: "Vui lòng chọn quốc gia",
                    id_states: "Vui lòng chọn tỉnh / thành phố",
                    id_cities: "Vui lòng chọn quận / huyện",
                    dia_chi: "Vui lòng nhập dịa chỉ"
                }
            });
            //Khi bàn phím được nhấn và thả ra thì sẽ chạy phương thức này
            $("#form-shopping").validate({
                rules: {
                    ten: "required",
                    email: {
                        email: true
                    },
                    dienthoai: "required",
                    diachi: "required"
                },
                messages: {
                    ten: "<?= $d->gettxt(44) ?>",
                    email: {
                        email: "<?= $d->gettxt(45) ?>"
                    },
                    dienthoai: "<?= $d->gettxt(46) ?>",
                    diachi: "<?= $d->gettxt(47) ?>",
                }
            });
        });
    <?php } ?>
</script>