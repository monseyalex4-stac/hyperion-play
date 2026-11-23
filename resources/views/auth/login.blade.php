<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar Sesión | Hyperion Play</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #030712;
        }
        .font-cinzel {
            font-family: 'Cinzel', serif;
        }
        .rpg-card {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(245, 158, 11, 0.2);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.7), 0 0 20px -5px rgba(245, 158, 11, 0.1);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-950/20 via-slate-950 to-black text-slate-100">

    <div class="w-full max-w-md space-y-6">
        
        <!-- Logo y Título -->
        <div class="text-center space-y-2">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-amber-600 to-amber-400 text-slate-950 text-2xl shadow-lg shadow-amber-500/20 mb-1">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h1 class="font-cinzel font-bold text-2xl text-transparent bg-clip-text bg-gradient-to-r from-amber-200 via-amber-400 to-amber-500 tracking-wider">
                HYPERION PLAY
            </h1>
            <p class="text-xs text-slate-400">Titán Mentor & Forja de Entrenamiento Físico</p>
        </div>

        <!-- Tarjeta de Login -->
        <div class="rpg-card rounded-2xl p-6 sm:p-8 space-y-6">

            <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
                <h2 class="font-bold text-slate-200 text-base flex items-center gap-2">
                    <i class="fa-solid fa-dungeon text-amber-400"></i>
                    <span>Acceder a tu Cuenta</span>
                </h2>
                <a href="{{ route('home') }}" class="text-xs text-slate-400 hover:text-amber-400 transition" title="Volver al inicio">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>

            @if(session('info'))
                <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs">
                    {{ session('info') }}
                </div>
            @endif

            @if($errors->any())
                <div class="p-3 rounded-xl bg-rose-950/40 border border-rose-500/30 text-rose-300 text-xs space-y-1">
                    @foreach($errors->all() as $error)
                        <div><i class="fa-solid fa-circle-exclamation mr-1.5 text-rose-400"></i>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <!-- Botón Google OAuth y Acceso Directo -->
            <div>
                <a href="{{ route('auth.google.redirect') }}" class="w-full py-2.5 px-4 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-semibold text-xs transition flex items-center justify-center gap-3 shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span>Continuar con Google (Acceso Directo)</span>
                </a>
            </div>

            <!-- Separador -->
            <div class="relative flex items-center justify-center">
                <div class="border-t border-slate-800 w-full"></div>
                <span class="bg-slate-900 px-3 text-[10px] text-slate-500 uppercase tracking-widest absolute">o con credenciales</span>
            </div>

            <!-- Formulario de Acceso -->
            <form action="/login" method="POST" class="space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block text-slate-300 font-medium mb-1">Nombre de Aventurero o Correo Electrónico</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <input type="text" name="login" value="{{ old('login') }}" required placeholder="Ej: Santiago, Valeria o tu@email.com" class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-9 pr-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500 text-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-300 font-medium mb-1">Contraseña</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" id="login-password" name="password" required placeholder="Tu contraseña secreta" class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-9 pr-9 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500 text-xs">
                        <button type="button" onclick="togglePasswordVisibility('login-password', 'login-eye')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-white">
                            <i id="login-eye" class="fa-solid fa-eye text-xs"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-bold text-xs shadow-lg shadow-amber-500/20 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-dungeon"></i>
                    <span>Entrar a la Forja</span>
                </button>
            </form>

            <!-- Acceso Rápido Asistido con Google (1 clic) -->
            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 text-[11px] text-slate-400 flex items-center justify-between">
                <span>¿Prefieres acceso directo sin contraseña?</span>
                <button type="button" onclick="abrirGoogleRapidoModal()" class="text-amber-400 hover:underline font-semibold text-xs flex items-center gap-1">
                    <i class="fa-brands fa-google text-[10px]"></i>
                    <span>1 Clic</span>
                </button>
            </div>

            <!-- Enlace a Registro -->
            <div class="pt-2 text-center text-xs text-slate-400 border-t border-slate-800/80">
                ¿Aún no tienes un aventurero?
                <a href="{{ route('register') }}" class="text-amber-400 hover:text-amber-300 font-semibold underline ml-1">
                    Crear Aventurero
                </a>
            </div>

        </div>

    </div>

    <!-- Modal Registro Rápido Google 1-Clic -->
    <div id="modal-google-rapido" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="rpg-card rounded-2xl max-w-sm w-full p-6 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <div class="flex items-center gap-2 text-sm font-bold text-white">
                    <i class="fa-brands fa-google text-amber-400"></i>
                    <span>Acceso Rápido con Google</span>
                </div>
                <button onclick="cerrarGoogleRapidoModal()" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <p class="text-xs text-slate-300">
                Elige cómo deseas acceder con Google:
            </p>

            <!-- Opción 1 Clic Instantáneo -->
            <form method="POST" action="/auth/google/direct" class="pt-1">
                @csrf
                <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-xs shadow-md flex items-center justify-center gap-2 group transition">
                    <svg class="w-4 h-4 bg-white rounded-full p-0.5" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span>⚡ Acceso Instantáneo con Google (1 Clic)</span>
                </button>
            </form>

            <div class="relative flex items-center justify-center my-2">
                <div class="border-t border-slate-800 w-full"></div>
                <span class="bg-slate-900 px-2 text-[10px] text-slate-500 uppercase">o con tu correo específico</span>
            </div>

            <form onsubmit="ejecutarGoogleOneClick(event)" class="space-y-3 text-xs">
                <div>
                    <label class="block text-slate-400 mb-1">Correo de Google *</label>
                    <input type="email" id="g-email" required placeholder="tu.cuenta@gmail.com" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-slate-400 mb-1">Nombre o Alias</label>
                    <input type="text" id="g-name" placeholder="Ej: Alex, Valeria..." class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                </div>
                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" onclick="cerrarGoogleRapidoModal()" class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300">Cancelar</button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-amber-500 text-slate-950 font-bold hover:bg-amber-400">Entrar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function togglePasswordVisibility(inputId, eyeId) {
            const input = document.getElementById(inputId);
            const eye = document.getElementById(eyeId);
            if (input.type === 'password') {
                input.type = 'text';
                eye.classList.remove('fa-eye');
                eye.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                eye.classList.remove('fa-eye-slash');
                eye.classList.add('fa-eye');
            }
        }

        function abrirGoogleRapidoModal() {
            document.getElementById('modal-google-rapido').classList.remove('hidden');
            document.getElementById('g-email').focus();
        }

        function cerrarGoogleRapidoModal() {
            document.getElementById('modal-google-rapido').classList.add('hidden');
        }

        async function ejecutarGoogleOneClick(e) {
            e.preventDefault();
            const email = document.getElementById('g-email').value.trim();
            const name = document.getElementById('g-name').value.trim();
            if (!email) return;

            try {
                const res = await fetch('/auth/google/one-click', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ email, name })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'Error al iniciar con Google.');
                }
            } catch (err) {
                console.error(err);
                alert('Error al conectar con el servidor.');
            }
        }
    </script>
</body>
</html>
