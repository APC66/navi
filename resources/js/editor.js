import domReady from '@wordpress/dom-ready';
import { registerBlockType } from "@wordpress/blocks";
import * as heroVideoBlock from "./editor/hero-video.block";
import * as modalBlock from "./editor/modal.block";
import * as latestSeedsBlock from "./editor/latest-seeds.block";
domReady(() => {
  /**
   * Register blocks with their configurations
   */
  const blocks = [
    modalBlock,
    latestSeedsBlock,
  ];

  blocks.forEach(block => {
    registerBlockType(block.name, {
      apiVersion: 3,
      title: block.title,
      category: block.category,
      icon: block.icon,
      attributes: block.attributes,
      edit: block.edit,
      save: block.save,
    });
  });
});

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
