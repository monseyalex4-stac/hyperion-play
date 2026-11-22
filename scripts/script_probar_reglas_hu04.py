"""
HYPERION PLAY — HU04: Demostración Interactiva del Motor de Reglas
Responsables: Pablo y Tere

Permite ejecutar el motor de reglas cruzando un perfil antropométrico
contra las etiquetas del catálogo para verificar las exclusiones de seguridad.
"""

import os
import json
from reglas_modelo import MotorReglasHyperion

def demo_reglas():
    base_dir = os.path.dirname(os.path.abspath(__file__))
    archivo_rag = os.path.join(base_dir, "..", "HU03_Etiquetado_RAG", "ejercicios_rag_etiquetados.json")
    archivo_reglas = os.path.join(base_dir, "reglas.json")

    if not os.path.exists(archivo_rag):
        archivo_rag = os.path.join(base_dir, "ejercicios_rag_etiquetados.json")

    motor = MotorReglasHyperion(catalogo_path=archivo_rag, reglas_path=archivo_reglas)

    print("=================================================================")
    print("      DEMOSTRACIÓN DEL MOTOR DE REGLAS CLÍNICAS (HU04)           ")
    print("=================================================================")
    
    # Perfil de prueba 1: Adulto mayor con sobrepeso y dolor de rodilla
    perfil_1 = {
        'nombre': 'Don Carlos (62 años, sobrepeso)',
        'edad': 62,
        'grasa': 28.5,
        'lesion': 'rodilla',
        'entorno': 'casa_sin_equipo'
    }

    # Perfil de prueba 2: Joven atlético sin lesiones
    perfil_2 = {
        'nombre': 'Valeria (24 años, atlética)',
        'edad': 24,
        'grasa': 14.0,
        'lesion': 'ninguna',
        'entorno': 'gimnasio'
    }

    for perfil in [perfil_1, perfil_2]:
        print(f"\n-> Evaluando: {perfil['nombre']}")
        print(f"   Edad: {perfil['edad']} | Grasa: {perfil['grasa']}% | Lesión: {perfil['lesion']} | Entorno: {perfil['entorno']}")

        seguros, descartados = motor.filtrar_catalogo(perfil)
        descartados_count = len(descartados)

        print(f"   [RESULTADO] Total seguros permitidos: {len(seguros)}")
        print(f"   [SEGURIDAD] Total descartados por reglas de riesgo: {descartados_count}")
        print("   Ejemplos de ejercicios recomendados y seguros:")
        for s in seguros[:3]:
            nombre = s.get('nombre') or s.get('name')
            print(f"     • {nombre} (Impacto: {s.get('nivel_impacto')}, Entorno: {s.get('entorno')})")

if __name__ == "__main__":
    demo_reglas()
