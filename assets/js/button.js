// Define Vars
const hamburgerButton = document.getElementById('hamburger-button'); // Hamburger button
const mobileMenu = document.getElementById('mobile-nav'); // Collapsible mobile menu
const mobileHeader = document.getElementById('mobile-header');
let aria = true; // Set initial aria state

// Set Event Listeners
hamburgerButton.addEventListener('click', toggleMenu); // Clicking hamburger button
document.addEventListener('keyup', escClose); // Closing with Escape key

// Define Functions
function toggleMenu() {
    hamburgerButton.classList.toggle('menu-open'); // Change hamburger to 'X'
    if (aria) {
        mobileMenu.className = 'expand'; // Expand mobile menu
        mobileMenu.style.height = mobileMenu.scrollHeight + 'px';
        document.getElementById('nav-home').focus(); // Move focus to first link in list

        // Change aria attributes on hamburger button:
        hamburgerButton.setAttribute('aria-label', 'close'); // Change aria-label from 'Menu' to 'Close'
        hamburgerButton.setAttribute('aria-expanded', 'true'); // Add aria-expanded attribute
        aria = false; // Reverse aria state
    } else {
        closeMenu();
    }

}

function closeMenu() {
    mobileMenu.className = 'collapse'; // Collapse mobile menu
    mobileMenu.style.height = '0px';

    // Change/remove aria attributes on hamburger button:
    hamburgerButton.setAttribute('aria-label', 'Menu'); // Change aria-label from 'Close' to 'Menu'
    hamburgerButton.removeAttribute('aria-expanded'); // Remove aria-expanded attribute
    aria = true; // Reverse aria state
}

function escClose(key) {
    if (key.key === 'Escape' && aria === false) { // IF Escape key is pressed and mobile menu is open...
        toggleMenu(); // Toggle hamburger button icon THEN close mobile menu
    }
}