import './bootstrap';
import 'preline';
import Toastify from 'toastify-js';
import "toastify-js/src/toastify.css";

window.Toastify = Toastify;

import flatpickr from "flatpickr";
import { Indonesian } from "flatpickr/dist/l10n/id.js";
import "flatpickr/dist/flatpickr.min.css";

window.flatpickr = flatpickr;
flatpickr.localize(Indonesian);

document.addEventListener('DOMContentLoaded', function () {
    flatpickr(".datepicker", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j F Y",
        allowInput: true
    });
});

// Connectivity status handler
window.addEventListener('online', () => {
    if (window.Toastify) {
        window.Toastify({
            text: "Koneksi terhubung kembali! Anda kembali online.",
            duration: 3000,
            gravity: "top",
            position: "center",
            style: { background: "#10b981" }
        }).showToast();
    }
});

window.addEventListener('offline', () => {
    if (window.Toastify) {
        window.Toastify({
            text: "Koneksi terputus. Anda sedang offline.",
            duration: 5000,
            gravity: "top",
            position: "center",
            style: { background: "#ef4444" }
        }).showToast();
    }
});