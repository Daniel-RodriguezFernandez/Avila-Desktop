"use strict";

class Meteorologia {
    #latitud;
    #longitud;
    #lugar;
    #endpoint;
    #actual;
    #previsiones;
    #unidadesActuales;
    #unidadesPrevision;

    static #DESCRIPCIONES = {
        0: "Cielo despejado",
        1: "Mayormente despejado",
        2: "Parcialmente nublado",
        3: "Nublado",
        45: "Niebla",
        48: "Niebla con escarcha",
        51: "Llovizna ligera",
        53: "Llovizna moderada",
        55: "Llovizna densa",
        56: "Llovizna helada ligera",
        57: "Llovizna helada densa",
        61: "Lluvia ligera",
        63: "Lluvia moderada",
        65: "Lluvia fuerte",
        66: "Lluvia helada ligera",
        67: "Lluvia helada fuerte",
        71: "Nevada ligera",
        73: "Nevada moderada",
        75: "Nevada fuerte",
        77: "Granos de nieve",
        80: "Chubascos ligeros",
        81: "Chubascos moderados",
        82: "Chubascos violentos",
        85: "Chubascos de nieve ligeros",
        86: "Chubascos de nieve fuertes",
        95: "Tormenta",
        96: "Tormenta con granizo ligero",
        99: "Tormenta con granizo fuerte"
    };

    static #ICONOS = {
        0: "☀️",
        1: "🌤️",
        2: "⛅",
        3: "☁️",
        45: "🌫️",
        48: "🌫️",
        51: "🌦️",
        53: "🌦️",
        55: "🌧️",
        56: "🌨️",
        57: "🌨️",
        61: "🌦️",
        63: "🌧️",
        65: "🌧️",
        66: "🌨️",
        67: "🌨️",
        71: "🌨️",
        73: "❄️",
        75: "❄️",
        77: "❄️",
        80: "🌦️",
        81: "🌧️",
        82: "⛈️",
        85: "🌨️",
        86: "❄️",
        95: "⛈️",
        96: "⛈️",
        99: "⛈️"
    };

    constructor() {
        this.#latitud = 40.6566;
        this.#longitud = -4.6818;
        this.#lugar = "Ávila";
        this.#endpoint = "https://api.open-meteo.com/v1/forecast";
        this.#actual = null;
        this.#previsiones = [];
        this.#unidadesActuales = {};
        this.#unidadesPrevision = {};
    }

    cargar() {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: this.#endpoint,
                method: "GET",
                dataType: "json",
                data: {
                    latitude: this.#latitud,
                    longitude: this.#longitud,
                    current: "weather_code,temperature_2m,apparent_temperature,relative_humidity_2m,precipitation,wind_speed_10m",
                    daily: "weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,wind_speed_10m_max",
                    timezone: "Europe/Madrid",
                    forecast_days: 7
                },
                success: (datos) => {
                    this.#procesarJSON(datos);
                    this.#mostrarTiempoActual();
                    this.#mostrarPrevision();
                    resolve();
                },
                error: (peticion) => {
                    this.#mostrarError("No se ha podido cargar la información meteorológica de " + this.#lugar + ".");
                    reject(peticion);
                }
            });
        });
    }

    #procesarJSON(datos) {
        this.#actual = null;
        this.#previsiones = [];

        if (!datos || !datos.current || !datos.daily) {
            this.#mostrarError("No se ha encontrado información meteorológica de " + this.#lugar + ".");
            return;
        }

        this.#unidadesActuales = datos.current_units || {};
        this.#unidadesPrevision = datos.daily_units || {};

        const actual = datos.current;
        this.#actual = {
            codigo: actual.weather_code,
            temperatura: actual.temperature_2m,
            sensacion: actual.apparent_temperature,
            humedad: actual.relative_humidity_2m,
            precipitacion: actual.precipitation,
            viento: actual.wind_speed_10m
        };

        const diario = datos.daily;
        const totalDias = Array.isArray(diario.time) ? diario.time.length : 0;
        for (let i = 0; i < totalDias; i++) {
            this.#previsiones.push({
                fecha: diario.time[i],
                codigo: diario.weather_code[i],
                temperaturaMaxima: diario.temperature_2m_max[i],
                temperaturaMinima: diario.temperature_2m_min[i],
                probabilidadPrecipitacion: diario.precipitation_probability_max[i],
                viento: diario.wind_speed_10m_max[i]
            });
        }
    }

    #mostrarTiempoActual() {
        if (!this.#actual) {
            return;
        }

        const seccion = $("<section></section>");
        seccion.append($("<h2></h2>").text("Tiempo actual en " + this.#lugar));

        const lista = $("<dl></dl>");
        this.#anadirDato(lista, "Estado", this.#estado(this.#actual.codigo));
        this.#anadirDato(lista, "Temperatura", this.#formatear(this.#actual.temperatura, this.#unidadesActuales.temperature_2m));
        this.#anadirDato(lista, "Sensación térmica", this.#formatear(this.#actual.sensacion, this.#unidadesActuales.apparent_temperature));
        this.#anadirDato(lista, "Humedad relativa", this.#formatear(this.#actual.humedad, this.#unidadesActuales.relative_humidity_2m));
        this.#anadirDato(lista, "Precipitación", this.#formatear(this.#actual.precipitacion, this.#unidadesActuales.precipitation));
        this.#anadirDato(lista, "Viento", this.#formatear(this.#actual.viento, this.#unidadesActuales.wind_speed_10m));

        seccion.append(lista);
        $("main").append(seccion);
    }

    #mostrarPrevision() {
        if (this.#previsiones.length === 0) {
            return;
        }

        const seccion = $("<section></section>");
        seccion.append($("<h2></h2>").text("Previsión para los próximos 7 días"));

        const tabla = $("<table></table>");
        tabla.append($("<caption></caption>").text("Previsión meteorológica en " + this.#lugar + " (incluye el día de hoy)."));
        tabla.append(this.#crearCabecera());

        const cuerpo = $("<tbody></tbody>");
        this.#previsiones.forEach((prevision, indice) => {
            cuerpo.append(this.#crearFilaPrevision(prevision, indice));
        });
        tabla.append(cuerpo);

        seccion.append(tabla);
        $("main").append(seccion);
    }

    #crearCabecera() {
        const cabecera = $("<thead></thead>");
        const fila = $("<tr></tr>");
        const columnas = ["Día", "Estado", "Mínima", "Máxima", "Prob. precipitación", "Viento máx."];

        for (const columna of columnas) {
            fila.append($("<th></th>").attr("scope", "col").text(columna));
        }

        cabecera.append(fila);
        return cabecera;
    }

    #crearFilaPrevision(prevision, indice) {
        const fila = $("<tr></tr>");

        fila.append($("<td></td>").attr("data-titulo", "Día").text(this.#formatearDia(prevision.fecha, indice)));
        fila.append($("<td></td>").attr("data-titulo", "Estado").text(this.#estado(prevision.codigo)));
        fila.append($("<td></td>").attr("data-titulo", "Mínima").text(this.#formatear(prevision.temperaturaMinima, this.#unidadesPrevision.temperature_2m_min)));
        fila.append($("<td></td>").attr("data-titulo", "Máxima").text(this.#formatear(prevision.temperaturaMaxima, this.#unidadesPrevision.temperature_2m_max)));
        fila.append($("<td></td>").attr("data-titulo", "Prob. precipitación").text(this.#formatear(prevision.probabilidadPrecipitacion, this.#unidadesPrevision.precipitation_probability_max)));
        fila.append($("<td></td>").attr("data-titulo", "Viento máx.").text(this.#formatear(prevision.viento, this.#unidadesPrevision.wind_speed_10m_max)));

        return fila;
    }

    #anadirDato(lista, termino, valor) {
        lista.append($("<dt></dt>").text(termino));
        lista.append($("<dd></dd>").text(valor));
    }

    #estado(codigo) {
        const icono = Meteorologia.#ICONOS[codigo] ?? "❓";
        const descripcion = Meteorologia.#DESCRIPCIONES[codigo] ?? "Estado desconocido";
        return icono + " " + descripcion;
    }

    #formatear(valor, unidad) {
        if (valor === null || valor === undefined) {
            return "No disponible";
        }
        return unidad ? `${valor} ${unidad}` : `${valor}`;
    }

    #formatearDia(fechaIso, indice) {
        if (indice === 0) {
            return "Hoy";
        }

        const fecha = new Date(`${fechaIso}T00:00:00`);
        if (isNaN(fecha.getTime())) {
            return fechaIso;
        }

        const texto = fecha.toLocaleDateString("es-ES", {
            weekday: "long",
            day: "numeric",
            month: "long"
        });
        return texto.charAt(0).toUpperCase() + texto.slice(1);
    }

    #mostrarError(mensaje) {
        $("main").append($("<p></p>").text(mensaje));
    }
}