<?php

?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="templates/css/bootstrap.min.css">
<link rel="stylesheet" href="templates/css/hover-min.css" />
<!-- <link rel="stylesheet" href="templates/css/locomotive-scroll.css" /> -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="templates/css/style.css?ver=1">
<link rel="stylesheet" href="templates/css/style-scaexpress.css?ver=1">
<link rel="stylesheet" href="templates/css/media.css?ver=1">
<link rel="stylesheet" href="templates/css/limit.css">


<!-- fui toast -->
<link rel="stylesheet" type="text/css" href="templates/css/fuiToast.min.css">
<script type="text/javascript" src="templates/js/fuiToast.min.js"></script>



<link rel="stylesheet" href="templates/module/sweetalert/sweetalert2.min.css">
<link rel="stylesheet" href="assets/css/nice-select.css">
<!-- <link rel="stylesheet" href="templates/css/jquery.fancybox.min.css" /> -->
<link rel="stylesheet" href="templates/module/swiper/swiper.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
<script src="templates/js/jquery.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" integrity="sha512-1cK78a1o+ht2JcaW6g8OXYwqpev9+6GqOkz9xmBN9iUUhIndKtxwILGWYOSibOKjLsEdjyjZvYDq/cZwNeak0w==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js" integrity="sha512-A7AYk1fGKX6S2SsHywmPkrnzTZHrgiVT7GcQkLGDe2ev0aWb8zejytzS8wjo7PGEXKqJOrjQ4oORtnimIRZBtw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<!-- <script src="https://cdn.jsdelivr.net/npm/locomotive-scroll@beta/bundled/locomotive-scroll.min.js"></script> -->
<!-- <script src="templates/js/locomotive-scroll.min.js"></script> -->

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- fancybox -->

<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />

<!-- gsap -->
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>
<!-- Lenis -->
<link rel="stylesheet" href="https://unpkg.com/lenis@1.1.14/dist/lenis.css">
<!-- Thêm Lenis từ CDN -->
<script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.4/bundled/lenis.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/split-type@0.3.4/umd/index.min.js"></script>


<!-- văn hiệp skeleton -->
<link rel="stylesheet" href="templates/css/vh-skeleton.css">
<script src="templates/js/vh-skeleton.js"></script>

<script src="templates/module/swiper/swiper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>




<script>
  let lenis; // Khai báo biến lenis toàn cục
  let destroyTimeout; // Biến lưu timeout để kiểm tra thời gian hover
 
  // Hàm khởi tạo Lenis
  function initLenis() {
    lenis = new Lenis({
      duration: 1.1, // Thời gian cuộn mượt
      easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)), // Easing mượt mà
      smoothWheel: true, // Kích hoạt cuộn mượt với chuột
      smoothTouch: false, // Không mượt trên cảm ứng
    });

    // Vòng lặp cập nhật Lenis
    function raf(time) {
      lenis.raf(time);
      requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);

    // lenis scroll bar start 

    $(document).ready(function() {
      const scrollbar = $('.lenis-scrollbar');
      let isScrolling;

      $(window).on('scroll', function() {
        scrollbar.addClass('visible');

        clearTimeout(isScrolling);
        isScrolling = setTimeout(function() {
          scrollbar.removeClass('visible');
        }, 1000);
      });

      scrollbar.hover(
        function() {
          scrollbar.addClass('visible');
        },
        function() {
          isScrolling = setTimeout(function() {
            scrollbar.removeClass('visible');
          }, 1000);
        }
      );
    });

    const scrollbar = document.querySelector('.lenis-scrollbar');
    const scrollbarThumb = document.querySelector('.lenis-scrollbar-thumb');

    lenis.on('scroll', ({
      scroll,
      limit
    }) => {
      const scrollRatio = scroll / limit;
      const thumbPosition = scrollRatio * (scrollbar.clientHeight - scrollbarThumb.clientHeight);

      scrollbarThumb.style.transform = `translateY(${thumbPosition}px)`;
    });

    // Drag functionality for scrollbar thumb
    let isDragging = false;
    let startY = 0;
    let startScroll = 0;

    scrollbarThumb.addEventListener('mousedown', (e) => {
      isDragging = true;
      startY = e.clientY;
      startScroll = lenis.scroll;

      document.body.style.userSelect = 'none'; // Disable text selection
    });

    document.addEventListener('mousemove', (e) => {
      if (!isDragging) return;

      const deltaY = e.clientY - startY;
      const scrollAmount = deltaY * (lenis.limit / (scrollbar.clientHeight - scrollbarThumb.clientHeight));

      lenis.scrollTo(startScroll + scrollAmount, {
        immediate: true
      });
    });

    document.addEventListener('mouseup', () => {
      if (isDragging) {
        isDragging = false;
        document.body.style.userSelect = ''; // Re-enable text selection
      }
    });

    // lenis scroll bar end

    // Đồng bộ GSAP với Lenis
    gsap.registerPlugin(ScrollTrigger);
    lenis.on('scroll', ScrollTrigger.update);

    ScrollTrigger.scrollerProxy(document.body, {
      scrollTop(value) {
        return arguments.length ? lenis.scrollTo(value) : lenis.scroll;
      },
      getBoundingClientRect() {
        return {
          top: 0,
          left: 0,
          width: window.innerWidth,
          height: window.innerHeight,
        };
      },
    });

    console.log('Lenis initialized');
  }


  document.addEventListener("DOMContentLoaded", function() {
    // Gọi hàm khởi tạo Lenis lần đầu tiên
    initLenis();
    Fancybox.bind("[data-fancybox]", {
      // Your custom options
    });

    // Khởi tạo skeletons
    initializeVHSkeleton();
    initializeSkeletonOnScroll();

    // Kiểm tra vị trí cuộn và hiển thị hoặc ẩn nút "Back to Top"
    $('body').append('<div id="toTop"><i class="fa-solid fa-chevron-up"></i></div> ');
    $('#toTop').fadeOut();
    $(window).scroll(function() {
      if ($(this).scrollTop() > 500) {
        $('#toTop').fadeIn();
      } else {
        $('#toTop').fadeOut();
      }
    });
    // Handle "Back to Top" button click
    $('#toTop').click(function() {
      scrollToElement('body', 1.5);
      return false;
    });



    // Lấy tất cả các phần tử có class ".vh-data-scroll"
    if (window.innerWidth > 1024) {
      const parallaxItems = gsap.utils.toArray('.vh-data-scroll');

      // Lặp qua từng phần tử để áp dụng hiệu ứng GSAP
      parallaxItems.forEach((item) => {
        const scrollSpeed = parseFloat(item.getAttribute('data-scroll-speed')) || 1; // Lấy giá trị data-scroll-speed

        // Áp dụng hiệu ứng parallax với GSAP
        gsap.fromTo(
          item, {
            y: `${scrollSpeed * 50}px`
          }, // Trạng thái ban đầu
          {
            y: `-${scrollSpeed * 70}px`, // Dịch chuyển theo tốc độ
            ease: 'power1.out', // Hiệu ứng easing nhẹ nhàng
            scrollTrigger: {
              trigger: ".maan-album-hot-section", // Phần tử kích hoạt
              start: "top 60%", // Bắt đầu khi phần tử chạm đáy khung nhìn
              end: "bottom top", // Kết thúc khi rời khỏi khung nhìn
              scrub: 5, // Điều chỉnh theo cuộn trang
              // markers: true, // Marker để debug (tắt nếu không cần)
            },
          }
        );
      });
    }

    // Xử lý hover trên phần tử .ql-editor
    $(document).on('mouseenter', '.vh-scrollbar', function() {
      // Khi hover vào, hủy bỏ Lenis scroll
      if (typeof lenis !== 'undefined' && lenis) {
        lenis.destroy();
        console.log('Lenis destroyed on hover');
      }
    });
    $(document).on('mouseleave', '.vh-scrollbar', function() {
      // Khi mouseout, khởi tạo lại Lenis scroll
      if (typeof initLenis === 'function') {
        initLenis();
        console.log('Lenis re-initialized on mouseout');
      }
    });



  });

  function scrollToElement(selector, duration = 3.0) {
    const isMobile = window.matchMedia("(max-width: 768px)").matches; // Kiểm tra nếu là thiết bị di động hoặc tablet
    const element = document.querySelector(selector);

    if (!element) {
      console.warn(`Phần tử với selector "${selector}" không tồn tại.`);
      return;
    }

    if (isMobile) {
      // Cuộn bằng cách sử dụng scrollTo cho mobile và tablet
      const offsetTop = element.offsetTop;
      window.scrollTo({
        top: offsetTop,
        behavior: 'smooth'
      });
    } else {
      // Cuộn bằng cách sử dụng Lenis cho PC, Laptop
      if (typeof lenis !== 'undefined') {
        lenis.scrollTo(selector, {
          duration: duration
        });
      } else {
        console.warn('Lenis chưa được khởi tạo.');
      }
    }
  }
</script>