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

// Import Cropper v2 web-components
import CropperCanvas from '@cropper/element-canvas';
import CropperImage from '@cropper/element-image';
import CropperSelection from '@cropper/element-selection';
import CropperHandle from '@cropper/element-handle';
import CropperCrosshair from '@cropper/element-crosshair';

// Manually register the custom elements
CropperCanvas.$define();
CropperImage.$define();
CropperSelection.$define();
CropperHandle.$define();
CropperCrosshair.$define();

console.log("=== CROPPER.JS v2 DEBUG START ===");
console.log("App.js chargé - Cropper web-components registered");

// Alpine.js component pour Cropper.js v2
window.imageCropperPopup = function() {
    return {
        showPopup: false,
        imageURL: null,
        preview: null,

        openFile() {
            console.log("openFile() appelé");
            this.$refs.inputFile.click();
        },

        onFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;

            console.log("\n=== onFileChange START ===");
            console.log("Fichier sélectionné :", file.name, file.size, "bytes");

            if (this.imageURL) URL.revokeObjectURL(this.imageURL);

            this.imageURL = URL.createObjectURL(file);
            this.preview = null;

            this.$nextTick(() => {
                setTimeout(() => {
                    const cropperCanvas = this.$refs.cropperCanvas;
                    const cropImage = this.$refs.cropImage;

                    console.log("\n--- Éléments trouvés ---");
                    console.log("cropperCanvas:", cropperCanvas?.tagName);
                    console.log("cropImage:", cropImage?.tagName);

                    if (!cropperCanvas || !cropImage) {
                        console.error("❌ Éléments manquants !");
                        return;
                    }

                    // Assigner le src
                    console.log("\n--- Assignation src ---");
                    cropImage.setAttribute('src', this.imageURL);
                    console.log("✓ src assigné");

                    // Chercher les méthodes disponibles
                    console.log("\n--- Méthodes disponibles ---");
                    const methodsToTest = [
                        'reset', 'render', 'fit', 'refresh', 'update', 'initialize', 'load',
                        'ready', 'redraw', 'draw', 'recrop', 'crop', 'getData', 'getCanvasData',
                        'getImageData', 'getContainer', 'getCanvas', 'getImage', 'toDataURL',
                        'getCroppedCanvas', 'setData', 'zoom', 'move', 'rotate'
                    ];

                    methodsToTest.forEach(method => {
                        if (typeof cropperCanvas[method] === 'function') {
                            console.log(`✓ cropperCanvas.${method}()`);
                        }
                    });

                    // Inspect cropper-selection
                    const selection = cropperCanvas.querySelector('cropper-selection');
                    console.log("\n--- Cropper Selection ---");
                    console.log("cropper-selection found:", !!selection);
                    if (selection) {
                        console.log("Selection tagName:", selection.tagName);
                        console.log("Selection movable:", selection.hasAttribute('movable'));
                        console.log("Selection resizable:", selection.hasAttribute('resizable'));
                        console.log("Selection style:", selection.getAttribute('style'));
                        console.log("Selection computed display:", window.getComputedStyle(selection).display);
                        console.log("Selection offsetWidth:", selection.offsetWidth);
                        console.log("Selection offsetHeight:", selection.offsetHeight);
                    }

                    // Event listeners
                    console.log("\n--- Event Listeners ---");
                    const events = ['load', 'ready', 'crop', 'cropstart', 'cropend', 'cropmove', 'cropresize'];
                    events.forEach(evt => {
                        cropperCanvas.addEventListener(evt, (e) => {
                            console.log(`📌 Event "${evt}":`, e.detail);
                        });
                    });

                    // Listen for ready event
                    cropperCanvas.addEventListener('ready', () => {
                        console.log("✅ Cropper READY!");
                        const sel = cropperCanvas.querySelector('cropper-selection');
                        if (sel) {
                            console.log("Selection after ready - offsetWidth:", sel.offsetWidth, "offsetHeight:", sel.offsetHeight);
                        }
                    });

                    // Force selection to fill the canvas (workaround for initialization bug)
                    setTimeout(() => {
                        const sel = cropperCanvas.querySelector('cropper-selection');
                        if (sel) {
                            console.log("⚙️ Forcing selection size...");
                            // Set initial coverage to fill most of canvas
                            sel.setAttribute('initial-coverage', '0.8');
                            
                            // Force recalculation by triggering a manual crop event
                            const canvas = cropperCanvas.querySelector('cropper-image');
                            if (canvas) {
                                // Dispatch a custom event to trigger cropper's internal layout
                                cropperCanvas.dispatchEvent(new Event('load', { bubbles: true }));
                            }
                            
                            // Log final state
                            console.log("After force - Selection offsetWidth:", sel.offsetWidth, "offsetHeight:", sel.offsetHeight);
                            console.log("After force - Selection style:", sel.getAttribute('style'));
                        }
                    }, 200);

                    // Générer la preview
                    this.updatePreview();

                    console.log("\n=== onFileChange END ===\n");
                }, 100);
            });
        },

        updatePreview() {
            if (!this.imageURL) {
                console.warn("updatePreview: pas d'imageURL");
                return;
            }

            console.log("updatePreview() appelé");

            const img = new Image();
            img.crossOrigin = 'anonymous';

            img.onload = () => {
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = 144;
                    canvas.height = 144;
                    const ctx = canvas.getContext('2d');

                    const ratio = Math.max(img.width / 144, img.height / 144);
                    const sw = 144 * ratio;
                    const sh = 144 * ratio;
                    const sx = Math.max(0, (img.width - sw) / 2);
                    const sy = Math.max(0, (img.height - sh) / 2);

                    ctx.drawImage(img, sx, sy, sw, sh, 0, 0, 144, 144);
                    this.preview = canvas.toDataURL('image/png');
                    console.log("✓ preview générée (144x144)");
                } catch (err) {
                    console.error("Erreur génération preview:", err);
                }
            };

            img.onerror = () => {
                console.error("Erreur chargement image pour preview");
            };

            img.src = this.imageURL;
        },
                async saveCrop() {
            if (!this.preview && !this.imageURL) {
                alert('Aucune image sélectionnée !');
                return;
            }

            console.log('=== saveCrop START ===');

            const cropperCanvas = this.$refs.cropperCanvas;
            const selection = cropperCanvas?.querySelector('cropper-selection');

            if (!selection) {
                console.error("❌ Selection not found");
                alert('Erreur: sélection non trouvée');
                return;
            }

            const selStyle = selection.getAttribute('style') || '';
            // parse numbers with optional decimals and optional negative sign
            const transformMatch = selStyle.match(/translate\((-?\d+(?:\.\d+)?)px,\s*(-?\d+(?:\.\d+)?)px\)/);
            const widthMatch = selStyle.match(/width:\s*(-?\d+(?:\.\d+)?)px/);
            const heightMatch = selStyle.match(/height:\s*(-?\d+(?:\.\d+)?)px/);

            const selX = transformMatch ? parseFloat(transformMatch[1]) : 0;
            const selY = transformMatch ? parseFloat(transformMatch[2]) : 0;
            const selW = widthMatch ? parseFloat(widthMatch[1]) : selection.offsetWidth || 144;
            const selH = heightMatch ? parseFloat(heightMatch[1]) : selection.offsetHeight || 144;

            console.log(`Selection raw: x=${selX}, y=${selY}, w=${selW}, h=${selH}`);

            // Try to find the internal <img> element inside cropper-image's shadowRoot
            let internalImg = null;
            try {
                const cropperImageEl = cropperCanvas.querySelector('cropper-image') || this.$refs.cropImage;
                internalImg = cropperImageEl && cropperImageEl.shadowRoot
                    ? cropperImageEl.shadowRoot.querySelector('img')
                    : cropperCanvas.querySelector('img') || null;
            } catch (e) {
                internalImg = cropperCanvas.querySelector('img') || null;
            }

            if (!internalImg) {
                console.warn('internal image element not found, fallback to using imageURL and natural size');
            }

            // Get bounding boxes
            const containerRect = cropperCanvas.getBoundingClientRect();
            const imgRect = internalImg ? internalImg.getBoundingClientRect() : containerRect;

            const imgDisplayLeft = imgRect.left - containerRect.left;
            const imgDisplayTop = imgRect.top - containerRect.top;
            const imgDisplayWidth = imgRect.width;
            const imgDisplayHeight = imgRect.height;

            const naturalW = internalImg && internalImg.naturalWidth ? internalImg.naturalWidth : imgDisplayWidth;
            const naturalH = internalImg && internalImg.naturalHeight ? internalImg.naturalHeight : imgDisplayHeight;

            console.log(`container: ${containerRect.width}x${containerRect.height}`);
            console.log(`img display: ${imgDisplayWidth}x${imgDisplayHeight} @ (${imgDisplayLeft},${imgDisplayTop})`);
            console.log(`img natural: ${naturalW}x${naturalH}`);

            // scale from display -> natural
            const scaleX = naturalW / imgDisplayWidth;
            const scaleY = naturalH / imgDisplayHeight;

            // map selection coordinates to image coordinates (account for image offset)
            const cropX = Math.max(0, (selX - imgDisplayLeft) * scaleX);
            const cropY = Math.max(0, (selY - imgDisplayTop) * scaleY);
            const cropW = Math.max(1, selW * scaleX);
            const cropH = Math.max(1, selH * scaleY);

            console.log(`Mapped crop on source: x=${cropX}, y=${cropY}, w=${cropW}, h=${cropH}`);

            // Use the internalImg if available (already loaded). Otherwise create a new Image from imageURL.
            const sourceImg = internalImg && internalImg.complete ? internalImg : await new Promise((resolve, reject) => {
                const i = new Image();
                i.crossOrigin = 'anonymous';
                i.onload = () => resolve(i);
                i.onerror = reject;
                i.src = this.imageURL;
            });

            // Create output canvas (144x144 preview size)
            const outSize = 144;
            const outputCanvas = document.createElement('canvas');
            outputCanvas.width = outSize;
            outputCanvas.height = outSize;
            const ctx = outputCanvas.getContext('2d');

            // Clamp crop to source bounds
            const sx = Math.max(0, Math.min(sourceImg.naturalWidth - 1, cropX));
            const sy = Math.max(0, Math.min(sourceImg.naturalHeight - 1, cropY));
            const sw = Math.max(1, Math.min(sourceImg.naturalWidth - sx, cropW));
            const sh = Math.max(1, Math.min(sourceImg.naturalHeight - sy, cropH));

            try {
                ctx.drawImage(sourceImg, sx, sy, sw, sh, 0, 0, outSize, outSize);
                const croppedBase64 = outputCanvas.toDataURL('image/png');
                console.log('✓ Cropped image extracted (manual mapping)');
                this.preview = croppedBase64;

                // keep or close modal: update preview and close
                this.close();
            } catch (err) {
                console.error('Error while drawing cropped image:', err);
                alert('Erreur lors de l\'extraction du crop');
            }

            console.log('=== saveCrop END ===');
        },
        close() {
            console.log("close() appelé");
            this.showPopup = false;
            this.imageURL = null;
            this.preview = null;
        }
    }
}

// Start Alpine
console.log('Démarrage d\'Alpine.js');
Alpine.start();
