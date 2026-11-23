<!DOCTYPE html>
<html lang="es" class="h-full bg-[#131314]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión con Google | Hyperion Play</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #131314;
        }
        .google-card {
            background-color: #1e1f20;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.8);
        }
        .account-item:hover {
            background-color: rgba(255, 255, 255, 0.06);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-3 sm:p-6 text-slate-200">

    <div class="w-full max-w-2xl google-card rounded-3xl overflow-hidden p-6 sm:p-10">
        <!-- Top bar: Google Branding -->
        <div class="flex items-center gap-2 mb-8 text-xs font-medium text-slate-300">
            <svg class="w-4 h-4" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span>Iniciar sesión con Google</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
            <!-- Columna Izquierda: Logo y Título -->
            <div class="md:col-span-5 space-y-4">
                <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-700 flex items-center justify-center shadow-md">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center">
                        <i class="fa-solid fa-shield-halved text-slate-950 text-sm"></i>
                    </div>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-normal text-white tracking-tight">
                        Selecciona una cuenta
                    </h1>
                    <p class="text-sm text-slate-400 mt-1.5 flex items-center gap-1.5">
                        <span>Ir a</span>
                        <span class="text-slate-200 font-medium">Hyperion Play</span>
                    </p>
                </div>
            </div>

            <!-- Columna Derecha: Lista de Cuentas de Google -->
            <div class="md:col-span-7 space-y-1 divide-y divide-slate-800/80">
                
                <!-- Cuenta 1: Santiago Jiménez Balbuena con Foto Real -->
                <div onclick="seleccionarCuenta('santiagojimenezbalbuena@gmail.com', 'Santiago Jiménez Balbuena', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=150&auto=format&fit=crop')" class="account-item p-3 rounded-xl cursor-pointer transition flex items-center gap-3.5 group">
                    <div class="w-9 h-9 rounded-full overflow-hidden border border-amber-500/40 shadow-sm shrink-0">
                        <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=150&auto=format&fit=crop" alt="Santiago" class="w-full h-full object-cover">
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-white group-hover:text-amber-300 transition truncate">
                            Santiago Jiménez Balbuena
                        </div>
                        <div class="text-xs text-slate-400 truncate">
                            santiagojimenezbalbuena@gmail.com
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-right text-[11px] text-slate-500 group-hover:text-amber-400 group-hover:translate-x-0.5 transition-all"></i>
                </div>

                <!-- Cuenta 2: santi jimenez (santjim0609@gmail.com) -->
                <div onclick="seleccionarCuenta('santjim0609@gmail.com', 'santi jimenez', 'https://api.dicebear.com/7.x/initials/svg?seed=santi&backgroundColor=0284c7&textColor=ffffff')" class="account-item p-3 rounded-xl cursor-pointer transition flex items-center gap-3.5 group">
                    <div class="w-9 h-9 rounded-full bg-sky-600 text-white font-bold flex items-center justify-center text-sm shadow-sm shrink-0">
                        s
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-white group-hover:text-sky-300 transition truncate">
                            santi jimenez
                        </div>
                        <div class="text-xs text-slate-400 truncate">
                            santjim0609@gmail.com
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-right text-[11px] text-slate-500 group-hover:text-sky-400 group-hover:translate-x-0.5 transition-all"></i>
                </div>

                <!-- Cuenta 3: santi jimenez (santijimutn@gmail.com) -->
                <div onclick="seleccionarCuenta('santijimutn@gmail.com', 'santi jimenez', 'https://api.dicebear.com/7.x/initials/svg?seed=santijim&backgroundColor=78350f&textColor=ffffff')" class="account-item p-3 rounded-xl cursor-pointer transition flex items-center gap-3.5 group">
                    <div class="w-9 h-9 rounded-full bg-amber-800 text-white font-bold flex items-center justify-center text-sm shadow-sm shrink-0">
                        s
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-white group-hover:text-amber-300 transition truncate">
                            santi jimenez
                        </div>
                        <div class="text-xs text-slate-400 truncate">
                            santijimutn@gmail.com
                        </div>
                    </div>
                    <span class="text-[10px] text-slate-500 font-medium">Sesión cerrada</span>
                </div>

                <!-- Cuenta 4: Santiago Balbuena (santiagobalbuena178@gmail.com) -->
                <div onclick="seleccionarCuenta('santiagobalbuena178@gmail.com', 'Santiago Balbuena', 'https://api.dicebear.com/7.x/initials/svg?seed=SantiagoBalbuena&backgroundColor=7e22ce&textColor=ffffff')" class="account-item p-3 rounded-xl cursor-pointer transition flex items-center gap-3.5 group">
                    <div class="w-9 h-9 rounded-full bg-purple-700 text-white font-bold flex items-center justify-center text-sm shadow-sm shrink-0">
                        S
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-white group-hover:text-purple-300 transition truncate">
                            Santiago Balbuena
                        </div>
                        <div class="text-xs text-slate-400 truncate">
                            santiagobalbuena178@gmail.com
                        </div>
                    </div>
                    <span class="text-[10px] text-slate-500 font-medium">Sesión cerrada</span>
                </div>

                <!-- Cuenta 5: Santiago Jiménez (santijim153@gmail.com) -->
                <div onclick="seleccionarCuenta('santijim153@gmail.com', 'Santiago Jiménez', 'https://api.dicebear.com/7.x/initials/svg?seed=SantiagoJimenez&backgroundColor=2563eb&textColor=ffffff')" class="account-item p-3 rounded-xl cursor-pointer transition flex items-center gap-3.5 group">
                    <div class="w-9 h-9 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-sm shadow-sm shrink-0">
                        S
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-white group-hover:text-blue-300 transition truncate">
                            Santiago Jiménez
                        </div>
                        <div class="text-xs text-slate-400 truncate">
                            santijim153@gmail.com
                        </div>
                    </div>
                    <span class="text-[10px] text-slate-500 font-medium">Sesión cerrada</span>
                </div>

                <!-- Cuenta 6: Usar otra cuenta -->
                <div onclick="toggleOtraCuenta()" class="account-item p-3 rounded-xl cursor-pointer transition flex items-center gap-3.5 group">
                    <div class="w-9 h-9 rounded-full bg-slate-800 text-slate-300 flex items-center justify-center text-sm shadow-sm shrink-0">
                        <i class="fa-solid fa-user-plus text-xs"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-slate-300 group-hover:text-white transition">
                            Usar otra cuenta
                        </div>
                    </div>
                </div>

                <!-- Formulario desplegable para otra cuenta -->
                <div id="form-otra-cuenta" class="hidden p-3 pt-4 space-y-3">
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-300">Correo de Google (@gmail.com):</label>
                        <input type="email" id="custom-email" placeholder="tu.cuenta@gmail.com" class="w-full bg-[#131314] border border-slate-700 rounded-lg px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500 text-xs">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-300">Nombre o Alias:</label>
                        <input type="text" id="custom-name" placeholder="Ej: Carlos, Valeria..." class="w-full bg-[#131314] border border-slate-700 rounded-lg px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500 text-xs">
                    </div>
                    <button type="button" onclick="enviarOtraCuenta()" class="w-full py-2 px-3 rounded-lg bg-amber-500 text-slate-950 font-bold text-xs hover:bg-amber-400 transition">
                        Continuar con esta cuenta
                    </button>
                </div>

            </div>
        </div>

        <!-- Indicador de carga -->
        <div id="loading-overlay" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex flex-col items-center justify-center gap-3">
            <div class="w-10 h-10 border-3 border-amber-500/20 border-t-amber-500 rounded-full animate-spin"></div>
            <p id="loading-text" class="text-xs text-slate-200 font-medium">Iniciando sesión con Google...</p>
        </div>

        <!-- Footer -->
        <div class="mt-8 pt-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-500">
            <a href="{{ route('login') }}" class="hover:text-slate-300 transition">
                ← Volver al login normal
            </a>
            <span>Hyperion Play &bull; Google Sign-In</span>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function toggleOtraCuenta() {
            const form = document.getElementById('form-otra-cuenta');
            form.classList.toggle('hidden');
            if (!form.classList.contains('hidden')) {
                document.getElementById('custom-email').focus();
            }
        }

        async function seleccionarCuenta(email, name, avatar) {
            mostrarCargando(`Iniciando sesión como ${name}...`);

            try {
                const res = await fetch('/auth/google/one-click', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ email, name, avatar })
                });

                const data = await res.json();
                if (data.status === 'success') {
                    window.location.href = data.redirect;
                } else {
                    ocultarCargando();
                    alert(data.message || 'Error al conectar con la cuenta.');
                }
            } catch (err) {
                console.error(err);
                ocultarCargando();
                alert('Error al conectar con el servidor.');
            }
        }

        function enviarOtraCuenta() {
            const email = document.getElementById('custom-email').value.trim();
            const name = document.getElementById('custom-name').value.trim() || email.split('@')[0];
            if (!email) {
                alert('Por favor ingresa tu correo de Google.');
                return;
            }
            const avatar = `https://api.dicebear.com/7.x/initials/svg?seed=${encodeURIComponent(name)}&backgroundColor=059669&textColor=ffffff`;
            seleccionarCuenta(email, name, avatar);
        }

        function mostrarCargando(txt) {
            document.getElementById('loading-text').innerText = txt;
            document.getElementById('loading-overlay').classList.remove('hidden');
        }

        function ocultarCargando() {
            document.getElementById('loading-overlay').classList.add('hidden');
        }
    </script>
</body>
</html>
