<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acceso Directo con Google | Hyperion Play</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #030712;
        }
        .font-rpg {
            font-family: 'Cinzel', serif;
        }
        .rpg-card {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.85));
            border: 1px solid rgba(245, 158, 11, 0.25);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.6), 0 0 20px 2px rgba(245, 158, 11, 0.1);
        }
        .google-card {
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.7);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative overflow-x-hidden selection:bg-amber-500 selection:text-slate-950">

    <!-- Fondo de runas y luces -->
    <div class="fixed inset-0 pointer-events-none opacity-20">
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-blue-600/30 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-amber-500/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-emerald-500/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-md">
        <!-- Logo Hyperion Superior -->
        <div class="text-center mb-6">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 group">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-400 via-amber-600 to-amber-700 flex items-center justify-center shadow-lg shadow-amber-500/30 border border-amber-300/40 group-hover:scale-105 transition-transform duration-300">
                    <i class="fa-solid fa-shield-halved text-slate-950 text-xl"></i>
                </div>
                <div class="text-left">
                    <span class="font-rpg text-xl font-black tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-amber-200 via-amber-400 to-amber-500 block">
                        HYPERION PLAY
                    </span>
                    <span class="text-[10px] text-slate-400 tracking-widest uppercase font-semibold">Titán Mentor RPG</span>
                </div>
            </a>
        </div>

        <!-- Tarjeta Google Direct -->
        <div class="google-card rounded-2xl p-6 sm:p-8 backdrop-blur-xl space-y-6">
            <!-- Encabezado Google -->
            <div class="text-center space-y-2 pb-4 border-b border-slate-800">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white shadow-md mb-2">
                    <svg class="w-8 h-8" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                </div>
                <h1 class="text-lg font-bold text-white tracking-wide">Acceso Directo con Google</h1>
                <p class="text-xs text-slate-400">
                    Ingresa a tu cuenta de aventurero al instante sin necesidad de recordar contraseñas.
                </p>
            </div>

            <!-- Opción 1: Acceso Instantáneo 1 Clic -->
            <form method="POST" action="/auth/google/direct" class="space-y-2">
                @csrf
                <button type="submit" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-blue-600/30 transition-all flex items-center justify-center gap-3 group">
                    <svg class="w-5 h-5 bg-white rounded-full p-0.5" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span>⚡ Entrar Directo con Google (1 Clic)</span>
                    <i class="fa-solid fa-arrow-right text-[11px] group-hover:translate-x-1 transition-transform"></i>
                </button>
            </form>

            <!-- Separador -->
            <div class="relative flex items-center justify-center">
                <div class="border-t border-slate-800 w-full"></div>
                <span class="bg-[#0f172a] px-3 text-[11px] text-slate-500 uppercase tracking-wider font-semibold">o con tu correo Gmail</span>
            </div>

            <!-- Opción 2: Ingresar correo de Google específico -->
            <form method="POST" action="/auth/google/direct" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-300">
                        Correo de Google (@gmail.com)
                    </label>
                    <div class="relative">
                        <i class="fa-regular fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                        <input type="email" name="email" required placeholder="tu.nombre@gmail.com" class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-9 pr-3 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-300">
                        Nombre o Alias del Aventurero <span class="text-slate-500 font-normal">(Opcional)</span>
                    </label>
                    <div class="relative">
                        <i class="fa-regular fa-user absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                        <input type="text" name="name" placeholder="Ej: Santiago, Valeria..." class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-9 pr-3 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs">
                    </div>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-white hover:bg-slate-100 text-slate-950 font-bold text-xs shadow-md transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span>Vincular y Entrar a Hyperion</span>
                </button>
            </form>

            <!-- Enlaces inferiores -->
            <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                <a href="{{ route('login') }}" class="hover:text-amber-400 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i>
                    <span>Login con contraseña</span>
                </a>
                <a href="{{ route('register') }}" class="text-amber-400 hover:underline font-semibold">
                    Crear nueva cuenta
                </a>
            </div>
        </div>

        <p class="text-center text-[11px] text-slate-600 mt-6">
            Hyperion Play &copy; {{ date('Y') }}. Todos los derechos reservados.
        </p>
    </div>
</body>
</html>
