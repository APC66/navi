import.meta.glob(["../images/**", "../fonts/**"]);
import alpine from "alpinejs";
import "./components/cruise-filters";

Object.assign(window, { Alpine: alpine }).Alpine.start();

// document.querySelectorAll('[data-js="reviews-carousel"]').forEach(el => {
//   import('./components/reviews-carousel').then(module => {
//     const initCarousel = module.init;
//     initCarousel(el);
//   });
// });


const blocks = {
  'cruise-swiper': () => import('./blocks/cruise-carousel').then(m => m.initCruiseCarousel),
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


