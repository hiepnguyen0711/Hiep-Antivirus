<?php

include '../../Mobile_Detect.php';
$detect = new Mobile_Detect;

$phone_number = _zalo;
$qr_code = 'abc123';

if ($detect->isAndroidOS()) {
    $url_zalo = "https://zaloapp.com/qr/p/" . _zalo_qr;
} else if ($detect->isiOS()) {
    $url_zalo = "zalo://qr/p/" . _zalo_qr;
} else {
    $url_zalo = "https://zalo.me/" . $phone_number;
}

// 
?>
<style>
    .btn-phone {
        position: fixed;
        left: auto;
        bottom: 100px;
        z-index: 99;
        right: 20px;
    }

    .btn-phone a svg {
        font-size: 24px;
        color: #fff;
    }

    .btn-phone a {
        background: #18bfe9;
        border-radius: 50%;
        box-shadow: -2px 0px 8px -3px black;
        display: block;
        line-height: 37px;
        text-align: center;
        width: 35px;
        height: 35px;
        font-size: 1rem;
        color: #fff;
    }

    .btn-phone a:after {
        content: '';
        display: block;
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        background-color: inherit;
        border-radius: inherit;
        -webkit-animation: pulse-animation 1.5s cubic-bezier(0.24, 0, 0.38, 1) infinite;
        animation: pulse-animation 1.5s cubic-bezier(0.24, 0, 0.38, 1) infinite;
        z-index: -1;
    }

    span.number-phone {
        position: absolute;
        background: #c01627;
        bottom: 0;
        left: 55%;
        font-size: 25px;
        color: #fff;
        font-weight: 700;
        padding: 0 15px 0 30px;
        z-index: -1;
        border-radius: 0 25px 25px 0;
        height: 81%;
        line-height: 37px;
        perspective: 1000px;
        transition: all 600ms cubic-bezier(0.04, 0.94, 0.21, 1.22);
        transform-origin: left;
        top: 6px;
        width: 164px;
        font-size: 20px;
    }

    .touch span.number-phone.no-hover,
    span.number-phone {
        transform: scaleX(0);
    }

    .btn-phone:hover span.number-phone,
    .btn-phone:focus span.number-phone {
        transform: scaleX(1);
    }

    @keyframes pulse-animation {
        0% {
            transform: scale(1);
            opacity: .6;
        }

        40% {
            transform: scale(1.3);
            opacity: .6;
        }

        100% {
            transform: scale(2);
            opacity: 0;
        }
    }

    .ctrlq.fb-button {
        z-index: 999;
        background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/PjwhRE9DVFlQRSBzdmcgIFBVQkxJQyAnLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4nICAnaHR0cDovL3d3dy53My5vcmcvR3JhcGhpY3MvU1ZHLzEuMS9EVEQvc3ZnMTEuZHRkJz48c3ZnIGVuYWJsZS1iYWNrZ3JvdW5kPSJuZXcgMCAwIDEyOCAxMjgiIGhlaWdodD0iMTI4cHgiIGlkPSJMYXllcl8xIiB2ZXJzaW9uPSIxLjEiIHZpZXdCb3g9IjAgMCAxMjggMTI4IiB3aWR0aD0iMTI4cHgiIHhtbDpzcGFjZT0icHJlc2VydmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiPjxnPjxyZWN0IGZpbGw9IiMwMDg0RkYiIGhlaWdodD0iMTI4IiB3aWR0aD0iMTI4Ii8+PC9nPjxwYXRoIGQ9Ik02NCwxNy41MzFjLTI1LjQwNSwwLTQ2LDE5LjI1OS00Niw0My4wMTVjMCwxMy41MTUsNi42NjUsMjUuNTc0LDE3LjA4OSwzMy40NnYxNi40NjIgIGwxNS42OTgtOC43MDdjNC4xODYsMS4xNzEsOC42MjEsMS44LDEzLjIxMywxLjhjMjUuNDA1LDAsNDYtMTkuMjU4LDQ2LTQzLjAxNUMxMTAsMzYuNzksODkuNDA1LDE3LjUzMSw2NCwxNy41MzF6IE02OC44NDUsNzUuMjE0ICBMNTYuOTQ3LDYyLjg1NUwzNC4wMzUsNzUuNTI0bDI1LjEyLTI2LjY1N2wxMS44OTgsMTIuMzU5bDIyLjkxLTEyLjY3TDY4Ljg0NSw3NS4yMTR6IiBmaWxsPSIjRkZGRkZGIiBpZD0iQnViYmxlX1NoYXBlIi8+PC9zdmc+) center no-repeat #0084ff;
        width: 40px;
        height: 40px;
        text-align: center;
        bottom: 90px;
        border: 0;
        outline: 0;
        border-radius: 60px;
        -webkit-border-radius: 60px;
        -moz-border-radius: 60px;
        -ms-border-radius: 60px;
        -o-border-radius: 60px;
        box-shadow: 0 1px 6px rgba(0, 0, 0, .06), 0 2px 32px rgba(0, 0, 0, .16);
        -webkit-transition: box-shadow .2s ease;
        background-size: 80%;
        transition: all .2s ease-in-out;
        position: fixed;
        left: 27px;
    }

    .ctrlq.fb-button:focus,
    .ctrlq.fb-button:hover {
        transform: scale(1.1);
        box-shadow: 0 2px 8px rgba(0, 0, 0, .09), 0 4px 40px rgba(0, 0, 0, .24)
    }
</style>
<?php /*
<!-- giỏ hàng start -->
<div class="btn-phone" style="bottom: 310px;">
    <a href="<?= URLLANG . $giohang_c['alias'] . ".html" ?>"><i class="fa-solid fa-bag-shopping"></i></a>
    <span class="bag-cart"><?= $num_cart ?></span>
</div>
<!-- giỏ hàng end -->
*/ ?>
<div class="btn-phone" style="bottom: 140px;">
    <a href="tel:<?= _hotline ?>" style="background: #e60808 !important;"><i class="fa fa-phone"></i></a>
</div>

<div class="btn-phone" style="bottom: 100px;">
    <a href="<?= $url_zalo ?>" rel="nofollow" style="background: #0069ef !important;">
        <svg style="margin-top: -3px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="25px" height="25px">
            <path fill="#2962ff" d="M15,36V6.827l-1.211-0.811C8.64,8.083,5,13.112,5,19v10c0,7.732,6.268,14,14,14h10	c4.722,0,8.883-2.348,11.417-5.931V36H15z" />
            <path fill="#eee" d="M29,5H19c-1.845,0-3.601,0.366-5.214,1.014C10.453,9.25,8,14.528,8,19	c0,6.771,0.936,10.735,3.712,14.607c0.216,0.301,0.357,0.653,0.376,1.022c0.043,0.835-0.129,2.365-1.634,3.742	c-0.162,0.148-0.059,0.419,0.16,0.428c0.942,0.041,2.843-0.014,4.797-0.877c0.557-0.246,1.191-0.203,1.729,0.083	C20.453,39.764,24.333,40,28,40c4.676,0,9.339-1.04,12.417-2.916C42.038,34.799,43,32.014,43,29V19C43,11.268,36.732,5,29,5z" />
            <path fill="#2962ff" d="M36.75,27C34.683,27,33,25.317,33,23.25s1.683-3.75,3.75-3.75s3.75,1.683,3.75,3.75	S38.817,27,36.75,27z M36.75,21c-1.24,0-2.25,1.01-2.25,2.25s1.01,2.25,2.25,2.25S39,24.49,39,23.25S37.99,21,36.75,21z" />
            <path fill="#2962ff" d="M31.5,27h-1c-0.276,0-0.5-0.224-0.5-0.5V18h1.5V27z" />
            <path fill="#2962ff" d="M27,19.75v0.519c-0.629-0.476-1.403-0.769-2.25-0.769c-2.067,0-3.75,1.683-3.75,3.75	S22.683,27,24.75,27c0.847,0,1.621-0.293,2.25-0.769V26.5c0,0.276,0.224,0.5,0.5,0.5h1v-7.25H27z M24.75,25.5	c-1.24,0-2.25-1.01-2.25-2.25S23.51,21,24.75,21S27,22.01,27,23.25S25.99,25.5,24.75,25.5z" />
            <path fill="#2962ff" d="M21.25,18h-8v1.5h5.321L13,26h0.026c-0.163,0.211-0.276,0.463-0.276,0.75V27h7.5	c0.276,0,0.5-0.224,0.5-0.5v-1h-5.321L21,19h-0.026c0.163-0.211,0.276-0.463,0.276-0.75V18z" />
        </svg>
    </a>
</div>

<div class="btn-phone" style="bottom: 60px;">
    <a href="<?= _messenger ?>" style="background: #fff !important;">
        <svg xmlns="http://www.w3.org/2000/svg" style="margin-top: -3px;" viewBox="0 0 48 48" width="25px" height="25px">
            <path fill="#448AFF" d="M24,4C13.5,4,5,12.1,5,22c0,5.2,2.3,9.8,6,13.1V44l7.8-4.7c1.6,0.4,3.4,0.7,5.2,0.7c10.5,0,19-8.1,19-18C43,12.1,34.5,4,24,4z" />
            <path fill="#FFF" d="M12 28L22 17 27 22 36 17 26 28 21 23z" />
        </svg>
    </a>
</div>

<?php  ?>