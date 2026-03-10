import { initImageCarousel } from './blocks/image-carousel.js'
import { initCruiseCarousel } from './blocks/cruise-carousel.js'

import.meta.glob(["../images/**", "../fonts/**"]);
import alpine from "alpinejs";
import "./components/cruise-filters";

Object.assign(window, { Alpine: alpine }).Alpine.start();

const blocks = {
  'cruise-swiper': () => import('./blocks/cruise-carousel').then(m => m.initCruiseCarousel),
  'image-carousel': () => import('./blocks/image-carousel').then(m => m.initImageCarousel),
};


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


