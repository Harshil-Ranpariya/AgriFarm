document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('a[href^="#"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            
            if (targetSection) {
                e.preventDefault();
                const offsetTop = targetSection.offsetTop - 80; 
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });

    const sections = document.querySelectorAll('section[id]');
    const navItems = document.querySelectorAll('.nav-link[href^="#"]');
    
    window.addEventListener('scroll', function() {
        let current = '';
        const scrollPosition = window.scrollY + 100;
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;
            
            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                current = section.getAttribute('id');
            }
        });
        
        navItems.forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('href') === `#${current}`) {
                item.classList.add('active');
            }
        });
    });

    const navbar = document.querySelector('.navbar');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            navbar.style.boxShadow = '0 2px 30px rgba(0, 0, 0, 0.15)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
        }
    });

    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    const animateElements = document.querySelectorAll('.feature-card, .about-content, .contact-form, .product-card, .gallery-item, .product-item');
    animateElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });

    const statNumbers = document.querySelectorAll('.stat-number');
    const statsObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = entry.target;
                const finalNumber = parseInt(target.textContent);
                animateCounter(target, 0, finalNumber, 2000);
                statsObserver.unobserve(target);
            }
        });
    }, { threshold: 0.5 });

    statNumbers.forEach(stat => {
        statsObserver.observe(stat);
    });

    function animateCounter(element, start, end, duration) {
        const startTime = performance.now();
        const difference = end - start;
        
        function updateCounter(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const current = Math.floor(start + (difference * progress));
            element.textContent = current + '+';
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            }
        }
        
        requestAnimationFrame(updateCounter);
    }

    const contactForm = document.querySelector('.contact-form form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            setTimeout(() => {
                submitBtn.innerHTML = '<i class="fas fa-check"></i> Message Sent!';
                submitBtn.style.background = '#4CAF50';
                
                setTimeout(() => {
                    this.reset();
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    submitBtn.style.background = '';
                }, 2000);
            }, 1500);
        });
    }

    const floatingElements = document.querySelectorAll('.floating-element');
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        floatingElements.forEach((element, index) => {
            const speed = 0.5 + (index * 0.1);
            element.style.transform = `translateY(${scrolled * speed}px)`;
        });
    });

    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-15px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    const revealSections = document.querySelectorAll('section');
    const revealObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
            }
        });
    }, { threshold: 0.1 });

    revealSections.forEach(section => {
        revealObserver.observe(section);
    });

    const style = document.createElement('style');
    style.textContent = `
        section {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.8s ease;
        }
        section.revealed {
            opacity: 1;
            transform: translateY(0);
        }
        section:first-child {
            opacity: 1;
            transform: translateY(0);
        }
    `;
    document.head.appendChild(style);

    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (navbarToggler && navbarCollapse) {
        navbarToggler.addEventListener('click', function() {
            this.classList.toggle('active');
        });
        
        const mobileNavLinks = document.querySelectorAll('.navbar-nav .nav-link');
        mobileNavLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    navbarCollapse.classList.remove('show');
                    navbarToggler.classList.remove('active');
                }
            });
        });
    }

    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('load', function() {
            this.style.opacity = '1';
        });
        
        img.style.opacity = '0';
        img.style.transition = 'opacity 0.5s ease';
    });

    const hero = document.querySelector('.hero');
    if (hero) {
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            hero.style.transform = `translateY(${rate}px)`;
        });
    }

    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    progressBar.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 0%;
        height: 3px;
        background: linear-gradient(90deg, #4CAF50, #45a049);
        z-index: 9999;
        transition: width 0.1s ease;
    `;
    document.body.appendChild(progressBar);

    window.addEventListener('scroll', function() {
        const scrollTop = document.documentElement.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrollPercent = (scrollTop / scrollHeight) * 100;
        progressBar.style.width = scrollPercent + '%';
    });
    const lightbox = document.getElementById('lightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxClose = document.getElementById('lightboxClose');
    if (lightbox && lightboxImage) {
        document.querySelectorAll('.gallery-item img').forEach(img => {
            img.addEventListener('click', () => {
                lightboxImage.src = img.src;
                lightbox.style.display = 'flex';
                lightbox.setAttribute('aria-hidden', 'false');
            });
        });
        const closeLb = () => {
            lightbox.style.display = 'none';
            lightbox.setAttribute('aria-hidden', 'true');
            lightboxImage.src = '';
        };
        lightbox.addEventListener('click', (e) => { if (e.target === lightbox) closeLb(); });
        if (lightboxClose) lightboxClose.addEventListener('click', closeLb);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeLb(); });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const heroTitle = document.querySelector('.hero-title .highlight');
    if (heroTitle) {
        const text = heroTitle.textContent;
        heroTitle.textContent = '';
        
        let i = 0;
        const typeWriter = () => {
            if (i < text.length) {
                heroTitle.textContent += text.charAt(i);
                i++;
                setTimeout(typeWriter, 100);
            }
        };
        
        setTimeout(typeWriter, 1000);
    }

    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
            `;
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });

    const rippleStyle = document.createElement('style');
    rippleStyle.textContent = `
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        .btn {
            position: relative;
            overflow: hidden;
        }
    `;
    document.head.appendChild(rippleStyle);
});

document.addEventListener('DOMContentLoaded', function() {
    const viewport = document.querySelector('.carousel-viewport');
    const track = document.querySelector('.carousel-track');
    const cards = document.querySelectorAll('.carousel-card');
    const prevBtn = document.querySelector('.carousel-btn.prev');
    const nextBtn = document.querySelector('.carousel-btn.next');
    const dotsContainer = document.querySelector('.carousel-dots');

    if (!viewport || !track || cards.length === 0) return;

    let current = 0;
    let autoplayId = null;
    let isDragging = false;
    let startX = 0;
    let deltaX = 0;

    // Build dots
    const total = cards.length;
    for (let i = 0; i < total; i++) {
        const dot = document.createElement('span');
        dot.className = 'dot' + (i === 0 ? ' active' : '');
        dot.addEventListener('click', () => goTo(i));
        dotsContainer && dotsContainer.appendChild(dot);
    }

    function layout() {
        const viewportWidth = viewport.clientWidth;
        const cardWidth = cards[0].offsetWidth;
        const baseX = -cardWidth / 2; // center the card over the middle
        const centerIndex = current;
        const gap = Math.min(300, Math.max(220, Math.floor(viewportWidth * 0.32)));
        const depth = 140;
        cards.forEach((card, index) => {
            const offset = index - centerIndex;
            const z = -Math.abs(offset) * depth;
            const x = baseX + offset * gap;
            const rotateY = offset * -12;
            card.style.transform = `translateX(${x}px) translateZ(${z}px) rotateY(${rotateY}deg) scale(${index===centerIndex?1:0.85})`;
            card.style.opacity = index === centerIndex ? '1' : '0.6';
            card.classList.toggle('is-center', index === centerIndex);
        });
        const dots = dotsContainer ? dotsContainer.querySelectorAll('.dot') : [];
        dots.forEach((d, i) => d.classList.toggle('active', i === current));
    }

    function goTo(index) {
        current = (index + cards.length) % cards.length;
        layout();
    }

    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    function startAutoplay() {
        stopAutoplay();
        autoplayId = setInterval(next, 4500);
    }
    function stopAutoplay() { if (autoplayId) clearInterval(autoplayId); autoplayId = null; }

    nextBtn && nextBtn.addEventListener('click', () => { next(); startAutoplay(); });
    prevBtn && prevBtn.addEventListener('click', () => { prev(); startAutoplay(); });

    // Drag / touch support
    const onDown = (x) => { isDragging = true; startX = x; deltaX = 0; stopAutoplay(); };
    const onMove = (x) => { if (!isDragging) return; deltaX = x - startX; track.style.transform = `translateX(${deltaX * 0.25}px)`; };
    const onUp = () => {
        if (!isDragging) return;
        isDragging = false;
        track.style.transform = '';
        if (Math.abs(deltaX) > 60) {
            if (deltaX < 0) next(); else prev();
        } else {
            layout();
        }
        startAutoplay();
    };

    viewport.addEventListener('mousedown', (e) => onDown(e.clientX));
    window.addEventListener('mousemove', (e) => onMove(e.clientX));
    window.addEventListener('mouseup', onUp);

    viewport.addEventListener('touchstart', (e) => onDown(e.touches[0].clientX), { passive: true });
    window.addEventListener('touchmove', (e) => onMove(e.touches[0].clientX), { passive: true });
    window.addEventListener('touchend', onUp);

    // Pause on hover
    viewport.addEventListener('mouseenter', stopAutoplay);
    viewport.addEventListener('mouseleave', startAutoplay);

    layout();
    startAutoplay();
    window.addEventListener('resize', layout);

    // Reveal grid product cards
    const productGrid = document.getElementById('our-products-list');
    if (productGrid) {
        fetch('products_api.php?ts=' + Date.now())
            .then(r => r.json())
            .then(items => {
                productGrid.innerHTML = '';
                if (!Array.isArray(items) || items.length === 0) {
                    productGrid.innerHTML = '<div class="col-12 text-center text-muted">No products available yet.</div>';
                    return;
                }
                const frag = document.createDocumentFragment();
                const baseUrl = window.location.origin + window.location.pathname.replace(/[^\/]+$/, '');
                items.forEach(p => {
                    const imgSrc = p.image_path ? (p.image_path.startsWith('http') ? p.image_path : (p.image_path.startsWith('/') ? window.location.origin + p.image_path : baseUrl + p.image_path.replace(/^\/?/, ''))) : '';

                    const col = document.createElement('div');
                    col.className = 'col-md-6 col-lg-4';
                    col.innerHTML = `
                        <article class="product-item" data-tilt>
                            <div class="pi-media">
                                ${imgSrc ? `<img src="${imgSrc}" alt="${p.name}" loading="lazy" onerror="this.parentElement.innerHTML='<div style\\"height:100%;display:flex;align-items:center;justify-content:center;background:#f1f5f4;color:#6b7280;\\"'>Image unavailable</div>'">` : `<div style="height:100%;display:flex;align-items:center;justify-content:center;background:#f1f5f4;color:#6b7280;">No image</div>`}
                         
                                <span class="pi-ribbon">Approved</span>
                            </div>
                            <div class="pi-body">
                                <h3>${p.name}</h3>
                                <p>${p.description || ''}</p>
                                <div class="pi-tags"><span>₹ ${p.price}</span></div>
                            </div>
                        </article>`;
                    frag.appendChild(col);
                });
                productGrid.appendChild(frag);

                // Animate newly injected cards
                const productItems = document.querySelectorAll('.product-item');
                const piObserver = new IntersectionObserver((entries)=>{
                    entries.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('revealed'); piObserver.unobserve(e.target);} });
                }, { threshold: 0.2 });
                productItems.forEach(pi => piObserver.observe(pi));

                // Rebind tilt
                document.querySelectorAll('[data-tilt]').forEach(card => {
                    card.addEventListener('mousemove', (e) => {
                        const r = card.getBoundingClientRect();
                        const x = ((e.clientX - r.left) / r.width - 0.5) * 8;
                        const y = ((e.clientY - r.top) / r.height - 0.5) * -8;
                        card.style.transform = `rotateY(${x}deg) rotateX(${y}deg)`;
                    });
                    card.addEventListener('mouseleave', () => { card.style.transform = ''; });
                });

                // Build ticker from first N items
                buildProductsTicker(items);

                // Build product shelf
                buildProductShelf(items);
            })
            .catch((err) => {
                console.error('Failed to load products_api.php', err);
                productGrid.innerHTML = '<div class="col-12 text-center text-muted">Failed to load products.</div>';
            });
    }

    // Subtle tilt on hover
    document.querySelectorAll('[data-tilt]').forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const r = card.getBoundingClientRect();
            const x = ((e.clientX - r.left) / r.width - 0.5) * 8;
            const y = ((e.clientY - r.top) / r.height - 0.5) * -8;
            card.style.transform = `rotateY(${x}deg) rotateX(${y}deg)`;
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
        });
    });

    // Horizontal ticker logic
    function buildProductsTicker(items) {
        const viewport = document.getElementById('productsTicker');
        const track = document.getElementById('productsTickerTrack');
        if (!viewport || !track) return;

        const visible = 3;
        // If there are already manual cards in HTML, use them. Otherwise, build from API.
        if (track.children.length === 0 && Array.isArray(items) && items.length) {
            const baseUrl = window.location.origin + window.location.pathname.replace(/[^\/]+$/, '');
            items.slice(0, 6).forEach(p => {
                const imgSrc = p.image_path ? (p.image_path.startsWith('http') ? p.image_path : (p.image_path.startsWith('/') ? window.location.origin + p.image_path : baseUrl + p.image_path.replace(/^\/?/, ''))) : '';
                const el = document.createElement('div');
                el.className = 'ticker-card';
                el.innerHTML = `
                    <div class="media">${imgSrc ? `<img src="${imgSrc}" alt="${p.name}">` : ''}</div>
                    <div class="body">${p.name}</div>
                `;
                track.appendChild(el);
            });
        }

        let autoplayId = null; let paused = false;
        function step() {
            if (paused || track.children.length <= visible) return;
            const firstWidth = track.children[0].getBoundingClientRect().width + parseFloat(getComputedStyle(track).gap || 0);
            track.style.transition = 'transform 0.6s ease';
            track.style.transform = `translateX(-${firstWidth}px)`;
            setTimeout(() => {
                track.style.transition = 'none';
                track.style.transform = 'translateX(0)';
                // remove first, append next
                if (track.children.length) track.removeChild(track.children[0]);
                // append clone of next card to keep loop
                const nextCard = track.children[0] ? track.children[0].cloneNode(true) : null;
                if (nextCard) track.appendChild(nextCard);
            }, 620);
        }

        function start() { stop(); autoplayId = setInterval(step, 2500); }
        function stop() { if (autoplayId) clearInterval(autoplayId); autoplayId = null; }

        viewport.addEventListener('mouseenter', () => { paused = true; stop(); });
        viewport.addEventListener('mouseleave', () => { paused = false; start(); });
        window.addEventListener('visibilitychange', () => { if (document.hidden) stop(); else start(); });

        start();
    }

    function buildProductShelf(items){
        const viewport = document.getElementById('dealShelf');
        const track = document.getElementById('dealShelfTrack');
        const prev = document.getElementById('shelfPrev');
        const next = document.getElementById('shelfNext');
        if (!viewport || !track) return;

        track.innerHTML = '';
        const baseUrl = window.location.origin + window.location.pathname.replace(/[^\/]+$/, '');
        const data = (Array.isArray(items) ? items : []).slice(0, 12);
        data.forEach(p => {
            const imgSrc = p.image_path ? (p.image_path.startsWith('http') ? p.image_path : (p.image_path.startsWith('/') ? window.location.origin + p.image_path : baseUrl + p.image_path.replace(/^\/?/, ''))) : '';
            const card = document.createElement('div');
            card.className = 'shelf-card';
            card.innerHTML = `
                <div class="media">${imgSrc ? `<img src="${imgSrc}" alt="${p.name}">` : ''}</div>
                <div class="body">
                    <div class="badge">Great Indian Festival</div>
                    <div class="title">${p.name}</div>
                    <div><span class="price">₹ ${Number(p.price || 0).toFixed(2)}</span> <span class="strike">₹ ${(Number(p.price||0)*1.2).toFixed(2)}</span></div>
                </div>`;
            track.appendChild(card);
        });

        let scrollX = 0; let autoplayId = null; let paused = false;
        function step(dir=1){
            const card = track.children[0];
            if (!card) return;
            const gap = parseFloat(getComputedStyle(track).gap||0);
            const w = card.getBoundingClientRect().width + gap;
            scrollX += dir * w;
            track.style.transition = 'transform 0.5s ease';
            track.style.transform = `translateX(-${scrollX}px)`;
        }
        function start(){ stop(); autoplayId = setInterval(()=>step(1), 3000); }
        function stop(){ if (autoplayId) clearInterval(autoplayId); autoplayId = null; }

        prev && prev.addEventListener('click', ()=>{ step(-1); start(); });
        next && next.addEventListener('click', ()=>{ step(1); start(); });
        viewport.addEventListener('mouseenter', ()=>{ paused=true; stop(); });
        viewport.addEventListener('mouseleave', ()=>{ paused=false; start(); });
        window.addEventListener('visibilitychange', ()=>{ if(document.hidden) stop(); else start(); });
        start();
    }
});
