import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

export const initCruiseCarousel = () => {
  const carousels = document.querySelectorAll('.cruise-swiper');
  if (!carousels.length) return;

  carousels.forEach(el => {
    if (el.classList.contains('swiper-initialized')) return;

    const parent = el.closest('section');
    const prevBtn = parent.querySelector('.swiper-button-prev-custom');
    const nextBtn = parent.querySelector('.swiper-button-next-custom');
    const pagination = parent.querySelector('.swiper-pagination');

    new Swiper(el, {
      modules: [Navigation, Pagination, Autoplay],
      slidesPerView: 1.2,
      spaceBetween: 20,
      loop: false,
      autoplay: {
        delay: 5000,
        disableOnInteraction: true,
        pauseOnMouseEnter: true
      },
      pagination: {
        el: pagination,
        clickable: true,
        dynamicBullets: true
      },
      navigation: {
        nextEl: nextBtn,
        prevEl: prevBtn
      },
      breakpoints: {
        640:
          { slidesPerView: 2.2,
            spaceBetween: 24
          },
        768: {
          slidesPerView: 3.2,
          spaceBetween: 32
        },
        1280: {
          slidesPerView: 4.9,
          spaceBetween: 40,
          autoplay: false
        }
        },
      on: {
        init() {
          if (prevBtn) prevBtn.style.opacity = '1';
          if (nextBtn) nextBtn.style.opacity = '1';
        }
      },
    });
  });
};

if (document.querySelector('.cruise-swiper')) {
  initCruiseCarousel();
}

if (window.acf) {
  window.acf.addAction('render_block_preview/type=cruise-carousel', initCruiseCarousel);
  window.acf.addAction('ready', initCruiseCarousel); // pour les blocks déjà sur la page editor
}
