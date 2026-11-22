import json
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent / "storage" / "app" / "DATASET"
RAG_FILE = BASE_DIR / "ejercicios_rag_etiquetados.json"
REGLAS_FILE = BASE_DIR / "reglas.json"

class MotorReglasHyperion:
    def __init__(self, catalogo_path=None, reglas_path=None):
        self.catalogo_path = catalogo_path or RAG_FILE
        self.reglas_path = reglas_path or REGLAS_FILE
        
        with open(self.reglas_path, "r", encoding="utf-8") as f:
            self.reglas = json.load(f)
            
        with open(self.catalogo_path, "r", encoding="utf-8") as f:
            self.catalogo = json.load(f)

    def evaluar_seguridad_ejercicio(self, ejercicio, perfil):
        """
        Evalúa si un ejercicio específico es seguro y compatible para el perfil dado.
        Retorna (es_seguro: bool, motivo_rechazo: str|None)
        """
        edad = perfil.get("edad", 25)
        body_fat = perfil.get("body_fat", perfil.get("BodyFat", 18.0))
        lesion = (perfil.get("lesion") or "").lower().strip()
        entorno = (perfil.get("entorno") or "casa_sin_equipo").lower().strip()

        # 1. Regla de Seguridad por Edad o Porcentaje de Grasa
        umbral_edad = self.reglas["reglas_seguridad"]["edad_limite_impacto_bajo"]
        umbral_grasa = self.reglas["reglas_seguridad"]["grasa_limite_impacto_bajo"]
        
        if (edad > umbral_edad or body_fat > umbral_grasa):
            if ejercicio.get("nivel_impacto") != "bajo":
                return False, f"Impacto {ejercicio.get('nivel_impacto')} no permitido para edad > {umbral_edad} o grasa > {umbral_grasa}%"

        # 2. Regla de Exclusión Estricta por Lesión Articular
        if lesion and lesion not in ["ninguna", "ninguno", "sano", "sin_lesion"]:
            zonas_riesgo = ejercicio.get("zonas_riesgo_evitar", [])
            for art in self.reglas["reglas_lesiones"]["articulaciones_monitoreadas"]:
                if art in lesion and art in zonas_riesgo:
                    return False, f"Riesgo articular para '{art}' (presente en zonas de riesgo del ejercicio)"

        # 3. Regla de Compatibilidad de Entorno / Equipamiento
        entorno_compat = self.reglas["reglas_entorno"]["compatibilidad"].get(entorno, ["casa_sin_equipo"])
        if ejercicio.get("entorno") not in entorno_compat:
            return False, f"Entorno '{ejercicio.get('entorno')}' incompatible con disponibilidad '{entorno}'"

        return True, None

    def filtrar_catalogo(self, perfil):
        """
        Filtra todo el catálogo para obtener el subconjunto seguro (candidatos RAG).
        """
        ejercicios_seguros = []
        descartados_detalle = []

        for ex in self.catalogo:
            es_seguro, motivo = self.evaluar_seguridad_ejercicio(ex, perfil)
            if es_seguro:
                ejercicios_seguros.append(ex)
            else:
                descartados_detalle.append({"id": ex.get("id"), "nombre": ex.get("nombre"), "motivo": motivo})

        return ejercicios_seguros, descartados_detalle

    def armar_rutina_tiempo(self, ejercicios_candidatos, perfil):
        """
        Selecciona una secuencia coherente respetando el tiempo total disponible.
        Ej: 15 minutos = 900 segundos.
        """
        tiempo_max_seg = perfil.get("tiempo_disponible_seg", perfil.get("tiempo_minutos", 15) * 60)
        series_por_ejercicio = self.reglas["reglas_tiempo"]["series_promedio"]
        
        rutina_seleccionada = []
        tiempo_acumulado_seg = 0
        xp_total = self.reglas["gamificacion_xp"]["base_completitud"]

        # Evitar repetir el mismo músculo principal consecutivamente si hay variedad
        musculos_usados = set()
        
        for ex in ejercicios_candidatos:
            dur_serie = ex.get("duracion_estimada_seg", 60)
            dur_ejercicio_total = dur_serie * series_por_ejercicio
            
            if tiempo_acumulado_seg + dur_ejercicio_total <= tiempo_max_seg:
                # Priorizar variedad muscular
                mp = ex.get("musculo_principal")
                if mp in musculos_usados and len(musculos_usados) < 4:
                    continue
                    
                musculos_usados.add(mp)
                rutina_seleccionada.append({
                    "id": ex.get("id"),
                    "nombre": ex.get("nombre"),
                    "musculo_principal": mp,
                    "equipo": ex.get("equipo"),
                    "nivel_impacto": ex.get("nivel_impacto"),
                    "series": series_por_ejercicio,
                    "duracion_estimada_seg": dur_ejercicio_total,
                    "instrucciones": ex.get("instrucciones", [])[:2],
                    "xp": ex.get("xp_base", 15)
                })
                tiempo_acumulado_seg += dur_ejercicio_total
                xp_total += ex.get("xp_base", 15)

            # Si ya se llenó la rutina con un número prudente de ejercicios (ej. 3 a 5 para 15 min)
            if len(rutina_seleccionada) >= 5 or tiempo_acumulado_seg >= (tiempo_max_seg - 60):
                break

        # Si por variedad quedaron muy pocos, relajar filtro de músculo
        if len(rutina_seleccionada) < 3 and len(ejercicios_candidatos) >= 3:
            for ex in ejercicios_candidatos:
                if any(r["id"] == ex["id"] for r in rutina_seleccionada):
                    continue
                dur_ejercicio_total = ex.get("duracion_estimada_seg", 60) * series_por_ejercicio
                if tiempo_acumulado_seg + dur_ejercicio_total <= tiempo_max_seg:
                    rutina_seleccionada.append({
                        "id": ex.get("id"),
                        "nombre": ex.get("nombre"),
                        "musculo_principal": ex.get("musculo_principal"),
                        "equipo": ex.get("equipo"),
                        "nivel_impacto": ex.get("nivel_impacto"),
                        "series": series_por_ejercicio,
                        "duracion_estimada_seg": dur_ejercicio_total,
                        "instrucciones": ex.get("instrucciones", [])[:2],
                        "xp": ex.get("xp_base", 15)
                    })
                    tiempo_acumulado_seg += dur_ejercicio_total
                    xp_total += ex.get("xp_base", 15)
                if len(rutina_seleccionada) >= 4:
                    break

        return {
            "tiempo_objetivo_seg": tiempo_max_seg,
            "tiempo_programado_seg": tiempo_acumulado_seg,
            "total_ejercicios": len(rutina_seleccionada),
            "xp_recompensa_total": xp_total,
            "ejercicios": rutina_seleccionada
        }

if __name__ == "__main__":
    motor = MotorReglasHyperion()
    perfil_test = {
        "edad": 50,
        "body_fat": 28.5,
        "lesion": "rodilla",
        "entorno": "casa_sin_equipo",
        "tiempo_minutos": 15
    }
    seguros, descartados = motor.filtrar_catalogo(perfil_test)
    rutina = motor.armar_rutina_tiempo(seguros, perfil_test)
    print(f"Test Motor de Reglas -> Candidatos Seguros: {len(seguros)} | Descartados: {len(descartados)}")
    print(f"Rutina 15 min armada -> Ejercicios: {rutina['total_ejercicios']}, Tiempo: {rutina['tiempo_programado_seg']}s, XP: {rutina['xp_recompensa_total']}")
    for e in rutina["ejercicios"]:
        print(f"  - {e['nombre']} ({e['musculo_principal']}) | Impacto: {e['nivel_impacto']} | Series: {e['series']}")
