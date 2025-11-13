/**
 * Landing page animations and interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Добавляем эффект параллакса при движении мыши
    document.querySelectorAll('.section-half').forEach(section => {
        section.addEventListener('mousemove', (e) => {
            const rect = section.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            const percentX = (x - centerX) / centerX;
            const percentY = (y - centerY) / centerY;

            const icon = section.querySelector('.section-icon');
            if (icon) {
                icon.style.transform = `translate(${percentX * 20}px, ${percentY * 20}px)`;
            }
        });

        section.addEventListener('mouseleave', () => {
            const icon = section.querySelector('.section-icon');
            if (icon) {
                icon.style.transform = 'translate(0, 0)';
            }
        });
    });
});
