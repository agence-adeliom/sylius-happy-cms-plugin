import './seo-block.css';

window.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[js-seo-block-content]').forEach((content) => {
    let btn = content.querySelector('[js-seo-block-opener]');
    btn.addEventListener('click', () => {
      content.classList.toggle('is-open');
    })
  });
});
