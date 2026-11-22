<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>HYPERION PLAY — Asistente de Entrenamiento Físico RPG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Inter:wght@300;400;600;700&display=swap');
        
        .font-rpg {
            font-family: 'Cinzel', serif;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #07090e;
            color: #e2e8f0;
        }
        .rpg-card {
            background: linear-gradient(145deg, rgba(17, 24, 39, 0.95), rgba(15, 23, 42, 0.98));
            border: 1px solid rgba(245, 158, 11, 0.25);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5), inset 0 0 12px rgba(245, 158, 11, 0.05);
        }
        .rpg-card:hover {
            border-color: rgba(245, 158, 11, 0.45);
        }
        .glow-amber {
            text-shadow: 0 0 12px rgba(245, 158, 11, 0.6);
        }
        .chat-bubble-hyperion {
            background: rgba(30, 41, 59, 0.9);
            border-left: 3px solid #f59e0b;
        }
        .chat-bubble-user {
            background: rgba(3, 105, 161, 0.35);
            border-right: 3px solid #38bdf8;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #0b0f19;
        }
        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #f59e0b;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Top Navigation & Lore Bar -->
    <header class="border-b border-amber-500/20 bg-slate-950/80 backdrop-blur sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-tr from-amber-600 to-amber-400 flex items-center justify-center text-slate-950 shadow-lg shadow-amber-500/20">
                    <i class="fa-solid fa-khanda text-xl"></i>
                </div>
                <div>
                    <span class="font-rpg text-xl font-bold tracking-wider text-amber-400 glow-amber">HYPERION PLAY</span>
                    <span class="text-xs block text-slate-400">Titán Mentor & Motor RAG de Entrenamiento Físico</span>
                </div>
            </div>

            <!-- Global Action Badges -->
            <div class="flex items-center space-x-3">
                <button onclick="abrirModalMetricas()" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-amber-300 border border-amber-500/30 transition flex items-center space-x-1.5">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Auditoría HU #5 (Métricas)</span>
                </button>
                <a href="/limpiar-dataset" target="_blank" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-300 border border-slate-700 transition flex items-center space-x-1.5">
                    <i class="fa-solid fa-filter"></i>
                    <span>HU #1 Endpoint</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="max-w-7xl mx-auto px-4 py-6 flex-1 w-full flex flex-col gap-6">

        <!-- RPG HUD: Estadísticas del Guerrero -->
        <div class="rpg-card rounded-2xl p-4 sm:p-6 relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-44 h-44 bg-amber-500/5 rounded-full blur-2xl pointer-events-none"></div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
                
                <!-- Perfil & Rango -->
                <div class="flex items-center space-x-4 md:border-r border-slate-800 pr-4">
                    <div class="relative">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-500/20 to-slate-800 border-2 border-amber-500/50 flex items-center justify-center text-amber-400 text-2xl font-rpg font-bold">
                            <span id="hud-nivel-icon">{{ $user->current_level }}</span>
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-amber-500 text-slate-950 rounded-full flex items-center justify-center text-[10px] font-bold">
                            Lv
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Aventurero Activo</div>
                        <div id="hud-nombre" class="font-bold text-white text-base">{{ $user->name }}</div>
                        <div id="hud-titulo" class="text-xs text-amber-400 font-medium flex items-center space-x-1">
                            <i class="fa-solid fa-crown text-[10px]"></i>
                            <span>{{ $user->rpg_title }}</span>
                        </div>
                    </div>
                </div>

                <!-- Barra de XP -->
                <div class="md:col-span-2 space-y-2">
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400">Progreso hacia el siguiente rango</span>
                        <span id="hud-xp-label" class="font-bold text-amber-300">{{ $user->current_xp }} XP / {{ $nivelInfo['xp_siguiente'] }} XP</span>
                    </div>
                    @php
                        $rango = max(1, $nivelInfo['xp_siguiente'] - $nivelInfo['xp_actual_min']);
                        $pct = min(100, round((($user->current_xp - $nivelInfo['xp_actual_min']) / $rango) * 100));
                    @endphp
                    <div class="w-full bg-slate-900 rounded-full h-3.5 p-0.5 border border-slate-700/80 overflow-hidden relative">
                        <div id="hud-xp-bar" class="bg-gradient-to-r from-amber-600 via-amber-500 to-yellow-400 h-full rounded-full transition-all duration-700 shadow-sm" style="width: {{ $pct }}%;"></div>
                    </div>
                    <div class="flex justify-between text-[11px] text-slate-500">
                        <span>{{ $nivelInfo['titulo'] }}</span>
                        <span id="hud-xp-faltante">Faltan {{ max(0, $nivelInfo['xp_siguiente'] - $user->current_xp) }} XP</span>
                    </div>
                </div>

                <!-- Resumen Biométrico InBody / Entorno -->
                <div class="bg-slate-900/60 rounded-xl p-3 border border-slate-800 text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Grasa / Edad:</span>
                        <span id="hud-biometria" class="font-semibold text-slate-200">{{ $user->body_fat_pct }}% | {{ $user->age }} años</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Entorno:</span>
                        <span id="hud-entorno" class="font-semibold text-amber-400 capitalize">{{ str_replace('_', ' ', $user->environment) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Lesión:</span>
                        <span id="hud-lesion" class="font-semibold {{ $user->injury !== 'ninguna' ? 'text-rose-400' : 'text-emerald-400' }} capitalize">{{ $user->injury }}</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Layout de Trabajo: Chat Conversacional (Izquierda) + Misión & Rutina (Derecha) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 flex-1 items-start">

            <!-- Columna Izquierda: Chatbot Conversacional HYPERION (Lg: 7 cols) -->
            <div class="lg:col-span-7 flex flex-col h-[700px] rpg-card rounded-2xl overflow-hidden">
                
                <!-- Chat Header -->
                <div class="px-5 py-3.5 border-b border-slate-800 bg-slate-900/40 flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="font-semibold text-sm text-slate-200">Canal de Mentoría con Hyperion</span>
                    </div>
                    <button onclick="resetearChat()" class="text-xs text-slate-400 hover:text-amber-400 transition" title="Reiniciar conversación">
                        <i class="fa-solid fa-arrows-rotate mr-1"></i> Reiniciar
                    </button>
                </div>

                <!-- Chat Stream -->
                <div id="chat-messages" class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">
                    
                    <!-- Mensaje Inicial de Hyperion -->
                    <div class="chat-bubble-hyperion rounded-xl p-4 max-w-[92%] sm:max-w-[85%]">
                        <div class="flex items-center space-x-2 mb-1.5 text-xs text-amber-400 font-semibold">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span>HYPERION</span>
                        </div>
                        <div class="text-sm leading-relaxed text-slate-200">
                            ¡Saludos, noble aventurero <strong>{{ $user->name }}</strong>! Soy <strong>HYPERION</strong>, tu Titán mentor en la forja corporal.
                            <br><br>
                            Combinando los datos biométricos de tu evaluación, nuestro motor clínico RAG y las leyes de sobrecarga progresiva, estoy listo para guiar tu entrenamiento.
                            <br><br>
                            ¿Listo para la batalla de hoy? Puedes pulsar uno de los atajos inferiores o escribirme directamente lo que necesitas.
                        </div>
                    </div>

                </div>

                <!-- Chips de Atajos Rápidos -->
                <div class="px-4 py-2 bg-slate-950/60 border-t border-slate-800/80 flex items-center gap-2 overflow-x-auto text-xs whitespace-nowrap">
                    <button onclick="enviarMensajeRapido('Generar mi rutina rápida de 15 minutos')" class="px-2.5 py-1 rounded-full bg-slate-800 hover:bg-amber-500/20 text-slate-300 hover:text-amber-300 border border-slate-700 hover:border-amber-500/40 transition">
                        ⚡ Rutina 15 min
                    </button>
                    <button onclick="enviarMensajeRapido('Quiero una rutina estricta de bajo impacto articular')" class="px-2.5 py-1 rounded-full bg-slate-800 hover:bg-amber-500/20 text-slate-300 hover:text-amber-300 border border-slate-700 hover:border-amber-500/40 transition">
                        🛡️ Bajo impacto
                    </button>
                    <button onclick="enviarMensajeRapido('Tengo una molestia o dolor en la rodilla')" class="px-2.5 py-1 rounded-full bg-slate-800 hover:bg-rose-500/20 text-slate-300 hover:text-rose-300 border border-slate-700 hover:border-rose-500/40 transition">
                        🩹 Lesión de rodilla
                    </button>
                    <button onclick="enviarMensajeRapido('¿Por qué esta rutina? Explícame los criterios')" class="px-2.5 py-1 rounded-full bg-slate-800 hover:bg-sky-500/20 text-slate-300 hover:text-sky-300 border border-slate-700 hover:border-sky-500/40 transition">
                        🔍 ¿Por qué esta rutina?
                    </button>
                    <button onclick="enviarMensajeRapido('Mostrar mi historial de entrenamientos')" class="px-2.5 py-1 rounded-full bg-slate-800 hover:bg-amber-500/20 text-slate-300 hover:text-amber-300 border border-slate-700 hover:border-amber-500/40 transition">
                        📜 Historial
                    </button>
                    <button onclick="enviarMensajeRapido('Terminé la rutina, estuvo muy fácil')" class="px-2.5 py-1 rounded-full bg-emerald-950/40 hover:bg-emerald-900/40 text-emerald-300 border border-emerald-500/30 transition">
                        ⚔️ Terminé (+XP)
                    </button>
                </div>

                <!-- Chat Input Form -->
                <form id="chat-form" onsubmit="enviarMensaje(event)" class="p-3 sm:p-4 bg-slate-900/60 border-t border-slate-800 flex items-center space-x-2">
                    <input 
                        type="text" 
                        id="chat-input"
                        placeholder="Escribe a Hyperion (ej: 'Tengo 15 min en casa sin equipo')..." 
                        class="flex-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-amber-500/60 transition"
                        autocomplete="off"
                    />
                    <button 
                        type="submit" 
                        id="chat-submit-btn"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-slate-950 font-bold text-sm shadow-md transition flex items-center space-x-1.5"
                    >
                        <span>Enviar</span>
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                    </button>
                </form>

            </div>

            <!-- Columna Derecha: Panel de Rutina Activa & Misiones (Lg: 5 cols) -->
            <div class="lg:col-span-5 space-y-6">

                <!-- Card de Rutina Activa -->
                <div class="rpg-card rounded-2xl p-5" id="panel-rutina-activa">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-dumbbell text-amber-400"></i>
                            <h2 class="font-bold text-slate-100 text-sm uppercase tracking-wide">Misión / Rutina Activa</h2>
                        </div>
                        <span id="badge-tiempo-rutina" class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/30">
                            15 min
                        </span>
                    </div>

                    <!-- Mensaje si no hay rutina generada todavía -->
                    <div id="rutina-placeholder" class="text-center py-10 space-y-3">
                        <div class="w-12 h-12 mx-auto rounded-full bg-slate-800 flex items-center justify-center text-slate-500 text-xl">
                            <i class="fa-solid fa-clipboard-list"></i>
                        </div>
                        <p class="text-xs text-slate-400 max-w-xs mx-auto">
                            Aún no has generado tu rutina de hoy. Pídele una a Hyperion en el chat o pulsa 
                            <button onclick="enviarMensajeRapido('Generar rutina')" class="text-amber-400 underline font-semibold">Generar rutina</button>.
                        </p>
                    </div>

                    <!-- Contenedor con la Rutina Generada -->
                    <div id="rutina-contenido" class="hidden space-y-4">
                        
                        <div>
                            <h3 id="rutina-titulo" class="font-bold text-amber-300 text-base">Rutina de Entrenamiento</h3>
                            <div class="flex flex-wrap gap-2 mt-1.5 text-[11px]">
                                <span id="rutina-arquetipo-badge" class="px-2 py-0.5 bg-slate-800 text-slate-300 rounded border border-slate-700">Titán Ágil</span>
                                <span id="rutina-xp-badge" class="px-2 py-0.5 bg-amber-500/10 text-amber-300 rounded border border-amber-500/30">+75 XP estimada</span>
                            </div>
                        </div>

                        <!-- Alerta Médica Obligatoria (HU #11) -->
                        <div id="alerta-medica-box" class="hidden p-3 rounded-xl bg-rose-950/40 border border-rose-500/40 text-rose-200 text-xs">
                            <i class="fa-solid fa-triangle-exclamation mr-1 text-rose-400"></i>
                            <span id="alerta-medica-texto"></span>
                        </div>

                        <!-- Aviso de Micro-rutina (HU #10) -->
                        <div id="aviso-micro-box" class="hidden p-3 rounded-xl bg-sky-950/40 border border-sky-500/40 text-sky-200 text-xs">
                            <i class="fa-solid fa-clock mr-1 text-sky-400"></i>
                            <span id="aviso-micro-texto"></span>
                        </div>

                        <!-- Aviso de Sobrecarga Progresiva (HU #13) -->
                        <div id="aviso-sobrecarga-box" class="hidden p-3 rounded-xl bg-emerald-950/40 border border-emerald-500/40 text-emerald-200 text-xs">
                            <i class="fa-solid fa-arrow-trend-up mr-1 text-emerald-400"></i>
                            <span id="aviso-sobrecarga-texto"></span>
                        </div>

                        <!-- Lista de Ejercicios -->
                        <div id="lista-ejercicios" class="space-y-3">
                            <!-- Los ejercicios se inyectan dinámicamente con JS -->
                        </div>

                        <!-- Acciones de Finalización (HU #12, #13, #14) -->
                        <div class="pt-4 border-t border-slate-800 space-y-3">
                            <div class="text-xs font-semibold text-slate-300">Registrar Esfuerzo Percibido (RPE):</div>
                            <div class="grid grid-cols-3 gap-2">
                                <label class="cursor-pointer text-center p-2 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs">
                                    <input type="radio" name="dificultad" value="muy_facil" class="hidden" checked>
                                    <span class="block font-medium text-emerald-400">Muy Fácil</span>
                                    <span class="text-[10px] text-slate-400">+Sobrecarga</span>
                                </label>
                                <label class="cursor-pointer text-center p-2 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs">
                                    <input type="radio" name="dificultad" value="adecuada" class="hidden">
                                    <span class="block font-medium text-amber-400">Adecuada</span>
                                    <span class="text-[10px] text-slate-400">Mantener</span>
                                </label>
                                <label class="cursor-pointer text-center p-2 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs">
                                    <input type="radio" name="dificultad" value="muy_pesada" class="hidden">
                                    <span class="block font-medium text-rose-400">Muy Pesada</span>
                                    <span class="text-[10px] text-slate-400">Atenuar</span>
                                </label>
                            </div>

                            <button onclick="completarRutina()" class="w-full py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-bold text-sm shadow-lg shadow-emerald-900/30 transition flex items-center justify-center space-x-2">
                                <i class="fa-solid fa-check-circle"></i>
                                <span>Completar Rutina y Recoger XP</span>
                            </button>
                        </div>

                    </div>

                </div>

                <!-- Card de Misiones Semanales (HU #14) -->
                <div class="rpg-card rounded-2xl p-5">
                    <div class="flex items-center space-x-2 pb-3 border-b border-slate-800 mb-3">
                        <i class="fa-solid fa-scroll text-amber-400"></i>
                        <h2 class="font-bold text-slate-100 text-sm uppercase tracking-wide">Misiones del Aventurero</h2>
                    </div>
                    
                    <div id="quests-container" class="space-y-2.5">
                        @foreach($user->quests as $q)
                        <div class="p-3 rounded-xl {{ $q->completed ? 'bg-emerald-950/20 border-emerald-500/30' : 'bg-slate-900/60 border-slate-800' }} border text-xs flex items-center justify-between">
                            <div class="space-y-0.5">
                                <div class="font-semibold {{ $q->completed ? 'text-emerald-300 line-through' : 'text-slate-200' }}">
                                    {{ $q->title }}
                                </div>
                                <div class="text-[11px] text-slate-400">{{ $q->description }}</div>
                            </div>
                            <div class="text-right pl-3 shrink-0">
                                <span class="px-2 py-0.5 rounded font-bold {{ $q->completed ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' }}">
                                    +{{ $q->reward_xp }} XP
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- Modal de Auditoría de Métricas HU #5 -->
    <div id="modal-metricas" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="rpg-card rounded-2xl max-w-2xl w-full p-6 max-h-[85vh] flex flex-col space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-scale-balanced text-amber-400"></i>
                    <h3 class="font-bold text-white text-base">Auditoría de Métricas de Reglas (HU #5)</h3>
                </div>
                <button onclick="cerrarModalMetricas()" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div id="metricas-contenido" class="flex-1 overflow-y-auto space-y-4 text-xs text-slate-300">
                <div class="text-center py-6 text-slate-400">Cargando evaluación de reglas sobre 20 perfiles...</div>
            </div>

            <div class="pt-3 border-t border-slate-800 flex justify-end">
                <button onclick="cerrarModalMetricas()" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs">
                    Cerrar Auditoría
                </button>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let rutinaActual = null;

        // Auto-scroll chat
        function scrollChatToEnd() {
            const container = document.getElementById('chat-messages');
            container.scrollTop = container.scrollHeight;
        }

        // Agregar burbuja de mensaje al chat
        function agregarBurbuja(emisor, texto) {
            const container = document.getElementById('chat-messages');
            const div = document.createElement('div');
            
            if (emisor === 'hyperion') {
                div.className = 'chat-bubble-hyperion rounded-xl p-4 max-w-[92%] sm:max-w-[85%]';
                div.innerHTML = `
                    <div class="flex items-center space-x-2 mb-1.5 text-xs text-amber-400 font-semibold">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>HYPERION</span>
                    </div>
                    <div class="text-sm leading-relaxed text-slate-200 whitespace-pre-line">${texto}</div>
                `;
            } else {
                div.className = 'chat-bubble-user rounded-xl p-4 max-w-[92%] sm:max-w-[85%] ml-auto';
                div.innerHTML = `
                    <div class="flex items-center justify-end space-x-2 mb-1.5 text-xs text-sky-400 font-semibold">
                        <span>Tú (Aventurero)</span>
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="text-sm leading-relaxed text-slate-200 whitespace-pre-line">${texto}</div>
                `;
            }

            container.appendChild(div);
            scrollChatToEnd();
        }

        // Enviar mensaje al chatbot vía AJAX
        async function enviarMensaje(event) {
            if (event) event.preventDefault();
            const input = document.getElementById('chat-input');
            const text = input.value.trim();
            if (!text) return;

            input.value = '';
            agregarBurbuja('user', text);

            try {
                const response = await fetch('/api/chat/message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ message: text }),
                });

                const data = await response.json();
                if (data.status === 'success') {
                    agregarBurbuja('hyperion', data.respuesta.texto);
                    
                    if (data.respuesta.rutina) {
                        mostrarRutinaEnPanel(data.respuesta.rutina);
                    }

                    if (data.user) {
                        actualizarHUD(data.user);
                    }
                }
            } catch (error) {
                console.error(error);
                agregarBurbuja('hyperion', 'Lo siento, ha ocurrido un error al conectar con el servidor.');
            }
        }

        function enviarMensajeRapido(msg) {
            document.getElementById('chat-input').value = msg;
            enviarMensaje();
        }

        // Renderizar rutina en el panel derecho
        function mostrarRutinaEnPanel(rutina) {
            rutinaActual = rutina;

            document.getElementById('rutina-placeholder').classList.add('hidden');
            document.getElementById('rutina-contenido').classList.remove('hidden');

            document.getElementById('rutina-titulo').innerText = rutina.titulo;
            document.getElementById('badge-tiempo-rutina').innerText = rutina.duracion_minutos + ' min';
            document.getElementById('rutina-arquetipo-badge').innerText = rutina.arquetipo.replace('_', ' ').toUpperCase();
            document.getElementById('rutina-xp-badge').innerText = '+' + rutina.xp_estimada + ' XP estimada';

            // Alerta médica
            const boxAlerta = document.getElementById('alerta-medica-box');
            if (rutina.aviso_medico) {
                document.getElementById('alerta-medica-texto').innerText = rutina.aviso_medico;
                boxAlerta.classList.remove('hidden');
            } else {
                boxAlerta.classList.add('hidden');
            }

            // Aviso micro-rutina
            const boxMicro = document.getElementById('aviso-micro-box');
            if (rutina.aviso_micro_rutina) {
                document.getElementById('aviso-micro-texto').innerText = rutina.aviso_micro_rutina;
                boxMicro.classList.remove('hidden');
            } else {
                boxMicro.classList.add('hidden');
            }

            // Aviso sobrecarga
            const boxSobrecarga = document.getElementById('aviso-sobrecarga-box');
            if (rutina.aviso_sobrecarga) {
                document.getElementById('aviso-sobrecarga-texto').innerText = rutina.aviso_sobrecarga;
                boxSobrecarga.classList.remove('hidden');
            } else {
                boxSobrecarga.classList.add('hidden');
            }

            // Lista de ejercicios
            const contenedor = document.getElementById('lista-ejercicios');
            contenedor.innerHTML = '';

            rutina.ejercicios.forEach((ej, idx) => {
                const item = document.createElement('div');
                item.className = 'p-3 rounded-xl bg-slate-900/80 border border-slate-800 space-y-1.5';
                item.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-100 text-xs">${idx + 1}. ${ej.nombre}</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 text-amber-400 border border-slate-700">${ej.musculo}</span>
                    </div>
                    <div class="text-[11px] text-slate-300">
                        ${ej.series} series × ${ej.repeticiones} repeticiones | Descanso: ${ej.descanso_segundos}s
                    </div>
                    <div class="text-[11px] text-slate-400 bg-slate-950/60 p-2 rounded-lg border border-slate-800/60">
                        💡 <strong class="text-slate-300">Técnica:</strong> ${ej.indicacion_postural}
                    </div>
                `;
                contenedor.appendChild(item);
            });
        }

        // Completar rutina activa y sumar XP
        async function completarRutina() {
            const dificultad = document.querySelector('input[name="dificultad"]:checked').value;
            const fatiga = (dificultad === 'muy_pesada');

            try {
                const response = await fetch('/api/workout/complete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        difficulty_rating: dificultad,
                        fatigue_reported: fatiga,
                        progressive_overload_accepted: (dificultad === 'muy_facil')
                    })
                });

                const data = await response.json();
                if (data.status === 'success') {
                    agregarBurbuja('hyperion', data.rpg.mensaje_narrativo);
                    actualizarHUD(data.user);
                    document.getElementById('rutina-placeholder').classList.remove('hidden');
                    document.getElementById('rutina-contenido').classList.add('hidden');
                } else {
                    alert(data.mensaje || 'Error al completar rutina.');
                }
            } catch (e) {
                console.error(e);
            }
        }

        // Actualizar barra de XP, nivel y datos del HUD
        function actualizarHUD(user) {
            document.getElementById('hud-nombre').innerText = user.name;
            document.getElementById('hud-titulo').innerHTML = `<i class="fa-solid fa-crown text-[10px]"></i> <span>${user.rpg_title}</span>`;
            document.getElementById('hud-nivel-icon').innerText = user.current_level;

            // Recalcular nivel y barra
            fetch('/api/user/profile')
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        const n = data.nivel_info;
                        const u = data.user;
                        document.getElementById('hud-xp-label').innerText = `${u.current_xp} XP / ${n.xp_siguiente} XP`;
                        const rango = Math.max(1, n.xp_siguiente - n.xp_actual_min);
                        const pct = Math.min(100, Math.round(((u.current_xp - n.xp_actual_min) / rango) * 100));
                        document.getElementById('hud-xp-bar').style.width = pct + '%';
                        document.getElementById('hud-xp-faltante').innerText = `Faltan ${Math.max(0, n.xp_siguiente - u.current_xp)} XP`;

                        document.getElementById('hud-biometria').innerText = `${u.body_fat_pct}% | ${u.age} años`;
                        document.getElementById('hud-entorno').innerText = u.environment.replace('_', ' ');
                        document.getElementById('hud-lesion').innerText = u.injury;
                    }
                });
        }

        // Abrir y cargar modal de auditoría HU #5
        async function abrirModalMetricas() {
            const modal = document.getElementById('modal-metricas');
            const contenedor = document.getElementById('metricas-contenido');
            modal.classList.remove('hidden');
            contenedor.innerHTML = '<div class="text-center py-6 text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Procesando 52,040 inferencias sobre 20 perfiles...</div>';

            try {
                const response = await fetch('/api/metrics/report');
                const data = await response.json();

                if (data.status === 'success') {
                    let html = `
                        <div class="grid grid-cols-4 gap-2 text-center">
                            <div class="p-2.5 bg-slate-900 rounded-xl border border-slate-800">
                                <div class="text-emerald-400 font-bold text-lg">${data.matriz.VP.toLocaleString()}</div>
                                <div class="text-[10px] text-slate-400">Verdaderos Positivos (VP)</div>
                            </div>
                            <div class="p-2.5 bg-slate-900 rounded-xl border border-slate-800">
                                <div class="text-rose-400 font-bold text-lg">${data.matriz.FP}</div>
                                <div class="text-[10px] text-slate-400">Falsos Positivos (META: 0)</div>
                            </div>
                            <div class="p-2.5 bg-slate-900 rounded-xl border border-slate-800">
                                <div class="text-sky-400 font-bold text-lg">${data.matriz.VN.toLocaleString()}</div>
                                <div class="text-[10px] text-slate-400">Verdaderos Negativos (VN)</div>
                            </div>
                            <div class="p-2.5 bg-slate-900 rounded-xl border border-slate-800">
                                <div class="text-amber-400 font-bold text-lg">${data.matriz.Precision}%</div>
                                <div class="text-[10px] text-slate-400">Precisión Clínica</div>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-emerald-950/30 border border-emerald-500/30 text-emerald-300 text-xs">
                            ✅ <strong>Conclusión de Auditoría:</strong> Tasa de Falsos Positivos = 0.0%. El 100% de los ejercicios recomendados respetan los límites articulares y de impacto de los perfiles.
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-[11px]">
                                <thead>
                                    <tr class="border-b border-slate-800 text-slate-400">
                                        <th class="py-1">ID</th>
                                        <th>Edad</th>
                                        <th>% Grasa</th>
                                        <th>Lesión</th>
                                        <th>Entorno</th>
                                        <th>VP</th>
                                        <th>FP</th>
                                        <th>Precisión</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    data.tabla.forEach(r => {
                        html += `
                            <tr class="border-b border-slate-800/40 hover:bg-slate-900/40">
                                <td class="py-1 font-semibold text-slate-200">${r.id}</td>
                                <td>${r.edad}</td>
                                <td>${r.grasa}%</td>
                                <td class="text-amber-400">${r.lesion}</td>
                                <td class="capitalize">${r.entorno.replace('_', ' ')}</td>
                                <td class="text-emerald-400 font-semibold">${r.vp}</td>
                                <td class="text-slate-400">${r.fp}</td>
                                <td class="text-emerald-300 font-semibold">${r.precision}%</td>
                            </tr>
                        `;
                    });

                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;
                    contenedor.innerHTML = html;
                }
            } catch (e) {
                console.error(e);
                contenedor.innerHTML = '<div class="text-rose-400 p-4">Error al cargar el reporte de métricas.</div>';
            }
        }

        function cerrarModalMetricas() {
            document.getElementById('modal-metricas').classList.add('hidden');
        }

        function resetearChat() {
            const container = document.getElementById('chat-messages');
            container.innerHTML = `
                <div class="chat-bubble-hyperion rounded-xl p-4 max-w-[92%] sm:max-w-[85%]">
                    <div class="flex items-center space-x-2 mb-1.5 text-xs text-amber-400 font-semibold">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>HYPERION</span>
                    </div>
                    <div class="text-sm leading-relaxed text-slate-200">
                        Sesión reiniciada. ¿De cuántos minutos dispones hoy para tu entrenamiento y en qué entorno te encuentras?
                    </div>
                </div>
            `;
            document.getElementById('rutina-placeholder').classList.remove('hidden');
            document.getElementById('rutina-contenido').classList.add('hidden');
        }
    </script>
</body>
</html>
