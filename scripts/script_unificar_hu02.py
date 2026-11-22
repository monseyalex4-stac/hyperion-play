"""
HYPERION PLAY — HU02: Recolección y Unificación de Catálogos de Ejercicios
Responsables: Quevedo y Jorge

Criterios de Aceptación:
1. Limpiar megaGymDataset.csv (quitar nulos, duplicados y descripciones vacías).
2. Extraer los campos clave de exercises-dataset.json (nombre, músculo, equipo, instrucciones).
3. Unificar ambos catálogos en un solo archivo limpio (catalogo_ejercicios_base.json).
"""

import os
import json
import pandas as pd

def unificar_catalogos():
    base_dir = os.path.dirname(os.path.abspath(__file__))
    file_csv = os.path.join(base_dir, "megaGymDataset.csv")
    file_json = os.path.join(base_dir, "exercises_dataset.json")
    output_file = os.path.join(base_dir, "catalogo_ejercicios_base.json")

    print("=== INICIANDO UNIFICACIÓN DE CATÁLOGOS (HU02) ===")
    catalogo_unificado = []
    nombres_vistos = set()

    # 1. Procesar exercises_dataset.json (Exercises Dataset GitHub)
    if os.path.exists(file_json):
        print("-> Procesando exercises_dataset.json...")
        with open(file_json, 'r', encoding='utf-8') as f:
            data_json = json.load(f)
        
        count_json = 0
        for item in data_json:
            nombre = item.get('name', '').strip()
            if not nombre:
                continue
            nombre_norm = nombre.lower()
            if nombre_norm in nombres_vistos:
                continue
            nombres_vistos.add(nombre_norm)

            catalogo_unificado.append({
                'id': f"EX-{item.get('id', str(len(catalogo_unificado)+1).zfill(4))}",
                'name': nombre.title(),
                'target': item.get('target', 'General').strip().lower(),
                'bodyPart': item.get('bodyPart', 'Full Body').strip().lower(),
                'equipment': item.get('equipment', 'body weight').strip().lower(),
                'instructions': item.get('instructions', []),
                'source': 'ExercisesDataset_GitHub'
            })
            count_json += 1
        print(f"   [OK] {count_json} ejercicios extraídos de JSON.")

    # 2. Procesar megaGymDataset.csv (Kaggle)
    if os.path.exists(file_csv):
        print("-> Procesando megaGymDataset.csv...")
        df_gym = pd.read_csv(file_csv)
        # Limpiar filas con título o músculo nulo
        df_gym = df_gym.dropna(subset=['Title'])
        df_gym = df_gym.drop_duplicates(subset=['Title'])

        count_gym = 0
        for _, row in df_gym.iterrows():
            nombre = str(row['Title']).strip()
            if not nombre or nombre.lower() in nombres_vistos:
                continue
            nombres_vistos.add(nombre.lower())

            desc = str(row.get('Desc', '')).strip()
            instrucciones = [desc] if desc and desc.lower() != 'nan' else []

            catalogo_unificado.append({
                'id': f"MG-{len(catalogo_unificado)+1:04d}",
                'name': nombre.title(),
                'target': str(row.get('BodyPart', 'General')).strip().lower(),
                'bodyPart': str(row.get('BodyPart', 'Full Body')).strip().lower(),
                'equipment': str(row.get('Equipment', 'body weight')).strip().lower(),
                'instructions': instrucciones,
                'source': 'MegaGym_Kaggle'
            })
            count_gym += 1
        print(f"   [OK] {count_gym} ejercicios nuevos agregados desde CSV.")

    total = len(catalogo_unificado)
    print(f"-> Total consolidado sin duplicados: {total} ejercicios.")

    # 3. Guardar catálogo base unificado
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(catalogo_unificado, f, indent=2, ensure_ascii=False)
    print(f"[OK] Archivo generado exitosamente: {output_file}")

if __name__ == "__main__":
    unificar_catalogos()
