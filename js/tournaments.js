// Tournament data
const tournaments = [
  {
    id: 1,
    title: 'Mumbai Premier League',
    date: 'May 15-30, 2025',
    location: 'Mumbai, Maharashtra',
    description: 'Annual premier tennis cricket tournament featuring top teams from across Mumbai competing for the championship title.',
    category: 'recent',
    images: [
      'IMG-20250628-WA0037.jpg',
      'IMG-20250628-WA0038.jpg',
      'IMG-20250628-WA0039.jpg',
      'IMG-20250628-WA0040.jpg'
    ],
    buttonText: 'View Gallery',
    buttonClass: 'btn-outline-primary',
    buttonAction: 'gallery'
  },
  {
    id: 2,
    title: 'Maharashtra State Cup',
    date: 'July 10-25, 2025',
    location: 'Pune, Maharashtra',
    description: 'State-level championship bringing together the best tennis cricket talent from across Maharashtra.',
    category: 'upcoming',
    images: [
      'IMG-20250628-WA0050.jpg',
      'IMG-20250628-WA0051.jpg',
      'IMG-20250628-WA0052.jpg',
      'IMG-20250628-WA0053.jpg'
    ],
    buttonText: 'Register Now',
    buttonClass: 'btn-primary',
    buttonAction: 'register'
  },
  {
    id: 3,
    title: 'TCAI Champions Trophy',
    date: 'March 5-20, 2025',
    location: 'Nashik, Maharashtra',
    description: 'The most prestigious tournament in the TCAI calendar featuring top teams from across the state.',
    category: 'highlights',
    images: [
      'IMG-20250628-WA0065.jpg',
      'IMG-20250628-WA0066.jpg',
      'IMG-20250628-WA0067.jpg',
      'IMG-20250628-WA0068.jpg'
    ],
    buttonText: 'Watch Highlights',
    buttonClass: 'btn-outline-primary',
    buttonAction: 'highlights'
  },
  {
    id: 4,
    title: 'Nashik Super League',
    date: 'February 1-15, 2025',
    location: 'Nashik, Maharashtra',
    description: 'Local tournament showcasing emerging talent from Nashik and surrounding areas.',
    category: 'recent',
    images: [
      'IMG-20250628-WA0045.jpg',
      'IMG-20250628-WA0046.jpg',
      'IMG-20250628-WA0047.jpg',
      'IMG-20250628-WA0048.jpg'
    ],
    buttonText: 'View Gallery',
    buttonClass: 'btn-outline-primary',
    buttonAction: 'gallery'
  },
  {
    id: 5,
    title: 'Maharashtra Youth Tournament',
    date: 'August 15-30, 2025',
    location: 'Mumbai, Maharashtra',
    description: 'Exclusive tournament for young talents under 19 years of age.',
    category: 'upcoming',
    images: [
      'IMG-20250628-WA0055.jpg',
      'IMG-20250628-WA0056.jpg',
      'IMG-20250628-WA0057.jpg',
      'IMG-20250628-WA0058.jpg'
    ],
    buttonText: 'Register Now',
    buttonClass: 'btn-primary',
    buttonAction: 'register'
  },
  {
    id: 6,
    title: 'TCAI T20 Challenge',
    date: 'January 10-25, 2025',
    location: 'Pune, Maharashtra',
    description: 'Fast-paced T20 format tournament featuring exciting matches.',
    category: 'highlights',
    images: [
      'IMG-20250628-WA0070.jpg',
      'IMG-20250628-WA0071.jpg',
      'IMG-20250628-WA0072.jpg',
      'IMG-20250628-WA0073.jpg'
    ],
    buttonText: 'Watch Highlights',
    buttonClass: 'btn-outline-primary',
    buttonAction: 'highlights'
  }
];

// Filter tournaments
function filterTournaments(category) {
  const container = document.getElementById('tournament-container');
  if (!container) return;
  
  container.innerHTML = '';
  
  const filteredTournaments = category === 'all' 
    ? [...tournaments] 
    : tournaments.filter(tournament => tournament.category === category);
  
  displayTournaments(filteredTournaments.slice(0, 6));
  
  // Show/hide load more button based on number of items
  const loadMoreBtn = document.getElementById('load-more-btn');
  if (loadMoreBtn) {
    loadMoreBtn.style.display = filteredTournaments.length > 6 ? 'inline-block' : 'none';
  }
}

// Display tournaments
let currentPage = 1;
const itemsPerPage = 6;

function displayTournaments(tournamentsToShow, append = false) {
  const container = document.getElementById('tournament-container');
  if (!container) return;
  
  if (!append) {
    container.innerHTML = '';
    currentPage = 1;
  }
  
  tournamentsToShow.forEach(tournament => {
    const card = `
      <div class="col-lg-4 col-md-6 mb-4" data-category="${tournament.category}">
        <div class="tournament-card h-100">
          <div class="position-relative overflow-hidden" style="height: 250px; border-radius: 10px; overflow: hidden; cursor: pointer;" 
               data-id="${tournament.id}" data-title="${tournament.title}">
            <img src="images/TCAI/${tournament.images[0]}" alt="${tournament.title}" class="img-fluid w-100 h-100" style="object-fit: cover; transition: transform 0.5s ease;">
            <div class="tournament-overlay d-flex flex-column justify-content-end p-4">
              <h4 class="text-white mb-1">${tournament.title}</h4>
              <div class="d-flex align-items-center text-white-50 mb-2">
                <i class="icon-calendar mr-2"></i>
                <span>${tournament.date}</span>
              </div>
              <div class="d-flex align-items-center text-white-50">
                <i class="icon-location-pin mr-2"></i>
                <span>${tournament.location}</span>
              </div>
            </div>
            ${tournament.category === 'upcoming' ? '<span class="badge badge-warning position-absolute" style="top: 15px; right: 15px; padding: 8px 12px; font-size: 0.8rem;">Upcoming</span>' : ''}
          </div>
          <div class="p-4">
            <p class="mb-3">${tournament.description}</p>
            <button class="btn btn-sm ${tournament.buttonClass} view-gallery" 
                    data-id="${tournament.id}" 
                    data-title="${tournament.title}"
                    data-action="${tournament.buttonAction}">
              ${tournament.buttonText}
            </button>
          </div>
        </div>
      </div>
    `;
    container.insertAdjacentHTML('beforeend', card);
  });
  
  // Add event listeners to the new buttons and cards
  document.querySelectorAll('.view-gallery').forEach(button => {
    button.addEventListener('click', function(e) {
      e.stopPropagation();
      const id = this.getAttribute('data-id');
      const title = this.getAttribute('data-title');
      const action = this.getAttribute('data-action');
      
      if (action === 'gallery' || action === 'highlights') {
        openGallery(id, title);
      } else if (action === 'register') {
        // Handle registration
        alert('Registration will open soon for ' + title);
      }
    });
  });
  
  // Add click event to tournament cards
  document.querySelectorAll('.tournament-card .position-relative').forEach(card => {
    card.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      const title = this.getAttribute('data-title');
      openGallery(id, title);
    });
  });
}

// Open gallery modal
function openGallery(id, title) {
  const tournament = tournaments.find(t => t.id === parseInt(id));
  if (!tournament) return;
  
  const modal = document.getElementById('galleryModal');
  const modalTitle = document.getElementById('galleryModalTitle');
  const modalGallery = document.getElementById('modal-gallery');
  const imageCaption = document.getElementById('image-caption');
  
  if (!modal || !modalTitle || !modalGallery || !imageCaption) return;
  
  modalTitle.textContent = title;
  modalGallery.innerHTML = '';
  
  // Add images to carousel
  tournament.images.forEach((image, index) => {
    const item = document.createElement('div');
    item.className = `carousel-item ${index === 0 ? 'active' : ''}`;
    item.innerHTML = `
      <img src="images/TCAI/${image}" class="d-block w-100" alt="${title} - Image ${index + 1}" style="max-height: 70vh; object-fit: contain; margin: 0 auto;">
    `;
    modalGallery.appendChild(item);
  });
  
  // Update caption
  imageCaption.textContent = `${title} - ${tournament.date} at ${tournament.location}`;
  
  // Initialize carousel if Bootstrap is available
  if (typeof $ !== 'undefined' && $.fn.carousel) {
    $('#galleryModal').modal('show');
    $('#carouselExampleControls').carousel();
  }
}

// Initialize the tournaments section
function initTournaments() {
  // Initial load
  filterTournaments('all');
  
  // Filter buttons
  document.querySelectorAll('.filter-btn').forEach(button => {
    button.addEventListener('click', function() {
      // Update active state
      document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
      this.classList.add('active');
      
      // Filter tournaments
      const filter = this.getAttribute('data-filter');
      filterTournaments(filter);
    });
  });
  
  // Load more button
  const loadMoreBtn = document.getElementById('load-more-btn');
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', function() {
      const activeFilter = document.querySelector('.filter-btn.active')?.getAttribute('data-filter') || 'all';
      const filteredTournaments = activeFilter === 'all' 
        ? [...tournaments] 
        : tournaments.filter(t => t.category === activeFilter);
      
      currentPage++;
      const startIndex = (currentPage - 1) * itemsPerPage;
      const endIndex = startIndex + itemsPerPage;
      const moreTournaments = filteredTournaments.slice(startIndex, endIndex);
      
      if (moreTournaments.length > 0) {
        displayTournaments(moreTournaments, true);
      }
      
      // Hide button if no more items
      if (endIndex >= filteredTournaments.length) {
        this.style.display = 'none';
      }
    });
  }
}

// Initialize when DOM is loaded
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initTournaments);
} else {
  initTournaments();
}

// Make functions available globally for inline event handlers
window.openGallery = openGallery;
