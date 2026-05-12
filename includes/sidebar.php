<?php include_once 'menu_config.php'; ?>
<script>
    const APP_MENUS = <?php echo json_encode($menus); ?>;
    const ACTIVE_MENU_ID = '<?php echo isset($active_menu_id) ? $active_menu_id : ""; ?>';
</script>
<!-- GLOBAL MAIN NAVIGATION (Los 8 Menús) -->
<nav
  id="global-nav"
  class="bg-[#0F172A] text-gray-300 h-full flex flex-col z-30 transition-all duration-300 ease-in-out border-r border-gray-800 w-20 hover:w-64"
>
  <div
    class="h-16 flex items-center justify-center border-b border-gray-800 overflow-hidden px-4"
  >
    <div
      class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0"
    >
      <span class="text-white font-black text-xl">G</span>
    </div>
    <span
      class="nav-text ml-3 font-bold text-white text-lg tracking-tight whitespace-nowrap opacity-0 transition-opacity duration-300 hidden"
      >GasManager</span
    >
  </div>

  <div
    id="main-menus-container"
    class="flex-1 py-6 flex flex-col gap-2 overflow-y-auto custom-scrollbar overflow-x-hidden px-3"
  >
    <!-- Menús inyectados por JS -->
  </div>

  <div
    class="p-4 border-t border-gray-800 flex items-center justify-center overflow-hidden"
  >
    <div
      class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0"
    >
      A
    </div>
    <div
      class="nav-text ml-3 overflow-hidden opacity-0 transition-opacity duration-300 hidden"
    >
      <p class="text-sm font-bold text-white whitespace-nowrap">
        Administrador
      </p>
      <p
        class="text-xs text-gray-400 whitespace-nowrap cursor-pointer hover:text-white transition-colors"
      >
        Cerrar Sesión
      </p>
    </div>
  </div>
</nav>
