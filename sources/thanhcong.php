<?php
// dd($row);
// var_dump($com);
// $row = $d->getCate(55);
$link = explode("?", $_SERVER['REQUEST_URI']);
$vari = explode("&", $link[1]);
$search = array();
foreach ($vari as $item) {
    $str = explode("=", $item);
    $search["$str[0]"] = $str[1];
}
if (isset($search['success']) and $search['success']) {
    $madonhang = validate_content($search['success']);
    $donhang = $d->simple_fetch("select * from #_dathang where ma_dh='$madonhang' ");
    $donhang_ct = $d->o_fet("select * from #_dathang_chitiet where ma_dh='$madonhang' ");
    if (isset($_POST['xacnhan'])) {
        $data['tinhtrang_donhang'] = 1;
        $d->reset();
        $d->setTable('#_dathang');
        $d->setWhere('id', $donhang['id']);
        if ($d->update($data)) {
            $d->location(URLLANG . $com . '.html?id=' . $madonhang);
        }
    }
    if (isset($_POST['huy'])) {
        $d->reset();
        $d->setTable('#_dathang');
        $d->setWhere('id', $donhang['id']);
        if ($d->delete()) {
            $d->location(URLLANG . $d->getCate(55)['alias'] . '.html');
        }
    }
} else {
    $d->location(URLLANG . "404.html");
}

?>
<?php include 'module/pt-breadcrumb.php'; ?>
<section class="wrapper-main">
    <div class="container">
        <div class="row">
            <div class="br-dathang" style="background-color: #f5f5f5;border-radius: 10px;border: 1px solid #eee;">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <h3 class="title-giohang" style="font-size: 25px; padding: 10px"><?= $d->gettxt(179) ?> - <?= $madonhang ?></h3>
                        <table class="table table-dathang-tc">
                            <tr>
                                <th><?= $d->gettxt(175) ?></th>
                                <th><?= $d->gettxt(176) ?></th>
                                <th><?= $d->gettxt(177) ?></th>
                                <th><?= $d->gettxt(178) ?></th>
                            </tr>
                            <?php
                            $thanhtien = 0;
                            foreach ($donhang_ct as $key => $value) {
                                $product = $d->getProduct($value['id_sp'], '*');
                                $thanhtien +=  ($value['so_luong'] * $value['gia_ban']);
                            ?>
                                <tr>
                                    <td class=" text-center">
                                        <a href="<?= Img($product['hinh_anh']) ?>" data-fancybox>
                                            <img class="vh-success-cart-product-img" src="<?= Img($product['hinh_anh']) ?>" alt="<?= $product['ten'] ?>" />
                                        </a>
                                    </td>
                                    <td style="font-size: 14px;">
                                        <b><?= $value['ten_sp'] ?></b>
                                        <p></p>

                                    </td>
                                    <td><?= $value['so_luong'] ?></td>
                                    <td>
                                        <?= format_gia($value['gia_ban'] * $value['so_luong'], "<sup>đ</sup>") ?>
                                    </td>
                                </tr>
                            <?php } ?>

                            <!-- phương thức vận chuyển start -->
                             <?php 
                                $ptvc = $d->simple_fetch("select * from #_phuong_thuc_van_chuyen where id = {$donhang['phuong_thuc_van_chuyen']}");
                             ?>
                            <tr>
                                <td colspan="3">Shipping:</td>
                                <td style="">
                                    <?= number_format($ptvc['gia']) ?> <sup>đ</sup>
                                </td>
                            </tr>
                            <!-- phương thức vận chuyển end -->

                            <tr style="font-weight: 600;font-size: 15px;">
                                <td colspan="3"><?= $d->gettxt(49) ?>:</td>
                                <td class="ak-total-price" colspan="3">
                                    <?php
                                    if ($row['lang'] == 'us') {
                                        // Nếu là tiếng anh thì $
                                    ?>
                                        <strong class="text-price">$ <?= format_gia($thanhtien + $ptvc['gia'], "") ?></strong>
                                    <?php  } else { ?>
                                        <strong class="text-price"><?= format_gia($thanhtien + $ptvc['gia'], "<sup>đ</sup>") ?></strong>
                                    <?php }
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-12 col-md-6">
                        <h4 style="padding: 10px;font-size: 25px; "><?= $d->gettxt(50) ?></h4>
                        <table class="table table-thongtin">
                            <tr>
                                <td><b><?= $d->gettxt(5) ?>:</b></td>
                                <td><?= $donhang['ho_ten'] ?></td>
                            </tr>
                            <tr>
                                <td><b><?= $d->gettxt(6) ?>:</b></td>
                                <td><?= $donhang['dien_thoai'] ?></td>
                            </tr>
                            <tr>
                                <td><b>Email:</b></td>
                                <td><?= $donhang['email'] ?></td>
                            </tr>
                            <tr>
                                <td><b><?= $d->gettxt(7) ?>:</b></td>
                                <td><?= $donhang['dia_chi'] ?></td>
                            </tr>
                            <!-- phương thức vận chuyển -->
                             <?php 
                                $pttt = $d->simple_fetch("select * from #_phuong_thuc_thanh_toan where id = {$donhang['phuong_thuc_thanh_toan']}")
                             ?>
                            <tr>
                                <td><b>Phương thức vận chuyển:</b></td>
                                <td><?= $ptvc['ten'] ?></td>
                            </tr>
                            <tr>
                                <td><b>Phương thức thanh toán:</b></td>
                                <td>
                                    <?= $pttt['ten'] ?>
                                </td>
                            </tr>
                            <!-- phương thức thanh toán end -->
                            <tr>
                                <td><b><?= $d->gettxt(174) ?>:</b></td>
                                <td><?= $donhang['loi_nhan'] ?></td>
                            </tr>
                            <?php /* <tr>
                                <td><b><?= $d->gettxt(51) ?>:</b></td>
                                <td><?= $donhang['thanh_toan'] ?></td>
                            </tr> */ ?>

                        </table>
                    </div>
                    <?php if ($donhang['tinhtrang_donhang'] == 0) { ?>
                        <form method="POST" action="" class="text-center">
                            <button class="btn btn-xacnhan" name="xacnhan"><?= $d->gettxt(52) ?></button>
                            <button class="btn btn-huy" name="huy"><?= $d->gettxt(53) ?></button>
                        </form>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    </div>