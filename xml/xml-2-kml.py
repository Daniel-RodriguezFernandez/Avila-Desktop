import os
import xml.etree.ElementTree as ET

NS = {'r': 'http://www.uniovi.es'}

ARCHIVO_XML = 'rutas.xml'

class Kml(object):

    def __init__(self, nombreDocumento):
        """ Crea el elemento raiz <kml>, su espacio de nombres y el <Document> """
        self.raiz = ET.Element('kml', xmlns="http://www.opengis.net/kml/2.2")
        self.doc = ET.SubElement(self.raiz, 'Document')
        ET.SubElement(self.doc, 'name').text = nombreDocumento

    def addPlacemark(self, nombre, descripcion, lon, lat, alt, modoAltitud):
        """ Anade un waypoint (Placemark con un Point) """
        pm = ET.SubElement(self.doc, 'Placemark')
        ET.SubElement(pm, 'name').text = nombre
        ET.SubElement(pm, 'description').text = descripcion
        punto = ET.SubElement(pm, 'Point')
        ET.SubElement(punto, 'coordinates').text = '{},{},{}'.format(lon, lat, alt)
        ET.SubElement(punto, 'altitudeMode').text = modoAltitud

    def addLineString(self, nombre, extrude, tesela, listaCoordenadas,
                      modoAltitud, color, ancho):
        """ Anade una linea (Placemark con un LineString) con su estilo """
        pm = ET.SubElement(self.doc, 'Placemark')
        ET.SubElement(pm, 'name').text = nombre
        estilo = ET.SubElement(pm, 'Style')
        linea = ET.SubElement(estilo, 'LineStyle')
        ET.SubElement(linea, 'color').text = color
        ET.SubElement(linea, 'width').text = ancho
        ls = ET.SubElement(pm, 'LineString')
        ET.SubElement(ls, 'extrude').text = extrude
        ET.SubElement(ls, 'tessellate').text = tesela
        ET.SubElement(ls, 'altitudeMode').text = modoAltitud
        ET.SubElement(ls, 'coordinates').text = listaCoordenadas

    def escribir(self, nombreArchivoKML):
        """ Escribe el archivo KML con declaracion, indentacion y codificacion """
        arbol = ET.ElementTree(self.raiz)
        ET.indent(arbol)
        arbol.write(nombreArchivoKML, encoding='utf-8', xml_declaration=True)


def texto(elemento):
    """ Devuelve el texto de un elemento, sin espacios ni saltos de linea """
    if elemento is None or elemento.text is None:
        return ''
    return elemento.text.strip()


def coordenadasDe(elementoCoordenadas):
    lon = texto(elementoCoordenadas.find('r:longitud', NS))
    lat = texto(elementoCoordenadas.find('r:latitud', NS))
    alt = texto(elementoCoordenadas.find('r:altitud', NS))
    if alt == '':
        alt = '0.0'
    return lon, lat, alt


def procesarRuta(ruta):
    idRuta = ruta.get('id')
    nombreRuta = ruta.get('nombre')

    kml = Kml(nombreRuta)

    lugar = texto(ruta.find('r:inicio/r:lugar', NS))
    coordInicio = ruta.find('r:inicio/r:coordenadas', NS)
    lon, lat, alt = coordenadasDe(coordInicio)
    kml.addPlacemark('INICIO: ' + lugar, 'Lugar de inicio de la ruta',
                     lon, lat, alt, 'clampToGround')

    for hito in ruta.findall('r:hitos/r:hito', NS):
        nombreHito = hito.get('nombre')
        descripcionHito = texto(hito.find('r:descripcion', NS))
        coordHito = hito.find('r:coordenadas', NS)
        lon, lat, alt = coordenadasDe(coordHito)
        kml.addPlacemark(nombreHito, descripcionHito, lon, lat, alt,
                         'clampToGround')

    listaCoordenadas = ''
    for coord in ruta.findall('r:trazado/r:coordenadas', NS):
        lon, lat, alt = coordenadasDe(coord)
        listaCoordenadas += '{},{},{}\n'.format(lon, lat, alt)

    kml.addLineString('Trazado: ' + nombreRuta, '0', '1',
                      listaCoordenadas, 'clampToGround', '#ff0000ff', '4')

    os.makedirs(idRuta, exist_ok=True)
    rutaArchivo = os.path.join(idRuta, 'planimetria.kml')
    kml.escribir(rutaArchivo)
    print('Creado:', rutaArchivo, '(' + nombreRuta + ')')


def main():
    arbol = ET.parse(ARCHIVO_XML)
    raiz = arbol.getroot()
    for ruta in raiz.findall('r:ruta', NS):
        procesarRuta(ruta)


if __name__ == '__main__':
    main()
