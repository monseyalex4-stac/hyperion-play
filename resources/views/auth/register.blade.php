<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Crear Aventurero | Hyperion Play</title>

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

    <div class="w-full max-w-lg space-y-6 my-6">
        
        <!-- Logo y Título -->
        <div class="text-center space-y-2">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-amber-600 to-amber-400 text-slate-950 text-2xl shadow-lg shadow-amber-500/20 mb-1">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h1 class="font-cinzel font-bold text-2xl text-transparent bg-clip-text bg-gradient-to-r from-amber-200 via-amber-400 to-amber-500 tracking-wider">
                FORJA TU LEYENDA
            </h1>
            <p class="text-xs text-slate-400">Crea tu cuenta de Aventurero en Hyperion Play</p>
        </div>

        <!-- Tarjeta de Registro -->
        <div class="rpg-card rounded-2xl p-6 sm:p-8 space-y-5">

            <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
                <h2 class="font-bold text-slate-200 text-base flex items-center gap-2">
                    <i class="fa-solid fa-scroll text-amber-400"></i>
                    <span>Registro de Aventurero</span>
                </h2>
                <a href="{{ route('login') }}" class="text-xs text-amber-400 hover:text-amber-300 transition font-medium">
                    Ya tengo cuenta →
                </a>
            </div>

            @if($errors->any())
                <div class="p-3 rounded-xl bg-rose-950/40 border border-rose-500/30 text-rose-300 text-xs space-y-1">
                    @foreach($errors->all() as $error)
                        <div><i class="fa-solid fa-circle-exclamation mr-1.5 text-rose-400"></i>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <!-- Botón Google OAuth y Selector de Cuentas (Imagen 3) -->
            <div>
                <button type="button" onclick="abrirSelectorGoogle()" class="w-full py-2.5 px-4 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-semibold text-xs transition flex items-center justify-center gap-3 shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span>Registrarse con Google</span>
                </button>
            </div>

            <!-- Separador -->
            <div class="relative flex items-center justify-center">
                <div class="border-t border-slate-800 w-full"></div>
                <span class="bg-slate-900 px-3 text-[10px] text-slate-500 uppercase tracking-widest absolute">o con nombre y contraseña</span>
            </div>

            <!-- Formulario de Registro -->
            <form action="/register" method="POST" class="space-y-4 text-xs">
                @csrf

                <!-- Nombre o Alias -->
                <div>
                    <label class="block text-slate-300 font-medium mb-1">Nombre o Alias del Aventurero *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                            <i class="fa-solid fa-user-ninja"></i>
                        </span>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="Ej: Valeria, Carlos, Leo..." class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-9 pr-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500 text-xs">
                    </div>
                </div>

                <!-- Correo Electrónico (Opcional) -->
                <div>
                    <label class="block text-slate-300 font-medium mb-1 flex items-center justify-between">
                        <span>Correo Electrónico</span>
                        <span class="text-[10px] text-slate-500 font-normal">(Opcional, para recuperar tu cuenta)</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="tu@email.com (o déjalo en blanco)" class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-9 pr-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500 text-xs">
                    </div>
                </div>

                <!-- Contraseña con Generador de 1 Clic -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-slate-300 font-medium">Contraseña *</label>
                        <button type="button" onclick="generarContrasenaSegura()" class="text-[11px] text-amber-400 hover:text-amber-300 font-semibold flex items-center gap-1.5 transition px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <span>Generar Contraseña Segura</span>
                        </button>
                    </div>

                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                            <i class="fa-solid fa-key"></i>
                        </span>
                        <input type="text" id="reg-password" name="password" required placeholder="Crea o genera tu clave secreta" class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-9 pr-20 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500 text-xs font-mono">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
                            <button type="button" onclick="copiarContrasena()" title="Copiar contraseña generada" class="p-1.5 text-slate-400 hover:text-amber-400 transition text-xs">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                            <button type="button" onclick="togglePasswordVisibility('reg-password', 'reg-eye')" class="p-1.5 text-slate-400 hover:text-white transition text-xs">
                                <i id="reg-eye" class="fa-solid fa-eye text-xs"></i>
                            </button>
                        </div>
                    </div>
                    <div id="password-alert" class="hidden text-[10px] text-emerald-400 font-medium mt-1 flex items-center gap-1">
                        <i class="fa-solid fa-check"></i>
                        <span>¡Contraseña generada y copiada al portapapeles! Guárdala bien.</span>
                    </div>
                </div>

                <!-- Biometría Rápida Inicial -->
                <div class="pt-2 border-t border-slate-800/80 space-y-3">
                    <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-sliders text-amber-400"></i>
                        <span>Parámetros Iniciales del Aventurero</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-400 mb-1">Edad</label>
                            <input type="number" id="reg-edad" name="age" min="12" max="100" value="25" oninput="calcularGrasaReg()" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">Género</label>
                            <select id="reg-genero" name="gender" onchange="calcularGrasaReg()" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                                <option value="no_disponible">Prefiero no decir</option>
                                <option value="femenino">Femenino</option>
                                <option value="masculino">Masculino</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-slate-400 mb-1">Peso (kg)</label>
                            <input type="number" step="0.5" id="reg-peso" name="weight_kg" value="70" oninput="calcularGrasaReg()" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">Altura (cm)</label>
                            <input type="number" id="reg-altura" name="height_cm" value="170" oninput="calcularGrasaReg()" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1 flex items-center justify-between">
                                <span>% Grasa</span>
                                <span class="text-[9px] text-amber-400">Auto</span>
                            </label>
                            <input type="number" step="0.1" id="reg-grasa" name="body_fat_pct" value="18" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-400 mb-1">Entorno Inicial de Entrenamiento</label>
                        <select name="environment" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                            <option value="casa_sin_equipo">Casa sin equipo (Calistenia / Peso corporal)</option>
                            <option value="casa_basico">Casa con equipo ligero (Mancuernas / Bandas)</option>
                            <option value="gimnasio">Gimnasio comercial completo (Máquinas y Pesas)</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-bold text-xs shadow-lg shadow-amber-500/20 transition flex items-center justify-center gap-2 mt-4">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Comenzar Aventura</span>
                </button>
            </form>

            <div class="pt-2 text-center text-xs text-slate-400 border-t border-slate-800/80">
                ¿Ya forjaste tu cuenta?
                <a href="{{ route('login') }}" class="text-amber-400 hover:text-amber-300 font-semibold underline ml-1">
                    Iniciar Sesión
                </a>
            </div>

        </div>

    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

        // Generador de contraseñas épicas y seguras (Titan-Rayo-849#)
        function generarContrasenaSegura() {
            const palabras = [
                'Titan', 'Esparta', 'Valquiria', 'Hyperion', 'Fenix', 'Olimpo',
                'Atlas', 'Guerro', 'Fuerza', 'Valkiria', 'Leon', 'Hierro',
                'Trueno', 'Sombra', 'Dragon', 'Escudo', 'Vencedor', 'Poder'
            ];
            const simbolos = ['#', '!', '$', '&', '*', '@'];
            
            const palabra1 = palabras[Math.floor(Math.random() * palabras.length)];
            const palabra2 = palabras[Math.floor(Math.random() * palabras.length)];
            const numero = Math.floor(100 + Math.random() * 900);
            const simbolo = simbolos[Math.floor(Math.random() * simbolos.length)];

            const password = `${palabra1}-${palabra2}-${numero}${simbolo}`;
            const input = document.getElementById('reg-password');
            input.value = password;
            input.type = 'text'; // Mostrar para que la vea de inmediato

            copiarContrasena();
        }

        function copiarContrasena() {
            const input = document.getElementById('reg-password');
            if (!input.value) return;

            navigator.clipboard.writeText(input.value).then(() => {
                const alertEl = document.getElementById('password-alert');
                alertEl.classList.remove('hidden');
                setTimeout(() => {
                    alertEl.classList.add('hidden');
                }, 4000);
            }).catch(() => {
                alert('Contraseña generada: ' + input.value);
            });
        }

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

        function calcularGrasaReg() {
            const peso = parseFloat(document.getElementById('reg-peso')?.value) || 0;
            const alturaCm = parseFloat(document.getElementById('reg-altura')?.value) || 0;
            const edad = parseInt(document.getElementById('reg-edad')?.value) || 25;
            const genero = document.getElementById('reg-genero')?.value || 'no_disponible';

            if (peso > 0 && alturaCm > 0) {
                const alturaM = alturaCm / 100;
                const imc = peso / (alturaM * alturaM);

                let sexo = 0.5;
                if (genero === 'masculino') sexo = 1;
                if (genero === 'femenino') sexo = 0;

                let grasa = (1.20 * imc) + (0.23 * edad) - (10.8 * sexo) - 5.4;
                grasa = Math.max(5.0, Math.min(50.0, grasa));

                const inputGrasa = document.getElementById('reg-grasa');
                if (inputGrasa) {
                    inputGrasa.value = grasa.toFixed(1);
                }
            }
        }

        // Ejecutar cálculo inicial
        document.addEventListener('DOMContentLoaded', () => {
            calcularGrasaReg();
        });

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

    <!-- MODAL SELECTOR DE CUENTAS GOOGLE (Diseño exacto Imagen 3) -->
    <div id="modal-selector-google" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-6 text-slate-200">
        <div class="w-full max-w-2xl bg-[#1e1f20] border border-slate-700/60 rounded-3xl overflow-hidden p-6 sm:p-10 shadow-2xl relative">
            
            <button onclick="cerrarSelectorGoogle()" class="absolute top-5 right-5 w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>

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
                <div class="md:col-span-5 space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-700 flex items-center justify-center shadow-md">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center">
                            <i class="fa-solid fa-shield-halved text-slate-950 text-sm"></i>
                        </div>
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

                <div class="md:col-span-7 space-y-1 divide-y divide-slate-800/80 max-h-96 overflow-y-auto pr-1">
                    <div onclick="ejecutarCuentaGoogle('santiagojimenezbalbuena@gmail.com', 'Santiago Jiménez Balbuena', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=150&auto=format&fit=crop')" class="p-3 rounded-xl cursor-pointer hover:bg-white/5 transition flex items-center gap-3.5 group">
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

                    <div onclick="ejecutarCuentaGoogle('santjim0609@gmail.com', 'santi jimenez', 'https://api.dicebear.com/7.x/initials/svg?seed=santi&backgroundColor=0284c7&textColor=ffffff')" class="p-3 rounded-xl cursor-pointer hover:bg-white/5 transition flex items-center gap-3.5 group">
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

                    <div onclick="ejecutarCuentaGoogle('santijimutn@gmail.com', 'santi jimenez', 'https://api.dicebear.com/7.x/initials/svg?seed=santijim&backgroundColor=78350f&textColor=ffffff')" class="p-3 rounded-xl cursor-pointer hover:bg-white/5 transition flex items-center gap-3.5 group">
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

                    <div onclick="ejecutarCuentaGoogle('santiagobalbuena178@gmail.com', 'Santiago Balbuena', 'https://api.dicebear.com/7.x/initials/svg?seed=SantiagoBalbuena&backgroundColor=7e22ce&textColor=ffffff')" class="p-3 rounded-xl cursor-pointer hover:bg-white/5 transition flex items-center gap-3.5 group">
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

                    <div onclick="ejecutarCuentaGoogle('santijim153@gmail.com', 'Santiago Jiménez', 'https://api.dicebear.com/7.x/initials/svg?seed=SantiagoJimenez&backgroundColor=2563eb&textColor=ffffff')" class="p-3 rounded-xl cursor-pointer hover:bg-white/5 transition flex items-center gap-3.5 group">
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

                    <div onclick="toggleOtraCuentaModal()" class="p-3 rounded-xl cursor-pointer hover:bg-white/5 transition flex items-center gap-3.5 group">
                        <div class="w-9 h-9 rounded-full bg-slate-800 text-slate-300 flex items-center justify-center text-sm shadow-sm shrink-0">
                            <i class="fa-solid fa-user-plus text-xs"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-slate-300 group-hover:text-white transition">
                                Usar otra cuenta
                            </div>
                        </div>
                    </div>

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

            <div id="modal-loading-overlay" class="hidden absolute inset-0 bg-[#1e1f20]/90 backdrop-blur-sm z-50 flex flex-col items-center justify-center gap-3">
                <div class="w-9 h-9 border-3 border-amber-500/20 border-t-amber-500 rounded-full animate-spin"></div>
                <p id="modal-loading-text" class="text-xs text-slate-200 font-medium">Iniciando sesión con Google...</p>
            </div>
        </div>
    </div>
</body>
</html>
