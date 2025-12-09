// assets/app.js

// Tailwind CSS pur
import './styles/app-tailwind.css';

// Tes styles personnalisés
import './styles/app.css';

// Alpine + Sortable
import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

window.Alpine = Alpine;
window.Sortable = Sortable;

// CropperJS pour le recadrage d'images
let cropper;

// Bouton "Charger une image" --> ouvre le sélecteur
document.getElementById('chargeImageBtn').addEventListener('click', () => {
    document.getElementById('fileInput').click();
});

// Quand une image est choisie
document.getElementById("fileInput").addEventListener("change", function (event) {
    const file = event.target.files[0];
    if (!file) return;

    const url = URL.createObjectURL(file);
    const img = document.getElementById("crop-selection-image");
    img.src = url;
    img.style.display = "block";

    // Détruire l'ancien cropper s'il existe
    if (cropper) cropper.destroy();

    // Initialiser CropperJS
    cropper = new Cropper(img, {
    aspectRatio: 1,
    viewMode: 2,
    autoCropArea: 0.5,
    background: false,
    responsive: true,
    zoomable: true,
    movable: false,
    crop(event) {
        const previewBox = document.getElementById("preview");
        const canvas = cropper.getCroppedCanvas({
        width: 144,
        height: 144
        });

        previewBox.innerHTML = "";
        previewBox.appendChild(canvas);
    }
    });
});

// Bouton "Fermer" --> ferme la popup
document.getElementById("closePopupBtn").addEventListener("click", function () {
    clearPopup();
});

// Bouton "Valider" --> récupère Base64 + ferme la popup
document.getElementById("savePopupBtn").addEventListener("click", async function () {
    if (!cropper) {
        alert("Veuillez d'abord charger une image.");
        return;
    }

    const itemName = document.getElementById("imageNameInput").value.trim();

    const pathParts = window.location.pathname.split("/");
    const tierListId = pathParts[2];

    // 1️⃣ Convertir le canvas en blob de manière synchrone
    const blob = await new Promise(resolve => {
        cropper.getCroppedCanvas({
            width: 144,
            height: 144
        }).toBlob(resolve);
    });

    // 2️⃣ Envoyer le FormData au back
    const formData = new FormData();
    formData.append('name', itemName);
    formData.append('image', blob, 'item.png');
    formData.append('tierListId', tierListId);

    const response = await fetch('/tier-item/create', {
        method: 'POST',
        body: formData,
    });

    const data = await response.json();

    const container = document.getElementById("unassigned-items");
    const div = document.createElement("div");

    div.classList.add("tier-item");
    div.dataset.itemId = "tierItemId_" + data.id;
    div.style.backgroundImage = `url('${data.imageUrl}')`;
    div.innerHTML = `
        <span class="w-full">${data.name}</span>
    `;

    container.appendChild(div);

    clearPopup();
});

// Nettoyage popup
function clearPopup() {
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }

    document.getElementById("preview").innerHTML = "";
    document.getElementById("crop-selection-image").style.display = "none";
    document.getElementById("crop-selection-image").src = "";
    document.getElementById("fileInput").value = "";
    document.getElementById("imageNameInput").value = "";
}

Alpine.data('tierEditor', (initialName, tierId, initialColor = '#ffffff') => ({
    editing: false,
    name: initialName,
    color: initialColor,
    originalColor: initialColor,
    openColorPicker: false,
    presets: ['#FFB3B3','#FFD9B3','#FFFFB3','#B3FFB3','#B3FFFF','#B3B3FF','#E6B3FF', '#FFFFFF'],

    init() {
        // attendre que le DOM soit rendu puis resize
        this.$nextTick(() => {
            this.resize();
        });

        // watcher pour resize automatique si le nom change
        this.$watch('name', () => {
            this.resize();
        });
    },

    enableEdit() {
        this.editing = true;
        this.$nextTick(() => {
            this.$refs.input.focus();
            this.$refs.input.select();
        });
    },

    save() {
        this.editing = false;
        this.resize();

        fetch('/tier/update-name', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                tierId: tierId,
                name: this.name
            })
        });
    },

    resize() {
        if (!this.$refs.txt) return;
        let span = this.$refs.txt;
        let newSize = 150 / span.textContent.length;
        span.style.fontSize = Math.min(100, Math.max(36, newSize)) + 'px';
        span.style.lineHeight = span.style.fontSize;
    },

    saveColor() {
        fetch('/tier/update-color', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                tierId: tierId,
                color: this.color
            })
        });
        this.originalColor = this.color;
        this.openColorPicker = false;
    },

    closeColorPicker() {
        this.color = this.originalColor; // revient à la dernière couleur sauvegardée
        this.openColorPicker = false;
    }
}));

Alpine.data('tierContextMenu', (tierId) => ({
    menuOpen: false,
    menuTop: 0,
    menuLeft: 0,
    openDeletePopup: false,

    // Ouvre le menu contextuel à la position du clic
    openContextMenu(event) {
        this.menuTop = event.offsetY;
        this.menuLeft = event.offsetX;
        this.menuOpen = true;

        // Fermer le menu si on clique ailleurs
        const closeMenu = (e) => {
            if (!this.$el.contains(e.target)) {
                this.menuOpen = false;
                document.removeEventListener('click', closeMenu);
            }
        };
        document.addEventListener('click', closeMenu);
    },

    // Ajouter un nouveau tier au dessus ou en dessous
    async addTier(position) {
        const response = await fetch('/tier/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                referenceTierId: tierId,
                position: position
            })
        });

        const data = await response.json();

        // Récupère l'id de la tierlist depuis l'URL
        const pathParts = window.location.pathname.split('/');
        const tierListId = pathParts[2];

        // Recharge proprement tous les tiers
        await reloadTiers(tierListId);

        this.menuOpen = false;
    },

    // Supprimer un tier avec reload
    async deleteTier() {
    try {
        const response = await fetch(`/tier/${tierId}`, {
            method: 'DELETE',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        // Vérifie que la réponse est bien JSON
        if (!response.ok) throw new Error('HTTP error ' + response.status);

        const data = await response.json();

        if (data.success) {
            this.openDeletePopup = false;

            // Ajouter les tierItems déplacés dans la zone non classée
            const container = document.getElementById("unassigned-items");
            data.tierItems.forEach(item => {
                const div = document.createElement("div");
                div.classList.add("tier-item");
                div.dataset.itemId = "tierItemId_" + item.id;
                div.style.backgroundImage = `url('${item.imageUrl}')`;
                div.innerHTML = `<span class="w-full">${item.name}</span>`;
                container.appendChild(div);
            });

            // Recharge tous les tiers pour garder les positions correctes
            const pathParts = window.location.pathname.split('/');
            const tierListId = pathParts[2];
            await reloadTiers(tierListId);

        } else {
            alert('Erreur lors de la suppression du tier');
        }
    } catch (err) {
        console.error(err);
        // Optionnel : tu peux commenter l'alerte si tu veux juste loguer
        // alert('Erreur lors de la suppression du tier');
    }
}
}));

// Reload tiers (inchangé)
window.reloadTiers = async function(tierListId) {
    const html = await fetch(`/tierlist/${tierListId}/tiers-html`)
        .then(r => r.text());

    const container = document.getElementById("tiers-container");
    container.innerHTML = html;

    // Réinitialiser Alpine sur le HTML reconstruit (v3)
    Alpine.initTree(container);

    // Réinitialiser Sortable après réinsertion
    Sortable.create(container, {
        animation: 150,
        handle: '.tier-header',
        draggable: '.tier',
        onEnd: evt => {
            const tiers = [...container.children];
            const positions = tiers.map((tierEl, index) => ({
                tierId: tierEl.querySelector('.tier-header').dataset.tierId,
                position: index
            }));

            fetch('/tier/update-positions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ positions })
            });
        }
    });
}


// Start Alpine
console.log('Démarrage d\'Alpine.js');
Alpine.start();
