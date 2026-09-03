</main>

<footer class="bg-[#1a1a1a] text-white">
    <div class="container mx-auto px-6 lg:px-12 py-16 md:py-24">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-10 md:gap-8 mb-16">
            <div class="col-span-2 md:col-span-1">
                <a href="/" class="serif text-2xl tracking-wide text-white mb-6 block">SZN</a>
                <p class="text-sm font-light text-white/60 leading-relaxed max-w-xs">
                    <?php echo __('maison_desc'); ?>
                </p>
            </div>

            <div>
                <h3 class="text-xs font-light tracking-widest uppercase text-white/80 mb-6"><?php echo __('shop'); ?></h3>
                <ul class="space-y-4 text-sm font-light text-white/60">
                    <li><a href="index.php?page=shop&sort=new" class="hover:text-white transition-colors"><?php echo __('new_arrivals'); ?></a></li>
                    <li><a href="index.php?page=shop&sort=popular" class="hover:text-white transition-colors"><?php echo __('best_sellers'); ?></a></li>
                    <li><a href="index.php?page=categories" class="hover:text-white transition-colors"><?php echo __('collections'); ?></a></li>
                    <li><a href="index.php?page=shop&sale=1" class="hover:text-white transition-colors"><?php echo __('sale'); ?></a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-xs font-light tracking-widest uppercase text-white/80 mb-6"><?php echo __('support'); ?></h3>
                <ul class="space-y-4 text-sm font-light text-white/60">
                    <li><a href="index.php?page=contact" class="hover:text-white transition-colors"><?php echo __('contact_us'); ?></a></li>
                    <li><a href="index.php?page=faq" class="hover:text-white transition-colors"><?php echo __('faq'); ?></a></li>
                    <li><a href="index.php?page=shipping" class="hover:text-white transition-colors"><?php echo __('shipping_returns'); ?></a></li>
                    <li><a href="index.php?page=size-guide" class="hover:text-white transition-colors"><?php echo __('size_guide'); ?></a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-xs font-light tracking-widest uppercase text-white/80 mb-6"><?php echo __('company'); ?></h3>
                <ul class="space-y-4 text-sm font-light text-white/60">
                    <li><a href="index.php?page=about" class="hover:text-white transition-colors"><?php echo __('about_us'); ?></a></li>
                    <li><a href="index.php?page=sustainability" class="hover:text-white transition-colors"><?php echo __('sustainability'); ?></a></li>
                    <li><a href="index.php?page=careers" class="hover:text-white transition-colors"><?php echo __('careers'); ?></a></li>
                    <li><a href="index.php?page=press" class="hover:text-white transition-colors"><?php echo __('press'); ?></a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-white/10 pt-10 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-6 text-white/60" id="social-links">
                <?php 
                $social_links = get_social_links();
                foreach ($social_links as $link): 
                ?>
                    <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" rel="noopener" class="hover:text-white transition-colors"><?php echo htmlspecialchars($link['platform']); ?></a>
                <?php endforeach; ?>
                <?php if (empty($social_links)): ?>
                    <span class="text-white/40 italic text-sm">No social links configured</span>
                <?php endif; ?>
            </div>
            <p class="text-xs font-light text-white/40">© 2026 SZN. <?php echo __('all_rights_reserved'); ?></p>
            <div class="flex items-center gap-6 text-xs font-light text-white/40">
                <a href="index.php?page=privacy" class="hover:text-white transition-colors"><?php echo __('privacy_policy'); ?></a>
                <a href="index.php?page=terms" class="hover:text-white transition-colors"><?php echo __('terms_of_service'); ?></a>
            </div>
        </div>
    </div>
</footer>

    <script src="assets/js/main.js"></script>
    <script>
        const nav = document.getElementById('main-nav');
        const cartDot = document.getElementById('cart-dot');

        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                nav.classList.add('bg-white', 'py-4', 'shadow-sm');
                nav.classList.remove('py-8', 'bg-transparent');
                cartDot.classList.add('bg-black', 'text-white');
                cartDot.classList.remove('bg-white', 'text-black');
            } else {
                nav.classList.remove('bg-white', 'py-4', 'shadow-sm');
                nav.classList.add('py-8', 'bg-transparent');
            }
        });

        // Mobile Menu Logic
        const mobileBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuIcon = mobileBtn.querySelector('svg path');
        let isMenuOpen = false;

        mobileBtn.addEventListener('click', () => {
            isMenuOpen = !isMenuOpen;
            if (isMenuOpen) {
                mobileMenu.classList.remove('translate-x-full');
                mobileMenu.classList.add('translate-x-0');
                menuIcon.setAttribute('d', 'M6 18L18 6M6 6l12 12');
                document.body.style.overflow = 'hidden';
            } else {
                mobileMenu.classList.add('translate-x-full');
                mobileMenu.classList.remove('translate-x-0');
                menuIcon.setAttribute('d', 'M4 6h16M4 12h16M4 18h16');
                document.body.style.overflow = '';
            }
        });

        // User Dropdown Logic
        const userDropdownBtn = document.getElementById('user-dropdown-btn');
        const userDropdown = document.getElementById('user-dropdown');
        
        if (userDropdownBtn && userDropdown) {
            userDropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle('opacity-100');
                userDropdown.classList.toggle('visible');
                userDropdown.classList.toggle('opacity-0');
                userDropdown.classList.toggle('invisible');
            });

            document.addEventListener('click', (e) => {
                if (!userDropdownBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.add('opacity-0', 'invisible');
                    userDropdown.classList.remove('opacity-100', 'visible');
                }
            });
        }
    </script>
</body>
</html>
