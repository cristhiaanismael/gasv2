<!-- FLOATING QUICK BUILDING SELECTOR -->
<div id="quick-building-selector" class="fixed inset-0 z-[100] hidden pointer-events-none">
    <!-- Backdrop (Transparent now, just for clicking outside) -->
    <div class="absolute inset-0 bg-transparent" id="selector-backdrop"></div>
    
    <!-- Floating Panel -->
    <div id="selector-content" class="absolute top-0 left-0 h-full w-[20%] min-w-[300px] bg-slate-900/95 backdrop-blur-xl shadow-[20px_0_50px_rgba(0,0,0,0.3)] transform -translate-x-full transition-all duration-300 ease-out flex flex-col border-r border-white/10 z-20">
        
        <!-- Header -->
        <div class="p-6 border-b border-white/5 bg-white/5">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <i data-lucide="building-2" class="w-5 h-5 text-blue-400"></i>
                    <h2 class="text-white font-black text-sm uppercase tracking-tighter">Selección de Edificio</h2>
                </div>
                <button onclick="closeQuickSelector()" class="text-white/40 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 text-gray-500 absolute left-3 top-1/2 transform -translate-y-1/2"></i>
                <input 
                    type="text" 
                    id="quick-search-input" 
                    placeholder="Filtrar edificio..." 
                    class="w-full pl-9 pr-4 py-2.5 bg-white/5 border border-white/10 rounded-xl focus:border-blue-500 focus:outline-none transition-all text-sm font-bold text-white placeholder-gray-500 shadow-inner"
                    autocomplete="off"
                >
            </div>
        </div>

        <!-- Results List -->
        <div id="quick-results-grid" class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
            <!-- Items inyectados por JS -->
        </div>

        <!-- Footer Info -->
        <div class="p-4 border-t border-white/5 bg-slate-900 flex items-center justify-between">
            <span id="total-buildings-badge" class="text-[10px] font-black text-blue-400 bg-blue-400/10 px-2 py-1 rounded">0 EDIFICIOS</span>
            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest">v2.0 Beta</span>
        </div>
    </div>
</div>

<style>
    #quick-building-selector.show {
        display: block;
        pointer-events: auto;
    }
    
    #quick-building-selector.show #selector-content {
        transform: translateX(0);
    }

    .selector-item {
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }

    .selector-item:hover {
        background-color: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.1);
        transform: translateX(4px);
    }

    .selector-item.active-result {
        background-color: rgba(59, 130, 246, 0.1);
        border-color: rgba(59, 130, 246, 0.3);
    }

    /* Custom Scrollbar for the dark panel */
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.2);
    }
</style>
