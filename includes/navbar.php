<!-- Top Header -->
<header
  class="bg-white border-b border-gray-200 h-16 px-6 flex justify-between items-center z-20 shadow-sm flex-shrink-0"
>
  <div class="flex items-center space-x-3">
    <?php if (!isset($hide_building_toggle) || !$hide_building_toggle): ?>
    <button
      id="toggle-sidebar-btn"
      class="flex items-center space-x-2 px-3 py-1.5 rounded-lg border transition-all bg-white border-gray-300 text-gray-700 hover:bg-gray-50"
    >
      <i
        data-lucide="panel-left-open"
        class="w-4 h-4"
        id="sidebar-icon"
      ></i>
      <span class="font-semibold text-sm hidden sm:inline"
        >Edificios</span
      >
    </button>
    <div class="h-6 w-px bg-gray-300 mx-2"></div>
    <?php endif; ?>
    <h2 class="text-lg font-black text-gray-800 flex items-center">
      Módulo: <?php echo isset($module_title) ? $module_title : 'Captura de Lecturas'; ?>
    </h2>
  </div>
  <div class="flex items-center space-x-4">
    <span
      id="navbar-active-period"
      class="text-sm text-gray-500 font-medium bg-gray-100 px-3 py-1 rounded-full hidden sm:inline"
      >Periodo: ---</span
    >
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Usa el base_url o la ruta relativa para funcionar en cualquier página del sistema,
        // independientemente de si historial_config.js está cargado o no.
        const apiBase = window.API_BASE_URL || 'apis_marvi/public/api/';
        
        function fetchPeriodo(url) {
            return fetch(url + 'config/periodo-activo')
                .then(response => {
                    if (!response.ok) throw new Error('Not OK');
                    return response.json();
                })
                .then(data => {
                    if (data && data.periodo) {
                        const el = document.getElementById('navbar-active-period');
                        if (el) el.textContent = 'Periodo: ' + data.periodo;
                    }
                });
        }

        // Primero intenta con el base URL. Si falla (mod_rewrite deshabilitado en XAMPP),
        // reintenta con el fallback explícito con index.php
        fetchPeriodo(apiBase)
            .catch(() => {
                const safeFallback = 'apis_marvi/public/index.php/api/';
                fetchPeriodo(safeFallback).catch(err => console.error('[Navbar] Error al obtener periodo activo:', err));
            });
    });
    </script>
    <div class="flex items-center space-x-6 border-l border-gray-100 pl-4">
        <div class="flex flex-col items-end hidden sm:flex">
            <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Conectado como</span>
            <span class="text-sm font-bold text-slate-800"><?php echo $_SESSION['usuario'] ?? 'Usuario'; ?></span>
        </div>
        <a href="login.php?logout=1" class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-100 transition-all border border-rose-100 group shadow-sm active:scale-95" title="Cerrar Sesión">
            <i data-lucide="power" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
        </a>
    </div>
  </div>
</header>
