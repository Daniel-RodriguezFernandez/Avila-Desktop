"use strict";


class Ruta {
    #datos;

    constructor(datos) {
        this.#datos = datos;
    }

    get id() {
        return this.#datos.id;
    }

    get nombre() {
        return this.#datos.nombre;
    }

    get tipo() {
        return this.#datos.tipo;
    }

    get medio() {
        return this.#datos.medio;
    }

    get agencia() {
        return this.#datos.agencia;
    }

    get recomendacion() {
        return this.#datos.recomendacion;
    }

    get fechaInicio() {
        return this.#datos.fechaInicio;
    }

    get horaInicio() {
        return this.#datos.horaInicio;
    }

    get duracion() {
        return this.#datos.duracion;
    }

    get descripcion() {
        return this.#datos.descripcion;
    }

    get personasAdecuadas() {
        return this.#datos.personasAdecuadas;
    }

    get inicio() {
        return this.#datos.inicio;
    }

    get referencias() {
        return this.#datos.referencias;
    }

    get hitosPrincipales() {
        return this.#datos.hitosPrincipales;
    }

    get resumen() {
        return this.#datos.resumen;
    }

    get planimetria() {
        return this.#datos.planimetria;
    }

    get altimetria() {
        return this.#datos.altimetria;
    }

    get coordenadasInicio() {
        return [this.#datos.inicio.longitud, this.#datos.inicio.latitud];
    }
}


class Mapa {
    #idContenedor;
    #urlKml;
    #centro;

    constructor(idContenedor, urlKml, centro) {
        this.#idContenedor = idContenedor;
        this.#urlKml = urlKml;
        this.#centro = centro;
    }

    dibujar() {
        if (typeof google !== "undefined" && google.maps) {
            this.#inicializar();
        } else {
            document.addEventListener("googlemapsready", () => this.#inicializar(), { once: true });
        }
    }

    #inicializar() {
        const mapa = new google.maps.Map(document.getElementById(this.#idContenedor), {
            center: { lat: this.#centro[1], lng: this.#centro[0] },
            zoom: 15
        });

        $.ajax({
            url: this.#urlKml,
            method: "GET",
            dataType: "text",
            success: (kmlTexto) => this.#cargarKml(kmlTexto, mapa),
            error: () => {
                $("#" + this.#idContenedor).text("No se ha podido cargar la planimetría.");
            }
        });
    }

    #cargarKml(kmlTexto, mapa) {
        const doc = new DOMParser().parseFromString(kmlTexto, "application/xml");
        const placemarks = Array.from(doc.getElementsByTagNameNS("*", "Placemark"));
        const bounds = new google.maps.LatLngBounds();

        for (const placemark of placemarks) {
            const nombre = this.#textoKml(placemark, "name");

            const punto = placemark.getElementsByTagNameNS("*", "Point")[0];
            if (punto) {
                const partes = this.#textoKml(punto, "coordinates").split(",");
                const pos = { lat: parseFloat(partes[1]), lng: parseFloat(partes[0]) };
                new google.maps.Marker({ position: pos, map: mapa, title: nombre });
                bounds.extend(pos);
            }

            const lineString = placemark.getElementsByTagNameNS("*", "LineString")[0];
            if (lineString) {
                const path = this.#textoKml(lineString, "coordinates")
                    .trim().split(/\s+/)
                    .map((coord) => {
                        const p = coord.split(",");
                        const latLng = { lat: parseFloat(p[1]), lng: parseFloat(p[0]) };
                        bounds.extend(latLng);
                        return latLng;
                    });

                const lineStyle = placemark.getElementsByTagNameNS("*", "LineStyle")[0];
                let strokeColor = "#FF0000";
                let strokeWeight = 4;
                if (lineStyle) {
                    const kmlColor = this.#textoKml(lineStyle, "color").replace("#", "");
                    if (kmlColor.length >= 8) {
                        // KML color is AABBGGRR → convertir a #RRGGBB
                        strokeColor = `#${kmlColor.slice(6, 8)}${kmlColor.slice(4, 6)}${kmlColor.slice(2, 4)}`;
                    }
                    const w = parseInt(this.#textoKml(lineStyle, "width"), 10);
                    if (!isNaN(w)) strokeWeight = w;
                }

                new google.maps.Polyline({ path, map: mapa, strokeColor, strokeWeight });
            }
        }

        if (!bounds.isEmpty()) {
            mapa.fitBounds(bounds);
        }
    }

    #textoKml(nodo, nombre) {
        const elem = nodo.getElementsByTagNameNS("*", nombre)[0];
        return elem ? elem.textContent.trim() : "";
    }
}


class Altimetria {
    #contenedor;
    #urlSvg;

    constructor(contenedor, urlSvg) {
        this.#contenedor = contenedor;
        this.#urlSvg = urlSvg;
    }

    cargar() {
        $.ajax({
            url: this.#urlSvg,
            method: "GET",
            dataType: "text",
            success: (svgTexto) => this.#insertar(svgTexto),
            error: () => this.#mostrarError()
        });
    }

    #insertar(svgTexto) {
        const documento = new DOMParser().parseFromString(svgTexto, "image/svg+xml");
        const svg = documento.documentElement;

        if (svg && svg.nodeName.toLowerCase() === "svg") {
            this.#contenedor.empty().append(document.importNode(svg, true));
        } else {
            this.#mostrarError();
        }
    }

    #mostrarError() {
        this.#contenedor.empty().append($("<p></p>").text("No se ha podido cargar la altimetría de la ruta."));
    }
}


class Rutas {
    #urlXml;
    #basePath;
    #rutas;

    constructor() {
        this.#urlXml = "xml/rutas.xml";
        this.#basePath = "xml/";
        this.#rutas = [];
    }

    cargar() {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: this.#urlXml,
                method: "GET",
                dataType: "xml",
                success: (xml) => {
                    this.#rutas = this.#parsearRutas(xml);
                    this.#render();
                    resolve();
                },
                error: (peticion) => {
                    this.#mostrarError("No se han podido cargar las rutas.");
                    reject(peticion);
                }
            });
        });
    }


    #parsearRutas(xml) {
        const nodos = Array.from(xml.getElementsByTagNameNS("*", "ruta"));
        return nodos.map((nodo) => this.#parsearRuta(nodo));
    }

    #parsearRuta(nodo) {
        const inicio = nodo.getElementsByTagNameNS("*", "inicio")[0];

        return new Ruta({
            id: nodo.getAttribute("id"),
            nombre: nodo.getAttribute("nombre"),
            tipo: nodo.getAttribute("tipo"),
            medio: nodo.getAttribute("medio"),
            agencia: nodo.getAttribute("agencia"),
            recomendacion: nodo.getAttribute("recomendacion"),
            fechaInicio: this.#texto(nodo, "fecha_inicio"),
            horaInicio: this.#texto(nodo, "hora_inicio"),
            duracion: this.#texto(nodo, "duracion"),
            descripcion: this.#limpiar(this.#texto(nodo, "descripcion")),
            personasAdecuadas: this.#limpiar(this.#texto(nodo, "personasAdecuadas")),
            inicio: {
                lugar: this.#texto(inicio, "lugar"),
                direccion: this.#texto(inicio, "direccion"),
                longitud: this.#numero(inicio, "longitud"),
                latitud: this.#numero(inicio, "latitud"),
                altitud: this.#numero(inicio, "altitud")
            },
            referencias: this.#parsearReferencias(nodo),
            hitosPrincipales: this.#parsearHitosPrincipales(nodo),
            resumen: this.#calcularResumen(nodo),
            planimetria: this.#texto(nodo, "planimetria"),
            altimetria: this.#texto(nodo, "altimetria")
        });
    }

    #parsearReferencias(nodo) {
        return Array.from(nodo.getElementsByTagNameNS("*", "referencia"))
            .map((referencia) => referencia.textContent.trim());
    }

    #parsearHitosPrincipales(nodo) {
        return Array.from(nodo.getElementsByTagNameNS("*", "hito"))
            .filter((hito) => hito.getAttribute("nombre"))
            .map((hito) => ({
                nombre: hito.getAttribute("nombre"),
                descripcion: this.#limpiar(this.#texto(hito, "descripcion")),
                longitud: this.#numero(hito, "longitud"),
                latitud: this.#numero(hito, "latitud"),
                altitud: this.#numero(hito, "altitud"),
                fotografias: this.#parsearFotografias(hito)
            }));
    }

    #parsearFotografias(nodo) {
        return Array.from(nodo.getElementsByTagNameNS("*", "fotografia"))
            .map((foto) => foto.textContent.trim())
            .filter((src) => src !== "");
    }

    #rutaMultimedia(nombreArchivo) {
        return nombreArchivo.includes("/") ? nombreArchivo : "multimedia/" + nombreArchivo;
    }

    #calcularResumen(nodo) {
        const hitos = Array.from(nodo.getElementsByTagNameNS("*", "hito"));
        let distanciaTotal = 0;
        let altitudMin = Infinity;
        let altitudMax = -Infinity;

        for (const hito of hitos) {
            const distancia = this.#numero(hito, "distancia");
            if (distancia !== null) {
                distanciaTotal += distancia;
            }
            const altitud = this.#numero(hito, "altitud");
            if (altitud !== null) {
                altitudMin = Math.min(altitudMin, altitud);
                altitudMax = Math.max(altitudMax, altitud);
            }
        }

        return {
            numHitos: hitos.length,
            distanciaTotal: distanciaTotal,
            altitudMin: Number.isFinite(altitudMin) ? altitudMin : null,
            altitudMax: Number.isFinite(altitudMax) ? altitudMax : null
        };
    }

    #texto(nodo, nombre) {
        if (!nodo) {
            return "";
        }
        const elementos = nodo.getElementsByTagNameNS("*", nombre);
        return elementos.length > 0 ? elementos[0].textContent.trim() : "";
    }

    #numero(nodo, nombre) {
        const texto = this.#texto(nodo, nombre);
        if (texto === "") {
            return null;
        }
        const valor = Number(texto);
        return Number.isNaN(valor) ? null : valor;
    }

    #render() {
        if (this.#rutas.length === 0) {
            this.#mostrarError("No se ha encontrado ninguna ruta.");
            return;
        }

        for (const ruta of this.#rutas) {
            const articulo = this.#crearArticulo(ruta);
            $("main").append(articulo);

            new Mapa("mapa-" + ruta.id, this.#basePath + ruta.planimetria, ruta.coordenadasInicio).dibujar();
            new Altimetria(articulo.children("figure"), this.#basePath + ruta.altimetria).cargar();
        }
    }

    #crearArticulo(ruta) {
        const articulo = $("<article></article>");
        articulo.append($("<h2></h2>").text(ruta.nombre));
        articulo.append(this.#crearFicha(ruta));

        articulo.append($("<h3></h3>").text("Descripción"));
        articulo.append($("<p></p>").text(ruta.descripcion));

        if (ruta.hitosPrincipales.length > 0) {
            articulo.append($("<h3></h3>").text("Hitos principales"));
            articulo.append(this.#crearListaHitos(ruta.hitosPrincipales));
        }

        if (ruta.referencias.length > 0) {
            articulo.append($("<h3></h3>").text("Referencias"));
            articulo.append(this.#crearListaReferencias(ruta.referencias));
        }

        articulo.append($("<h3></h3>").text("Planimetría"));
        articulo.append($("<div></div>").addClass("mapa").attr("id", "mapa-" + ruta.id));

        articulo.append($("<h3></h3>").text("Altimetría"));
        articulo.append($("<figure></figure>"));

        return articulo;
    }

    #crearFicha(ruta) {
        const lista = $("<dl></dl>");
        const inicio = ruta.inicio;
        const resumen = ruta.resumen;

        this.#dato(lista, "Identificador", ruta.id);
        this.#dato(lista, "Tipo", ruta.tipo);
        this.#dato(lista, "Medio", ruta.medio);
        this.#dato(lista, "Agencia", ruta.agencia);
        this.#dato(lista, "Recomendación", `${ruta.recomendacion} / 10`);

        if (ruta.fechaInicio) {
            this.#dato(lista, "Fecha de inicio", this.#formatearFecha(ruta.fechaInicio));
        }
        if (ruta.horaInicio) {
            this.#dato(lista, "Hora de inicio", ruta.horaInicio.slice(0, 5));
        }
        this.#dato(lista, "Duración", this.#formatearDuracion(ruta.duracion));
        this.#dato(lista, "Personas adecuadas", ruta.personasAdecuadas);
        this.#dato(lista, "Lugar de inicio", inicio.lugar);
        this.#dato(lista, "Dirección", inicio.direccion);
        this.#dato(lista, "Coordenadas de inicio",
            `Lat. ${inicio.latitud}, Lon. ${inicio.longitud}, Alt. ${inicio.altitud} m`);
        this.#dato(lista, "Distancia total", this.#formatearDistancia(resumen.distanciaTotal));

        if (resumen.altitudMin !== null) {
            this.#dato(lista, "Altitud", `${resumen.altitudMin} – ${resumen.altitudMax} m`);
        }
        this.#dato(lista, "Número de puntos", resumen.numHitos.toLocaleString("es-ES"));

        return lista;
    }

    #crearListaHitos(hitos) {
        const lista = $("<ul></ul>");
        for (const hito of hitos) {
            const elemento = $("<li></li>");

            const detalles = [];
            if (hito.descripcion) {
                detalles.push(hito.descripcion);
            }
            if (hito.altitud !== null) {
                detalles.push(`${hito.altitud} m`);
            }
            const texto = detalles.length > 0 ? `${hito.nombre} — ${detalles.join(", ")}` : hito.nombre;
            elemento.append($("<span></span>").text(texto));

            if (hito.fotografias.length > 0) {
                const figura = $("<figure></figure>");
                const imagen = $("<img>")
                    .attr("src", this.#rutaMultimedia(hito.fotografias[0]))
                    .attr("alt", `Fotografía del hito ${hito.nombre}`)
                    .attr("loading", "lazy");
                figura.append(imagen);
                elemento.append(figura);
            }

            lista.append(elemento);
        }
        return lista;
    }

    #crearListaReferencias(referencias) {
        const lista = $("<ul></ul>");
        for (const referencia of referencias) {
            const enlace = $("<a></a>")
                .attr("href", referencia)
                .attr("target", "_blank")
                .attr("rel", "noopener noreferrer")
                .text(referencia);
            lista.append($("<li></li>").append(enlace));
        }
        return lista;
    }

    #dato(lista, termino, valor) {
        lista.append($("<dt></dt>").text(termino));
        lista.append($("<dd></dd>").text(valor));
    }


    #formatearFecha(fechaIso) {
        const fecha = new Date(`${fechaIso}T00:00:00`);
        if (Number.isNaN(fecha.getTime())) {
            return fechaIso;
        }
        return fecha.toLocaleDateString("es-ES", { day: "numeric", month: "long", year: "numeric" });
    }

    #formatearDuracion(iso) {
        if (!iso) {
            return "";
        }
        const patron = /^P(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+(?:\.\d+)?)S)?)?$/;
        const coincidencia = patron.exec(iso.trim());
        if (!coincidencia) {
            return iso;
        }

        const [, dias, horas, minutos, segundos] = coincidencia;
        const partes = [];
        if (dias) {
            partes.push(`${dias} d`);
        }
        if (horas) {
            partes.push(`${horas} h`);
        }
        if (minutos) {
            partes.push(`${minutos} min`);
        }
        if (segundos) {
            partes.push(`${segundos} s`);
        }
        return partes.length > 0 ? partes.join(" ") : "0 min";
    }

    #formatearDistancia(metros) {
        if (metros >= 1000) {
            const km = (metros / 1000).toLocaleString("es-ES", { maximumFractionDigits: 2 });
            return `${km} km`;
        }
        return `${Math.round(metros)} m`;
    }

    #limpiar(texto) {
        return texto.replace(/\s+/g, " ").trim();
    }

    #mostrarError(mensaje) {
        $("main").append($("<p></p>").text(mensaje));
    }
}