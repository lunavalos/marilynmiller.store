/* ═══════════════════════════════════════════════════════════════
   MILLYFIT — INTERACTIONS & ANIMATIONS
═══════════════════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

  /* ─── NAVBAR: scroll effect ─── */
  const navbar = document.getElementById('navbar');
  const navToggle = document.getElementById('nav-toggle');
  const navLinks = document.getElementById('nav-links');

  const onScroll = () => {
    if (window.scrollY > 60) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ─── MOBILE MENU ─── */
  navToggle.addEventListener('click', () => {
    const isOpen = navLinks.classList.toggle('open');
    navToggle.classList.toggle('open', isOpen);
    navToggle.setAttribute('aria-expanded', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
  });

  // Close on link click
  navLinks.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      navLinks.classList.remove('open');
      navToggle.classList.remove('open');
      navToggle.setAttribute('aria-expanded', false);
      document.body.style.overflow = '';
    });
  });

  /* ─── SCROLL REVEAL ─── */
  const reveals = document.querySelectorAll('.reveal-up, .reveal-left, .reveal-right');
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  reveals.forEach(el => revealObserver.observe(el));

  /* ─── COUNTER ANIMATION ─── */
  const counters = document.querySelectorAll('.stat-number[data-target]');
  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el = entry.target;
      const target = parseInt(el.dataset.target, 10);
      const duration = 1600;
      const step = target / (duration / 16);
      let current = 0;
      const timer = setInterval(() => {
        current += step;
        if (current >= target) {
          current = target;
          clearInterval(timer);
        }
        el.textContent = Math.floor(current);
      }, 16);
      counterObserver.unobserve(el);
    });
  }, { threshold: 0.5 });
  counters.forEach(c => counterObserver.observe(c));

  /* ─── TESTIMONIALS CAROUSEL ─── */
  const track = document.getElementById('testimonials-track');
  const dotsContainer = document.getElementById('testimonials-dots');
  const prevBtn = document.getElementById('testimonial-prev-btn');
  const nextBtn = document.getElementById('testimonial-next-btn');

  if (track) {
    const cards = track.children;
    let currentIndex = 0;
    let autoplay;

    // Determine visible count based on viewport
    const getVisible = () => {
      if (window.innerWidth < 768) return 1;
      if (window.innerWidth < 1100) return 2;
      return 3;
    };

    const totalSlides = () => Math.max(0, cards.length - getVisible() + 1);

    // Build dots
    const buildDots = () => {
      dotsContainer.innerHTML = '';
      const count = totalSlides();
      for (let i = 0; i < count; i++) {
        const dot = document.createElement('button');
        dot.className = 'dot' + (i === currentIndex ? ' active' : '');
        dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
        dot.addEventListener('click', () => goTo(i));
        dotsContainer.appendChild(dot);
      }
    };

    const goTo = (index) => {
      const max = totalSlides() - 1;
      currentIndex = Math.max(0, Math.min(index, max));
      const cardWidth = cards[0].offsetWidth + 24; // gap
      track.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
      dotsContainer.querySelectorAll('.dot').forEach((d, i) => {
        d.classList.toggle('active', i === currentIndex);
      });
    };

    prevBtn.addEventListener('click', () => {
      goTo(currentIndex - 1);
      resetAutoplay();
    });
    nextBtn.addEventListener('click', () => {
      goTo(currentIndex < totalSlides() - 1 ? currentIndex + 1 : 0);
      resetAutoplay();
    });

    const startAutoplay = () => {
      autoplay = setInterval(() => {
        goTo(currentIndex < totalSlides() - 1 ? currentIndex + 1 : 0);
      }, 5000);
    };
    const resetAutoplay = () => {
      clearInterval(autoplay);
      startAutoplay();
    };

    buildDots();
    startAutoplay();

    let resizeTimer;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        buildDots();
        goTo(currentIndex);
      }, 200);
    });

    // Touch/swipe support
    let touchStartX = 0;
    track.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
    track.addEventListener('touchend', e => {
      const diff = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 40) {
        if (diff > 0) goTo(currentIndex < totalSlides() - 1 ? currentIndex + 1 : 0);
        else goTo(currentIndex - 1);
        resetAutoplay();
      }
    });
  }

  /* ─── FAQ ACCORDION ─── */
  document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', () => {
      const isOpen = btn.getAttribute('aria-expanded') === 'true';
      // Close all
      document.querySelectorAll('.faq-question').forEach(b => {
        b.setAttribute('aria-expanded', 'false');
        const ans = document.getElementById(b.id.replace('btn', 'answer'));
        if (ans) ans.classList.remove('open');
      });
      // Open clicked (if wasn't open)
      if (!isOpen) {
        btn.setAttribute('aria-expanded', 'true');
        const ans = document.getElementById(btn.id.replace('btn', 'answer'));
        if (ans) ans.classList.add('open');
      }
    });
  });

  /* ─── SMOOTH SCROLL for anchor links ─── */
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', e => {
      const target = document.querySelector(anchor.getAttribute('href'));
      if (target) {
        e.preventDefault();
        const navHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--navbar-h'), 10) || 72;
        const top = target.getBoundingClientRect().top + window.scrollY - navHeight;
        window.scrollTo({ top, behavior: 'smooth' });
      }
    });
  });

  /* ─── HERO parallax (subtle) ─── */
  const heroImg = document.querySelector('.hero-img');
  if (heroImg && window.matchMedia('(min-width: 768px)').matches) {
    window.addEventListener('scroll', () => {
      const scrolled = window.scrollY;
      heroImg.style.transform = `translateY(${scrolled * 0.3}px)`;
    }, { passive: true });
  }

  /* ─── Feature cards: tilt on hover ─── */
  document.querySelectorAll('.feature-card').forEach(card => {
    card.addEventListener('mousemove', e => {
      const rect = card.getBoundingClientRect();
      const x = (e.clientX - rect.left) / rect.width - 0.5;
      const y = (e.clientY - rect.top) / rect.height - 0.5;
      card.style.transform = `translateY(-6px) rotateX(${-y * 4}deg) rotateY(${x * 4}deg)`;
    });
    card.addEventListener('mouseleave', () => {
      card.style.transform = '';
    });
  });

});
