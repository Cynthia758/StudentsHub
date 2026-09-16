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