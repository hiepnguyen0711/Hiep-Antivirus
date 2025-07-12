<div class="clearfix"></div>
<?php 
function getphantram($number, $tong){
    if($tong>0){
        $phantram = round($number*100/$tong);
        return $phantram;
    }else{
        return 0;
    }
    
}
    if(isset($_POST['guibinhluan'])){
        $data['danh_gia']   =   addslashes($_POST['diem']);
        $data['tieu_de']    =   addslashes($_POST['tieude']);
        $data['noi_dung']   =   addslashes($_POST['noi_dung']);
        $data['id_user']    =   $_SESSION['id_login'];
        $data['ho_ten']     =   addslashes($_POST['ho_ten']);
        $data['email']      =   addslashes($_POST['email']);
        //$data['dien_thoai'] =   $thanhvien['dien_thoai'];
        $data['ngay']       =   date('Y-m-d H:i:s');
        $data['id_sanpham'] =   $row['id_code'];
        $data['parent']     =   addslashes($_POST['parent']);
        $d->reset();
        $d->setTable('#_binhluan');
        if($id = $d->insert($data)) {
            $thongbao_tt        =   '';
            $thongbao_icon      =   'success';
            $thongbao_content   =   'Cảm ơn bạn đã đánh giá sản phẩm';
            $thongbao_url       =  $url_page;
        }
    }
    $list_bl = $d->o_fet("select * from #_binhluan where id_sanpham =".(int)$row['id_code']." and trang_thai = 1 and parent=0 order by id DESC ");
    $count_bl = $d->num_rows("select * from #_binhluan where id_sanpham =".(int)$row['id_code']." and trang_thai = 1 and parent=0 and danh_gia > 0 order by id DESC ");
    $tongsao = $d->simple_fetch("select sum(danh_gia) as tong from #_binhluan where id_sanpham =".(int)$row['id_code']." and trang_thai = 1 and parent=0 and danh_gia > 0 order by id DESC ");
    if($count_bl>0){
        $sao_trung_binh = $tongsao['tong']/$count_bl;
    }else{
        $sao_trung_binh = 0;
    }
    
    $namsao = $d->num_rows("select id from #_binhluan where id_sanpham  =   ".(int)$row['id_code']." and trang_thai = 1 and parent=0 and danh_gia=5 ");
    $bonsao = $d->num_rows("select id from #_binhluan where id_sanpham  =   ".(int)$row['id_code']." and trang_thai = 1 and parent=0 and danh_gia=4 ");
    $basao = $d->num_rows("select id from #_binhluan where id_sanpham   =   ".(int)$row['id_code']." and trang_thai = 1 and parent=0 and danh_gia=3 ");
    $haisao = $d->num_rows("select id from #_binhluan where id_sanpham  =   ".(int)$row['id_code']." and trang_thai = 1 and parent=0 and danh_gia=2 ");
    $motsao = $d->num_rows("select id from #_binhluan where id_sanpham  =   ".(int)$row['id_code']." and trang_thai = 1 and parent=0 and danh_gia=1 ");
?>
<link href="<?=URLPATH?>assets/css/binh-luan-style.css" rel="stylesheet" />
<div class="danh-gia" id="nhanxet">
    <div class="row m-10">
        <div class="col-md-3 col-sm-5 p10">
            <div class="rating-medium">
                <div class="title-medium-score">Đánh Giá Trung Bình</div>
                <div class="score-rating"><?=  round( $sao_trung_binh, 1, PHP_ROUND_HALF_UP)?>/5</div>
                <div class="quantity-star">
                    <?php for($i=0;$i<$sao_trung_binh;$i++){ ?>
                     <i class="fas fa-star"></i>
                    <?php }?>
                    <?php for($i=0;$i< 5-$sao_trung_binh;$i++){ ?>
                    <i class="far fa-star"></i>
                    <?php }?>
                </div>
                <div class="quantity-comment">
                    (<span class="total-review"><?= count($list_bl)?></span> nhận xét)
                </div>
            </div>
        </div>
        <?php 
        
        ?>
        <div class="col-md-6 col-sm-8 p10">
            <div class="progress-rating">
            <div class="item-progress">
                <span class="rating-num">5 <i class="fa fa-star"></i></span>
                <div class="box-progress">
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: <?=  getphantram($namsao, count($list_bl))?>%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>                                        
                    </div>                                    
                </div>
                <span class="rating-num-total"><?=  getphantram($namsao, $count_bl)?>%</span>
                <div class="clearfix"></div>
            </div>
                <div class="item-progress">
                    <span class="rating-num">4 <i class="fa fa-star"></i></span>
                    <div class="box-progress">
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: <?=  getphantram($bonsao, count($list_bl))?>%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>                                            
                        </div>                                        
                    </div>
                    <span class="rating-num-total"><?=  getphantram($bonsao, $count_bl)?>%</span>
                    <div class="clearfix"></div>
                </div>
                <div class="item-progress">
                    <span class="rating-num">3 <i class="fa fa-star"></i></span>
                    <div class="box-progress">
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: <?=  getphantram($basao, count($list_bl))?>%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>         
                        </div>                                        
                    </div>
                    <span class="rating-num-total"><?=  getphantram($basao, $count_bl)?>%</span>
                    <div class="clearfix"></div>
                </div>
                <div class="item-progress">
                    <span class="rating-num">2 <i class="fa fa-star"></i></span>
                    <div class="box-progress">
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: <?=  getphantram($haisao, count($list_bl))?>%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div> 
                        </div>                                        
                    </div>
                    <span class="rating-num-total"><?=  getphantram($haisao, $count_bl)?>%</span>
                    <div class="clearfix"></div>
                </div>
                <div class="item-progress">
                    <span class="rating-num">1 <i class="fa fa-star"></i></span>
                    <div class="box-progress">
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width:<?=  getphantram($motsao, count($list_bl))?>%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <span class="rating-num-total"><?=  getphantram($motsao, $count_bl)?>%</span>
                    <div class="clearfix"></div>
                </div>
           </div>
        </div>
        <div class="col-md-3 col-sm-12 p10 text-center">
            <p>Chia sẻ nhận xét về sản phẩm</p>
            <button onclick="$('#main-nx').slideToggle();" type="button" class="btn btn-nhanxet">Viết nhận xét của bạn</button>
        </div>
    </div>
    <div class="content-nhanxet" id="main-nx">
        <form method="POST" action="">
            <p style="font-weight: 400;font-size: 16px;">Đánh giá cả bạn về sản phẩm này</p>
            <div id="cate-rating" class="cate-rating">
                <div class="stars">
                    <a id="star-1" data="1" class="star vote-active" ><i class="fas fa-star"></i></a>
                    <a id="star-2" data="2" class="star vote-active"><i class="fas fa-star"></i></a>
                    <a id="star-3" data="3" class="star vote-active"><i class="fas fa-star"></i></a>
                    <a id="star-4" data="4" class="star vote-active"><i class="fas fa-star"></i></a>
                    <a id="star-5" data="5" class="star vote-active"><i class="fas fa-star"></i></a>
                </div>
                <div class="clearfix"></div>
                <input name="diem" type="hidden" value="5" id="diem" />
            </div>
            <?php if(isset($_SESSION['id_login']) and $_SESSION['id_login'] !=""){?>
            <input type="hidden" name="ho_ten" value="<?=$user_login['ho_ten']?>" placeholder="Nhập họ tên" class="form-control" />
            <input type="hidden" required name="email" value="<?=$user_login['email']?>" placeholder="Nhập email" class="form-control" />
            <?php }else{?>
            <div class="row">
                <div class="form-group col-sm-6">
                    <p>Họ tên</p>
                    <input type="text" name="ho_ten" value="<?=$user_login['ho_ten']?>" placeholder="Nhập họ tên" class="form-control" />
                </div>
                <div class="form-group col-sm-6">
                    <p>Email</p>
                    <input type="enail" required name="email" value="<?=$user_login['email']?>" placeholder="Nhập email" class="form-control" />
                </div>
            </div>
            <?php }?>
            <p style="font-weight: 400;font-size: 16px;">Viết nhận xét của bạn vào bên dưới</p>
            <div class="f form-group">
                <textarea class="form-control" rows="5" name="noi_dung" placeholder="Nhận xét của bạn về sản phẩm này"></textarea>
            </div>
            <button type="submit" name="guibinhluan" class="btn btn-guibl">Gửi nhận xét</button>
        </form>
    </div>
    
    <div class="list-binhluan">
       
        <?php foreach ($list_bl as $key => $value) {?>
        <div class="item-binhluan">
            <div class="row">
                <div class="col-sm-3">
                    <span class="img_user">
                        <img src="<?=URLPATH?>templates/images/user.svg" />
                    </span>
                    <p class="name-user"><?=$value['ho_ten']?></p>
                    <span class="ngaydang"><?=  getthoigiandang($value['ngay'])?></span>
                </div>
                <div class="col-sm-9" id="accordionExample">
                    <div class="chitiet-bl">
                        <?php if($value['danh_gia']>0){ ?>
                        <div class="quantity-star">
                            <?php for($i=0;$i<$value['danh_gia'];$i++){ ?>
                            <i class="fa fa-star" aria-hidden="true"></i>
                            <?php }?>
                            <?php for($i=0;$i< 5-$value['danh_gia'];$i++){ ?>
                            <i class="fa fa-star-o" aria-hidden="true"></i>
                            <?php }?>
                        </div>
                        <?php }?>
                        <h5 class="titlede-bl"><?=$value['tieu_de']?></h5>
                        <p><?=$value['noi_dung']?></p>
                    </div>
                    <a class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#collapse<?=$value['id']?>" aria-expanded="false" aria-controls="collapse<?=$value['id']?>" href="#"> <i class="fa fa-comments-o" aria-hidden="true"></i>Trả lời bình luận</a>
                    <?php $binhluan_tl= $d->o_fet("select * from #_binhluan where parent = ".$value['id']." and trang_thai=1 order by id ASC "); ?>
                    <?php if(count($binhluan_tl)>0){ ?>
                    <div class="list-traloi">
                        <?php foreach ($binhluan_tl as $key => $item) {?>
                        <div class="item-traloi">
                            <b><?= $item['ho_ten']=='' ? 'Admin' : $item['ho_ten'] ?></b><span><?=  getthoigiandang($item['ngay'])?></span>
                            <p><?=$item['noi_dung']?></p>
                        </div>       
                        <?php } ?>
                    </div>
                    <?php }?>
                    <div  id="collapse<?=$value['id']?>" class="accordion-collapse collapse panel-traloi" aria-labelledby="heading<?=$value['id']?>" data-bs-parent="#accordionExample">
                        <form method="POST" action="">
                            <input type="hidden" name="parent" value="<?=$value['id']?>" />
                            <?php if(isset($_SESSION['id_login']) and $_SESSION['id_login'] !=""){
                            ?>
                            <input type="hidden" name="ho_ten" value="<?=$user_login['ho_ten']?>" placeholder="Nhập họ tên" class="form-control" />
                            <input type="hidden" required name="email" value="<?=$user_login['email']?>" placeholder="Nhập email" class="form-control" />
                            <?php }else{?>
                            <div class="row">
                                <div class="form-group col-sm-6">
                                    <input type="text" name="ho_ten" placeholder="Nhập họ tên" class="form-control" />
                                </div>
                                <div class="form-group col-sm-6">
                                    <input type="enail" required name="email" placeholder="Nhập email" class="form-control" />
                                </div>
                            </div>
                            <?php }?>
                            <div class="form-group">
                                <textarea class="form-control" rows="5" name="noi_dung" placeholder="Trả lời nhận xét này"></textarea>
                            </div>
                            <button type="submit" name="guibinhluan" class="btn btn-guibl">Gửi nhận xét</button>
                            <a class="btn" data-bs-toggle="collapse" data-bs-target="#collapse<?=$value['id']?>" aria-expanded="false" aria-controls="collapse<?=$value['id']?>">Hủy bỏ</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
        
        
    </div>
</div>

<script>
    $(document).ready(function() {
        /*
         * Hiệu ứng khi rê chuột lên ngôi sao
         */
        $('a.star').mouseenter(function() {
            if ($('#cate-rating').hasClass('rating-ok') == false) {
                var eID = $(this).attr('id');
                eID = eID.split('-').splice(-1);
                $('a.star').removeClass('vote-active');
                for (var i = 1; i <= eID; i++) {
                    $('#star-' + i).addClass('vote-hover');
                }
            }
        }).mouseleave(function() {
            if ($('#cate-rating').hasClass('rating-ok') == false) {
                $('a.star').removeClass('vote-hover');
            }
        });

        /*
         * Sự kiện khi cho điểm
         */
        $('a.star').click(function() {
            var num = $(this).attr('data');
            $('#diem').val(num);
            if ($('#cate-rating').hasClass('rating-ok') == false) {
				$('a.star').removeClass('vote-hover');
                var eID = $(this).attr('id');
                eID = eID.split('-').splice(-1).toString();
                for (var i = 1; i <= eID; i++) {
                    $('#star-' + i).addClass('vote-active');
					$('#star-' + i).addClass('vote-hover');
                }
                //$('p#vote-desc').html('<span class="blue">' + eID + ' (' + eID * 10 + '%)</span> &middot; ' + 1 + ' đánh giá');
                //$('#cate-rating').addClass('rating-ok');
            }
        });
    });
</script>
<script>
    <?php if(isset($thongbao_icon) and $thongbao_icon!=""){ ?>
    swal({
        title: '<?=$thongbao_tt ?>',
        text: '<?= $thongbao_content ?>',
        icon: '<?= $thongbao_icon ?>',
        button: false,
        timer: 2000
    }).then((value) => {
        window.location="<?=$thongbao_url?>";
    }); 
    <?php }?> 
</script>