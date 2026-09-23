document.addEventListener("DOMContentLoaded", function () {

    const navLinks = document.querySelectorAll(".nav-links a");
    const currentPage = window.location.pathname.split("/").pop() || "index.html";

    navLinks.forEach(link => {
        const linkPage = link.getAttribute("href");

        if (linkPage === currentPage) {
            link.classList.add("active");
        }
    });


    const registerButtons = document.querySelectorAll(
        'a[href="register.html"]'
    );

    registerButtons.forEach(button => {
        button.addEventListener("click", function () {
            console.log("Redirecting to registration page...");
        });
    });


    const eventCards = document.querySelectorAll(".event-card");

    eventCards.forEach(card => {
        card.addEventListener("mouseenter", function () {
            this.style.transform = "translateY(-8px)";
        });

        card.addEventListener("mouseleave", function () {
            this.style.transform = "translateY(0)";
        });
    });


    const testimonialCards = document.querySelectorAll(".testimonial-card");

    testimonialCards.forEach(card => {
        card.addEventListener("click", function () {
            testimonialCards.forEach(item => {
                item.classList.remove("selected");
            });

            this.classList.add("selected");
        });
    });


    const learnMoreButton = document.querySelector(
        'a[href="about.html"].secondary-btn'
    );

    if (learnMoreButton) {
        learnMoreButton.addEventListener("click", function () {
            console.log("Opening About StudentHub...");
        });
    }


    const currentYear = new Date().getFullYear();
    const copyright = document.querySelector(".copyright");

    if (copyright) {
        copyright.textContent =
            `© ${currentYear} StudentHub. All Rights Reserved.`;
    }


    const sections = document.querySelectorAll("section");

    window.addEventListener("scroll", function () {
        const scrollPosition = window.scrollY + 200;

        sections.forEach(section => {
            if (
                scrollPosition >= section.offsetTop &&
                scrollPosition < section.offsetTop + section.offsetHeight
            ) {
                section.classList.add("visible");
            }
        });
    });

});
/* =========================================
   NOTIFICATION BANNER
   ========================================= */

const closeNotification = document.getElementById("closeNotification");
const notificationBanner = document.getElementById("notificationBanner");

if (closeNotification && notificationBanner) {
    closeNotification.addEventListener("click", function () {
        notificationBanner.style.display = "none";
    });
}


/* =========================================
   CONTENT SLIDER
   ========================================= */

const slides = document.querySelectorAll(".slide");
const dots = document.querySelectorAll(".dot");
const prevButton = document.getElementById("sliderPrev");
const nextButton = document.getElementById("sliderNext");

let currentSlide = 0;
let slideInterval;


/* Show selected slide */

function showSlide(index) {

    if (index >= slides.length) {
        currentSlide = 0;
    } else if (index < 0) {
        currentSlide = slides.length - 1;
    } else {
        currentSlide = index;
    }

    slides.forEach(function (slide) {
        slide.classList.remove("active");
    });

    dots.forEach(function (dot) {
        dot.classList.remove("active");
    });

    slides[currentSlide].classList.add("active");

    if (dots[currentSlide]) {
        dots[currentSlide].classList.add("active");
    }
}


/* Next slide */

function nextSlide() {
    showSlide(currentSlide + 1);
}


/* Previous slide */

function previousSlide() {
    showSlide(currentSlide - 1);
}


/* Buttons */

if (nextButton) {
    nextButton.addEventListener("click", function () {
        nextSlide();
        restartSlider();
    });
}

if (prevButton) {
    prevButton.addEventListener("click", function () {
        previousSlide();
        restartSlider();
    });
}


/* Dots */

dots.forEach(function (dot, index) {

    dot.addEventListener("click", function () {
        showSlide(index);
        restartSlider();
    });

});


/* Auto slide */

function startSlider() {
    slideInterval = setInterval(nextSlide, 5000);
}

function restartSlider() {
    clearInterval(slideInterval);
    startSlider();
}


/* Start */

if (slides.length > 0) {
    showSlide(0);
    startSlider();
}