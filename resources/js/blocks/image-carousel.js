import Swiper from 'swiper';
import { Pagination, Autoplay } from 'swiper/modules';

import 'swiper/css';
import 'swiper/css/pagination';

// 1. Import du module Material You (ESM) de UI Initiative
import EffectMaterial from '../vendor/effect-material.esm.js';
import '../vendor/effect-material.css';

export const initImageCarousel = () => {
  const carousels = document.querySelectorAll('.image-carousel-swiper');
  if (!carousels.length) return;

  carousels.forEach(el => {
    if (el.classList.contains('swiper-initialized')) return;

    const parent = el.closest('section');
    if (!parent) return;

    const pagination = parent.querySelector('.swiper-pagination');

    new Swiper(el, {
      modules: [Pagination, Autoplay, EffectMaterial],

      effect: 'material',
      materialEffect: {
        slideSplitRatio: 0.25,
      },
      autoplay:true,
      spaceBetween:20,
      slidesPerView: 1.6,
      centeredSlides: true,
      loop: true,
      speed: 800,
      grabCursor: true,
      pagination: {
        el: pagination,
        clickable: true,
      },
    });
  });
};

if (document.querySelector('.image-carousel-swiper')) {
  initImageCarousel();
}

if (window.acf) {
  window.acf.addAction('render_block_preview/type=image-carousel', initImageCarousel);
  window.acf.addAction('ready', initImageCarousel);
}
