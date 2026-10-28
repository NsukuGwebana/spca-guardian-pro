document.addEventListener('DOMContentLoaded', () => {
    const searchForm = document.getElementById('search-form');
    const searchInput = document.getElementById('search-input');
    const adoptionCardsContainer = document.querySelector('.adoption-cards');
    const adoptionCards = document.querySelectorAll('.adoption-cards .card');

    searchForm.addEventListener('submit', (e) => {
        // Prevent the form from submitting normally and reloading the page
        e.preventDefault();

        const searchTerm = searchInput.value.toLowerCase().trim();

        // If the search box is empty, show all cards
        if (searchTerm === '') {
            adoptionCards.forEach(card => card.style.display = '');
            return;
        }

        // Loop through each pet card and hide the ones that don't match the search term
        let hasResults = false;
        adoptionCards.forEach(card => {
            // Get the text content of the card's title and description
            const petName = card.querySelector('h3').textContent.toLowerCase();
            const petDescription = card.querySelector('p').textContent.toLowerCase();

            // Check if the search term is found in the pet's name or description
            if (petName.includes(searchTerm) || petDescription.includes(searchTerm)) {
                card.style.display = ''; // Show the card
                hasResults = true;
            } else {
                card.style.display = 'none'; // Hide the card
            }
        });

        // Add a message if no results are found
        let noResultsMessage = document.getElementById('no-results-message');
        if (hasResults) {
            if (noResultsMessage) {
                noResultsMessage.remove();
            }
        } else {
            if (!noResultsMessage) {
                noResultsMessage = document.createElement('p');
                noResultsMessage.id = 'no-results-message';
                noResultsMessage.textContent = 'Sorry, no animals match your search.';
                adoptionCardsContainer.appendChild(noResultsMessage);
            }
        }
    });
});


    document.getElementById('search-form').addEventListener('submit', function(event) {
        event.preventDefault(); // Stop the form from trying to submit and reload the page

        const searchTerm = document.getElementById('search-input').value.toLowerCase().trim();

        if (searchTerm === 'adopt') {
            window.location.href = '#adopt';
        } else if (searchTerm === 'community') {
            window.location.href = '#community';
        } else if (searchTerm === 'impact') {
            window.location.href = '#stats';
        } else if (searchTerm === 'find us' || searchTerm === 'map') {
            window.location.href = '#map';
        } else if (searchTerm === 'contact') {
            window.location.href = '#contact';
        }else if (searchTerm === 'Report Animal Cruelty' || searchTerm === 'report cruelty') {
            window.location.href = 'cruelty_report_form.php';
         } else {
            alert('No matching section found. Please try "Adopt", "Community", "Impact", or "Contact".');
        }
    });
    
