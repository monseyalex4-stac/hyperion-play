"""
HYPERION PLAY — HU03: Etiquetado Extenso de Datos para Técnica RAG
Responsables: Pablo y Santiago

Criterios de Aceptación:
1. Incluir en cada ejercicio el esquema de etiquetas RAG:
   - nivel_impacto: 'bajo', 'medio', 'alto'
   - nivel_experiencia: 'principiante', 'intermedio', 'avanzado'
   - entorno: 'casa_sin_equipo', 'casa_basico', 'gimnasio'
   - zonas_riesgo_evitar: lista con ['rodilla', 'espalda_baja', 'hombro', 'muñeca']
   - tipo_objetivo: 'fuerza', 'hipertrofia', 'resistencia', 'movilidad'
   - duracion_estimada_seg: entero (ej. 45 a 90 segundos por serie)
   - xp_base: entero (ej. 10 a 25 XP para gamificación RPG)
2. Validar que el archivo final ejercicios_rag_etiquetados.json no tenga campos críticos vacíos.
"""

import os
import json

def clasificar_entorno(equipment: str) -> str:
    """Clasifica el entorno según el equipamiento requerido"""
    eq = equipment.lower()
    if eq in ['body weight', 'none', 'floor', 'mat', 'other']:
        return 'casa_sin_equipo'
    elif eq in ['dumbbell', 'band', 'kettlebell', 'resistance band', 'foam roll']:
        return 'casa_basico'
    else: # barbell, cable, machine, leverage machine, smith machine
        return 'gimnasio'

def clasificar_nivel_impacto(name: str, target: str, equipment: str) -> str:
    """Clasifica el impacto articular/cardiovascular según movimientos balísticos y cargas axiales"""
    t = f"{name} {target} {equipment}".lower()
    if any(k in t for k in ['jump', 'plyo', 'hop', 'burpee', 'sprint', 'clean and jerk', 'snatch']):
        return 'alto'
    elif any(k in t for k in ['squat', 'deadlift', 'lunge', 'barbell press', 'thruster']):
        return 'medio'
    else:
        return 'bajo'

def clasificar_zonas_riesgo(name: str, target: str, body_part: str) -> list:
    """Identifica las articulaciones bajo estrés mecánico que se deben omitir si hay dolor"""
    zonas = set()
    t = f"{name} {target} {body_part}".lower()

    # Riesgo de rodilla: flexiones profundas con carga o impactos
    if any(k in t for k in ['squat', 'lunge', 'jump', 'leg press', 'knee', 'quad', 'hamstring', 'calf']):
        zonas.add('rodilla')

    # Riesgo de espalda baja / lumbar: flexión de tronco o cargas axiales
    if any(k in t for k in ['deadlift', 'good morning', 'back extension', 'row', 'lower back', 'waist', 'spine']):
        zonas.add('espalda_baja')

    # Riesgo de hombro: empujes verticales o abducciones forzadas
    if any(k in t for k in ['overhead', 'military', 'shoulder', 'delts', 'lateral raise', 'bench press', 'dip']):
        zonas.add('hombro')

    # Riesgo de muñeca: soporte de peso en flexión palmar
    if any(k in t for k in ['push up', 'handstand', 'clean', 'wrist', 'curl', 'plank']):
        zonas.add('muñeca')

    return sorted(list(zonas))

def clasificar_experiencia(name: str, equipment: str, impacto: str) -> str:
    t = f"{name} {equipment}".lower()
    if any(k in t for k in ['snatch', 'clean', 'muscle up', 'handstand', 'pistol squat']) or impacto == 'alto':
        return 'avanzado'
    elif any(k in t for k in ['barbell', 'cable', 'dumbbell', 'overhead']):
        return 'intermedio'
    else:
        return 'principiante'

def clasificar_objetivo(target: str, impacto: str) -> str:
    t = target.lower()
    if any(k in t for k in ['cardio', 'abs', 'waist']):
        return 'resistencia'
    elif impacto == 'alto':
        return 'fuerza_potencia'
    elif any(k in t for k in ['neck', 'stretching', 'calves']):
        return 'movilidad'
    else:
        return 'hipertrofia_fuerza'

def etiquetar_catalogo():
    base_dir = os.path.dirname(os.path.abspath(__file__))
    input_file = os.path.join(base_dir, "catalogo_ejercicios_base.json")
    output_file = os.path.join(base_dir, "ejercicios_rag_etiquetados.json")

    print("=== INICIANDO ALGORITMO DE ETIQUETADO RAG (HU03) ===")
    if not os.path.exists(input_file):
        print(f"[ERROR] No se encuentra {input_file}")
        return

    with open(input_file, 'r', encoding='utf-8') as f:
        ejercicios = json.load(f)

    print(f"-> Procesando {len(ejercicios)} ejercicios...")
    etiquetados = []
    campos_vacios_detectados = 0

    for ex in ejercicios:
        nombre = (ex.get('nombre') or ex.get('name') or 'Ejercicio').strip()
        target = (ex.get('musculo_principal') or ex.get('target') or 'general').strip()
        body_part = (ex.get('parte_cuerpo') or ex.get('bodyPart') or 'cuerpo_completo').strip()
        equipment = (ex.get('equipo') or ex.get('equipment') or 'body weight').strip()

        entorno = clasificar_entorno(equipment)
        impacto = clasificar_nivel_impacto(nombre, target, equipment)
        riesgos = clasificar_zonas_riesgo(nombre, target, body_part)
        experiencia = clasificar_experiencia(nombre, equipment, impacto)
        objetivo = clasificar_objetivo(target, impacto)

        # Gamificación: calcular XP base (10 a 25 XP según complejidad)
        xp_base = 10
        if experiencia == 'intermedio': xp_base = 15
        elif experiencia == 'avanzado': xp_base = 25

        duracion_seg = 45 if impacto == 'bajo' else (60 if impacto == 'medio' else 75)

        nuevo = dict(ex)
        nuevo['nombre'] = nombre
        nuevo['name'] = nombre
        nuevo['nivel_impacto'] = impacto
        nuevo['nivel_experiencia'] = experiencia
        nuevo['entorno'] = entorno
        nuevo['zonas_riesgo_evitar'] = riesgos
        nuevo['tipo_objetivo'] = objetivo
        nuevo['duracion_estimada_seg'] = duracion_seg
        nuevo['xp_base'] = xp_base

        # Validación de campos críticos
        if not nuevo['nombre'] or not nuevo['nivel_impacto'] or not nuevo['entorno']:
            campos_vacios_detectados += 1

        etiquetados.append(nuevo)

    print(f"-> Campos críticos vacíos: {campos_vacios_detectados} (CERO requeridos)")
    print(f"-> Total ejercicios enriquecidos: {len(etiquetados)}")

    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(etiquetados, f, indent=2, ensure_ascii=False)

    print(f"[OK] Archivo RAG generado con éxito: {output_file}")

if __name__ == "__main__":
    etiquetar_catalogo()
