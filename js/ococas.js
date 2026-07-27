/* ===== OCOCAS — SHARED JAVASCRIPT ===== */

// ---- Language ----
const DICT = {
  en: {
    nav: { about:'Restaurant', menus:'Menus', gallery:'Gallery', visit:'Visit', reserve:'Reserve' },
    footer: { rights:'© 2026 ococas — All rights reserved', lisbon:'Made in Lisbon', news:'Newsletter', newsPh:'Your email', follow:'Follow' }
  },
  pt: {
    nav: { about:'Restaurante', menus:'Menus', gallery:'Galeria', visit:'Visite', reserve:'Reservar' },
    footer: { rights:'© 2026 ococas — Todos os direitos reservados', lisbon:'Feito em Lisboa', news:'Newsletter', newsPh:'O seu email', follow:'Siga-nos' }
  }
};

function getLang() {
  try { return localStorage.getItem('oc_lang') || 'en'; } catch(e) { return 'en'; }
}
function setLang(l) {
  try { localStorage.setItem('oc_lang', l); } catch(e) {}
  applyLang(l);
}
function applyLang(lang) {
  // Update data-lang elements
  document.querySelectorAll('[data-en]').forEach(el => {
    el.textContent = el.getAttribute('data-' + lang) || el.getAttribute('data-en');
  });
  // Update placeholders
  document.querySelectorAll('[data-ph-en]').forEach(el => {
    el.placeholder = el.getAttribute('data-ph-' + lang) || el.getAttribute('data-ph-en');
  });
  // Update lang buttons
  document.querySelectorAll('.oc-nav__lang button').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.lang === lang);
  });
  // Page-level callback
  if (typeof window.onLangChange === 'function') window.onLangChange(lang);
}

// ---- Nav ----
function initNav() {
  const burger = document.querySelector('.oc-nav__burger');
  const mobileMenu = document.querySelector('.oc-nav__mobile-menu');
  const mobileClose = document.querySelector('.oc-nav__mobile-close');
  if (burger && mobileMenu) {
    burger.addEventListener('click', () => mobileMenu.classList.add('open'));
    if (mobileClose) mobileClose.addEventListener('click', () => mobileMenu.classList.remove('open'));
    mobileMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => mobileMenu.classList.remove('open')));
  }

  // Lang toggles
  document.querySelectorAll('.oc-nav__lang button').forEach(btn => {
    btn.addEventListener('click', () => setLang(btn.dataset.lang));
  });

  applyLang(getLang());
}

document.addEventListener('DOMContentLoaded', initNav);

// ---- Utilities ----
function seed(s) {
  let h = 2166136261;
  for (let i = 0; i < s.length; i++) { h ^= s.charCodeAt(i); h = Math.imul(h, 16777619); }
  return (h >>> 0) / 4294967296;
}
function slotStatus(iso, guests, time, loc) {
  if (!iso) return 'na';
  const r = seed(iso + '|' + time + '|' + (loc || ''));
  const need = 0.30 + (guests - 2) * 0.045;
  if (r < need) return 'full';
  return 'open';
}
function randRef() {
  return 'OC-' + Math.random().toString(36).slice(2, 7).toUpperCase();
}
