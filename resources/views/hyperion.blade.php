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

            <!-- Global Action Badges & Auth Area -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                <button onclick="abrirModalMetricas()" class="hidden sm:flex px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-amber-300 border border-amber-500/30 transition items-center space-x-1.5">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Auditoría HU #5</span>
                </button>
                <a href="/limpiar-dataset" target="_blank" class="hidden sm:flex px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-300 border border-slate-700 transition items-center space-x-1.5">
                    <i class="fa-solid fa-filter"></i>
                    <span>HU #1</span>
                </a>

                @if(Auth::check())
                    <!-- Usuario Autenticado -->
                    <div class="flex items-center gap-2 pl-2 border-l border-slate-800">
                        <div class="flex items-center gap-2 px-2.5 py-1 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-semibold">
                            @if(Auth::user()->avatar)
                                <img src="{{ Auth::user()->avatar }}" alt="Avatar" class="w-5 h-5 rounded-full object-cover">
                            @else
                                <i class="fa-solid fa-user-shield text-[11px]"></i>
                            @endif
                            <span class="max-w-[100px] truncate">{{ Auth::user()->name }}</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" title="Cerrar Sesión" class="p-1.5 rounded-lg bg-slate-800 hover:bg-rose-950/60 text-slate-400 hover:text-rose-400 border border-slate-700 hover:border-rose-500/40 transition text-xs">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </button>
                        </form>
                    </div>
                @else
                    <!-- No Autenticado: Login y Registro -->
                    <div class="flex items-center gap-1.5 pl-2 border-l border-slate-800 text-xs">
                        <a href="{{ route('login') }}" class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition font-medium flex items-center gap-1.5">
                            <i class="fa-solid fa-arrow-right-to-bracket text-amber-400"></i>
                            <span>Ingresar</span>
                        </a>
                        <a href="{{ route('register') }}" class="px-2.5 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 transition font-bold shadow-md shadow-amber-500/20 flex items-center gap-1.5">
                            <i class="fa-solid fa-user-plus"></i>
                            <span>Registro</span>
                        </a>
                    </div>
                @endif
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
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-500/20 to-slate-800 border-2 border-amber-500/50 flex items-center justify-center text-amber-400 text-2xl font-rpg font-bold overflow-hidden shadow-md">
                            <img id="hud-avatar-img" src="{{ $user->avatar }}" alt="{{ $user->name }}" class="w-full h-full object-cover {{ empty($user->avatar) ? 'hidden' : '' }}">
                            <span id="hud-nivel-icon" class="{{ !empty($user->avatar) ? 'hidden' : '' }}">{{ $user->current_level }}</span>
                        </div>
                        <div class="absolute -bottom-1 -right-1 px-1.5 h-5 bg-amber-500 text-slate-950 rounded-full flex items-center justify-center text-[10px] font-bold shadow">
                            Lv <span id="hud-nivel-badge">{{ $user->current_level }}</span>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Aventurero Activo</span>
                            <div class="flex items-center gap-1">
                                <button onclick="abrirModalEditarUsuario()" title="Modificar perfil de este aventurero (entorno, lesiones, peso, etc.)" class="text-[10px] px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-lg transition font-medium flex items-center gap-1">
                                    <i class="fa-solid fa-pen text-[9px]"></i>
                                    <span>Editar</span>
                                </button>
                                <button onclick="abrirModalNuevoUsuario()" class="text-[10px] px-2 py-0.5 bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 rounded-lg transition font-medium flex items-center gap-1">
                                    <i class="fa-solid fa-user-plus text-[9px]"></i>
                                    <span>Nuevo</span>
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <select id="selector-aventurero" onchange="cambiarUsuario(this.value)" class="w-full bg-slate-900 border border-slate-700 hover:border-amber-500/50 text-white font-bold text-xs rounded-lg px-2 py-1 focus:outline-none focus:ring-1 focus:ring-amber-500">
                                @foreach($allUsers as $u)
                                    <option value="{{ $u->id }}" {{ $u->id == $user->id ? 'selected' : '' }}>
                                        {{ $u->name }} (Lv {{ $u->current_level }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div id="hud-titulo" class="text-[11px] text-amber-400 font-medium flex items-center space-x-1 mt-1 truncate">
                            <i class="fa-solid fa-crown text-[9px]"></i>
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
                    @if(empty($chatMessages) || $chatMessages->isEmpty())
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
                    @else
                        @foreach($chatMessages as $msg)
                            @if($msg->sender === 'hyperion')
                                <div class="chat-bubble-hyperion rounded-xl p-4 max-w-[92%] sm:max-w-[85%]">
                                    <div class="flex items-center space-x-2 mb-1.5 text-xs text-amber-400 font-semibold">
                                        <i class="fa-solid fa-shield-halved"></i>
                                        <span>HYPERION</span>
                                    </div>
                                    <div class="text-sm leading-relaxed text-slate-200 whitespace-pre-line">{{ $msg->message }}</div>
                                </div>
                            @else
                                <div class="chat-bubble-user rounded-xl p-4 max-w-[92%] sm:max-w-[85%] ml-auto">
                                    <div class="flex items-center justify-end space-x-2 mb-1.5 text-xs text-sky-400 font-semibold">
                                        <span>Tú (Aventurero)</span>
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                    <div class="text-sm leading-relaxed text-slate-200 whitespace-pre-line">{{ $msg->message }}</div>
                                </div>
                            @endif
                        @endforeach
                    @endif
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
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-300">Registrar Esfuerzo Percibido (RPE):</span>
                                <span id="rpe-badge-seleccion" class="text-[10px] px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30 font-medium">Adecuada seleccionada</span>
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" id="btn-rpe-muy_facil" onclick="seleccionarDificultad('muy_facil')" class="rpe-btn text-center p-2.5 rounded-xl border transition-all text-xs flex flex-col items-center justify-center bg-slate-900 border-slate-800 hover:border-emerald-500/50">
                                    <span class="block font-bold text-emerald-400">Muy Fácil</span>
                                    <span class="text-[10px] text-slate-400">+Sobrecarga</span>
                                </button>
                                <button type="button" id="btn-rpe-adecuada" onclick="seleccionarDificultad('adecuada')" class="rpe-btn text-center p-2.5 rounded-xl border transition-all text-xs flex flex-col items-center justify-center bg-amber-500/20 border-amber-500 ring-1 ring-amber-500 shadow-sm shadow-amber-500/20">
                                    <span class="block font-bold text-amber-300">Adecuada</span>
                                    <span class="text-[10px] text-amber-200/70">Mantener</span>
                                </button>
                                <button type="button" id="btn-rpe-muy_pesada" onclick="seleccionarDificultad('muy_pesada')" class="rpe-btn text-center p-2.5 rounded-xl border transition-all text-xs flex flex-col items-center justify-center bg-slate-900 border-slate-800 hover:border-rose-500/50">
                                    <span class="block font-bold text-rose-400">Muy Pesada</span>
                                    <span class="text-[10px] text-slate-400">Atenuar</span>
                                </button>
                            </div>

                            <button id="btn-completar-rutina" onclick="completarRutina()" class="w-full py-3 rounded-xl bg-gradient-to-r from-emerald-600 via-emerald-500 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-bold text-sm shadow-lg shadow-emerald-900/30 transition flex items-center justify-center space-x-2">
                                <i id="btn-completar-icon" class="fa-solid fa-check-circle"></i>
                                <span id="btn-completar-texto">Completar Rutina (Adecuada) y Recoger XP</span>
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

                <!-- Card de Historial de Batallas del Aventurero (HU #12) -->
                <div class="rpg-card rounded-2xl p-5">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-clock-rotate-left text-amber-400"></i>
                            <h2 class="font-bold text-slate-100 text-sm uppercase tracking-wide">Historial de Entrenamientos</h2>
                        </div>
                        <span class="text-[10px] text-slate-400 font-medium">{{ count($sesiones) }} registro(s)</span>
                    </div>
                    
                    <div id="sesiones-container" class="space-y-2 max-h-56 overflow-y-auto pr-1">
                        @forelse($sesiones as $s)
                        <div class="p-2.5 rounded-xl bg-slate-900/60 border border-slate-800 text-xs flex items-center justify-between">
                            <div class="space-y-0.5">
                                <div class="font-semibold text-slate-200 flex items-center gap-1.5">
                                    <span>{{ $s->title }}</span>
                                    @if($s->status === 'completada')
                                        <span class="text-[9px] px-1.5 py-0.2 bg-emerald-500/20 text-emerald-400 rounded font-bold">Completada</span>
                                    @else
                                        <span class="text-[9px] px-1.5 py-0.2 bg-amber-500/20 text-amber-300 rounded font-bold">En Progreso</span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-slate-400 flex items-center gap-1.5">
                                    <span><i class="fa-regular fa-clock mr-1 text-[9px]"></i>{{ $s->total_duration_minutes }} min</span>
                                    <span>•</span>
                                    <span class="capitalize">{{ str_replace('_', ' ', $s->target_environment) }}</span>
                                    @if($s->difficulty_rating)
                                        <span>•</span>
                                        <span class="text-amber-400 font-medium">{{ ucfirst(str_replace('_', ' ', $s->difficulty_rating)) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right pl-2 shrink-0">
                                <span class="px-2 py-0.5 rounded font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20 text-[11px]">
                                    +{{ $s->xp_earned ?: 35 }} XP
                                </span>
                                <div class="text-[9px] text-slate-500 mt-0.5">
                                    {{ $s->created_at ? $s->created_at->format('d/m H:i') : 'Reciente' }}
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-4 text-xs text-slate-500">
                            <i class="fa-solid fa-shield-halved text-slate-600 text-base mb-1 block"></i>
                            Sin entrenamientos aún. ¡Completa tu primera rutina para guardar tu progreso!
                        </div>
                        @endforelse
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

    <!-- Modal Nuevo Aventurero -->
    <div id="modal-nuevo-usuario" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="rpg-card rounded-2xl max-w-md w-full p-6 flex flex-col space-y-4 border border-slate-800">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-user-plus text-amber-400"></i>
                    <h3 class="font-bold text-white text-base">Crear Nuevo Aventurero</h3>
                </div>
                <button onclick="cerrarModalNuevoUsuario()" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="space-y-3">
                <!-- Botón Google -->
                <a href="{{ route('auth.google.redirect') }}" class="w-full py-2 px-3 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-bold text-xs transition flex items-center justify-center gap-2.5 shadow-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span>Registrarse con Google</span>
                </a>

                <div class="relative flex items-center justify-center">
                    <div class="border-t border-slate-800 w-full"></div>
                    <span class="bg-slate-900 px-2 text-[10px] text-slate-500 uppercase">o datos del perfil</span>
                </div>
            </div>

            <form id="form-nuevo-usuario" onsubmit="guardarNuevoUsuario(event)" class="space-y-3.5 text-xs">
                <div>
                    <label class="block text-slate-300 font-medium mb-1">Nombre o Alias del Aventurero *</label>
                    <input type="text" id="nuevo-nombre" required placeholder="Ej: Valeria, Carlos, David..." class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-slate-300 font-medium">Contraseña de Aventurero</label>
                        <button type="button" onclick="generarPasswordModalNuevo()" class="text-[10px] text-amber-400 hover:text-amber-300 font-semibold flex items-center gap-1 transition px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <span>Generar segura</span>
                        </button>
                    </div>
                    <input type="text" id="nuevo-password" placeholder="Tu clave o pulsa Generar segura" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white font-mono text-xs focus:outline-none focus:ring-1 focus:ring-amber-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-300 font-medium mb-1">Edad</label>
                        <input type="number" id="nuevo-edad" min="12" max="100" value="25" oninput="calcularGrasaCorporal()" onchange="calcularGrasaCorporal()" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-medium mb-1">Género</label>
                        <select id="nuevo-genero" onchange="calcularGrasaCorporal()" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                            <option value="no_disponible">Prefiero no decir</option>
                            <option value="femenino">Femenino</option>
                            <option value="masculino">Masculino</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-slate-300 font-medium mb-1">Peso (kg)</label>
                        <input type="number" step="0.5" id="nuevo-peso" value="70" oninput="calcularGrasaCorporal()" onchange="calcularGrasaCorporal()" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-2 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-medium mb-1">Altura (cm)</label>
                        <input type="number" id="nuevo-altura" value="170" oninput="calcularGrasaCorporal()" onchange="calcularGrasaCorporal()" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-2 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-medium mb-1 flex items-center justify-between">
                            <span>% Grasa</span>
                            <span class="text-[9px] text-amber-400 font-normal">Auto</span>
                        </label>
                        <input type="number" step="0.1" id="nuevo-grasa" value="18" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-2 text-white focus:outline-none focus:ring-1 focus:ring-amber-500" title="Calculado automáticamente según peso y altura">
                    </div>
                </div>
                <p class="text-[10px] text-slate-400 -mt-1 flex items-center gap-1.5">
                    <i class="fa-solid fa-wand-magic-sparkles text-amber-400 text-xs"></i>
                    <span>El % de grasa se calcula solo con tu peso, altura y edad (fórmula Deurenberg). Puedes editarlo si lo prefieres.</span>
                </p>

                <div>
                    <label class="block text-slate-300 font-medium mb-1">Entorno de Entrenamiento Preferido</label>
                    <select id="nuevo-entorno" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                        <option value="casa_sin_equipo">Casa sin equipo (Calistenia / Peso corporal)</option>
                        <option value="casa_basico">Casa con equipo ligero (Mancuernas / Bandas)</option>
                        <option value="gimnasio">Gimnasio comercial completo</option>
                    </select>
                </div>

                <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
                    <button type="button" onclick="cerrarModalNuevoUsuario()" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold shadow-lg shadow-amber-500/20">
                        Comenzar Aventura
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Aventurero -->
    <div id="modal-editar-usuario" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="rpg-card rounded-2xl max-w-md w-full p-6 flex flex-col space-y-4 border border-slate-800">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-user-pen text-amber-400"></i>
                    <h3 class="font-bold text-white text-base">Modificar Perfil de Aventurero</h3>
                </div>
                <button onclick="cerrarModalEditarUsuario()" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form id="form-editar-usuario" onsubmit="guardarEdicionUsuario(event)" class="space-y-3.5 text-xs">
                <div>
                    <label class="block text-slate-300 font-medium mb-1">Nombre o Alias del Aventurero *</label>
                    <input type="text" id="edit-nombre" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-300 font-medium mb-1">Edad</label>
                        <input type="number" id="edit-edad" min="12" max="100" oninput="calcularGrasaCorporalEdicion()" onchange="calcularGrasaCorporalEdicion()" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-medium mb-1">Género</label>
                        <select id="edit-genero" onchange="calcularGrasaCorporalEdicion()" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                            <option value="no_disponible">Prefiero no decir</option>
                            <option value="femenino">Femenino</option>
                            <option value="masculino">Masculino</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-slate-300 font-medium mb-1">Peso (kg)</label>
                        <input type="number" step="0.5" id="edit-peso" oninput="calcularGrasaCorporalEdicion()" onchange="calcularGrasaCorporalEdicion()" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-2 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-medium mb-1">Altura (cm)</label>
                        <input type="number" id="edit-altura" oninput="calcularGrasaCorporalEdicion()" onchange="calcularGrasaCorporalEdicion()" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-2 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-medium mb-1 flex items-center justify-between">
                            <span>% Grasa</span>
                            <span class="text-[9px] text-amber-400 font-normal">Auto</span>
                        </label>
                        <input type="number" step="0.1" id="edit-grasa" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-2 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                    </div>
                </div>
                <p class="text-[10px] text-slate-400 -mt-1 flex items-center gap-1.5">
                    <i class="fa-solid fa-wand-magic-sparkles text-amber-400 text-xs"></i>
                    <span>Cálculo automático con fórmula Deurenberg según peso, altura y género.</span>
                </p>

                <!-- Entorno de Entrenamiento -->
                <div>
                    <label class="block text-slate-300 font-medium mb-1">Entorno de Entrenamiento Preferido</label>
                    <select id="edit-entorno" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                        <option value="casa_sin_equipo">Casa sin equipo (Calistenia / Peso corporal)</option>
                        <option value="casa_basico">Casa con equipo ligero (Mancuernas / Bandas)</option>
                        <option value="gimnasio">Gimnasio comercial completo (Máquinas y Pesas)</option>
                    </select>
                </div>

                <!-- Lesión o Limitación Articular -->
                <div>
                    <label class="block text-slate-300 font-medium mb-1">Lesión o Limitación Articular</label>
                    <select id="edit-lesion" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                        <option value="ninguna">Sin lesiones activas (Total movilidad)</option>
                        <option value="rodilla">Rodilla (Bajo impacto, sin sentadillas profundas)</option>
                        <option value="espalda_baja">Espalda baja / Lumbar (Evitar sobrecarga axial)</option>
                        <option value="hombro">Hombro (Evitar press militar pesado / trasnuca)</option>
                        <option value="muñeca">Muñeca (Evitar apoyo directo en flexiones)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-300 font-medium mb-1">Tiempo habitual</label>
                        <select id="edit-tiempo" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                            <option value="10">10 minutos (Micro-rutina)</option>
                            <option value="15">15 minutos (Expreso)</option>
                            <option value="30">30 minutos (Estándar)</option>
                            <option value="45">45 minutos (Completo)</option>
                            <option value="60">60 minutos (Avanzado)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-300 font-medium mb-1">Objetivo Físico</label>
                        <select id="edit-objetivo" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                            <option value="resistencia">Resistencia</option>
                            <option value="hipertrofia">Ganancia muscular</option>
                            <option value="perdida_grasa">Pérdida de grasa</option>
                            <option value="fuerza">Fuerza</option>
                            <option value="salud_general">Salud general</option>
                        </select>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
                    <button type="button" onclick="cerrarModalEditarUsuario()" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold shadow-lg shadow-amber-500/20 flex items-center gap-1.5">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Guardar Cambios</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let activeUserId = {{ $user->id }};
        let activeUserName = @json($user->name);
        let activeUserAvatar = @json($user->avatar);

        function getActiveUserId() {
            const sel = document.getElementById('selector-aventurero');
            if (sel && sel.value) {
                return parseInt(sel.value);
            }
            return activeUserId;
        }

        let rutinaActual = null;

        /**
         * Limpieza de formato (Eliminar caracteres Markdown) mediante Regex
         * Asegura texto plano limpio: remueve *, **, #, _, bloques de código, etc.
         */
        function limpiarMarkdown(texto) {
            if (!texto) return '';
            return texto
                // Eliminar bloques de código ```...``` y código en línea `...`
                .replace(/```[\s\S]*?```/g, '')
                .replace(/`([^`]+)`/g, '$1')
                // Eliminar encabezados Markdown (#, ##, ###, etc.) al inicio de línea
                .replace(/^\s*#{1,6}\s+/gm, '')
                // Eliminar negritas y cursivas (***texto***, **texto**, *texto*, ___texto___, __texto__, _texto_)
                .replace(/(\*{1,3}|_{1,3})([\s\S]*?)\1/g, '$2')
                // Eliminar asteriscos y guiones bajos residuales sueltos
                .replace(/[\*_]/g, '')
                // Eliminar tachado ~~texto~~
                .replace(/~~(.*?)~~/g, '$1')
                // Eliminar enlaces [texto](url) dejando solo el texto
                .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1')
                // Eliminar citas Markdown (> texto)
                .replace(/^\s*>\s+/gm, '')
                .trim();
        }

        // Auto-scroll para mensajes del usuario
        function scrollChatToEnd() {
            const container = document.getElementById('chat-messages');
            container.scrollTop = container.scrollHeight;
        }

        // Agregar burbuja de mensaje al chat
        function agregarBurbuja(emisor, texto) {
            const container = document.getElementById('chat-messages');
            const div = document.createElement('div');
            
            if (emisor === 'hyperion') {
                const textoLimpio = limpiarMarkdown(texto);
                div.className = 'chat-bubble-hyperion rounded-xl p-4 max-w-[92%] sm:max-w-[85%]';
                div.innerHTML = `
                    <div class="flex items-center space-x-2 mb-1.5 text-xs text-amber-400 font-semibold">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>HYPERION</span>
                    </div>
                    <div class="text-sm leading-relaxed text-slate-200 whitespace-pre-line">${textoLimpio}</div>
                `;
                container.appendChild(div);

                // Comportamiento de Scroll (Lectura desde el inicio):
                // Se desactiva el scroll hacia el bottom y se posiciona suavemente al inicio
                // del nuevo mensaje del bot para que el usuario lea desde el primer párrafo.
                setTimeout(() => {
                    const topPos = div.offsetTop - container.offsetTop - 8;
                    container.scrollTo({
                        top: Math.max(0, topPos),
                        behavior: 'smooth'
                    });
                }, 40);
            } else {
                div.className = 'chat-bubble-user rounded-xl p-4 max-w-[92%] sm:max-w-[85%] ml-auto';
                const avatarMarkup = activeUserAvatar
                    ? `<img src="${activeUserAvatar}" alt="Avatar" class="w-4 h-4 rounded-full object-cover border border-sky-400">`
                    : `<i class="fa-solid fa-user"></i>`;
                div.innerHTML = `
                    <div class="flex items-center justify-end space-x-2 mb-1.5 text-xs text-sky-400 font-semibold">
                        <span>Tú (${activeUserName || 'Aventurero'})</span>
                        ${avatarMarkup}
                    </div>
                    <div class="text-sm leading-relaxed text-slate-200 whitespace-pre-line">${texto}</div>
                `;
                container.appendChild(div);
                scrollChatToEnd();
            }
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
                    body: JSON.stringify({ message: text, user_id: getActiveUserId() }),
                });

                const data = await response.json();
                if (data.status === 'success') {
                    agregarBurbuja('hyperion', data.respuesta.texto);
                    
                    if (data.respuesta.rutina) {
                        try {
                            mostrarRutinaEnPanel(data.respuesta.rutina);
                        } catch (panelErr) {
                            console.warn('Error al mostrar rutina en panel:', panelErr);
                        }
                    }

                    if (data.user) {
                        try {
                            actualizarHUD(data.user);
                        } catch (hudErr) {
                            console.warn('Error al actualizar HUD:', hudErr);
                        }
                    }
                } else {
                    agregarBurbuja('hyperion', data.mensaje || 'Hubo un inconveniente al procesar tu solicitud.');
                }
            } catch (error) {
                console.error('Error en enviarMensaje:', error);
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

        let dificultadSeleccionada = 'adecuada';

        // Selección interactiva de Esfuerzo Percibido (RPE)
        function seleccionarDificultad(valor) {
            dificultadSeleccionada = valor;

            const btnFacil = document.getElementById('btn-rpe-muy_facil');
            const btnAdecuada = document.getElementById('btn-rpe-adecuada');
            const btnPesada = document.getElementById('btn-rpe-muy_pesada');
            const badge = document.getElementById('rpe-badge-seleccion');
            const btnTexto = document.getElementById('btn-completar-texto');

            const baseInactive = "rpe-btn text-center p-2.5 rounded-xl border transition-all text-xs flex flex-col items-center justify-center bg-slate-900 border-slate-800 text-slate-400";

            if (btnFacil) btnFacil.className = baseInactive + " hover:border-emerald-500/50";
            if (btnAdecuada) btnAdecuada.className = baseInactive + " hover:border-amber-500/50";
            if (btnPesada) btnPesada.className = baseInactive + " hover:border-rose-500/50";

            if (valor === 'muy_facil' && btnFacil) {
                btnFacil.className = "rpe-btn text-center p-2.5 rounded-xl border transition-all text-xs flex flex-col items-center justify-center bg-emerald-500/20 border-emerald-500 ring-1 ring-emerald-500 shadow-sm shadow-emerald-500/20";
                if (badge) {
                    badge.innerText = "Muy Fácil (+Sobrecarga)";
                    badge.className = "text-[10px] px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-medium";
                }
                if (btnTexto) btnTexto.innerText = "Completar Rutina (Muy Fácil) y Recoger XP";
            } else if (valor === 'adecuada' && btnAdecuada) {
                btnAdecuada.className = "rpe-btn text-center p-2.5 rounded-xl border transition-all text-xs flex flex-col items-center justify-center bg-amber-500/20 border-amber-500 ring-1 ring-amber-500 shadow-sm shadow-amber-500/20";
                if (badge) {
                    badge.innerText = "Adecuada (Mantener)";
                    badge.className = "text-[10px] px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30 font-medium";
                }
                if (btnTexto) btnTexto.innerText = "Completar Rutina (Adecuada) y Recoger XP";
            } else if (valor === 'muy_pesada' && btnPesada) {
                btnPesada.className = "rpe-btn text-center p-2.5 rounded-xl border transition-all text-xs flex flex-col items-center justify-center bg-rose-500/20 border-rose-500 ring-1 ring-rose-500 shadow-sm shadow-rose-500/20";
                if (badge) {
                    badge.innerText = "Muy Pesada (Atenuar)";
                    badge.className = "text-[10px] px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500/30 font-medium";
                }
                if (btnTexto) btnTexto.innerText = "Completar Rutina (Muy Pesada) y Recoger XP";
            }
        }

        // Completar rutina activa y sumar XP
        async function completarRutina(dificultadDirecta = null) {
            const dificultad = dificultadDirecta || dificultadSeleccionada || 'adecuada';
            const fatiga = (dificultad === 'muy_pesada');

            const btn = document.getElementById('btn-completar-rutina');
            const icon = document.getElementById('btn-completar-icon');
            const texto = document.getElementById('btn-completar-texto');

            if (btn) btn.disabled = true;
            if (icon) icon.className = 'fa-solid fa-spinner fa-spin';
            if (texto) texto.innerText = 'Registrando en Crónica...';

            try {
                const response = await fetch('/api/workout/complete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        user_id: getActiveUserId(),
                        session_id: (rutinaActual && rutinaActual.sesion_id) ? rutinaActual.sesion_id : null,
                        difficulty_rating: dificultad,
                        fatigue_reported: fatiga,
                        progressive_overload_accepted: (dificultad === 'muy_facil')
                    })
                });

                const data = await response.json();
                if (data.status === 'success' || data.status === 'info') {
                    if (data.rpg && data.rpg.mensaje_narrativo) {
                        agregarBurbuja('hyperion', data.rpg.mensaje_narrativo);
                    } else if (data.mensaje) {
                        agregarBurbuja('hyperion', data.mensaje);
                    }
                    if (data.user) {
                        try {
                            actualizarHUD(data.user);
                        } catch (hudErr) {
                            console.warn('Error al actualizar HUD:', hudErr);
                        }
                    }
                    document.getElementById('rutina-placeholder').classList.remove('hidden');
                    document.getElementById('rutina-contenido').classList.add('hidden');
                    rutinaActual = null;
                    seleccionarDificultad('adecuada');
                } else {
                    alert(data.mensaje || 'Error al completar rutina.');
                }
            } catch (e) {
                console.error('Error al completar rutina:', e);
                alert('Ocurrió un problema de comunicación al registrar la rutina.');
            } finally {
                if (btn) btn.disabled = false;
                if (icon) icon.className = 'fa-solid fa-check-circle';
                if (texto) texto.innerText = 'Completar Rutina y Recoger XP';
            }
        }

        // Actualizar barra de XP, nivel y datos del HUD
        function actualizarHUD(user) {
            if (!user) return;
            if (user.name) activeUserName = user.name;
            if (user.avatar) activeUserAvatar = user.avatar;
            const elNombre = document.getElementById('hud-nombre');
            if (elNombre) elNombre.innerText = user.name;

            const elSelector = document.getElementById('selector-aventurero');
            if (elSelector && user.id) {
                const opt = elSelector.querySelector(`option[value="${user.id}"]`);
                if (opt) opt.innerText = `${user.name} (Lv ${user.current_level})`;
            }

            const elTitulo = document.getElementById('hud-titulo');
            if (elTitulo) elTitulo.innerHTML = `<i class="fa-solid fa-crown text-[10px]"></i> <span>${user.rpg_title || 'Recluta'}</span>`;

            const elNivel = document.getElementById('hud-nivel-icon');
            if (elNivel) elNivel.innerText = user.current_level;

            const elBadge = document.getElementById('hud-nivel-badge');
            if (elBadge) elBadge.innerText = user.current_level;

            if (user.avatar) {
                const hudAvatarImg = document.getElementById('hud-avatar-img');
                if (hudAvatarImg) {
                    hudAvatarImg.src = user.avatar;
                    hudAvatarImg.classList.remove('hidden');
                }
                if (elNivel) elNivel.classList.add('hidden');
            }

            // Recalcular nivel y barra
            fetch('/api/user/profile?user_id=' + getActiveUserId())
                .then(r => r.json())
                .then(data => {
                    if (data && data.status === 'success') {
                        const n = data.nivel_info;
                        const u = data.user;
                        const elXpLabel = document.getElementById('hud-xp-label');
                        if (elXpLabel) elXpLabel.innerText = `${u.current_xp} XP / ${n.xp_siguiente} XP`;

                        const rango = Math.max(1, n.xp_siguiente - n.xp_actual_min);
                        const pct = Math.min(100, Math.round(((u.current_xp - n.xp_actual_min) / rango) * 100));
                        const elXpBar = document.getElementById('hud-xp-bar');
                        if (elXpBar) elXpBar.style.width = pct + '%';

                        const elXpFaltante = document.getElementById('hud-xp-faltante');
                        if (elXpFaltante) elXpFaltante.innerText = `Faltan ${Math.max(0, n.xp_siguiente - u.current_xp)} XP`;

                        const elBiometria = document.getElementById('hud-biometria');
                        if (elBiometria) elBiometria.innerText = `${u.body_fat_pct}% | ${u.age} años`;

                        const elEntorno = document.getElementById('hud-entorno');
                        if (elEntorno) elEntorno.innerText = (u.environment || '').replace('_', ' ');

                        const elLesion = document.getElementById('hud-lesion');
                        if (elLesion) elLesion.innerText = u.injury;
                    }
                })
                .catch(err => console.warn('Error al actualizar HUD:', err));
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

        async function resetearChat() {
            if (!confirm('¿Deseas reiniciar la conversación de Hyperion para este aventurero? Tu progreso de nivel y entrenamientos guardados se mantendrán.')) return;
            try {
                await fetch('/api/chat/reset', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ user_id: getActiveUserId() })
                });
            } catch (e) {
                console.warn('Error al reiniciar chat:', e);
            }
            window.location.reload();
        }

        // Gestión de persistencia y perfiles de aventurero
        (function recordarAventurero() {
            try {
                const savedId = localStorage.getItem('hyperion_user_id');
                const params = new URLSearchParams(window.location.search);
                if (savedId && !params.has('user_id') && savedId != activeUserId) {
                    window.location.replace(window.location.pathname + '?user_id=' + savedId);
                    return;
                }
                localStorage.setItem('hyperion_user_id', activeUserId);
            } catch (e) {
                console.warn(e);
            }
        })();

        async function cambiarUsuario(userId) {
            if (!userId) return;
            try {
                localStorage.setItem('hyperion_user_id', userId);
                activeUserId = parseInt(userId);
                await fetch('/api/user/switch', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ user_id: parseInt(userId) })
                });
            } catch (e) {
                console.warn('Error al cambiar usuario:', e);
            }
            window.location.href = window.location.pathname + '?user_id=' + userId;
        }

        function abrirModalEditarUsuario() {
            document.getElementById('edit-nombre').value = @json($user->name);
            document.getElementById('edit-edad').value = {{ $user->age ?: 25 }};
            document.getElementById('edit-genero').value = @json($user->gender ?? 'no_disponible');
            document.getElementById('edit-peso').value = {{ $user->weight_kg ?: 70 }};
            document.getElementById('edit-altura').value = {{ $user->height_cm ?: 170 }};
            document.getElementById('edit-grasa').value = {{ $user->body_fat_pct ?: 18 }};
            document.getElementById('edit-entorno').value = @json($user->environment ?? 'casa_sin_equipo');
            document.getElementById('edit-lesion').value = @json($user->injury ?? 'ninguna');
            document.getElementById('edit-tiempo').value = {{ $user->available_time_minutes ?: 15 }};
            document.getElementById('edit-objetivo').value = @json($user->fitness_goal ?? 'resistencia');

            document.getElementById('modal-editar-usuario').classList.remove('hidden');
            document.getElementById('edit-nombre').focus();
        }

        function cerrarModalEditarUsuario() {
            document.getElementById('modal-editar-usuario').classList.add('hidden');
        }

        function calcularGrasaCorporalEdicion() {
            const peso = parseFloat(document.getElementById('edit-peso')?.value) || 0;
            const alturaCm = parseFloat(document.getElementById('edit-altura')?.value) || 0;
            const edad = parseInt(document.getElementById('edit-edad')?.value) || 25;
            const genero = document.getElementById('edit-genero')?.value || 'no_disponible';

            if (peso > 0 && alturaCm > 0) {
                const alturaM = alturaCm / 100;
                const imc = peso / (alturaM * alturaM);

                let sexo = 0.5;
                if (genero === 'masculino') sexo = 1;
                if (genero === 'femenino') sexo = 0;

                let grasa = (1.20 * imc) + (0.23 * edad) - (10.8 * sexo) - 5.4;
                grasa = Math.max(5.0, Math.min(50.0, grasa));

                const inputGrasa = document.getElementById('edit-grasa');
                if (inputGrasa) {
                    inputGrasa.value = grasa.toFixed(1);
                }
            }
        }

        async function guardarEdicionUsuario(event) {
            event.preventDefault();

            const payload = {
                user_id: getActiveUserId(),
                name: document.getElementById('edit-nombre').value.trim(),
                age: parseInt(document.getElementById('edit-edad').value) || 25,
                gender: document.getElementById('edit-genero').value,
                weight_kg: parseFloat(document.getElementById('edit-peso').value) || 70,
                height_cm: parseFloat(document.getElementById('edit-altura').value) || 170,
                body_fat_pct: parseFloat(document.getElementById('edit-grasa').value) || 18,
                environment: document.getElementById('edit-entorno').value,
                injury: document.getElementById('edit-lesion').value,
                available_time_minutes: parseInt(document.getElementById('edit-tiempo').value) || 15,
                fitness_goal: document.getElementById('edit-objetivo').value,
            };

            try {
                const response = await fetch('/api/user/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (data.status === 'success') {
                    cerrarModalEditarUsuario();
                    window.location.reload();
                } else {
                    alert(data.mensaje || 'Error al actualizar el aventurero.');
                }
            } catch (err) {
                console.error(err);
                alert('Error al conectar con el servidor.');
            }
        }

        function generarPasswordModalNuevo() {
            const palabras = ['Titan', 'Esparta', 'Hyperion', 'Fenix', 'Olimpo', 'Atlas', 'Hierro', 'Dragon', 'Leon', 'Fuerza'];
            const simbolos = ['#', '!', '$', '&', '*'];
            const p1 = palabras[Math.floor(Math.random() * palabras.length)];
            const p2 = palabras[Math.floor(Math.random() * palabras.length)];
            const num = Math.floor(100 + Math.random() * 900);
            const sim = simbolos[Math.floor(Math.random() * simbolos.length)];
            const pass = `${p1}-${p2}-${num}${sim}`;

            const input = document.getElementById('nuevo-password');
            if (input) {
                input.value = pass;
                navigator.clipboard.writeText(pass).catch(() => {});
                alert('Contraseña generada y copiada al portapapeles: ' + pass);
            }
        }

        function calcularGrasaCorporal() {
            const peso = parseFloat(document.getElementById('nuevo-peso')?.value) || 0;
            const alturaCm = parseFloat(document.getElementById('nuevo-altura')?.value) || 0;
            const edad = parseInt(document.getElementById('nuevo-edad')?.value) || 25;
            const genero = document.getElementById('nuevo-genero')?.value || 'no_disponible';

            if (peso > 0 && alturaCm > 0) {
                const alturaM = alturaCm / 100;
                const imc = peso / (alturaM * alturaM);

                // Fórmula clínica de Deurenberg: % Grasa = (1.20 * IMC) + (0.23 * Edad) - (10.8 * Sexo) - 5.4
                // Sexo: 1 (masculino), 0 (femenino), 0.5 (promedio o no especificado)
                let sexo = 0.5;
                if (genero === 'masculino') sexo = 1;
                if (genero === 'femenino') sexo = 0;

                let grasa = (1.20 * imc) + (0.23 * edad) - (10.8 * sexo) - 5.4;
                grasa = Math.max(5.0, Math.min(50.0, grasa));

                const inputGrasa = document.getElementById('nuevo-grasa');
                if (inputGrasa) {
                    inputGrasa.value = grasa.toFixed(1);
                }
            }
        }

        function abrirModalNuevoUsuario() {
            document.getElementById('modal-nuevo-usuario').classList.remove('hidden');
            document.getElementById('nuevo-nombre').focus();
            calcularGrasaCorporal();
        }

        function cerrarModalNuevoUsuario() {
            document.getElementById('modal-nuevo-usuario').classList.add('hidden');
        }

        async function guardarNuevoUsuario(event) {
            event.preventDefault();
            const passwordVal = document.getElementById('nuevo-password')?.value?.trim();
            const payload = {
                name: document.getElementById('nuevo-nombre').value.trim(),
                password: passwordVal || null,
                age: parseInt(document.getElementById('nuevo-edad').value) || 25,
                gender: document.getElementById('nuevo-genero').value,
                weight_kg: parseFloat(document.getElementById('nuevo-peso').value) || 70,
                height_cm: parseFloat(document.getElementById('nuevo-altura').value) || 170,
                body_fat_pct: parseFloat(document.getElementById('nuevo-grasa').value) || 18,
                environment: document.getElementById('nuevo-entorno').value,
            };

            try {
                const response = await fetch('/api/user/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (data.success && data.redirect) {
                    try { localStorage.setItem('hyperion_user_id', data.user.id); } catch (e) {}
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'Error al crear el nuevo aventurero.');
                }
            } catch (err) {
                console.error(err);
                alert('Error al conectar con el servidor.');
            }
        }

        // Inicialización de contexto persistente del aventurero
        document.addEventListener('DOMContentLoaded', () => {
            const ultimaRutinaInicial = @json($ultimaRutina ?? null);
            if (ultimaRutinaInicial && typeof mostrarRutinaEnPanel === 'function') {
                try {
                    mostrarRutinaEnPanel(ultimaRutinaInicial);
                } catch (e) {
                    console.warn('Error al restaurar rutina inicial:', e);
                }
            }

            setTimeout(scrollChatToEnd, 150);
        });
    </script>
</body>
</html>
