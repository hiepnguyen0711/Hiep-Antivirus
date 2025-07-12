<?php
$list_video = $d->o_fet("select * from #_video where hien_thi = 1 and id_loai = '" . $row['id_code'] . "' " . $where_lang . "");
?>

<div class="container py-5">
    <div class="row">
        <?php foreach ($list_video as $key => $value) { ?>
            <!-- gallery item -->
            <div class="col-6 col-md-4 items album_<?= $value['id_album'] ?> position-relative mb-4">
                <?php if ($value['video'] != '') { ?>
                    <div class="item-img wow fadeInUp" data-wow-delay=".2s">
                        <a data-fancybox href="#video<?= $value['id'] ?>">
                            <img src="<?= getImageYoutube("https://www.youtube.com/watch?v=" . $value['ma_video']) ?>" alt="image">
                            <span class="fas fa-play" style="position: absolute;position: absolute;top: 50%;left: 50%;transform: translate(-50%, -50%);z-index: 100;color: #24c2ea;width: 30px;height: 30px;background-color: #060606a6;border-radius: 50%;text-align: center;padding: 15px;color: #fff;box-shadow: 1px 1px 10px 1px #fff;"></span>
                        </a>
                    </div>
                    <div class="cont">
                        <h6><?= $value['ten'] ?></h6>
                    </div>
                    <video width="90%" height="90%" controls id="video<?= $value['id'] ?>" style="display:none;">
                        <source src="img_data/images/<?= $value['video'] ?>" type="video/mp4">
                    </video>
                <?php } elseif ($value['ma_video'] != '') { ?>
                    <div class="item-img mb-3 wow fadeInUp" data-wow-delay=".2s">
                        <a data-fancybox href="https://www.youtube.com/watch?v=<?= $value['ma_video'] ?>">
                            <img src="<?= getImageYoutube("https://www.youtube.com/watch?v=" . $value['ma_video']) ?>" alt="image">
                            <span class="fas fa-play" style="position: absolute;position: absolute;top: 50%;left: 50%;transform: translate(-50%, -50%);z-index: 100;color: #24c2ea;width: 30px;height: 30px;background-color: #060606a6;border-radius: 50%;text-align: center;padding: 15px;color: #fff;box-shadow: 1px 1px 10px 1px #fff;"></span>
                        </a>
                    </div>
                    <h6 class="text-center"><?= $value['ten'] ?></h6>
                <?php } else { ?>
                    <div class="item-img wow fadeInUp" data-wow-delay=".2s">
                        <a href="img_data/images/<?= $value['hinh_anh'] ?>" data-fancybox="images">
                            <img src="img_data/images/<?= $value['hinh_anh'] ?>" alt="image">
                        </a>
                    </div>
                    <div class="cont">
                        <h6><?= $value['ten'] ?></h6>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>