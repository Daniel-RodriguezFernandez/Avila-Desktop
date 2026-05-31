#!/usr/bin/env python3
"""
Reordena los hitos de cada ruta y recalcula el campo <distancia>.

1) ORDEN: los hitos con nombre (puntos de interes) se intercalan en su
   posicion real del recorrido, justo despues del punto anonimo mas cercano,
   de modo que toda la secuencia <hito> queda en orden a lo largo de la ruta.

2) DISTANCIA: deja de ser opcional. A cada hito se le asigna (o se le
   actualiza) <distancia unidades="m"> con la distancia, en metros, al hito
   inmediatamente anterior de la secuencia ya ordenada. El primer hito = 0.

Uso:
    python3 reordenar_y_distancias.py entrada.xml salida.xml
"""

import sys
import math
import xml.etree.ElementTree as ET

NS_URI = "http://www.uniovi.es"
NS = {'r': NS_URI}
RADIO_TIERRA = 6371000.0  # metros

ET.register_namespace('', NS_URI)  # evita prefijos ns0 al escribir


def q(tag):
    return f"{{{NS_URI}}}{tag}"


def texto(elemento):
    if elemento is None or elemento.text is None:
        return ''
    return elemento.text.strip()


def esAnonimo(hito):
    return hito.get('nombre') is None


def haversine(lon1, lat1, lon2, lat2):
    rlat1, rlat2 = math.radians(lat1), math.radians(lat2)
    dlat = math.radians(lat2 - lat1)
    dlon = math.radians(lon2 - lon1)
    a = (math.sin(dlat / 2) ** 2 +
         math.cos(rlat1) * math.cos(rlat2) * math.sin(dlon / 2) ** 2)
    return RADIO_TIERRA * 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a))


def lonlat(hito):
    coord = hito.find('r:coordenadas', NS)
    lon = float(texto(coord.find('r:longitud', NS)))
    lat = float(texto(coord.find('r:latitud', NS)))
    return lon, lat


def fijarDistancia(hito, metros):
    """ Crea o actualiza <distancia unidades="m"> justo despues de
        <coordenadas>, respetando el orden del esquema. """
    dist = hito.find('r:distancia', NS)
    if dist is None:
        dist = ET.Element(q('distancia'))
        # Insertar justo detras de <coordenadas>
        hijos = list(hito)
        coord = hito.find('r:coordenadas', NS)
        pos = hijos.index(coord) + 1
        hito.insert(pos, dist)
    dist.set('unidades', 'm')
    dist.text = '{:.2f}'.format(metros)


def procesarRuta(ruta):
    contenedor = ruta.find('r:hitos', NS)
    if contenedor is None:
        return 0, 0

    hitos = contenedor.findall('r:hito', NS)
    anonimos = [h for h in hitos if esAnonimo(h)]
    nombrados = [h for h in hitos if not esAnonimo(h)]

    # --- Posicion de cada hito a lo largo del recorrido ---
    # Los anonimos definen el recorrido: posicion = su indice (0,1,2,...).
    posiciones = []
    for i, h in enumerate(anonimos):
        posiciones.append((float(i), h))

    # Cada nombrado se ubica tras el anonimo mas cercano (indice + 0.5).
    coordsAnon = [lonlat(h) for h in anonimos]
    for h in nombrados:
        hlon, hlat = lonlat(h)
        mejor, mejorD = 0, None
        for i, (alon, alat) in enumerate(coordsAnon):
            d = haversine(hlon, hlat, alon, alat)
            if mejorD is None or d < mejorD:
                mejorD, mejor = d, i
        posiciones.append((mejor + 0.5, h))

    # Orden estable: si coinciden, se respeta el orden original.
    posiciones.sort(key=lambda p: p[0])
    ordenados = [h for _, h in posiciones]

    # --- Reescribir el contenedor en el nuevo orden ---
    for h in hitos:
        contenedor.remove(h)
    for h in ordenados:
        contenedor.append(h)

    # --- Recalcular distancia entre puntos adyacentes ---
    lonPrev = latPrev = None
    for h in ordenados:
        lon, lat = lonlat(h)
        if lonPrev is None:
            metros = 0.0
        else:
            metros = haversine(lonPrev, latPrev, lon, lat)
        fijarDistancia(h, metros)
        lonPrev, latPrev = lon, lat

    return len(nombrados), len(anonimos)


def main():
    if len(sys.argv) != 3:
        print('Uso: python3 reordenar_y_distancias.py entrada.xml salida.xml')
        sys.exit(1)
    entrada, salida = sys.argv[1], sys.argv[2]

    arbol = ET.parse(entrada)
    raiz = arbol.getroot()
    for ruta in raiz.findall('r:ruta', NS):
        nom, ano = procesarRuta(ruta)
        print('  ruta {}: {} con nombre intercalados entre {} anonimos'.format(
            ruta.get('id'), nom, ano))

    ET.indent(arbol)
    arbol.write(salida, encoding='utf-8', xml_declaration=True)
    print('Escrito en:', salida)


if __name__ == '__main__':
    main()