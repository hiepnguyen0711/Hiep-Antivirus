<?php
// Xử lý meta robots trực tiếp
if ($row['noindex'] == 1 or $category['noindex'] == 1) {
    $meta_robots = '<meta name="robots" content="noindex">';
} else {
    $meta_robots = '<meta name="robots" content="index, follow">';
}
echo $meta_robots;


$seo_title = $row['title'];
$seo_keyword = $row['keyword'];
$seo_description = $row['des'];
$favicon = "";

if (_banner_share != '') {
    $img_cn = URLPATH . "img_data/images/" . _banner_share;
} else {
    if ($row['hinh_anh'] != '') {
        $img_cn = URLPATH . "img_data/images/" . $row['hinh_anh'];
    } else {
        $img_cn = _logo;
    }
}
?>
<?php if ($row['noindex'] == 1 or $category['noindex'] == 1) { ?>
    <meta name="robots" content="noindex">
<?php } ?>
<title><?= $seo_title ?></title>
<meta name="keywords" content="<?= $seo_keyword ?>" />
<meta name="description" content="<?= $seo_description ?>" />
<link href="<?= $d->fullAddress() ?>" rel="canonical" />
<link href="<?= _favicon ?>" rel="shortcut icon" type="image/x-icon" />
<?php include 'sitemap/seo_head.inc'; ?>
<!-- Twitter Card -->
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= $seo_title ?>">
<meta name="twitter:site" content="@<?= _url_page ?>">
<meta name="twitter:description" content="<?= $seo_description ?>">
<meta name="twitter:image" content="<?= $img_cn ?>">
<meta name="twitter:image:alt" content="<?= $seo_title ?>">
<!-- Open Graph -->
<?php if ($type == 'article') { ?>
    <meta property="og:type" content="article">
    <meta property="article:published_time" content="<?= date("c", $row['ngay_dang']) ?>">
    <meta property="article:modified_time" content="<?= date("c", $row['cap_nhat']) ?>">
    <meta property="article:section" content="<?= $category['ten'] ?>">
    <?php if ($row['tags_hienthi'] != '') {
        $arr_tag = explode(',', $row['tags_hienthi']);
        for ($i = 0; $i < count($arr_tag); $i++) {
            if (trim($arr_tag['i']) != '') {
    ?>
                <meta property="article:tag" content="<?= $arr_tag['i'] ?>">
    <?php }
        }
    } ?>
<?php } elseif ($type == 'product') { ?>
    <meta property="og:type" content="product">
    <meta property="product:plural_title" content="">
    <meta property="product:price.amount" content="<?= $row['gia'] ?>">
    <meta property="product:price.currency" content="<?= get_json('lang', '0', 'price') ?>">
<?php } else { ?>
    <meta property="og:type" content="website">
<?php } ?>
<meta property="og:url" content="<?= _url_page ?>" />
<meta property="og:title" content="<?= $seo_title ?>" />
<meta property="og:image" content="<?= $img_cn ?>" />
<meta property="og:site_name" content="<?= ucfirst($_SERVER['SERVER_NAME']) ?>" />
<meta property="og:description" content="<?= $seo_description ?>" />
<meta property="fb:page_id" content="<?= _messenger ?>" />
<!-- Khai báo ngôn ngữ -->
<?php if (count(get_json('lang')) > 1) {
    if ($com != '') {
        $link_lang = $row['alias'] . '.html';
    } else {
        $link_lang = '';
    }
?>
    <?php foreach (get_json('lang') as $key => $value) { ?>
        <link rel="alternate" hreflang="<?= $value['code'] ?>" href="<?= URLPATH . $value['code'] ?>/<?= $link_lang ?>" />
    <?php } ?>
    <link rel="alternate" hreflang="x-default" href="<?= URLPATH . LANG ?>/<?= $link_lang ?>" />
<?php } ?>
<script type="application/ld+json">
    {
        "@context": "http://schema.org",
        "@type": "Organization",
        "legalname": "<?= _ten_cong_ty ?>",
        "url": "<?= _web_page ?>",
        "contactPoint": [{
            "@type": "ContactPoint",
            "telephone": "+84<?= _hotline ?>",
            "contactType": "customer service",
            "contactOption": "TollFree",
            "areaServed": "VN"
        }],
        "logo": "<?= _logo ?>"
    }
</script>
<?php
if (!empty($row['ten'])) {
?>
    <h1 style="display: none; position: absolute; top: 0; left: 0; height: 0;"><?= $row['ten'] ?></h1>
<?php } ?>