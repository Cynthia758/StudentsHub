const eventGrid = document.getElementById("eventGrid");
const searchInput = document.getElementById("searchInput");
const categoryFilter = document.getElementById("categoryFilter");
const sortSelect = document.getElementById("sortSelect");
const pagination = document.getElementById("pagination");

let events = [];
let filteredEvents = [];
let currentPage = 1;

const eventsPerPage = 6;

// Fetch API

fetch("data/events.json")
    .then(response => response.json())
    .then(data => {

        events = data;
        filteredEvents = [...events];

        displayEvents();
    })
    .catch(error => {
        console.log("Error loading events:", error);
    });


// Search

searchInput.addEventListener("input", function () {

    currentPage = 1;
    applyFilters();

});


// Filter

categoryFilter.addEventListener("change", function () {

    currentPage = 1;
    applyFilters();

});


// Sorting

sortSelect.addEventListener("change", function () {

    currentPage = 1;
    applyFilters();

});


// Apply Search + Filter + Sort

function applyFilters() {

    const searchText = searchInput.value.toLowerCase();
    const category = categoryFilter.value;
    const sortValue = sortSelect.value;

    filteredEvents = events.filter(event => {

        const matchesSearch =
            event.title.toLowerCase().includes(searchText);

        const matchesCategory =
            category === "All" || event.category === category;

        return matchesSearch && matchesCategory;

    });

    if (sortValue === "title") {

        filteredEvents.sort((a, b) =>
            a.title.localeCompare(b.title)
        );

    }

    else if (sortValue === "date") {

        filteredEvents.sort((a, b) =>
            new Date(a.date) - new Date(b.date)
        );

    }

    displayEvents();

}


// Dynamic Rendering

function displayEvents() {

    eventGrid.innerHTML = "";

    const start = (currentPage - 1) * eventsPerPage;
    const end = start + eventsPerPage;

    const pageEvents =
        filteredEvents.slice(start, end);

    if (pageEvents.length === 0) {

        eventGrid.innerHTML =
            "<h3>No Events Found</h3>";

        pagination.innerHTML = "";

        return;

    }

    pageEvents.forEach(event => {

        const formattedDate =
            new Date(event.date).toLocaleDateString(
                "en-GB",
                {
                    day: "numeric",
                    month: "long",
                    year: "numeric"
                }
            );

        eventGrid.innerHTML += `

        <article class="event-card">

            <img src="${event.image}" alt="${event.title}">

            <div class="event-info">

                <span>${formattedDate}</span>

                <h3>${event.title}</h3>

                <p>${event.description}</p>

                <button>Register</button>

            </div>

        </article>

        `;

    });

    displayPagination();

}


// Pagination

function displayPagination() {

    pagination.innerHTML = "";

    const totalPages =
        Math.ceil(filteredEvents.length / eventsPerPage);

    for (let i = 1; i <= totalPages; i++) {

        const button =
            document.createElement("button");

        button.innerText = i;

        if (i === currentPage) {

            button.style.background = "#c8a64d";
            button.style.color = "white";

        }

        button.addEventListener("click", function () {

            currentPage = i;
            displayEvents();

        });

        pagination.appendChild(button);

    }

}