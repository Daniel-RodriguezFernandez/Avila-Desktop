class Noticias {

    constructor() {
        this.url = "https://gnews.io/api/v4/search";
        this.clave = "5b04f7c5c1ebc851cca270df43aceb55";
        this.consulta = "Ávila";
        this.idioma = "es";
        this.pais = "es";
        this.maximo = 6;
        this.contenedor = null;
    }

    mostrarNoticias() {
        let seccion = $("<section></section>").attr("id", "noticias");
        let titulo = $("<h2></h2>").text("Noticias de la provincia");
        this.contenedor = $("<div></div>").addClass("lista-noticias");

        seccion.append(titulo).append(this.contenedor);
        $("main").append(seccion);

        this.solicitarNoticias();
    }

    solicitarNoticias() {
        $.ajax({
            url: this.url,
            method: "GET",
            dataType: "json",
            data: {
                q: this.consulta,
                lang: this.idioma,
                country: this.pais,
                max: this.maximo,
                apikey: this.clave
            }
        })
            .done(this.pintarNoticias.bind(this))
            .fail(this.mostrarError.bind(this));
    }

    pintarNoticias(respuesta) {
        this.contenedor.empty();

        if (!respuesta.articles || respuesta.articles.length === 0) {
            this.mostrarError();
            return;
        }

        respuesta.articles.forEach(this.crearNoticia.bind(this));
    }

    crearNoticia(articulo) {
        let noticia = $("<article></article>").addClass("noticia");

        let enlace = $("<a></a>")
            .attr("href", articulo.url)
            .attr("target", "_blank")
            .attr("rel", "noopener")
            .text(articulo.title);
        let titular = $("<h3></h3>").append(enlace);

        noticia.append(titular);

        if (articulo.image) {
            let imagen = $("<img>")
                .attr("src", articulo.image)
                .attr("alt", articulo.title);
            noticia.append(imagen);
        }

        let entradilla = $("<p></p>").text(articulo.description);
        let fuente = $("<span></span>")
            .addClass("fuente")
            .text(articulo.source.name + " · " + this.formatearFecha(articulo.publishedAt));

        noticia.append(entradilla).append(fuente);
        this.contenedor.append(noticia);
    }

    formatearFecha(fechaIso) {
        let fecha = new Date(fechaIso);
        return fecha.toLocaleDateString("es-ES");
    }

    mostrarError() {
        this.contenedor
            .empty()
            .append($("<p></p>").text("No se han podido cargar las noticias en este momento."));
    }
}

$(document).ready(function () {
    let noticias = new Noticias();
    noticias.mostrarNoticias();
});