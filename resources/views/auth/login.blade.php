<!DOCTYPE html>
<html lang="es" class="h-full bg-[#131314]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión o registrarse | Hyperion Play</title>
    <link rel="icon" type="image/png" href="/images/logo.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #131314;
        }
        .auth-card {
            background-color: #202124;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 20px 45px -15px rgba(0, 0, 0, 0.8);
        }
        .btn-pill-dark {
            background-color: #2a2b2e;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .btn-pill-dark:hover {
            background-color: #35363a;
        }
        .account-item:hover {
            background-color: rgba(255, 255, 255, 0.06);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 text-slate-100">

    <!-- Tarjeta Principal (Diseño exacto Imagen 2) -->
    <div class="auth-card rounded-3xl p-6 sm:p-10 max-w-md w-full relative space-y-6">
        
        <!-- Botón cerrar (X) en círculo superior derecho -->
        <a href="{{ route('home') }}" class="absolute top-5 right-5 w-8 h-8 rounded-full bg-slate-800/80 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition text-sm">
            <i class="fa-solid fa-xmark"></i>
        </a>

        <!-- Encabezado -->
        <div class="text-center pt-2 space-y-2">
            <img src="/images/logo.png" alt="Hyperion Play Logo" class="w-16 h-16 object-contain mx-auto mb-2 rounded-2xl border border-amber-500/30 bg-slate-900 p-1 shadow-lg shadow-amber-500/10">
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">
                Iniciar sesión o registrarse
            </h1>
            <p class="text-xs text-slate-400 leading-relaxed px-2">
                Obtendrás rutinas adaptativas guiadas por Hyperion, sobrecarga progresiva asistida y registrarás todo tu progreso RPG.
            </p>
        </div>

        @if(session('info'))
            <div class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs text-center">
                {{ session('info') }}
            </div>
        @endif

        @if($errors->any())
            <div class="p-3 rounded-2xl bg-rose-950/40 border border-rose-500/30 text-rose-300 text-xs space-y-1 text-center">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <!-- Botones de Acceso Rápido (Pills estilo ChatGPT - Imagen de Referencia) -->
        <div class="space-y-2.5 pt-1">
            <!-- Botón 1: Continuar con Google -->
            <button type="button" onclick="abrirSelectorGoogle()" class="w-full py-3 px-4 rounded-full btn-pill-dark text-white font-medium text-xs transition flex items-center justify-center gap-3 shadow-sm hover:border-slate-500">
                <svg class="w-4 h-4" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>Continuar con Google</span>
            </button>

            <!-- Botón 2: Continuar con Apple -->
            <button type="button" onclick="iniciarConApple()" class="w-full py-3 px-4 rounded-full btn-pill-dark text-white font-medium text-xs transition flex items-center justify-center gap-2.5 shadow-sm hover:border-slate-500">
                <i class="fa-brands fa-apple text-base"></i>
                <span>Continuar con Apple</span>
            </button>

            <!-- Botón 3: Continuar con el teléfono -->
            <button type="button" onclick="iniciarConTelefono()" class="w-full py-3 px-4 rounded-full btn-pill-dark text-white font-medium text-xs transition flex items-center justify-center gap-2.5 shadow-sm hover:border-slate-500">
                <i class="fa-solid fa-phone text-xs text-slate-300"></i>
                <span>Continuar con el teléfono</span>
            </button>
        </div>

        <!-- Separador 'o' -->
        <div class="relative flex items-center justify-center py-1">
            <div class="border-t border-slate-700/80 w-full"></div>
            <span class="bg-[#202124] px-3 text-xs text-slate-400">o</span>
        </div>

        <!-- Formulario por Correo o Nombre -->
        <form action="/login" method="POST" class="space-y-4">
            @csrf
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-slate-300">
                    Dirección de correo electrónico o Alias
                </label>
                <input type="text" name="login" value="{{ old('login') }}" required placeholder="ejemplo@gmail.com o Santiago" class="w-full bg-[#121212] border border-slate-700/80 rounded-2xl px-4 py-3 text-white placeholder-slate-500 text-xs focus:outline-none focus:ring-1 focus:ring-amber-500">
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-slate-300">
                    Contraseña
                </label>
                <div class="relative">
                    <input type="password" id="login-password" name="password" required placeholder="Tu contraseña" class="w-full bg-[#121212] border border-slate-700/80 rounded-2xl px-4 pr-10 py-3 text-white placeholder-slate-500 text-xs focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <button type="button" onclick="togglePasswordVisibility('login-password', 'login-eye')" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-white">
                        <i id="login-eye" class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full py-3 rounded-full bg-white hover:bg-slate-200 text-slate-950 font-bold text-xs transition shadow-md">
                Continuar
            </button>
        </form>

        <div class="text-center pt-2">
            <a href="{{ route('register') }}" class="text-xs text-amber-400 hover:underline">
                ¿Primera vez en Hyperion? Forja tu leyenda aquí
            </a>
        </div>
    </div>


    <!-- MODAL SELECTOR DE CUENTAS GOOGLE (Diseño exacto Imagen 3) -->
    <div id="modal-selector-google" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-6">
        <div class="w-full max-w-2xl bg-[#1e1f20] border border-slate-700/60 rounded-3xl overflow-hidden p-6 sm:p-10 shadow-2xl relative">
            
            <!-- Botón cerrar modal -->
            <button onclick="cerrarSelectorGoogle()" class="absolute top-5 right-5 w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <!-- Top Google logo -->
            <div class="flex items-center gap-2 mb-6 text-xs font-medium text-slate-300">
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
                        <img src="/images/logo.png" alt="Hyperion Play" class="w-10 h-10 object-contain rounded-lg p-0.5">
                    </div>
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-normal text-white tracking-tight">
                            Selecciona una cuenta
                        </h2>
                        <p class="text-sm text-slate-400 mt-1.5 flex items-center gap-1.5">
                            <span>Ir a</span>
                            <span class="text-slate-200 font-medium">Hyperion Play</span>
                        </p>
                    </div>
                </div>

                <!-- Columna Derecha: Lista de Cuentas (Imagen 3) -->
                <div class="md:col-span-7 space-y-1 divide-y divide-slate-800/80 max-h-96 overflow-y-auto pr-1">
                    
                    <!-- Cuenta 1: Santiago Jiménez Balbuena con Foto Real -->
                    <div onclick="ejecutarCuentaGoogle('santiagojimenezbalbuena@gmail.com', 'Santiago Jiménez Balbuena', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=150&auto=format&fit=crop')" class="account-item p-3 rounded-xl cursor-pointer transition flex items-center gap-3.5 group">
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
                    <div onclick="ejecutarCuentaGoogle('santjim0609@gmail.com', 'santi jimenez', 'https://api.dicebear.com/7.x/initials/svg?seed=santi&backgroundColor=0284c7&textColor=ffffff')" class="account-item p-3 rounded-xl cursor-pointer transition flex items-center gap-3.5 group">
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
                    <div onclick="ejecutarCuentaGoogle('santijimutn@gmail.com', 'santi jimenez', 'https://api.dicebear.com/7.x/initials/svg?seed=santijim&backgroundColor=78350f&textColor=ffffff')" class="account-item p-3 rounded-xl cursor-pointer transition flex items-center gap-3.5 group">
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
                    <div onclick="ejecutarCuentaGoogle('santiagobalbuena178@gmail.com', 'Santiago Balbuena', 'https://api.dicebear.com/7.x/initials/svg?seed=SantiagoBalbuena&backgroundColor=7e22ce&textColor=ffffff')" class="account-item p-3 rounded-xl cursor-pointer transition flex items-center gap-3.5 group">
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
                    <div onclick="ejecutarCuentaGoogle('santijim153@gmail.com', 'Santiago Jiménez', 'https://api.dicebear.com/7.x/initials/svg?seed=SantiagoJimenez&backgroundColor=2563eb&textColor=ffffff')" class="account-item p-3 rounded-xl cursor-pointer transition flex items-center gap-3.5 group">
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
                    <div onclick="toggleOtraCuentaModal()" class="account-item p-3 rounded-xl cursor-pointer transition flex items-center gap-3.5 group">
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
                    <div id="form-otra-cuenta-modal" class="hidden p-3 pt-4 space-y-3">
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-300">Correo de Google (@gmail.com):</label>
                            <input type="email" id="modal-custom-email" placeholder="tu.cuenta@gmail.com" class="w-full bg-[#131314] border border-slate-700 rounded-lg px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500 text-xs">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-300">Nombre o Alias:</label>
                            <input type="text" id="modal-custom-name" placeholder="Ej: Carlos, Valeria..." class="w-full bg-[#131314] border border-slate-700 rounded-lg px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500 text-xs">
                        </div>
                        <button type="button" onclick="enviarOtraCuentaModal()" class="w-full py-2 px-3 rounded-lg bg-amber-500 text-slate-950 font-bold text-xs hover:bg-amber-400 transition">
                            Continuar con esta cuenta
                        </button>
                    </div>

                </div>
            </div>

            <!-- Loading overlay -->
            <div id="modal-loading-overlay" class="hidden absolute inset-0 bg-[#1e1f20]/90 backdrop-blur-sm z-50 flex flex-col items-center justify-center gap-3">
                <div class="w-9 h-9 border-3 border-amber-500/20 border-t-amber-500 rounded-full animate-spin"></div>
                <p id="modal-loading-text" class="text-xs text-slate-200 font-medium">Iniciando sesión con Google...</p>
            </div>
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

        function abrirSelectorGoogle() {
            document.getElementById('modal-selector-google').classList.remove('hidden');
        }

        function cerrarSelectorGoogle() {
            document.getElementById('modal-selector-google').classList.add('hidden');
        }

        function toggleOtraCuentaModal() {
            const form = document.getElementById('form-otra-cuenta-modal');
            form.classList.toggle('hidden');
            if (!form.classList.contains('hidden')) {
                document.getElementById('modal-custom-email').focus();
            }
        }

        async function ejecutarCuentaGoogle(email, name, avatar) {
            document.getElementById('modal-loading-text').innerText = `Iniciando sesión como ${name}...`;
            document.getElementById('modal-loading-overlay').classList.remove('hidden');

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || csrfToken || '{{ csrf_token() }}';
                const res = await fetch('/auth/google/one-click', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ email, name, avatar, _token: token })
                });

                const data = await res.json();
                if (data.status === 'success' && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    document.getElementById('modal-loading-overlay').classList.add('hidden');
                    alert(data.message || 'Error al conectar con la cuenta.');
                }
            } catch (err) {
                console.error(err);
                document.getElementById('modal-loading-overlay').classList.add('hidden');
                alert('Error al conectar con el servidor.');
            }
        }

        function iniciarConApple() {
            const appleEmail = prompt('Ingresa tu Apple ID o correo de iCloud:', 'aventurero@icloud.com');
            if (appleEmail) {
                const name = appleEmail.split('@')[0];
                const avatar = `https://api.dicebear.com/7.x/initials/svg?seed=${encodeURIComponent(name)}&backgroundColor=000000&textColor=ffffff`;
                ejecutarCuentaGoogle(appleEmail, name + ' (Apple)', avatar);
            }
        }

        function iniciarConTelefono() {
            const tel = prompt('Ingresa tu número de teléfono móvil (+52...):', '+52 55 1234 5678');
            if (tel) {
                const telClean = tel.replace(/[^0-9]/g, '');
                const telEmail = `tel_${telClean || 'movil'}@hyperionplay.com`;
                const avatar = `https://api.dicebear.com/7.x/initials/svg?seed=${encodeURIComponent(tel)}&backgroundColor=1e293b&textColor=ffffff`;
                ejecutarCuentaGoogle(telEmail, 'Móvil ' + (tel.slice(-4) || 'Usuario'), avatar);
            }
        }

        function enviarOtraCuentaModal() {
            const email = document.getElementById('modal-custom-email').value.trim();
            const name = document.getElementById('modal-custom-name').value.trim() || email.split('@')[0];
            if (!email) {
                alert('Por favor ingresa tu correo de Google.');
                return;
            }
            const avatar = `https://api.dicebear.com/7.x/initials/svg?seed=${encodeURIComponent(name)}&backgroundColor=059669&textColor=ffffff`;
            ejecutarCuentaGoogle(email, name, avatar);
        }
    </script>
</body>
</html>
