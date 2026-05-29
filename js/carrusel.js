class Carrusel {

    constructor() {
        this.carpeta = "multimedia/";
        this.actual = 0;
        this.maximo = 5;
        this.imagen = null;
    }

    rutaFoto(indice) {
        let numero = String(indice + 1).padStart(2, "0");
        return this.carpeta + "CR" + numero + ".jpg";
    }

    mostrarFotografias() {
        let articulo = $("<article></article>");
        let titulo = $("<h2></h2>").text("Recursos turísticos de Ávila");
        this.imagen = $("<img>")
            .attr("src", this.rutaFoto(this.actual))
            .attr("alt", "Recurso turístico de Ávila");

        articulo.append(titulo).append(this.imagen);
        $("main p").after(articulo);

        setInterval(this.cambiarFotografia.bind(this), 3000);
    }
    cambiarFotografia() {
        this.actual = (this.actual + 1) % this.maximo;
        this.imagen.attr("src", this.rutaFoto(this.actual));
    }
}

$(document).ready(function () {
    let carrusel = new Carrusel();
    carrusel.mostrarFotografias();
});