/* Layout JavaScript - Fonctionnalités du layout */

document.addEventListener('DOMContentLoaded', function() {
    // Activer le menu selon l'URL actuelle
    const currentPath = window.location.pathname;
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach(item => {
        const href = item.getAttribute('href');
        if(currentPath.includes(href) || (href === '/' && currentPath === '/')) {
            item.classList.add('active');
        }
    });

    // Bouton de déconnexion
    const logoutBtn = document.querySelector('.logout-btn');
    if(logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            if(confirm('Êtes-vous sûr de vouloir vous déconnecter?')) {
                // Rediriger vers la route de déconnexion
                window.location.href = (window.BASE_URL || '/') + 'logout';
            }
        });
    }

    // Smooth scrolling pour les liens internes
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if(targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Fermer les alertes après 5 secondes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 300);
        }, 5000);
    });

    // Validation des formulaires
    const forms = document.querySelectorAll('.form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const inputs = this.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;

            inputs.forEach(input => {
                if(!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = '#e74c3c';
                } else {
                    input.style.borderColor = '';
                }
            });

            if(!isValid) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs requis!');
            }
        });

        // Réinitialiser la couleur de bordure lors du focus
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.style.borderColor = '';
            });
        });
    });

    // Toggle sidebar sur mobile
    const toggleBtn = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if(toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }

    // Fermer le sidebar en cliquant sur le contenu
    const mainContent = document.querySelector('.main-content');
    if(mainContent) {
        mainContent.addEventListener('click', function() {
            if(window.innerWidth < 768 && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
    }

    // Afficher un loading spinner sur les actions longues
    const deleteButtons = document.querySelectorAll('button[onclick*="confirm"]');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if(confirm(this.getAttribute('onclick').match(/'([^']*)'/)[1])) {
                // Afficher un spinner
                this.disabled = true;
                this.innerHTML = '⏳ Suppression...';
            }
        });
    });
});

// Fonction utilitaire pour afficher les notifications
function showNotification(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const contentWrapper = document.querySelector('.content-wrapper');
    if(contentWrapper) {
        contentWrapper.insertBefore(alertDiv, contentWrapper.firstChild);
        
        setTimeout(() => {
            alertDiv.style.opacity = '0';
            alertDiv.style.transition = 'opacity 0.3s';
            setTimeout(() => {
                alertDiv.remove();
            }, 300);
        }, 5000);
    }
}

// Fonction pour formater les dates
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
}

// Fonction pour formater la devise
function formatCurrency(value) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'MGA'
    }).format(value);
}
