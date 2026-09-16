document.addEventListener("DOMContentLoaded", function () {

    const faqItems = document.querySelectorAll(".faq-item");

    faqItems.forEach(function (item) {

        const question = item.querySelector("h3");
        const answer = item.querySelector("p");
        answer.style.display = "none";

        question.style.cursor = "pointer";

        question.addEventListener("click", function () {

            faqItems.forEach(function (otherItem) {

                if (otherItem !== item) {
                    const otherAnswer = otherItem.querySelector("p");
                    const otherQuestion = otherItem.querySelector("h3");

                    otherAnswer.style.display = "none";
                    otherQuestion.classList.remove("active");
                }

            });

            if (answer.style.display === "none") {

                answer.style.display = "block";
                question.classList.add("active");

            } else {

                answer.style.display = "none";
                question.classList.remove("active");

            }

        });

    });

});