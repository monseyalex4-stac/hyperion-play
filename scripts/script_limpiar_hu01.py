"""
HYPERION PLAY — HU01: Limpieza, Normalización y Ampliación Demográfica (Género)
Responsables: Tere y Jorge (Refactorizado Senior)

Criterios de Aceptación:
1. Eliminar duplicados y registros nulos.
2. Convertir unidades imperiales a métricas (Height en pulgadas a cm, Weight en lbs a kg).
3. Filtrar valores fuera de rangos biológicos realistas:
   - Altura: 100 - 250 cm
   - Peso: 30 - 300 kg
   - Grasa corporal: 3% - 60%
4. Incorporar el campo 'genero':
   - Verificar si existe evidencia comprobable en la fuente original.
   - Si no existe evidencia explícita, asignar estrictamente 'no_disponible' (sin inventar ni inferir).
5. Exportar dataset_limpio.csv y reporte_limpieza.txt manteniendo intacto el archivo original.
"""

import os
import csv
from pathlib import Path

def limpiar_dataset():
    base_dir = Path(__file__).resolve().parent
    dataset_dir = base_dir.parent / "storage" / "app" / "DATASET"
    
    # Buscar archivo original en storage/app/DATASET o en base_dir
    input_file = dataset_dir / "body_fat.csv"
    if not input_file.exists():
        input_file = dataset_dir / "bodyfat_original_sucio.csv"
    if not input_file.exists():
        input_file = base_dir / "bodyfat_original_sucio.csv"

    output_file = dataset_dir / "dataset_limpio.csv"
    report_file = dataset_dir / "reporte_limpieza.txt"

    if not input_file.exists():
        print(f"[ERROR] No se encontró el archivo de entrada: {input_file}")
        return

    print("=== INICIANDO LIMPIEZA Y NORMALIZACIÓN DE DATASET INBODY (HU01) ===")
    print(f"-> Archivo fuente original (intacto): {input_file}")

    # 1. Leer archivo original
    with open(input_file, mode='r', encoding='utf-8-sig') as f:
        reader = csv.reader(f)
        header = next(reader)
        raw_rows = list(reader)

    n_inicial = len(raw_rows)
    cols_iniciales = len(header)
    print(f"-> Registros cargados: {n_inicial} filas, {cols_iniciales} columnas")

    # Mapeo de columnas
    header_clean = [c.strip() for c in header]
    idx_unnamed = [i for i, c in enumerate(header_clean) if 'unnamed' in c.lower()]
    
    # 2. Detección y eliminación de duplicados
    seen_rows = set()
    deduped_rows = []
    duplicados_previos = 0

    for row in raw_rows:
        row_tuple = tuple(row)
        if row_tuple in seen_rows:
            duplicados_previos += 1
            continue
        seen_rows.add(row_tuple)
        deduped_rows.append(row)

    print(f"-> Filas duplicadas eliminadas: {duplicados_previos}")

    # Verificar si existe evidencia de género en el header
    col_genero_idx = None
    for i, c in enumerate(header_clean):
        if c.lower() in ['gender', 'genero', 'sex', 'sexo']:
            col_genero_idx = i
            break

    tiene_evidencia_genero = (col_genero_idx is not None)
    print(f"-> Evidencia comprobable de género en columnas fuente: {'SÍ' if tiene_evidencia_genero else 'NO (Aplicando regla de integridad: no_disponible)'}")

    # Columnas esperadas
    col_map = {c.lower(): i for i, c in enumerate(header_clean)}
    
    height_idx = col_map.get('height')
    weight_idx = col_map.get('weight')
    bodyfat_idx = col_map.get('bodyfat') or col_map.get('body fat') or col_map.get('bodyfat_pct')
    age_idx = col_map.get('age')

    # Filtrar columnas útiles
    cols_a_conservar = [i for i in range(len(header_clean)) if i not in idx_unnamed]
    nuevo_header = [header_clean[i] for i in cols_a_conservar]

    # Renombrar BodyFat a BodyFat_pct si aplica
    for idx_h, col_name in enumerate(nuevo_header):
        if col_name.lower() in ['bodyfat', 'body fat', 'body_fat']:
            nuevo_header[idx_h] = 'BodyFat_pct'
        elif col_name.lower() == 'age':
            nuevo_header[idx_h] = 'Age'

    # Agregar nuevas columnas normalizadas
    nuevo_header.extend(['Height_cm', 'Weight_kg', 'genero'])

    filas_limpias = []
    descartados_biologicos = 0

    for row in deduped_rows:
        try:
            h_val = float(row[height_idx]) if height_idx is not None and row[height_idx] != '' else None
            w_val = float(row[weight_idx]) if weight_idx is not None and row[weight_idx] != '' else None
            bf_val = float(row[bodyfat_idx]) if bodyfat_idx is not None and row[bodyfat_idx] != '' else None
            age_val = int(float(row[age_idx])) if age_idx is not None and row[age_idx] != '' else None
        except (ValueError, IndexError):
            descartados_biologicos += 1
            continue

        if h_val is None or w_val is None or bf_val is None:
            descartados_biologicos += 1
            continue

        # Conversión métrica
        h_cm = round(h_val * 2.54, 1) if h_val < 100 else round(h_val, 1)
        w_kg = round(w_val * 0.453592, 1) if w_val > 100 else round(w_val, 1)

        # Filtros biológicos realistas
        if not (100.0 <= h_cm <= 250.0):
            descartados_biologicos += 1
            continue
        if not (30.0 <= w_kg <= 300.0):
            descartados_biologicos += 1
            continue
        if not (3.0 <= bf_val <= 60.0):
            descartados_biologicos += 1
            continue

        # Regla estricta de género (HU01)
        if tiene_evidencia_genero and row[col_genero_idx].strip() != '':
            val_genero = row[col_genero_idx].strip().lower()
        else:
            val_genero = 'no_disponible'

        fila_base = [row[i] for i in cols_a_conservar]
        fila_base.extend([h_cm, w_kg, val_genero])
        filas_limpias.append(fila_base)

    n_final = len(filas_limpias)
    print(f"-> Descartados por filtros biológicos o nulos: {descartados_biologicos}")
    print(f"-> Registros conservados limpios: {n_final}")

    # Guardar dataset limpio en storage/app/DATASET y scripts/
    for out_path in [output_file, base_dir.parent / "storage" / "app" / "datasets" / "dataset_limpio.csv", base_dir / "dataset_limpio.csv"]:
        out_path.parent.mkdir(parents=True, exist_ok=True)
        with open(out_path, mode='w', encoding='utf-8', newline='') as f:
            writer = csv.writer(f)
            writer.writerow(nuevo_header)
            writer.writerows(filas_limpias)
        print(f"[OK] Dataset limpio guardado en: {out_path}")

    # Reporte formal de limpieza
    reporte = f"""=======================================================
       REPORTE DE LIMPIEZA DE DATOS — HYPERION PLAY    
=======================================================

1. Registros iniciales: {n_inicial}
2. Columnas iniciales: {cols_iniciales}
3. Duplicados eliminados: {duplicados_previos}
4. Descartados biológicos o valores nulos: {descartados_biologicos}
5. Registros finales conservados: {n_final}
6. Normalización de unidades:
   - Altura convertida a Height_cm (pulgadas a cm x 2.54)
   - Peso convertido a Weight_kg (libras a kg x 0.453592)
7. Rangos biológicos validados:
   - Altura: [100 - 250 cm]
   - Peso: [30 - 300 kg]
   - Grasa Corporal: [3% - 60%]
8. Ampliación demográfica (HU01 - Género):
   - Columna incorporada: 'genero'
   - Política de integridad: Asignación explícita de 'no_disponible'
     debido a ausencia de metadatos o evidencia comprobable en el archivo
     fuente original (no se infieren ni inventan valores).
9. Archivo exportado: dataset_limpio.csv
=======================================================
"""
    for rep_path in [report_file, base_dir.parent / "storage" / "app" / "datasets" / "reporte_limpieza.txt", base_dir / "reporte_limpieza.txt"]:
        with open(rep_path, 'w', encoding='utf-8') as f:
            f.write(reporte)
        print(f"[OK] Reporte exportado a: {rep_path}")

if __name__ == "__main__":
    limpiar_dataset()
