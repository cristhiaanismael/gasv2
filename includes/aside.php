<!-- SECUNDARIO: Sidebar de Edificios -->
<aside
  id="building-sidebar"
  class="bg-white border-r border-gray-200 flex flex-col h-full flex-shrink-0 transition-all duration-300 ease-in-out w-0 -translate-x-full absolute opacity-0 z-10"
>
  <div class="p-4 border-b border-gray-100 bg-gray-50/50">
    <div class="flex justify-between items-center mb-3">
      <h3
        class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center"
      >
        Ruta de Trabajo (<span id="total-buildings-count">0</span>)
      </h3>
      <button
        id="close-sidebar-mobile"
        class="text-gray-400 hover:text-gray-700"
      >
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
    </div>
    <div class="relative">
      <i
        data-lucide="search"
        class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2"
      ></i>
      <input
        type="text"
        id="search-building-input"
        placeholder="Buscar edificio..."
        class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm bg-white"
      />
    </div>
  </div>

  <div
    id="building-list-container"
    class="flex-1 overflow-y-auto p-3 space-y-1 custom-scrollbar"
  >
    <!-- Lista de edificios inyectada por JS -->
  </div>
</aside>
