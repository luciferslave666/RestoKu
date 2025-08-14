// Pastikan file ini dimuat SETELAH jQuery dan Slick Carousel JS di HTML Anda.

$(document).ready(function() {
    // === SMOOTH SCROLLING UNTUK ANCHOR LINK ===
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetElement = document.querySelector(this.getAttribute('href'));
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth' });
            }

            const mobileMenu = document.getElementById('mobile-menu');
            if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
                mobileMenu.classList.add('hidden');
            }

            const overlayMenu = document.querySelector('.overlay-menu');
            const menuIcon = document.querySelector('.menuIcon');
            if (overlayMenu && overlayMenu.classList.contains('active')) {
                overlayMenu.classList.remove('active');
                if (menuIcon) menuIcon.classList.remove('toggle');
            }
        });
    });

    // === MOBILE MENU TOGGLER ===
    const menuBtn = document.getElementById('menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }

    const menuIcon = document.querySelector('.menuIcon');
    const overlayMenu = document.querySelector('.overlay-menu');
    if (menuIcon && overlayMenu) {
        menuIcon.addEventListener('click', () => {
            menuIcon.classList.toggle('toggle');
            overlayMenu.classList.toggle('active');
        });

        const menuLinks = document.querySelectorAll('.overlay-menu ul li a');
        menuLinks.forEach(link => {
            link.addEventListener('click', () => {
                menuIcon.classList.remove('toggle');
                overlayMenu.classList.remove('active');
            });
        });
    }

    // === SLICK CAROUSEL INITIALIZATION ===
    $('.menu-slider').slick({
        infinite: true,
        slidesToShow: 4, // Menampilkan 3 kartu di layar besar (desktop)
        slidesToScroll: 1, // Menggulir 1 kartu setiap kali
        autoplay: true,
        autoplaySpeed: 300, // Otomatis maju setiap 3 detik
        arrows: false, // Tampilkan panah navigasi
        dots: true, // Tampilkan dot navigasi
        responsive: [
            {
                breakpoint: 1024, // Untuk layar <= 1024px (tablet dan laptop kecil)
                settings: {
                    slidesToShow: 2, // Tampilkan 2 kartu
                    slidesToScroll: 1
                }
            },
            {
                breakpoint: 768, // Untuk layar <= 768px (tablet vertikal dan smartphone besar)
                settings: {
                    slidesToShow: 1, // Tampilkan 1 kartu
                    slidesToScroll: 1
                }
            },
            {
                breakpoint: 480, // Untuk layar <= 480px (smartphone)
                settings: {
                    slidesToShow: 1, // Tampilkan 1 kartu
                    slidesToScroll: 1
                }
            }
        ]
    });
});