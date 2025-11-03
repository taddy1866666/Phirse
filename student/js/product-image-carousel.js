/**
 * Product Image Carousel
 * Cycles through multiple product images on hover (desktop) or hold (mobile)
 */

class ProductImageCarousel {
    constructor() {
        this.init();
    }

    init() {
        // Find all product cards
        const productCards = document.querySelectorAll('.product-card');
        
        productCards.forEach(card => {
            const imageContainer = card.querySelector('.product-image-container');
            const mainImage = card.querySelector('.product-image');
            
            if (!imageContainer || !mainImage) return;
            
            // Get all images from data attribute
            const imagesData = mainImage.getAttribute('data-images');
            if (!imagesData) return;
            
            const images = imagesData.split(',').map(img => img.trim()).filter(img => img);
            
            // Only proceed if there are multiple images
            if (images.length <= 1) return;
            
            let currentIndex = 0;
            let intervalId = null;
            let touchTimer = null;
            let isHovering = false;
            let touchStartX = 0;
            let touchStartY = 0;
            let isScrolling = false;
            
            // Preload all images to prevent flash
            const preloadedImages = [];
            images.forEach(src => {
                const img = new Image();
                img.src = src;
                preloadedImages.push(img);
            });
            
            // Create white overlay for smooth fade transition
            const imageWrapper = mainImage.parentElement;
            const fadeOverlay = document.createElement('div');
            fadeOverlay.style.position = 'absolute';
            fadeOverlay.style.top = '0';
            fadeOverlay.style.left = '0';
            fadeOverlay.style.width = '100%';
            fadeOverlay.style.height = '100%';
            fadeOverlay.style.backgroundColor = 'rgba(255, 255, 255, 0)';
            fadeOverlay.style.transition = 'background-color 0.1s ease-in-out';
            fadeOverlay.style.pointerEvents = 'none';
            fadeOverlay.style.zIndex = '1';
            imageWrapper.style.position = 'relative';
            imageWrapper.appendChild(fadeOverlay);
            
            // Create smooth fade through white overlay
            const cycleImage = () => {
                currentIndex = (currentIndex + 1) % images.length;
                
                // Fade to white (low opacity)
                fadeOverlay.style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
                
                // Change image during fade
                setTimeout(() => {
                    mainImage.src = images[currentIndex];
                    
                    // Fade back from white
                    setTimeout(() => {
                        fadeOverlay.style.backgroundColor = 'rgba(255, 255, 255, 0)';
                    }, 30);
                }, 100);
            };
            
            // Start cycling
            const startCycling = () => {
                if (intervalId) return; // Already cycling
                isHovering = true;
                cycleImage(); // Start immediately
                intervalId = setInterval(cycleImage, 1000); // Change every 1 second
            };
            
            // Stop cycling and reset to first image
            const stopCycling = () => {
                isHovering = false;
                if (intervalId) {
                    clearInterval(intervalId);
                    intervalId = null;
                }
                
                // Reset to first image with smooth fade
                if (currentIndex !== 0) {
                    currentIndex = 0;
                    
                    // Fade to white
                    fadeOverlay.style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
                    
                    setTimeout(() => {
                        mainImage.src = images[0];
                        
                        // Fade back from white
                        setTimeout(() => {
                            fadeOverlay.style.backgroundColor = 'rgba(255, 255, 255, 0)';
                        }, 30);
                    }, 100);
                }
            };
            
            // Desktop: Hover events
            card.addEventListener('mouseenter', startCycling);
            card.addEventListener('mouseleave', stopCycling);
            
            // Mobile: Touch events (cycle on tap, not hold)
            card.addEventListener('touchstart', (e) => {
                // Record initial touch position
                touchStartX = e.touches[0].clientX;
                touchStartY = e.touches[0].clientY;
                isScrolling = false;
            });
            
            card.addEventListener('touchmove', (e) => {
                // Detect if user is scrolling
                const touchCurrentX = e.touches[0].clientX;
                const touchCurrentY = e.touches[0].clientY;
                const deltaX = Math.abs(touchCurrentX - touchStartX);
                const deltaY = Math.abs(touchCurrentY - touchStartY);
                
                // If any significant movement (horizontal or vertical), consider it scrolling
                if (deltaX > 10 || deltaY > 10) {
                    isScrolling = true;
                }
            });
            
            card.addEventListener('touchend', (e) => {
                // Only cycle image if user tapped (not scrolled)
                if (!isScrolling && !e.target.closest('a')) {
                    // Just cycle to next image on tap
                    cycleImage();
                }
                isScrolling = false;
            });
            
            // Add smooth transition for both desktop and mobile
            mainImage.style.transition = 'none';
            mainImage.style.display = 'block';
        });
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new ProductImageCarousel();
        initMutationObserver();
    });
} else {
    new ProductImageCarousel();
    initMutationObserver();
}

// Watch for dynamically added product cards (for search modal)
function initMutationObserver() {
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) { // Element node
                    // Check if the added node or its children contain product cards
                    if (node.classList && node.classList.contains('product-card')) {
                        new ProductImageCarousel();
                    } else if (node.querySelectorAll) {
                        const cards = node.querySelectorAll('.product-card');
                        if (cards.length > 0) {
                            new ProductImageCarousel();
                        }
                    }
                }
            });
        });
    });
    
    // Start observing the document for changes
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}
