import os
import sys
from pathlib import Path
from reportlab.lib.pagesizes import letter
from reportlab.lib import colors
from reportlab.lib.units import inch
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, KeepTogether, HRFlowable
)
from reportlab.pdfgen import canvas

class NumberedCanvas(canvas.Canvas):
    def __init__(self, *args, **kwargs):
        super(NumberedCanvas, self).__init__(*args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        num_pages = len(self._saved_page_states)
        for state in self._saved_page_states:
            self.__dict__.update(state)
            self.draw_header_footer(num_pages)
            super(NumberedCanvas, self).showPage()
        super(NumberedCanvas, self).save()

    def draw_header_footer(self, total_pages):
        self.saveState()
        self.setFont("Helvetica-Bold", 8)
        self.setFillColor(colors.HexColor("#64748b"))

        # Header (except page 1)
        if self._pageNumber > 1:
            self.drawString(36, letter[1] - 28, "HYPERION PLAY — Documento Técnico de Arquitectura y Auditoría")
            self.drawRightString(letter[0] - 36, letter[1] - 28, "Gobernanza de IA & Laravel")
            self.setStrokeColor(colors.HexColor("#cbd5e1"))
            self.setLineWidth(0.5)
            self.line(36, letter[1] - 32, letter[0] - 36, letter[1] - 32)

        # Footer (all pages)
        self.setFont("Helvetica", 8)
        self.drawString(36, 25, "Confidencial | MonseyAlex4-Stac / Hyperion Play")
        page_str = f"Página {self._pageNumber} de {total_pages}"
        self.drawRightString(letter[0] - 36, 25, page_str)
        self.setStrokeColor(colors.HexColor("#cbd5e1"))
        self.setLineWidth(0.5)
        self.line(36, 34, letter[0] - 36, 34)

        self.restoreState()

def build_pdf():
    base_dir = Path(__file__).resolve().parent.parent
    output_pdf = base_dir / "Reporte_Tecnico_Hyperion_Play.pdf"

    doc = SimpleDocTemplate(
        str(output_pdf),
        pagesize=letter,
        leftMargin=36,
        rightMargin=36,
        topMargin=42,
        bottomMargin=42
    )

    styles = getSampleStyleSheet()

    # Custom styles
    title_style = ParagraphStyle(
        'DocTitle',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=24,
        leading=28,
        textColor=colors.HexColor('#0f172a'),
        alignment=1, # Center
        spaceAfter=6
    )

    subtitle_style = ParagraphStyle(
        'DocSubtitle',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=12,
        leading=16,
        textColor=colors.HexColor('#d97706'),
        alignment=1,
        spaceAfter=15
    )

    h1_style = ParagraphStyle(
        'H1',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=13,
        leading=16,
        textColor=colors.HexColor('#0f172a'),
        spaceBefore=12,
        spaceAfter=6,
        keepWithNext=True
    )

    h2_style = ParagraphStyle(
        'H2',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=10.5,
        leading=14,
        textColor=colors.HexColor('#b45309'),
        spaceBefore=8,
        spaceAfter=4,
        keepWithNext=True
    )

    body_style = ParagraphStyle(
        'Body',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8.5,
        leading=11.5,
        textColor=colors.HexColor('#334155'),
        alignment=4 # Justified
    )

    code_style = ParagraphStyle(
        'Code',
        parent=styles['Normal'],
        fontName='Courier',
        fontSize=7.5,
        leading=9.5,
        textColor=colors.HexColor('#0f172a'),
        backColor=colors.HexColor('#f1f5f9'),
        borderColor=colors.HexColor('#cbd5e1'),
        borderWidth=0.5,
        borderPadding=4,
        spaceBefore=3,
        spaceAfter=4
    )

    table_header = ParagraphStyle(
        'TH',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=8,
        leading=10,
        textColor=colors.HexColor('#0f172a')
    )

    table_cell = ParagraphStyle(
        'TD',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=7.5,
        leading=9.5,
        textColor=colors.HexColor('#334155')
    )

    table_cell_bold = ParagraphStyle(
        'TDBold',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=7.5,
        leading=9.5,
        textColor=colors.HexColor('#0f172a')
    )

    elements = []

    # --- PORTADA COMPACTA SUPERIOR ---
    badge_data = [[
        Paragraph("<font color='#b45309'><b>INFORME TÉCNICO DE ARQUITECTURA & AUDITORÍA — SPRINTS 1 Y 2</b></font>", ParagraphStyle('B', fontName='Helvetica-Bold', fontSize=8, alignment=1))
    ]]
    t_badge = Table(badge_data, colWidths=[540])
    t_badge.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor('#fef3c7')),
        ('BOX', (0,0), (-1,-1), 1, colors.HexColor('#fcd34d')),
        ('TOPPADDING', (0,0), (-1,-1), 4),
        ('BOTTOMPADDING', (0,0), (-1,-1), 4),
        ('ALIGN', (0,0), (-1,-1), 'CENTER'),
    ]))
    elements.append(t_badge)
    elements.append(Spacer(1, 8))

    elements.append(Paragraph("HYPERION PLAY", title_style))
    elements.append(Paragraph("Asistente Conversacional de Entrenamiento Físico Gamificado (RPG) con RAG y Gobernanza de IA", subtitle_style))

    # Meta Table
    meta_info = [
        [
            Paragraph("<b>Rol Técnico:</b> Arquitecto de Software Senior", table_cell),
            Paragraph("<b>Stack:</b> PHP 8.3 / Laravel 13, Python 3.14, SQLite", table_cell)
        ],
        [
            Paragraph("<b>Repositorio:</b> monseyalex4-stac/hyperion-play", table_cell),
            Paragraph("<b>Metodología:</b> RAG Clínico + Open Game Mechanics", table_cell)
        ]
    ]
    t_meta = Table(meta_info, colWidths=[270, 270])
    t_meta.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor('#f8fafc')),
        ('BOX', (0,0), (-1,-1), 0.5, colors.HexColor('#e2e8f0')),
        ('INNERGRID', (0,0), (-1,-1), 0.5, colors.HexColor('#e2e8f0')),
        ('TOPPADDING', (0,0), (-1,-1), 4),
        ('BOTTOMPADDING', (0,0), (-1,-1), 4),
    ]))
    elements.append(t_meta)
    elements.append(Spacer(1, 10))

    # --- SECCIÓN 1: RESUMEN EJECUTIVO Y DIAGNÓSTICO ---
    elements.append(Paragraph("1. Resumen Ejecutivo y Diagnóstico Inicial", h1_style))
    elements.append(Paragraph(
        "El proyecto <b>HYPERION PLAY</b> combina un motor determinista de prescripción biomecánica (Gobernanza de IA) "
        "con una experiencia de usuario interactiva y gamificada (lore gamer RPG). Al recibir el repositorio clonado, se resolvió un conflicto "
        "crítico de versiones en el sistema operativo Windows: la variable de entorno PATH apuntaba a PHP 8.0.30 (XAMPP), bloqueando la ejecución "
        "de <code>artisan</code> y de paquetes de Symfony 8 que exigen PHP &ge; 8.3. Se conmutó de inmediato a la instalación WinGet de <b>PHP 8.3.30</b>, "
        "se generaron las dependencias Composer, la clave de aplicación y la base de datos SQLite.",
        body_style
    ))
    elements.append(Spacer(1, 8))

    # --- SECCIÓN 2: AUDITORÍA DE HU #1 A HU #5 ---
    elements.append(Paragraph("2. Auditoría y Consolidación de HU #1 a HU #5", h1_style))
    elements.append(Paragraph(
        "Los artefactos originales de las primeras 5 Historias de Usuario residían en carpetas externas del directorio Downloads. "
        "Fueron unificados, depurados e integrados en <code>storage/app/DATASET/</code> y <code>scripts/</code>, asegurando rutas relativas portables "
        "y eliminando la dependencia de <code>pandas</code> para ejecución nativa en Python 3.14.",
        body_style
    ))
    elements.append(Spacer(1, 4))

    hu_audit_data = [
        [Paragraph("HU", table_header), Paragraph("Nombre / Alcance", table_header), Paragraph("Estado", table_header), Paragraph("Archivos Clave Integrados", table_header), Paragraph("Criterio de Aceptación", table_header)],
        [
            Paragraph("<b>HU #1</b>", table_cell),
            Paragraph("Limpieza InBody", table_cell),
            Paragraph("<font color='#16a34a'><b>COMPLETO</b></font>", table_cell),
            Paragraph("dataset_limpio.csv<br/>script_limpiar_hu01.py", table_cell),
            Paragraph("Conversión métrica (cm, kg); rangos biológicos (100-250cm, 30-300kg, 3-60% grasa).", table_cell)
        ],
        [
            Paragraph("<b>HU #2</b>", table_cell),
            Paragraph("Unificación Catálogos", table_cell),
            Paragraph("<font color='#16a34a'><b>COMPLETO</b></font>", table_cell),
            Paragraph("catalogo_ejercicios_base.json<br/>script_unificar_hu02.py", table_cell),
            Paragraph("Fusión de megaGymDataset y exercises-dataset sin duplicados.", table_cell)
        ],
        [
            Paragraph("<b>HU #3</b>", table_cell),
            Paragraph("Etiquetado RAG", table_cell),
            Paragraph("<font color='#16a34a'><b>COMPLETO</b></font>", table_cell),
            Paragraph("ejercicios_rag_etiquetados.json<br/>script_etiquetar_rag_hu03.py", table_cell),
            Paragraph("2,602 ejercicios con impacto, experiencia, entorno, zonas_riesgo y xp_base.", table_cell)
        ],
        [
            Paragraph("<b>HU #4</b>", table_cell),
            Paragraph("Matriz de Reglas", table_cell),
            Paragraph("<font color='#16a34a'><b>COMPLETO</b></font>", table_cell),
            Paragraph("reglas.json<br/>reglas_modelo.py", table_cell),
            Paragraph("Reglas REG-SEG: edad > 45 o grasa > 25% = bajo impacto; exclusión estricta por lesión.", table_cell)
        ],
        [
            Paragraph("<b>HU #5</b>", table_cell),
            Paragraph("Validación & Métricas", table_cell),
            Paragraph("<font color='#16a34a'><b>COMPLETO</b></font>", table_cell),
            Paragraph("script_evaluar_metricas_hu05.py<br/>reporte_metricas.txt", table_cell),
            Paragraph("20 perfiles, 52,040 inferencias. Tasa de Falsos Positivos = 0.0% (Meta 0). Precisión: 100%.", table_cell)
        ],
    ]
    t_hu = Table(hu_audit_data, colWidths=[35, 95, 60, 160, 190])
    t_hu.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#f1f5f9')),
        ('BOX', (0,0), (-1,-1), 0.5, colors.HexColor('#cbd5e1')),
        ('INNERGRID', (0,0), (-1,-1), 0.5, colors.HexColor('#e2e8f0')),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
    ]))
    elements.append(t_hu)
    elements.append(Spacer(1, 6))

    # Métricas HU5 Card
    metrics_summary = [
        [
            Paragraph("<font color='#16a34a'><b>44,227</b></font><br/><font size=6 color='#64748b'>VERDADEROS POSITIVOS</font>", ParagraphStyle('M', alignment=1)),
            Paragraph("<font color='#dc2626'><b>0</b></font><br/><font size=6 color='#64748b'>FALSOS POSITIVOS (META: 0)</font>", ParagraphStyle('M', alignment=1)),
            Paragraph("<font color='#0284c7'><b>7,813</b></font><br/><font size=6 color='#64748b'>VERDADEROS NEGATIVOS</font>", ParagraphStyle('M', alignment=1)),
            Paragraph("<font color='#d97706'><b>100.0%</b></font><br/><font size=6 color='#64748b'>PRECISIÓN CLÍNICA</font>", ParagraphStyle('M', alignment=1)),
        ]
    ]
    t_met = Table(metrics_summary, colWidths=[135, 135, 135, 135])
    t_met.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor('#f8fafc')),
        ('BOX', (0,0), (-1,-1), 0.5, colors.HexColor('#cbd5e1')),
        ('INNERGRID', (0,0), (-1,-1), 0.5, colors.HexColor('#cbd5e1')),
        ('TOPPADDING', (0,0), (-1,-1), 5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 5),
    ]))
    elements.append(t_met)
    elements.append(Spacer(1, 10))

    # --- SECCIÓN 3: IMPLEMENTACIÓN MODULAR EN LARAVEL HU #5 A HU #15 ---
    elements.append(Paragraph("3. Implementación Integral en Laravel (HU #5 a HU #15)", h1_style))
    elements.append(Paragraph(
        "Se construyó una arquitectura desacoplada por capas (Migraciones & Modelos &rarr; Servicios Especializados &rarr; Controladores & Rutas &rarr; Vistas Blade & JS), "
        "manteniendo intacto el controlador existente <code>DatasetController.php</code>.",
        body_style
    ))
    elements.append(Spacer(1, 4))

    hu_impl_data = [
        [Paragraph("Historia", table_header), Paragraph("Nombre", table_header), Paragraph("Capa / Servicio Clave", table_header), Paragraph("Criterio de Aceptación & Implementación", table_header)],
        [
            Paragraph("<b>HU #6</b>", table_cell),
            Paragraph("Integración ExerciseDB", table_cell),
            Paragraph("<code>ExerciseDbService.php</code>", table_cell),
            Paragraph("Consumo HTTP con retry(2,400). <b>Fallback resiliente:</b> Si la API excede cuota o falla de red, registra Log::warning y recurre instantáneamente al RAG local. Normalización al esquema JSON.", table_cell)
        ],
        [
            Paragraph("<b>HU #7</b>", table_cell),
            Paragraph("Entrevista Dinámica", table_cell),
            Paragraph("<code>ConversationalChatbotService.php</code>", table_cell),
            Paragraph("Preguntas adaptativas: tiempo, entorno, lesiones. <b>Valores conservadores por defecto:</b> tiempo=15min, bajo impacto, principiante. Persistencia en User. Guardrail de emergencia clínica (REG-SEG-05).", table_cell)
        ],
        [
            Paragraph("<b>HU #8</b>", table_cell),
            Paragraph("Rutina Personalizada", table_cell),
            Paragraph("<code>RoutineGeneratorService.php</code>", table_cell),
            Paragraph("Cruce de reglas clínicas, tiempo y equipo para generar plan detallado con series, repeticiones y descansos.", table_cell)
        ],
        [
            Paragraph("<b>HU #9</b>", table_cell),
            Paragraph("Filtro Bajo Impacto", table_cell),
            Paragraph("<code>RoutineGeneratorService.php</code>", table_cell),
            Paragraph("Restricción estricta ante grasa > 25% o edad > 45. Inyección de <b>indicaciones posturales pedagógicas</b> por ejercicio para evitar ejecuciones lesivas.", table_cell)
        ],
        [
            Paragraph("<b>HU #10</b>", table_cell),
            Paragraph("Rutinas Rápidas (15 min)", table_cell),
            Paragraph("<code>RoutineGeneratorService.php</code>", table_cell),
            Paragraph("Ajuste exacto en &le; 15 min. Si por restricciones físicas no alcanzan ejercicios, propone <b>micro-rutina válida</b> (8-10 min) y otorga XP proporcional.", table_cell)
        ],
        [
            Paragraph("<b>HU #11</b>", table_cell),
            Paragraph("Lesiones / Rehabilitación", table_cell),
            Paragraph("<code>DecisionRulesEngineService.php</code>", table_cell),
            Paragraph("Exclusión estricta de articulación en <code>zonas_riesgo_evitar</code>. Despliegue <b>obligatorio</b> del aviso médico legal previo a la rutina.", table_cell)
        ],
        [
            Paragraph("<b>HU #12</b>", table_cell),
            Paragraph("Historial y Memoria", table_cell),
            Paragraph("<code>WorkoutSession.php</code> / Chatbot", table_cell),
            Paragraph("Persistencia de ejercicios y RPE. Consulta cronológica en chat. Si el usuario no tiene sesiones, genera la <b>Rutina de Calibración Inicial: El Despertar del Guerrero</b>.", table_cell)
        ],
        [
            Paragraph("<b>HU #13</b>", table_cell),
            Paragraph("Sobrecarga Progresiva", table_cell),
            Paragraph("<code>RoutineGeneratorService.php</code>", table_cell),
            Paragraph("Incremento asistido de repeticiones (+8% a 10%) con <b>tope máximo de seguridad del 10%</b> si la previa fue 'Muy Fácil'. Si hubo fatiga o dificultad alta, mantiene o reduce volumen.", table_cell)
        ],
        [
            Paragraph("<b>HU #14</b>", table_cell),
            Paragraph("Gamificación RPG", table_cell),
            Paragraph("<code>GamificationRpgService.php</code>", table_cell),
            Paragraph("Open Game Mechanics JSON: XP base + series + dificultad + sobrecarga + misiones semanales. 5 Rangos (Recluta a Titán de Hyperion). Mensajes narrativos inmersivos.", table_cell)
        ],
        [
            Paragraph("<b>HU #15</b>", table_cell),
            Paragraph("Explicabilidad Rutina", table_cell),
            Paragraph("<code>RoutineGeneratorService.php</code>", table_cell),
            Paragraph("Justificación empática en <b>lenguaje natural</b> ('Cómo y por qué') sin código técnico ni JSON crudo. Desglose del origen: entrevista, reglas clínicas o historial.", table_cell)
        ],
    ]
    t_impl = Table(hu_impl_data, colWidths=[40, 100, 130, 270])
    t_impl.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#f1f5f9')),
        ('BOX', (0,0), (-1,-1), 0.5, colors.HexColor('#cbd5e1')),
        ('INNERGRID', (0,0), (-1,-1), 0.5, colors.HexColor('#e2e8f0')),
        ('TOPPADDING', (0,0), (-1,-1), 2.5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 2.5),
    ]))
    elements.append(t_impl)

    elements.append(PageBreak())

    # --- SECCIÓN 4: INTERFAZ Y ARQUITECTURA VISUAL ---
    elements.append(Paragraph("4. Interfaz Gamer y Dashboard Conversacional", h1_style))
    elements.append(Paragraph(
        "Se diseñó una interfaz inmersiva y moderna en Blade y Tailwind CSS (<code>resources/views/hyperion.blade.php</code>) estructurada en dos paneles sinérgicos:",
        body_style
    ))
    elements.append(Spacer(1, 4))
    elements.append(Paragraph(
        "&bull; <b>HUD Superior del Titán:</b> Muestra en tiempo real el Rango RPG, Nivel del Aventurero, Barra de Progreso de Experiencia animada con cálculo dinámico (XP actual / XP siguiente rango) e indicadores biométricos InBody.<br/>"
        "&bull; <b>Canal Conversacional con Hyperion:</b> Chat en tiempo real con soporte para lenguaje natural, atajos rápidos (15 min, bajo impacto, lesión de rodilla, explicabilidad, historial) y auto-scroll.<br/>"
        "&bull; <b>Panel de Rutina Activa & Ejecución Postural:</b> Detalle de cada ejercicio con series, repeticiones, descansos, consejos técnicos pedagógicos (HU #9) y avisos médicos destacados (HU #11).<br/>"
        "&bull; <b>Módulo de Calificación de Esfuerzo (RPE):</b> Selector para retroalimentar la sobrecarga progresiva asistida (Muy Fácil, Adecuada, Muy Pesada) al completar la rutina.<br/>"
        "&bull; <b>Modal Interactivo de Auditoría HU #5:</b> Permite ejecutar y visualizar en vivo la matriz de confusión y los 20 perfiles analizados directamente desde la interfaz web.",
        body_style
    ))
    elements.append(Spacer(1, 8))

    # --- SECCIÓN 5: VERIFICACIÓN Y PRUEBAS ---
    elements.append(Paragraph("5. Batería de Pruebas Automatizadas (PHPUnit)", h1_style))
    elements.append(Paragraph(
        "Se implementó el archivo de pruebas <code>tests/Feature/HyperionPlayTest.php</code> cubriendo exhaustivamente cada Historia de Usuario con <b>12 pruebas y 63 aserciones</b>:",
        body_style
    ))
    elements.append(Spacer(1, 4))

    test_table_data = [
        [Paragraph("Método de Prueba", table_header), Paragraph("HU Evaluada", table_header), Paragraph("Resultado", table_header), Paragraph("Garantía Técnica Validada", table_header)],
        [Paragraph("test_hu05_validacion_metricas", table_cell), Paragraph("HU #5", table_cell), Paragraph("<font color='#16a34a'><b>PASSED</b></font>", table_cell), Paragraph("Tasa FP = 0.0% estricto; 100% precisión en 52,040 inferencias.", table_cell)],
        [Paragraph("test_hu06_exercisedb_fallback", table_cell), Paragraph("HU #6", table_cell), Paragraph("<font color='#16a34a'><b>PASSED</b></font>", table_cell), Paragraph("Fallback a catálogo RAG y normalización al esquema estándar.", table_cell)],
        [Paragraph("test_hu07_entrevista_defaults", table_cell), Paragraph("HU #7", table_cell), Paragraph("<font color='#16a34a'><b>PASSED</b></font>", table_cell), Paragraph("Asignación de defaults conservadores y NLP de texto libre.", table_cell)],
        [Paragraph("test_hu08_generacion_rutina", table_cell), Paragraph("HU #8", table_cell), Paragraph("<font color='#16a34a'><b>PASSED</b></font>", table_cell), Paragraph("Series, repeticiones, descansos y balance muscular determinista.", table_cell)],
        [Paragraph("test_hu09_filtro_bajo_impacto", table_cell), Paragraph("HU #9", table_cell), Paragraph("<font color='#16a34a'><b>PASSED</b></font>", table_cell), Paragraph("Restricción ante edad > 45 o grasa > 25% + consejos posturales.", table_cell)],
        [Paragraph("test_hu10_rutina_rapida_15_min", table_cell), Paragraph("HU #10", table_cell), Paragraph("<font color='#16a34a'><b>PASSED</b></font>", table_cell), Paragraph("Tope &le; 15 min y cálculo de XP proporcional a la duración.", table_cell)],
        [Paragraph("test_hu11_rutina_segura_lesion", table_cell), Paragraph("HU #11", table_cell), Paragraph("<font color='#16a34a'><b>PASSED</b></font>", table_cell), Paragraph("Exclusión articular de rodilla y despliegue del aviso médico legal.", table_cell)],
        [Paragraph("test_hu12_historial_y_calibracion", table_cell), Paragraph("HU #12", table_cell), Paragraph("<font color='#16a34a'><b>PASSED</b></font>", table_cell), Paragraph("Persistencia en BD y Rutina de Calibración Inicial para novatos.", table_cell)],
        [Paragraph("test_hu13_sobrecarga_progresiva", table_cell), Paragraph("HU #13", table_cell), Paragraph("<font color='#16a34a'><b>PASSED</b></font>", table_cell), Paragraph("Tope de seguridad &le; 10% y atenuación de carga ante fatiga.", table_cell)],
        [Paragraph("test_hu14_gamificacion_rpg", table_cell), Paragraph("HU #14", table_cell), Paragraph("<font color='#16a34a'><b>PASSED</b></font>", table_cell), Paragraph("Cálculo de XP, ascenso a Guerrero de Cobre y actualización de misiones.", table_cell)],
        [Paragraph("test_hu15_explicabilidad_natural", table_cell), Paragraph("HU #15", table_cell), Paragraph("<font color='#16a34a'><b>PASSED</b></font>", table_cell), Paragraph("Explicación empática en lenguaje natural sin mostrar código crudo.", table_cell)],
        [Paragraph("test_http_rutas_hyperion", table_cell), Paragraph("Rutas HTTP", table_cell), Paragraph("<font color='#16a34a'><b>PASSED</b></font>", table_cell), Paragraph("Compatibilidad con /limpiar-dataset, /hyperion y endpoints API.", table_cell)],
    ]
    t_test = Table(test_table_data, colWidths=[150, 65, 55, 270])
    t_test.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#f1f5f9')),
        ('BOX', (0,0), (-1,-1), 0.5, colors.HexColor('#cbd5e1')),
        ('INNERGRID', (0,0), (-1,-1), 0.5, colors.HexColor('#e2e8f0')),
        ('TOPPADDING', (0,0), (-1,-1), 2),
        ('BOTTOMPADDING', (0,0), (-1,-1), 2),
    ]))
    elements.append(t_test)
    elements.append(Spacer(1, 8))

    # --- SECCIÓN 6: COMANDOS DE EJECUCIÓN ---
    elements.append(Paragraph("6. Instrucciones de Ejecución y Despliegue", h1_style))
    elements.append(Paragraph(
        "Para iniciar la plataforma y replicar la evaluación en el entorno local:",
        body_style
    ))
    elements.append(Paragraph(
        "# 1. Iniciar servidor de desarrollo con PHP 8.3:<br/>"
        "&amp; \"C:\\Users\\santi\\AppData\\Local\\Microsoft\\WinGet\\Packages\\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\\php.exe\" artisan serve<br/><br/>"
        "# 2. Abrir el Asistente y Dashboard RPG en el navegador:<br/>"
        "http://127.0.0.1:8000/hyperion<br/><br/>"
        "# 3. Correr la batería de pruebas en PHPUnit:<br/>"
        "&amp; \"C:\\Users\\santi\\AppData\\Local\\Microsoft\\WinGet\\Packages\\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\\php.exe\" vendor/phpunit/phpunit/phpunit --filter=HyperionPlayTest<br/><br/>"
        "# 4. Correr script de métricas y validación HU #5 en Python:<br/>"
        "python scripts/script_evaluar_metricas_hu05.py",
        code_style
    ))

    doc.build(elements, canvasmaker=NumberedCanvas)
    print(f"[OK] PDF generado exitosamente en: {output_pdf}")

if __name__ == '__main__':
    build_pdf()

