<?php
?>
<script src="templates/js/bootstrap.bundle.min.js"></script>
<script src="templates/js/jquery.matchHeight-min.js"></script>
<script src="templates/module/sweetalert/sweetalert2.min.js"></script>
<script type="text/javascript" src="templates/js/cart.js"></script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>





<script>
  $(document).ready(function() {
    // Lấy tất cả img trong .sanpham-details
    const imagesvh = $('.vanhiep-content img');
    imagesvh.each(function() {
      // Lấy đường dẫn hình ảnh
      let src = $(this).attr('src');
      // Tạo thẻ <a>
      let a = $('<a>');
      a.attr('data-fancybox', 'images');
      a.attr('href', src);

      // Chuyển img thành con của thẻ <a>
      $(this).wrap(a);

    });
    // Lấy tất cả img trong .sanpham-details
    const images = $('.vs-product-detail-content-box-text img');

    images.each(function() {
      // Lấy đường dẫn hình ảnh
      let src = $(this).attr('src');

      // Tạo thẻ <a>
      let a = $('<a>');
      a.attr('data-fancybox', 'images');
      a.attr('href', src);

      // Chuyển img thành con của thẻ <a>
      $(this).wrap(a);

    });


    // Lấy tất cả img trong .sanpham-details
    const imagesc = $('.vs-product-detail-content-card img');

    imagesc.each(function() {
      // Lấy đường dẫn hình ảnh
      let src = $(this).attr('src');

      // Tạo thẻ <a>
      let a = $('<a>');
      a.attr('data-fancybox', 'images');
      a.attr('href', src);

      // Chuyển img thành con của thẻ <a>
      $(this).wrap(a);

    });


    // Lấy tất cả img trong .tintuc-details
    const images_t = $('.tin-tuc-detail section img');

    images_t.each(function() {
      // Lấy đường dẫn hình ảnh
      let src = $(this).attr('src');

      // Tạo thẻ <a>
      let a = $('<a>');
      a.attr('data-fancybox', 'images');
      a.attr('href', src);

      // Chuyển img thành con của thẻ <a>
      $(this).wrap(a);

    });


  })
</script>






<!-- AOS -->
<script>
  AOS.init();
</script>
<!-- AOS END -->

<!-- Intersection Observer Lazy Load Ảnh -->
<script>
  window.addEventListener("DOMContentLoaded", function() {

    // Lazy load images
    const imagesToLazyLoad = document.querySelectorAll('img[data-src]');
    imagesToLazyLoad.forEach((element) => {
      const dataSrc = element.getAttribute('data-src');
      if (dataSrc != "") {
        element.setAttribute('src', '<?= URLPATH . "/img_data/no-image.png" ?>');
      }
    });

    // Lazy load videos
    const videosToLazyLoad = document.querySelectorAll('video[data-src]');
    videosToLazyLoad.forEach((element) => {
      element.setAttribute('poster', '<?= URLPATH . "/img_data/no-image.png" ?>');
    });

    // Lazy load background images
    const bgElementsToLazyLoad = document.querySelectorAll('[data-bg-src]');
    bgElementsToLazyLoad.forEach((element) => {
      element.style.backgroundImage = "url('<?= URLPATH . "/img_data/no-image.png" ?>')";
    });

    const options = {
      root: null, // Theo dõi trong toàn bộ viewport
      rootMargin: '1500px', // Điều chỉnh theo nhu cầu
      threshold: 0, // Bắt đầu khi một phần tử xuất hiện trong viewport
    };

    const lazyObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const lazyElement = entry.target;

          // Lazy load ảnh
          if (lazyElement.tagName === 'IMG') {
            lazyElement.src = lazyElement.dataset.src;
            lazyElement.removeAttribute('data-src');
          }

          // Lazy load video
          if (lazyElement.tagName === 'VIDEO') {
            lazyElement.src = lazyElement.dataset.src;
            lazyElement.removeAttribute('data-src');
          }

          // Lazy load background-image
          if (lazyElement.hasAttribute('data-bg-src')) {
            lazyElement.style.backgroundImage = `url(${lazyElement.dataset.bgSrc})`;
            lazyElement.removeAttribute('data-bg-src');
          }

          // Thêm lớp CSS "animate" để kích hoạt hiệu ứng (nếu có)
          lazyElement.classList.add('animate');
          lazyObserver.unobserve(lazyElement);
        }
      });
    }, options);

    imagesToLazyLoad.forEach((image) => {
      lazyObserver.observe(image);
    });

    videosToLazyLoad.forEach((video) => {
      lazyObserver.observe(video);
    });

    bgElementsToLazyLoad.forEach((bgElement) => {
      lazyObserver.observe(bgElement);
    });

  });
</script>





<!-- Backtotop -->
<script>
  $(document).ready(function() {
    // $('body').append('<div id="toTop"><i class="fa-solid fa-chevron-up"></i></div> ');
    // $(window).scroll(function() {
    //   if ($(this).scrollTop() > 100) {
    //     $('#toTop').fadeIn();
    //   } else {
    //     $('#toTop').fadeOut();
    //   }
    // });
    // $('#toTop').click(function() {
    //   $("html, body").animate({
    //     scrollTop: 0
    //   }, 600);
    //   return false;
    // });
  });
</script>


<script>
  $(function() {
    $('.col-text').matchHeight();
  });
</script>




<!-- Contact -->
<script>
  <?php if (isset($thongbao_tt) and $thongbao_tt != "") { ?>
    Swal.fire({
      title: '<?= $thongbao_tt ?>',
      text: '<?= $thongbao_content ?>',
      icon: '<?= $thongbao_icon ?>',
      button: false,
      timer: 2000
    }).then((value) => {
      window.location = "<?= $thongbao_url ?>";
    });
  <?php } ?>
</script>


<!-- Product detail -->

<script>
  function goBack() {
    window.history.back();
  }
</script>

<!-- Script Index -->

<?php if ($com == '') { ?>






  <!-- 
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const tabButtons = document.querySelectorAll(".nav-link");
      const tabContents = document.querySelectorAll(".tab-pane");
      let activeIndex = null; // Lưu trạng thái active index

      tabButtons.forEach((button, index) => {
        button.addEventListener("mouseenter", () => {
          removeActiveClasses();
          button.classList.add("active");
          tabContents[index].classList.add("show", "active");
          activeIndex = index; // Lưu index khi hover
        });

        button.addEventListener("mouseleave", () => {
          // Chỉ bỏ lớp active khỏi button, không bỏ khỏi tab-content
          button.classList.remove("active");
        });
      });



      function removeActiveClasses() {
        tabButtons.forEach(button => {
          button.classList.remove("active");
        });

        tabContents.forEach(content => {
          content.classList.remove("show", "active");
        });
      }
    });
  </script> -->




<?php } ?>