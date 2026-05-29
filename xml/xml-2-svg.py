    import os
import math
import xml.etree.ElementTree as ET

NS = {'r': 'http://www.uniovi.es'}

ARCHIVO_XML = 'rutas.xml'

ANCHO = 900
ALTO = 420
MARGEN_IZQ = 80 
MARGEN_DER = 30
MARGEN_SUP = 30
MARGEN_INF = 70 

PLOT_ANCHO = ANCHO - MARGEN_IZQ - MARGEN_DER
PLOT_ALTO = ALTO - MARGEN_SUP - MARGEN_INF

RADIO_TIERRA = 6371000.0   # metros


class Svg(object):

    def __init__(self, ancho, alto):
        self.raiz = ET.Element('svg', {
            'xmlns': "http://www.w3.org/2000/svg",
            'version': "1.1",
            'width': str(ancho),
            'height': str(alto),
            'viewBox': '0 0 {} {}'.format(ancho, alto),
        })

    def addRect(self, x, y, width, height, fill, strokeWidth, stroke):
        ET.SubElement(self.raiz, 'rect', {
            'x': x, 'y': y, 'width': width, 'height': height,
            'fill': fill, 'stroke-width': strokeWidth, 'stroke': stroke})

    def addLine(self, x1, y1, x2, y2, stroke, strokeWidth):
        ET.SubElement(self.raiz, 'line', {
            'x1': x1, 'y1': y1, 'x2': x2, 'y2': y2,
            'stroke': stroke, 'stroke-width': strokeWidth})

    def addPolyline(self, points, stroke, strokeWidth, fill):
        ET.SubElement(self.raiz, 'polyline', {
            'points': points, 'stroke': stroke,
            'stroke-width': strokeWidth, 'fill': fill})

    def addText(self, texto, x, y, fontFamily, fontSize, style, transform=None):
        atributos = {'x': x, 'y': y, 'font-family': fontFamily,
                     'font-size': fontSize, 'style': style}
        if transform is not None:
            atributos['transform'] = transform
        ET.SubElement(self.raiz, 'text', atributos).text = texto

    def escribir(self, nombreArchivoSVG):
        arbol = ET.ElementTree(self.raiz)
        ET.indent(arbol)
        arbol.write(nombreArchivoSVG, encoding='utf-8', xml_declaration=True)


def texto(elemento):
    if elemento is None or elemento.text is None:
        return ''
    return elemento.text.strip()


def haversine(lon1, lat1, lon2, lat2):
    rlat1, rlat2 = math.radians(lat1), math.radians(lat2)
    dlat = math.radians(lat2 - lat1)
    dlon = math.radians(lon2 - lon1)
    a = (math.sin(dlat / 2) ** 2 +
         math.cos(rlat1) * math.cos(rlat2) * math.sin(dlon / 2) ** 2)
    return RADIO_TIERRA * 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a))


def pasoBonito(rango, objetivoMarcas):
    if rango <= 0:
        return 1.0
    crudo = rango / objetivoMarcas
    magnitud = 10 ** math.floor(math.log10(crudo))
    norm = crudo / magnitud
    if norm < 1.5:
        paso = 1
    elif norm < 3:
        paso = 2
    elif norm < 7:
        paso = 5
    else:
        paso = 10
    return paso * magnitud


def construirPerfil(ruta):
    distancias = []
    altitudes = []
    distanciaAcum = 0.0
    lonPrev = latPrev = None

    for coord in ruta.findall('r:trazado/r:coordenadas', NS):
        lon = float(texto(coord.find('r:longitud', NS)))
        lat = float(texto(coord.find('r:latitud', NS)))
        altTxt = texto(coord.find('r:altitud', NS))
        alt = float(altTxt) if altTxt != '' else 0.0

        if lonPrev is not None:
            distanciaAcum += haversine(lonPrev, latPrev, lon, lat)
        distancias.append(distanciaAcum)
        altitudes.append(alt)
        lonPrev, latPrev = lon, lat

    return distancias, altitudes


def procesarRuta(ruta):
    idRuta = ruta.get('id')
    nombreRuta = ruta.get('nombre')

    distancias, altitudes = construirPerfil(ruta)
    if len(distancias) < 2:
        print('Ruta', idRuta, 'sin trazado suficiente; se omite.')
        return

    distMax = distancias[-1]
    altMin = min(altitudes)
    altMax = max(altitudes)

    pasoY = pasoBonito(altMax - altMin if altMax > altMin else 1, 5)
    altBase = math.floor(altMin / pasoY) * pasoY
    altTope = math.ceil(altMax / pasoY) * pasoY
    if altTope == altBase:
        altTope = altBase + pasoY

    pasoX = pasoBonito(distMax, 6)

    def xpix(d):
        return MARGEN_IZQ + (d / distMax) * PLOT_ANCHO

    def ypix(a):
        return MARGEN_SUP + PLOT_ALTO - ((a - altBase) / (altTope - altBase)) * PLOT_ALTO

    svg = Svg(ANCHO, ALTO)

    svg.addRect(str(MARGEN_IZQ), str(MARGEN_SUP),
                str(PLOT_ANCHO), str(PLOT_ALTO),
                '#fbfbfb', '1', '#cccccc')

    yBase = ypix(altBase)

    a = altBase
    while a <= altTope + 1e-6:
        y = ypix(a)
        svg.addLine(str(MARGEN_IZQ), str(y),
                    str(MARGEN_IZQ + PLOT_ANCHO), str(y), '#e6e6e6', '1')
        svg.addText('{:.0f}'.format(a),
                    str(MARGEN_IZQ - 10), str(y + 4),
                    'Verdana', '11', 'text-anchor: end; fill: #555;')
        a += pasoY

    d = 0.0
    while d <= distMax + 1e-6:
        x = xpix(d)
        svg.addLine(str(x), str(MARGEN_SUP),
                    str(x), str(MARGEN_SUP + PLOT_ALTO), '#e6e6e6', '1')
        svg.addText('{:.0f}'.format(d),
                    str(x), str(MARGEN_SUP + PLOT_ALTO + 18),
                    'Verdana', '11', 'text-anchor: middle; fill: #555;')
        d += pasoX

    svg.addLine(str(MARGEN_IZQ), str(MARGEN_SUP),
                str(MARGEN_IZQ), str(MARGEN_SUP + PLOT_ALTO), '#333', '1.5')
    svg.addLine(str(MARGEN_IZQ), str(MARGEN_SUP + PLOT_ALTO),
                str(MARGEN_IZQ + PLOT_ANCHO), str(MARGEN_SUP + PLOT_ALTO),
                '#333', '1.5')

    puntos = []
    puntos.append('{:.2f},{:.2f}'.format(xpix(0), yBase))
    for i in range(len(distancias)):
        puntos.append('{:.2f},{:.2f}'.format(xpix(distancias[i]), ypix(altitudes[i])))
    puntos.append('{:.2f},{:.2f}'.format(xpix(distMax), yBase))
    puntos.append('{:.2f},{:.2f}'.format(xpix(0), yBase))   # cierre
    cadenaPuntos = ' '.join(puntos)

    svg.addPolyline(cadenaPuntos, '#1a6fc4', '2', 'rgba(26,111,196,0.18)')
    svg.addText('Distancia (m)',
                str(MARGEN_IZQ + PLOT_ANCHO / 2), str(ALTO - 20),
                'Verdana', '13', 'text-anchor: middle; fill: #333;')
    svg.addText('Altitud (m)', '20', str(MARGEN_SUP + PLOT_ALTO / 2),
                'Verdana', '13', 'text-anchor: middle; fill: #333;',
                transform='rotate(-90 20 {:.0f})'.format(MARGEN_SUP + PLOT_ALTO / 2))
    svg.addText('Altimetria: ' + nombreRuta,
                str(MARGEN_IZQ), str(MARGEN_SUP - 12),
                'Verdana', '13', 'fill: #222; font-weight: bold;')

    os.makedirs(idRuta, exist_ok=True)
    rutaArchivo = os.path.join(idRuta, 'altimetria.svg')
    svg.escribir(rutaArchivo)
    print('Creado:', rutaArchivo,
          '(dist {:.0f} m, alt {:.0f}-{:.0f} m)'.format(distMax, altMin, altMax))


def main():
    arbol = ET.parse(ARCHIVO_XML)
    raiz = arbol.getroot()
    for ruta in raiz.findall('r:ruta', NS):
        procesarRuta(ruta)


if __name__ == '__main__':
    main()