<?php
session_start();

// Logout logic
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if (isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true) {
    header("Location: lecturas.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso | Marvifet Inn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; overflow: hidden; }
        .organic-bg {
            background-color: #f8fafc;
            background-image: 
                radial-gradient(at 0% 0%, hsla(199,100%,95%,1) 0, transparent 50%), 
                radial-gradient(at 100% 100%, hsla(262,100%,95%,1) 0, transparent 50%);
        }
        .blob {
            position: absolute;
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(168, 85, 247, 0.1) 100%);
            filter: blur(80px);
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            animation: blobby 20s infinite alternate;
            z-index: -1;
        }
        @keyframes blobby {
            0% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; transform: translate(0, 0) scale(1); }
            33% { border-radius: 50% 50% 50% 50% / 30% 70% 30% 70%; transform: translate(100px, 50px) scale(1.1); }
            66% { border-radius: 70% 30% 30% 70% / 70% 30% 70% 30%; transform: translate(-50px, 150px) scale(0.9); }
            100% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; transform: translate(0, 0) scale(1); }
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.05);
        }
        .input-organic {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .input-organic:focus {
            background: #fff;
            border-color: #6366f1;
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.1);
            transform: translateY(-2px);
        }
        .shake { animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both; }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
    </style>
</head>
<body class="organic-bg min-h-screen flex items-center justify-center p-6 relative">
    
    <!-- Blobs Orgánicos Dynamicos -->
    <div class="blob top-[-10%] left-[-10%]" style="animation-delay: 0s;"></div>
    <div class="blob bottom-[-10%] right-[-10%]" style="animation-delay: -5s; background: linear-gradient(135deg, rgba(244, 63, 94, 0.05) 0%, rgba(249, 115, 22, 0.05) 100%);"></div>

    <div class="w-full max-w-lg relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-1 gap-12 items-center">
            
            <div class="glass-card rounded-[40px] p-10 md:p-12 animate-in fade-in slide-in-from-bottom-8 duration-1000">
                <div class="mb-10">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                            <i data-lucide="zap" class="w-6 h-6"></i>
                        </div>
                        <h2 class="text-3xl font-black text-slate-800 tracking-tight">Acceso <span class="text-indigo-600">Portal</span></h2>
                    </div>
                    <p class="text-slate-500 font-medium leading-relaxed">Bienvenido de nuevo. Ingresa tus credenciales para administrar Marvifet Inn.</p>
                </div>

                <div id="login-container" class="space-y-8">
                    <div class="space-y-6">
                        <div class="group">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1 group-focus-within:text-indigo-600 transition-colors">Usuario Maestro</label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    id="input-usuario" 
                                    required
                                    placeholder="Nombre de usuario"
                                    class="input-organic w-full rounded-2xl py-5 px-6 outline-none font-semibold text-slate-700 placeholder:text-slate-300"
                                >
                            </div>
                        </div>

                        <div class="group">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1 group-focus-within:text-indigo-600 transition-colors">Clave de Acceso</label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    id="input-password" 
                                    required
                                    placeholder="••••••••"
                                    class="input-organic w-full rounded-2xl py-5 px-6 outline-none font-semibold text-slate-700 placeholder:text-slate-300"
                                >
                            </div>
                        </div>
                    </div>

                    <div id="error-msg" class="hidden animate-in zoom-in duration-300">
                        <p class="text-rose-500 text-sm font-bold flex items-center justify-center py-2 bg-rose-50 rounded-xl border border-rose-100">
                            <i data-lucide="shield-alert" class="w-4 h-4 mr-2"></i>
                            Acceso denegado. Intenta de nuevo.
                        </p>
                    </div>

                    <button 
                        type="button"
                        id="btn-login-action"
                        class="w-full bg-slate-900 text-white font-black py-5 rounded-2xl transition-all shadow-2xl shadow-slate-200 hover:bg-indigo-600 active:scale-[0.98] flex items-center justify-center space-x-3 text-lg"
                    >
                        <span>Entrar ahora</span>
                        <i data-lucide="arrow-right" class="w-5 h-5"></i>
                    </button>
                </div>
                
                <div class="mt-12 flex justify-between items-center text-[10px] font-black text-slate-300 uppercase tracking-widest border-t border-slate-100 pt-8">
                    <span>Marvifet Inn &copy; 2026</span>
                    <span class="flex items-center">
                        <span class="w-1 h-1 rounded-full bg-green-400 mr-2 animate-pulse"></span>
                        Servidor Activo
                    </span>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        $(document).ready(function() {
            const apiUrl = 'apis_marvi/public/index.php/api/login';

            $('#btn-login-action').on('click', function() {
                const btn = $(this);
                const originalContent = btn.html();
                
                // Obtener valores explícitamente
                const usuario = $('#input-usuario').val();
                const password = $('#input-password').val();

                if (!usuario || !password) {
                    $('#error-msg').removeClass('hidden').find('span').text('Completa todos los campos');
                    return;
                }
                
                $('#error-msg').addClass('hidden');
                btn.prop('disabled', true).html('<i data-lucide="loader-2" class="w-6 h-6 animate-spin"></i>');
                lucide.createIcons();

                $.ajax({
                    url: apiUrl,
                    method: 'POST',
                    data: {
                        usuario: usuario,
                        password: password
                    },
                    dataType: 'json',
                    success: function(res) {
                        console.log("Acceso concedido:", res);
                        window.location.href = 'lecturas.php';
                    },
                    error: function(err) {
                        console.error("Fallo autenticación:", err);
                        $('.glass-card').addClass('shake');
                        $('#error-msg').removeClass('hidden');
                        setTimeout(() => $('.glass-card').removeClass('shake'), 400);
                        btn.prop('disabled', false).html(originalContent);
                        lucide.createIcons();
                    }
                });
            });

            // Tecla enter solo dispara el click
            $('#input-usuario, #input-password').on('keydown', function (e) {
                if (e.keyCode === 13) {
                    $('#btn-login-action').trigger('click');
                }
            });
        });
    </script>
</body>
</html>
