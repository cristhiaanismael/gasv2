/**
 * App.js - Core Platform Initializer
 * Handles global UI, Shell effects, and generic components.
 */

// Constante Global de la API
window.API_BASE_URL = window.API_BASE_URL || 'apis_marvi/public/api/';

$(document).ready(function () {
    // 0. BLOQUEO DE DOBLE INICIALIZACIÓN
    if (window.appInitialized) return;
    window.appInitialized = true;
    console.log("App Core inicializado.");

    // 1. RENDERIZADO DEL MENÚ GLOBAL
    function renderGlobalNav() {
        if (typeof APP_MENUS === 'undefined') return;

        const activeId = typeof ACTIVE_MENU_ID !== 'undefined' ? ACTIVE_MENU_ID : '';
        
        const filteredMenus = APP_MENUS.filter(m => activeId !== 'ajustes' || m.id !== 'infraestructura');
        
        const navHtml = filteredMenus.map((menu) => {
            const isActive = activeId === menu.id;
            const bgClass = isActive ? "bg-blue-600/20 text-blue-400" : "hover:bg-gray-800 hover:text-white";
            const iconColor = isActive ? "text-blue-500" : "text-gray-400 group-hover:text-gray-200";
            const badgeHtml = menu.badge ? `<span class="text-[10px] px-2 py-0.5 rounded-full font-bold ${menu.badge === "Activo" || menu.badge === "BETA" ? "bg-blue-500 text-white" : "bg-red-500 text-white"}">${menu.badge}</span>` : '';

            return `
                <button class="menu-btn flex items-center w-full p-3 rounded-xl transition-all duration-200 relative group ${bgClass}" data-id="${menu.id}" data-url="${menu.url}">
                    <i data-lucide="${menu.icon}" class="w-6 h-6 flex-shrink-0 ${iconColor}"></i>
                    <div class="nav-text ml-4 flex items-center justify-between flex-1 transition-opacity duration-200 hidden opacity-0">
                        <span class="font-semibold text-sm whitespace-nowrap ${isActive ? "text-white" : ""}">${menu.label}</span>
                        ${badgeHtml}
                    </div>
                    <div class="nav-tooltip absolute left-14 bg-gray-900 text-white text-xs font-bold px-3 py-2 rounded shadow-xl opacity-0 transition-opacity pointer-events-none whitespace-nowrap z-50">
                        ${menu.label}
                    </div>
                </button>
            `;
        }).join("");

        $("#main-menus-container").html(navHtml);
        lucide.createIcons();
    }

    // 2. REDIRECCIÓN Y NAVEGACIÓN
    $(document).on("click", ".menu-btn", function () {
        const url = $(this).data("url");
        if (url && url !== "#") {
            window.location.href = url;
        } else {
            const id = $(this).data("id");
            const label = $(this).find('.nav-text span').text() || "Módulo";
            showWorkingModal(label);
        }
    });

    // SISTEMA DE MODAL "EN CONSTRUCCIÓN" PREMIUM
    window.showWorkingModal = function(moduleName) {
        const modalId = "working-modal-" + Date.now();
        const modalHtml = `
            <div id="${modalId}" class="fixed inset-0 z-[100] flex items-center justify-center p-4 animate-in fade-in duration-300">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-md" onclick="closeWorkingModal('${modalId}')"></div>
                <div class="bg-white/80 backdrop-blur-2xl border border-white/50 rounded-[32px] p-8 max-w-sm w-full shadow-2xl relative z-10 transform transition-all animate-in zoom-in-95 duration-300">
                    <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center text-white mb-6 shadow-xl shadow-indigo-200 mx-auto">
                        <i data-lucide="construction" class="w-8 h-8"></i>
                    </div>
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-black text-slate-800 mb-3 tracking-tight">${moduleName}</h3>
                        <p class="text-slate-500 font-medium leading-relaxed">
                            Estamos trabajando arduamente para brindarte la mejor experiencia en este módulo. Estará disponible muy pronto.
                        </p>
                    </div>
                    <button 
                        onclick="closeWorkingModal('${modalId}')"
                        class="w-full bg-slate-900 text-white font-black py-4 rounded-2xl hover:bg-indigo-600 transition-all active:scale-95 shadow-lg shadow-slate-200"
                    >
                        Entendido
                    </button>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        lucide.createIcons();
    };

    window.closeWorkingModal = function(id) {
        const $modal = $(`#${id}`);
        $modal.find('> div:last-child').addClass('zoom-out-95 opacity-0');
        $modal.addClass('fade-out');
        setTimeout(() => $modal.remove(), 300);
    };

    // 3. EFECTOS DE LA BARRA LATERAL GLOBAL
    $("#global-nav").on("mouseenter", function () {
        $(".nav-text").removeClass("hidden");
        setTimeout(() => $(".nav-text").removeClass("opacity-0"), 10);
        $(".nav-tooltip").addClass("hidden");
    }).on("mouseleave", function () {
        $(".nav-text").addClass("opacity-0");
        setTimeout(() => {
            $(".nav-text").addClass("hidden");
            $(".nav-tooltip").removeClass("hidden");
        }, 300);
    });

    // 4. SELECTOR DE EDIFICIOS DISRUPTIVO (QUICK SELECTOR)
    const $selector = $("#quick-building-selector");
    const $searchInput = $("#quick-search-input");
    const $resultsGrid = $("#quick-results-grid");
    const $emptyState = $("#quick-empty-state");
    let allBuildings = [];

    function openQuickSelector() {
        $selector.removeClass("hidden").addClass("show");
        $searchInput.val("").focus();
        loadSelectorData();
    }

    function closeQuickSelector() {
        $selector.removeClass("show");
        setTimeout(() => $selector.addClass("hidden"), 300);
    }

    async function loadSelectorData() {
        if (allBuildings.length === 0) {
            $resultsGrid.html('<div class="col-span-full py-10 text-center"><i data-lucide="loader-2" class="w-10 h-10 animate-spin text-white/50 mx-auto"></i></div>');
            lucide.createIcons();
            
            try {
                const response = await fetch(API_BASE_URL + "edificios");
                allBuildings = await response.json();
            } catch (e) {
                console.error(e);
                showToast("Error al cargar edificios", "error");
                closeQuickSelector();
                return;
            }
        }
        $("#total-buildings-badge").text(`${allBuildings.length} EDIFICIOS`);
        renderSelectorResults(allBuildings);
    }

    function renderSelectorResults(list) {
        if (list.length === 0) {
            $resultsGrid.empty();
            $emptyState.removeClass("hidden");
            return;
        }
        $emptyState.addClass("hidden");

        const html = list.map((ed, index) => `
            <div class="selector-item group bg-white/5 rounded-xl p-4 border border-white/5 cursor-pointer hover:bg-white/10 transition-all" data-id="${ed.id_edificio}" data-name="${ed.num_edificio}" data-index="${index}">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center group-hover:bg-blue-600 transition-colors">
                        <i data-lucide="building" class="w-5 h-5 text-white"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-black text-white truncate uppercase tracking-tight">${ed.num_edificio}</h4>
                        <p class="text-[10px] text-white/40 font-medium truncate">${ed.calle} ${ed.num_ext}</p>
                    </div>
                </div>
            </div>
        `).join("");

        $resultsGrid.html(html);
        lucide.createIcons();
    }

    $(document).on("click", "#toggle-sidebar-btn", function (e) {
        e.preventDefault();
        openQuickSelector();
    });

    $(document).on("click", "#selector-backdrop", closeQuickSelector);

    $(document).on("keydown", function (e) {
        if (e.key === "Escape") closeQuickSelector();
    });

    $searchInput.on("input", function() {
        const term = $(this).val().toLowerCase();
        const filtered = allBuildings.filter(ed => 
            ed.num_edificio.toLowerCase().includes(term) || 
            ed.calle.toLowerCase().includes(term) ||
            ed.colonia.toLowerCase().includes(term)
        );
        renderSelectorResults(filtered);
    });

    $(document).on("click", ".selector-item", function() {
        const id = $(this).data("id");
        const name = $(this).data("name");
        
        // Disparar evento global
        const event = new CustomEvent('building-selected', { detail: { id, name } });
        document.dispatchEvent(event);
        
        closeQuickSelector();
    });

    // 5. SISTEMA DE TOASTS GLOBAL
    window.showToast = function(message, type = "success") {
        const toastContainer = $("#toast-container");
        if (!toastContainer.length) {
            $('body').append('<div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>');
        }

        const toastId = "toast-" + Date.now();
        const bgColor = type === "success" ? "bg-green-100 border-green-400 text-green-800" : 
                        type === "error" ? "bg-red-100 border-red-400 text-red-800" : 
                        "bg-blue-100 border-blue-400 text-blue-800";
        const icon = type === "success" ? "check-circle" : "alert-circle";

        const toastHtml = `
            <div id="${toastId}" class="flex items-center p-4 border rounded-lg shadow-lg ${bgColor} transform translate-y-full opacity-0 transition-all duration-300 pointer-events-auto">
                <i data-lucide="${icon}" class="w-5 h-5 mr-3"></i>
                <span class="font-medium text-sm">${message}</span>
                <button class="close-toast ml-4 hover:opacity-75" onclick="removeToast('${toastId}')"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
        `;

        $("#toast-container").append(toastHtml);
        lucide.createIcons();

        setTimeout(() => $(`#${toastId}`).removeClass("translate-y-full opacity-0"), 10);
        setTimeout(() => removeToast(toastId), 4000);
    };

    window.removeToast = function(id) {
        const toast = $(`#${id}`);
        if (toast.length) {
            toast.addClass("translate-y-full opacity-0");
            setTimeout(() => toast.remove(), 300);
        }
    };

    renderGlobalNav();
    lucide.createIcons();
});
