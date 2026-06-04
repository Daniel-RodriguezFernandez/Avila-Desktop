"use strict";

class Carrusel {
    #imagenes;
    #indiceActual;
    #intervaloMs;
    #temporizador;
    #imagenDom;

    constructor() {
        this.#imagenes = [
            { src: "multimedia/CR01.jpg", alt: "Mapa de situación de la provincia de Ávila" },
            { src: "multimedia/CR02.jpg", alt: "Muralla medieval de Ávila" },
            { src: "multimedia/CR03.jpg", alt: "Vista aérea de Ávila" },
            { src: "multimedia/CR04.jpg", alt: "Iglesia de Santa Teresa de Ávila" },
            { src: "multimedia/CR05.jpg", alt: "Catedral de Ávila" }
        ];
        this.#indiceActual = 0;
        this.#intervaloMs = 3000;
        this.#temporizador = null;
        this.#imagenDom = null;
    }

    iniciar() {
        if (this.#imagenes.length === 0) {
            this.#mostrarError("No hay imágenes disponibles para mostrar en el carrusel.");
            return;
        }
        this.#construirEstructura();
        this.#programarRotacion();
    }

    detener() {
        if (this.#temporizador !== null) {
            clearInterval(this.#temporizador);
            this.#temporizador = null;
        }
    }

    #construirEstructura() {
        const primera = this.#imagenes[this.#indiceActual];

        const articulo = $("<article></article>");
        const titulo = $("<h2></h2>").text("Recursos turísticos de la provincia de Ávila");

        this.#imagenDom = $("<img>")
            .attr("src", primera.src)
            .attr("alt", primera.alt);

        articulo.append(titulo, this.#imagenDom);
        $("main").append(articulo);
    }

    #programarRotacion() {
        this.#temporizador = setInterval(this.#cambiarImagen.bind(this), this.#intervaloMs);
    }

    #cambiarImagen() {
        this.#indiceActual = (this.#indiceActual + 1) % this.#imagenes.length;
        const imagen = this.#imagenes[this.#indiceActual];
        this.#imagenDom
            .attr("src", imagen.src)
            .attr("alt", imagen.alt);
    }

    #mostrarError(mensaje) {
        $("main").append($("<p></p>").text(mensaje));
    }
}