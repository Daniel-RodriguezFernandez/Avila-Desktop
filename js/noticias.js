"use strict";

class Noticias {
    #busqueda;
    #idioma;
    #pais;
    #maximo;
    #apikey;
    #endpoint;
    #noticias;

    constructor() {
        this.#busqueda = "Ávila";
        this.#idioma = "es";
        this.#pais = "es";
        this.#maximo = 6;
        this.#apikey = "5b04f7c5c1ebc851cca270df43aceb55";
        this.#endpoint = "https://gnews.io/api/v4/search";
        this.#noticias = [];
    }

    buscar() {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: this.#endpoint,
                method: "GET",
                dataType: "json", // GNews admite CORS y responde en JSON
                data: {
                    q: this.#busqueda,
                    lang: this.#idioma,
                    country: this.#pais,
                    max: this.#maximo,
                    apikey: this.#apikey
                },
                success: (datos) => {
                    this.#procesarJSON(datos);
                    this.#mostrarNoticias();
                    resolve();
                },
                error: (peticion) => {
                    this.#mostrarError("No se han podido cargar las noticias sobre la provincia.");
                    reject(peticion);
                }
            });
        });
    }

    #procesarJSON(datos) {
        this.#noticias = [];

        if (!datos || !Array.isArray(datos.articles) || datos.articles.length === 0) {
            this.#mostrarError("No se han encontrado noticias sobre la provincia de Ávila.");
            return;
        }

        for (const articulo of datos.articles) {
            this.#noticias.push({
                titulo: articulo.title,
                descripcion: articulo.description,
                url: articulo.url,
                fuente: articulo.source ? articulo.source.name : "",
                fecha: articulo.publishedAt
            });
        }
    }

    #mostrarNoticias() {
        if (this.#noticias.length === 0) {
            return; // El error ya se ha mostrado en #procesarJSON.
        }

        const seccion = $("<section></section>");
        seccion.append($("<h2></h2>").text("Noticias sobre la provincia de Ávila"));

        for (const noticia of this.#noticias) {
            seccion.append(this.#crearArticulo(noticia));
        }

        $("main").append(seccion);
    }

    #crearArticulo(noticia) {
        const articulo = $("<article></article>");

        const enlace = $("<a></a>")
            .attr("href", noticia.url)
            .attr("target", "_blank")
            .attr("rel", "noopener noreferrer")
            .text(noticia.titulo);

        articulo.append($("<h3></h3>").append(enlace));

        if (noticia.descripcion) {
            articulo.append($("<p></p>").text(noticia.descripcion));
        }

        articulo.append($("<p></p>").text(this.#formatearPie(noticia)));
        return articulo;
    }

    #formatearPie(noticia) {
        const fecha = this.#formatearFecha(noticia.fecha);
        if (noticia.fuente && fecha) {
            return `${noticia.fuente} · ${fecha}`;
        }
        return noticia.fuente || fecha;
    }

    #formatearFecha(fechaIso) {
        if (!fechaIso) {
            return "";
        }
        const fecha = new Date(fechaIso);
        if (isNaN(fecha.getTime())) {
            return "";
        }
        return fecha.toLocaleDateString("es-ES", {
            day: "numeric",
            month: "long",
            year: "numeric"
        });
    }

    #mostrarError(mensaje) {
        $("main").append($("<p></p>").text(mensaje));
    }
}
