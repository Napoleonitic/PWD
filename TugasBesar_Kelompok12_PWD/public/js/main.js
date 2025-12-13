function findNearestCinema() {
    const info = document.getElementById("geo-info");
    if (!info) return;

    const CINEMAS = [
        { name: "CinePals XXI - Ambarukmo Plaza", lat: -7.7829, lng: 110.4028 },
        { name: "CinePals XXI - Malioboro", lat: -7.7924, lng: 110.3658 },
        { name: "CinePals Premiere - Sleman City Hall", lat: -7.7468, lng: 110.3665 },
        { name: "CinePals XXI - Rita Supermall Purwokerto", lat: -7.4271, lng: 109.2461 },
        { name: "CinePals - Alun-alun Purwokerto", lat: -7.4249, lng: 109.2340 },
        { name: "CinePals - Purwokerto Barat", lat: -7.4020, lng: 109.2260 }
    ];

    if (!navigator.geolocation) {
        info.textContent = "Browser tidak mendukung geolocation.";
        return;
    }
    info.textContent = "Mencari lokasi kamu...";

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const { latitude, longitude } = pos.coords;

            console.log("Your GEO:", latitude, longitude);

            function toRad(deg) {
                return (deg * Math.PI) / 180;
            }

            function distanceKm(lat1, lon1, lat2, lon2) {
                // Pastikan tipe number
                lat1 = parseFloat(lat1);
                lon1 = parseFloat(lon1);
                lat2 = parseFloat(lat2);
                lon2 = parseFloat(lon2);

                if (![lat1, lon1, lat2, lon2].every(Number.isFinite)) {
                    console.warn('distanceKm: non-finite input', { lat1, lon1, lat2, lon2 });
                    return Infinity;
                }

                const R = 6371; // km
                const dLat = toRad(lat2 - lat1);
                const dLon = toRad(lon2 - lon1);
                const radLat1 = toRad(lat1);
                const radLat2 = toRad(lat2);

                const s1 = Math.sin(dLat / 2);
                const s2 = Math.sin(dLon / 2);
                let a = s1 * s1 + Math.cos(radLat1) * Math.cos(radLat2) * s2 * s2;

                // Floating point safety: clamp a into [0,1]
                a = Math.min(1, Math.max(0, a));

                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                const d = R * c;
                return d;
            }

            CINEMAS.forEach(c => {
                const dDbg = distanceKm(latitude, longitude, c.lat, c.lng);
                console.info('distance to', c.name, dDbg, 'km');
            });

            let nearest = null;
            let minDist = Infinity;

            CINEMAS.forEach(c => {
                const d = distanceKm(latitude, longitude, c.lat, c.lng);
                if (d < minDist) {
                    minDist = d;
                    nearest = c;
                }
            });

            if (nearest) {
                info.textContent =
                    `Lokasi terdeteksi! Bioskop terdekat: ${nearest.name} (± ${minDist.toFixed(1)} km).`;
            } else {
                info.textContent = "Lokasi terdeteksi, tetapi daftar bioskop tidak ditemukan.";
            }
        },
        () => {
            info.textContent = "Gagal mengakses lokasi.";
        }
    );
}

function checkEmail(input) {
    const email = input.value;
    const info = document.getElementById("email-info");
    if (!info) return;

    if (!email) {
        info.textContent = "";
        return;
    }

    fetch("api_check_email.php?email=" + encodeURIComponent(email))
        .then(res => res.json())
        .then(data => {
            if (data.exists) {
                info.textContent = "Email sudah terdaftar, gunakan email lain.";
                info.style.color = "#b00020";
            } else {
                info.textContent = "Email tersedia.";
                info.style.color = "#0c7a35";
            }
        })
        .catch(() => {
            info.textContent = "";
        });
}

function formatRupiah(num) {
    if (isNaN(num)) return "Rp 0";
    return "Rp " + Number(num).toLocaleString("id-ID");
}

function updateBookingSummary() {
    const summary = document.getElementById("booking-summary");
    if (!summary) return;

    const cinemaSel = document.getElementById("cinema-select");
    const filmSel = document.getElementById("film-select");
    const dateInput = document.getElementById("date-input");
    const timeSel = document.getElementById("time-select");
    const qtyInput = document.getElementById("qty-input");
    const foodSel = document.getElementById("food-select");
    const drinkSel = document.getElementById("drink-select");
    const selectedSeatsInput = document.getElementById("selected-seats-input");

    const cinemaText = cinemaSel && cinemaSel.value ? cinemaSel.options[cinemaSel.selectedIndex].text : "-";
    const filmText = filmSel && filmSel.value ? filmSel.options[filmSel.selectedIndex].text : "-";
    const dateText = dateInput && dateInput.value ? dateInput.value : "-";
    const timeText = timeSel && timeSel.value ? (timeSel.options[timeSel.selectedIndex].text || timeSel.value) : "-";
    const qty = qtyInput && qtyInput.value ? parseInt(qtyInput.value, 10) : 0;
    const seats = selectedSeatsInput && selectedSeatsInput.value ? selectedSeatsInput.value.split(",") : [];

    const foodText = foodSel && foodSel.value ? foodSel.options[foodSel.selectedIndex].text : "Tanpa Makanan";
    const drinkText = drinkSel && drinkSel.value ? drinkSel.options[drinkSel.selectedIndex].text : "Tanpa Minuman";

    const tiketPricePer = 50000;
    const tiketTotal = qty * tiketPricePer;

    let foodPrice = 0;
    let drinkPrice = 0;
    if (foodSel) {
        const opt = foodSel.options[foodSel.selectedIndex];
        foodPrice = parseInt(opt.dataset.price || "0", 10);
    }
    if (drinkSel) {
        const opt = drinkSel.options[drinkSel.selectedIndex];
        drinkPrice = parseInt(opt.dataset.price || "0", 10);
    }
    const addonTotal = foodPrice + drinkPrice;
    const grandTotal = tiketTotal + addonTotal;

    summary.innerHTML = `
        <div><strong>Ringkasan Booking</strong></div>
        <div style="margin-top:4px;">
            <div>Film: ${filmText}</div>
            <div>Bioskop: ${cinemaText}</div>
            <div>Tanggal &amp; Jam: ${dateText} • ${timeText}</div>
            <div>Jumlah Tiket: ${qty} tiket (Rp 50.000 / tiket)</div>
            <div>Kursi: ${seats.length ? seats.join(", ") : "-"}</div>
            <div>Makanan: ${foodText}</div>
            <div>Minuman: ${drinkText}</div>
        </div>
        <div style="margin-top:6px;">
            <div>Subtotal Tiket: ${formatRupiah(tiketTotal)}</div>
            <div>Subtotal Add-on: ${formatRupiah(addonTotal)}</div>
            <div><strong>Total Bayar: ${formatRupiah(grandTotal)}</strong></div>
        </div>
    `;
}

function initFilmPreview() {
    const select = document.getElementById("film-select");
    if (!select) return;

    const iframe = document.getElementById("preview-iframe");
    const emptyText = document.getElementById("preview-empty");
    const titleText = document.getElementById("preview-title");
    const synopsisText = document.getElementById("preview-synopsis");
    const metaText = document.getElementById("preview-meta");
    const card = document.getElementById("preview-card");

    function updatePreview() {
        const opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) {
            if (iframe) {
                iframe.style.display = "none";
                iframe.src = "";
            }
            if (emptyText) emptyText.style.display = "block";
            if (card) card.style.display = "none";
            if (titleText) titleText.textContent = "";
            if (synopsisText) synopsisText.textContent = "";
            if (metaText) metaText.textContent = "";
            updateBookingSummary();
            return;
        }

        const trailer = opt.dataset.trailer || "";
        const title = opt.dataset.title || "Trailer Film";
        const synopsis = opt.dataset.synopsis || "";
        const year = opt.dataset.year || "";
        const rating = opt.dataset.rating || "";

        if (titleText) titleText.textContent = title;
        if (synopsisText) synopsisText.textContent = synopsis;
        if (metaText) {
            const meta = [];
            if (year) meta.push(year);
            if (rating) meta.push("Rating: " + rating);
            metaText.textContent = meta.join(" • ");
        }
        if (card) card.style.display = "block";
        if (emptyText) emptyText.style.display = "none";

        if (trailer && iframe) {
            const match = trailer.match(/(?:v=|be\/)([A-Za-z0-9_-]{11})/);
            if (match) {
                const videoId = match[1];
                iframe.src = "https://www.youtube.com/embed/" + videoId;
                iframe.style.display = "block";
            } else {
                iframe.style.display = "none";
                iframe.src = "";
            }
        } else if (iframe) {
            iframe.style.display = "none";
            iframe.src = "";
        }
        updateBookingSummary();
    }

    select.addEventListener("change", () => {
        updatePreview();
        if (typeof loadBookedSeats === "function") {
            loadBookedSeats();
        }
    });
    updatePreview();
}

let globalSelectedSeats = new Set();
let globalBookedSeats = new Set();

function initSeatMap() {
    const seatGrid = document.getElementById("seat-grid");
    const hiddenInput = document.getElementById("selected-seats-input");
    const text = document.getElementById("selected-seat-text");
    const qtyInput = document.getElementById("qty-input");
    if (!seatGrid || !hiddenInput || !text || !qtyInput) return;

    const rows = ["A", "B", "C", "D", "E"];
    const cols = [1, 2, 3, 4, 5, 6, 7, 8];

    function updateSelectedHidden() {
        const arr = Array.from(globalSelectedSeats);
        hiddenInput.value = arr.join(",");
        if (arr.length === 0) {
            text.textContent = "Belum ada kursi yang dipilih.";
        } else {
            text.innerHTML = `Kursi terpilih: <strong>${arr.join(", ")}</strong>`;
        }
        updateBookingSummary();
    }

    function render() {
        seatGrid.innerHTML = "";
        rows.forEach(row => {
            const rowDiv = document.createElement("div");
            rowDiv.className = "seat-row";

            const label = document.createElement("div");
            label.className = "seat-row-label";
            label.textContent = row;
            rowDiv.appendChild(label);

            cols.forEach(col => {
                const code = row + col;
                const btn = document.createElement("button");
                btn.type = "button";
                btn.textContent = col;
                btn.className = "seat";
                btn.dataset.code = code;

                if (globalBookedSeats.has(code)) {
                    btn.classList.add("booked");
                    btn.disabled = true;
                } else if (globalSelectedSeats.has(code)) {
                    btn.classList.add("selected");
                }

                btn.addEventListener("click", () => {
                    if (globalBookedSeats.has(code)) return;
                    const maxSeats = parseInt(qtyInput.value || "1", 10);
                    if (globalSelectedSeats.has(code)) {
                        globalSelectedSeats.delete(code);
                    } else {
                        if (globalSelectedSeats.size >= maxSeats) return;
                        globalSelectedSeats.add(code);
                    }
                    updateSelectedHidden();
                    render();
                });

                rowDiv.appendChild(btn);
            });

            seatGrid.appendChild(rowDiv);
        });
    }

    qtyInput.addEventListener("change", () => {
        const maxSeats = parseInt(qtyInput.value || "1", 10);
        while (globalSelectedSeats.size > maxSeats) {
            const first = globalSelectedSeats.values().next().value;
            globalSelectedSeats.delete(first);
        }
        updateSelectedHidden();
        render();
    });

    window.loadBookedSeats = function () {
        const cinemaSel = document.getElementById("cinema-select");
        const filmSel = document.getElementById("film-select");
        const dateInput = document.getElementById("date-input");
        const timeSel = document.getElementById("time-select");
        if (!cinemaSel || !filmSel || !dateInput || !timeSel) return;

        const cinemaId = cinemaSel.value;
        const filmId = filmSel.value;
        const dateVal = dateInput.value;
        const timeVal = timeSel.value;

        globalSelectedSeats.clear();

        if (!cinemaId || !filmId || !dateVal || !timeVal) {
            globalBookedSeats = new Set();
            updateSelectedHidden();
            render();
            return;
        }

        const url =
            "api_booked_seats.php?cinema_id=" + encodeURIComponent(cinemaId) +
            "&film_id=" + encodeURIComponent(filmId) +
            "&date=" + encodeURIComponent(dateVal) +
            "&time=" + encodeURIComponent(timeVal);

        fetch(url)
            .then(res => res.json())
            .then(data => {
                const arr = Array.isArray(data.seats) ? data.seats : [];
                globalBookedSeats = new Set(arr);
                updateSelectedHidden();
                render();
            })
            .catch(() => {
                globalBookedSeats = new Set();
                updateSelectedHidden();
                render();
            });
    };

    updateSelectedHidden();
    render();
}

/* DATE STRIP – auto 7 hari dari hari ini */
function initDateStrip() {
    const strip = document.getElementById("date-strip");
    const input = document.getElementById("date-input");
    if (!strip || !input) return;

    const dayNames = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];
    const today = new Date();
    const todayISO = today.toISOString().split("T")[0];
    input.min = todayISO;

    strip.innerHTML = "";

    for (let i = 0; i < 7; i++) {
        const d = new Date();
        d.setDate(today.getDate() + i);
        const iso = d.toISOString().split("T")[0];
        const day = dayNames[d.getDay()];
        const dateNum = String(d.getDate()).padStart(2, "0");

        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "date-chip";
        if (i === 0) btn.classList.add("active");
        btn.dataset.date = iso;
        btn.innerHTML = `
            <div class="day">${day}</div>
            <div class="date">${dateNum}</div>
        `;
        btn.addEventListener("click", () => {
            document.querySelectorAll(".date-chip").forEach(c => c.classList.remove("active"));
            btn.classList.add("active");
            input.value = iso;
            updateBookingSummary();
            if (typeof loadBookedSeats === "function") {
                loadBookedSeats();
            }
        });

        strip.appendChild(btn);
    }

    input.value = todayISO;
    updateBookingSummary();
}

/* TIME STRIP – 3 jam preset */
function initTimeStrip() {
    const strip = document.getElementById("time-strip");
    const select = document.getElementById("time-select");
    if (!strip || !select) return;

    const chips = strip.querySelectorAll(".time-chip");

    function setTime(value) {
        select.value = value;
        updateBookingSummary();
        if (typeof loadBookedSeats === "function") {
            loadBookedSeats();
        }
    }

    chips.forEach(chip => {
        chip.addEventListener("click", () => {
            chips.forEach(c => c.classList.remove("active"));
            chip.classList.add("active");
            setTime(chip.dataset.time);
        });
    });

    if (chips.length > 0) {
        chips[0].classList.add("active");
        setTime(chips[0].dataset.time);
    }
}

/* ====== HALAMAN C.FOOD ====== */
function initCFoodPage() {
    const tabs = document.querySelectorAll(".cfood-tab");
    const productCards = document.querySelectorAll(".cfood-card");
    const sectionTitle = document.querySelector(".cfood-section-title");
    const searchInput = document.getElementById("cfood-search");

    const emptyBox = document.getElementById("cfood-cart-empty");
    const cartItemsBox = document.getElementById("cfood-cart-items");
    const cartCountText = document.getElementById("cfood-cart-count");
    const cartTotalText = document.getElementById("cfood-cart-total");
    const cartSubmitBtn = document.getElementById("cfood-cart-submit");

    const checkoutForm = document.getElementById("cfood-checkout-form");
    const itemsField = document.getElementById("cfood-items-json");

    if (!tabs.length || !productCards.length || !cartItemsBox || !emptyBox ||
        !cartCountText || !cartTotalText || !cartSubmitBtn || !checkoutForm || !itemsField) {
        return;
    }

    // ====== FILTER KATEGORI ======
    function setActiveCategory(cat) {
        tabs.forEach(t => {
            t.classList.toggle("active", t.dataset.category === cat);
        });
        if (sectionTitle) {
            sectionTitle.textContent = cat;
        }
        applyFilter();
    }

    tabs.forEach(tab => {
        tab.addEventListener("click", () => {
            const cat = tab.dataset.category;
            setActiveCategory(cat);
        });
    });

    // ====== SEARCH + FILTER ======
    function applyFilter() {
        const activeTab = document.querySelector(".cfood-tab.active");
        const activeCat = activeTab ? activeTab.dataset.category : null;
        const q = (searchInput ? searchInput.value : "").toLowerCase().trim();

        productCards.forEach(card => {
            const cardCat = card.dataset.category;
            const name = (card.dataset.name || "").toLowerCase();
            const desc = (card.dataset.desc || "").toLowerCase();

            let visible = true;

            if (activeCat && cardCat !== activeCat) {
                visible = false;
            }

            if (q && !(name.includes(q) || desc.includes(q))) {
                visible = false;
            }

            card.style.display = visible ? "flex" : "none";
        });
    }

    if (searchInput) {
        searchInput.addEventListener("input", applyFilter);
    }

    const firstTab = tabs[0];
    if (firstTab) {
        setActiveCategory(firstTab.dataset.category);
    }

    // ====== KERANJANG ======
    const cart = {};

    // Prefill kalau sedang edit pesanan (data dari PHP)
    if (window.CFOOD_EDIT_ITEMS && Array.isArray(window.CFOOD_EDIT_ITEMS)) {
        window.CFOOD_EDIT_ITEMS.forEach(item => {
            if (!item || !item.id) return;
            const id = String(item.id);
            const name = item.name || "";
            const price = parseInt(item.price || "0", 10);
            const qty = parseInt(item.qty || "0", 10);
            if (!id || !name || !price || !qty) return;

            cart[id] = { name, price, qty };
        });
    }

    function formatRupiahCFood(num) {
        if (isNaN(num)) return "Rp 0";
        return "Rp " + Number(num).toLocaleString("id-ID");
    }

    function renderCart() {
        const ids = Object.keys(cart);
        if (ids.length === 0) {
            emptyBox.style.display = "block";
            cartItemsBox.style.display = "none";
            cartCountText.textContent = "0 item dipilih";
            cartTotalText.textContent = "Rp 0";
            cartSubmitBtn.disabled = true;
            return;
        }

        emptyBox.style.display = "none";
        cartItemsBox.style.display = "flex";
        cartItemsBox.innerHTML = "";

        let totalQty = 0;
        let totalPrice = 0;

        ids.forEach(id => {
            const item = cart[id];
            totalQty += item.qty;
            totalPrice += item.qty * item.price;

            const row = document.createElement("div");
            row.className = "cfood-cart-item";
            row.innerHTML = `
                <div class="cfood-cart-item-title">${item.name}</div>
                <div class="cfood-cart-item-qty">x${item.qty}</div>
                <div class="cfood-cart-item-price">${formatRupiahCFood(item.qty * item.price)}</div>
            `;
            cartItemsBox.appendChild(row);
        });

        cartCountText.textContent = totalQty + " item dipilih";
        cartTotalText.textContent = formatRupiahCFood(totalPrice);
        cartSubmitBtn.disabled = false;
    }

    const addButtons = document.querySelectorAll(".cfood-add-btn");
    addButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const price = parseInt(btn.dataset.price || "0", 10);
            if (!id || !name || !price) return;

            if (!cart[id]) {
                cart[id] = { name, price, qty: 0 };
            }
            cart[id].qty += 1;
            renderCart();
        });
    });

    checkoutForm.addEventListener("submit", (e) => {
        const ids = Object.keys(cart);
        if (ids.length === 0) {
            e.preventDefault();
            return;
        }

        const itemsPayload = ids.map(id => ({
            id: id,
            name: cart[id].name,
            price: cart[id].price,
            qty: cart[id].qty
        }));

        itemsField.value = JSON.stringify(itemsPayload);
        
    });


    renderCart();
}

/* ================= PROFILE PAGE ================= */
function initProfilePage() {
    const container = document.querySelector(".profile-page");
    console.log("Container:", container);

    if (!container) {
        return;
    }

    const img = document.getElementById("profilePreview");
    const input = document.getElementById("photoInput");
    const trigger = document.getElementById("openPhotoPicker");

    console.log("img:", img);
    console.log("input:", input);
    console.log("trigger:", trigger);

    if (!img || !input || !trigger) {
        console.warn("Profile Picture tidak lengkap.");
        return;
    }

    trigger.addEventListener("click", () => {
        console.log("File picker dibuka");
        input.click();
    });

    input.addEventListener("change", () => {
        const file = input.files[0];
        if (!file) {
            console.warn("Tidak ada file dipilih");
            return;
        }

        console.log("File dipilih:", file.name);
        img.src = URL.createObjectURL(file);

        const form = document.querySelector("form");
        if (form) {
            console.log("Form disubmit");
            form.submit();
        } else {
            console.warn("Form tidak ditemukan!");
        }
    });
}

document.addEventListener("DOMContentLoaded", () => {
    if (document.querySelector(".profile-page")) {
        initProfilePage();
    }

    initFilmPreview();
    initSeatMap();
    initDateStrip();
    initTimeStrip();

    const cinemaSel = document.getElementById("cinema-select");
    const dateInput = document.getElementById("date-input");
    const timeSel = document.getElementById("time-select");
    const qtyInput = document.getElementById("qty-input");
    const foodSel = document.getElementById("food-select");
    const drinkSel = document.getElementById("drink-select");
    const carousel = document.querySelector(".film-carousel");
    const btnLeft = document.querySelector(".btn-left"); //button left crsl
    const btnRight = document.querySelector(".btn-right"); //button right crsl

    if (carousel){
        carousel.addEventListener("wheel", (el) => {
                el.preventDefault();
                carousel.scrollLeft += el.deltaY;
            },
            {passive: false});

        carousel.querySelectorAll("*").forEach(child => {
                child.addEventListener("wheel",(ev) => {
                        ev.preventDefault();
                        carousel.scrollLeft += ev.deltaY;
                    },
                    {passive: false});
            });
    }

    if (btnLeft && btnRight && carousel) {
        btnLeft.addEventListener("click", () => {
            carousel.scrollBy({ left: -300, behavior: "smooth" });
        });
        btnRight.addEventListener("click", () => {
            carousel.scrollBy({ left: 300, behavior: "smooth" });
        });
    }

    [cinemaSel].forEach(el => {
        if (el) {
            el.addEventListener("change", () => {
                updateBookingSummary();
                if (typeof loadBookedSeats === "function") {
                    loadBookedSeats();
                }
            });
        }
    });

    [dateInput, timeSel].forEach(el => {
        if (el) {
            el.addEventListener("change", () => {
                updateBookingSummary();
                if (typeof loadBookedSeats === "function") {
                    loadBookedSeats();
                }
            });
        }
    });

    [qtyInput, foodSel, drinkSel].forEach(el => {
        if (el) {
            el.addEventListener("change", updateBookingSummary);
        }
    });

    updateBookingSummary();

    // halaman C.Food
    initCFoodPage();
});
