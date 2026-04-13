import { initImageCarousel } from './blocks/image-carousel.js'
import { initCruiseCarousel } from './blocks/cruise-carousel.js'
import AOS from 'aos'
import 'aos/dist/aos.css'

import.meta.glob(["../images/**", "../fonts/**"]);
import alpine from "alpinejs";
import "./components/cruise-filters";

Object.assign(window, { Alpine: alpine }).Alpine.start();

const blocks = {
  'cruise-swiper': () => import('./blocks/cruise-carousel').then(m => m.initCruiseCarousel),
  'image-carousel': () => import('./blocks/image-carousel').then(m => m.initImageCarousel),
};

AOS.init({
  duration: 800,
})


Object.entries(blocks).forEach(([selector, loader]) => {
  if (document.querySelector(`.${selector}`)) {
    loader().then(initFn => initFn());
  }

  if (window.acf) {
    loader().then(initFn => {
      window.acf.addAction(`render_block_preview/type=${selector}`, initFn);
      window.acf.addAction('ready', initFn);
    });
  }
});

document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener('click', function (e) {
    const target = document.querySelector(this.getAttribute('href'))
    if (target) {
      e.preventDefault()
      target.scrollIntoView({ behavior: 'smooth' })
    }
  })
})



