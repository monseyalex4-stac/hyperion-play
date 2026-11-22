"""
HYPERION PLAY — HU01: Generador / Inyector de Datos Sucios
Permite restablecer o reinyectar ruido en el dataset original para demostración en vivo.
"""

import os
import pandas as pd
import numpy as np

def generar_dataset_sucio():
    base_dir = os.path.dirname(os.path.abspath(__file__))
    archivo_sucio = os.path.join(base_dir, "bodyfat_original_sucio.csv")

    if not os.path.exists(archivo_sucio):
        print("[ERROR] No se encuentra bodyfat_original_sucio.csv")
        return

    df = pd.read_csv(archivo_sucio)
    print(f"Cargando dataset base: {len(df)} filas...")

    # 1. Agregar duplicados sintéticos (30 filas aleatorias)
    duplicados = df.sample(30, random_state=42)
    df_con_ruido = pd.concat([df, duplicados], ignore_index=True)

    # 2. Agregar columnas basura (ej. Unnamed: 0)
    df_con_ruido['Unnamed: 0'] = range(len(df_con_ruido))

    # 3. Guardar archivo sucio listo para la demo
    df_con_ruido.to_csv(archivo_sucio, index=False)
    print(f"[OK] Archivo sucio preparado con {len(df_con_ruido)} registros (incluye duplicados y columnas basura).")
    print("Listo para correr: python script_limpiar_hu01.py")

if __name__ == "__main__":
    generar_dataset_sucio()
