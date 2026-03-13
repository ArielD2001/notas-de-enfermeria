    </main>
<?php if (isset($_SESSION['id_usuario'])): ?>
        </div> <!-- .flex-grow (Content Area) -->
    </div> <!-- .min-h-full (Main Layout) -->
<?php endif; ?>

    <!-- Loader Script -->
    <script>
        function hideLoader() {
            const loader = document.getElementById('loader-wrapper');
            if (loader) {
                // Manual fallback if CSS doesn't load/work
                loader.style.opacity = '0';
                loader.style.pointerEvents = 'none';
                loader.classList.add('fade-out');
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 500);
            }
        }

        // Hide on window load (all assets)
        window.addEventListener('load', hideLoader);

        // Fallback: Hide after 3 seconds anyway if window load hasn't fired
        setTimeout(hideLoader, 3000);

        // Toggle Sidebar on mobile
        const toggleBtn = document.getElementById('toggle-sidebar');
        const closeBtn = document.getElementById('close-sidebar');
        const sidebar = document.getElementById('sidebar');
        
        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 bg-black/50 z-30 hidden transition-opacity duration-300 opacity-0';
        document.body.appendChild(overlay);

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
            setTimeout(() => overlay.classList.add('opacity-100'), 10);
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.remove('opacity-100');
            setTimeout(() => {
                overlay.classList.add('hidden');
            }, 300);
            document.body.style.overflow = '';
        }

        if(toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', openSidebar);
        }

        if(closeBtn) {
            closeBtn.addEventListener('click', closeSidebar);
        }

        overlay.addEventListener('click', closeSidebar);
    </script>

</body>
</html>
