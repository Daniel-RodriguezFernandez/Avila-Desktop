"use strict";


class Pregunta {
    #enunciado;
    #opciones;
    #indiceCorrecta;

    constructor(enunciado, opciones, indiceCorrecta) {
        this.#enunciado = enunciado;
        this.#opciones = opciones;
        this.#indiceCorrecta = indiceCorrecta;
    }

    get enunciado() {
        return this.#enunciado;
    }

    get opciones() {
        return this.#opciones;
    }

    get indiceCorrecta() {
        return this.#indiceCorrecta;
    }

    get respuestaCorrecta() {
        return this.#opciones[this.#indiceCorrecta];
    }

    esCorrecta(indice) {
        return Number(indice) === this.#indiceCorrecta;
    }
}


class Juego {
    #preguntas;
    #nombreGrupo;
    #seccion;
    #contenedorPreguntas;
    #boton;
    #resultado;

    constructor() {
        this.#preguntas = Juego.#crearPreguntas();
        this.#nombreGrupo = "pregunta";
        this.#seccion = null;
        this.#contenedorPreguntas = null;
        this.#boton = null;
        this.#resultado = null;
    }

    static #crearPreguntas() {
        return [
            new Pregunta(
                "¿Cuál es el plato más reconocido de la cocina abulense según la lista de platos representativos?",
                [
                    "Patatas revolconas",
                    "Judías del Barco de Ávila con chorizo",
                    "Yemas de Santa Teresa",
                    "Chuletón de Ávila",
                    "Cordero churro"
                ],
                3
            ),
            new Pregunta(
                "¿Qué posición ocupa las Yemas de Santa Teresa en la lista de platos más populares de la cocina abulense?",
                ["Primera", "Segunda", "Tercera", "Cuarta", "Quinta"],
                3
            ),
            new Pregunta(
                "¿Qué Indicación Geográfica Protegida (IGP) tiene la ternera de Ávila?",
                [
                    "Denominación de Origen",
                    "Indicación Geográfica Protegida",
                    "Marca de Calidad Regional",
                    "Sello de Producto Artesano",
                    "Certificado Ecológico"
                ],
                1
            ),
            new Pregunta(
                "¿Con qué acompañamiento se suelen servir las patatas revolconas?",
                [
                    "Pimientos asados",
                    "Chorizo frito",
                    "Torreznos crujientes",
                    "Huevo poché",
                    "Queso curado"
                ],
                2
            ),
            new Pregunta(
                "¿Con qué ingrediente se aliñan las patatas revolconas, además de aceite de oliva?",
                [
                    "Ajo y perejil",
                    "Comino molido",
                    "Pimentón ahumado",
                    "Azafrán",
                    "Pimienta negra"
                ],
                2
            ),
            new Pregunta(
                "¿En honor a quién reciben su nombre las Yemas de Santa Teresa?",
                [
                    "Una reina medieval de Castilla",
                    "La patrona de la ciudad de Ávila",
                    "Una monja repostera del siglo XVIII",
                    "La fundadora de la primera confitería de Ávila",
                    "Una santa romana mártir"
                ],
                1
            ),
            new Pregunta(
                "¿En qué valle se cultivan las Judías del Barco de Ávila con Indicación Geográfica Protegida?",
                ["Valle del Duero", "Valle del Tajo", "Valle del Tormes", "Valle del Adaja", "Valle del Eresma"],
                2
            ),
            new Pregunta(
                "¿Cuál de estos platos típicos de Ávila tiene una temporada recomendada de otoño e invierno?",
                [
                    "Chuletón de Ávila",
                    "Patatas revolconas",
                    "Judías del Barco con chorizo",
                    "Yemas de Santa Teresa",
                    "Cerdo ibérico de la sierra"
                ],
                2
            ),
            new Pregunta(
                "¿Cuántos platos aparecen en la tabla de temporadas de la página de Gastronomía?",
                ["2", "3", "4", "5", "6"],
                2
            ),
            new Pregunta(
                "¿Cuál de las siguientes carnes NO aparece mencionada entre los productos típicos de Ávila?",
                [
                    "Ternera de Ávila (IGP)",
                    "Cordero churro",
                    "Cerdo ibérico de la sierra",
                    "Cochinillo de Segovia",
                    "Ninguna, todas aparecen"
                ],
                3
            )
        ];
    }

    iniciar() {
        this.#seccion = $("<section></section>");
        this.#seccion.append($("<h2></h2>").text("Cuestionario sobre Ávila-Desktop"));

        this.#contenedorPreguntas = $("<ol></ol>");
        this.#preguntas.forEach((pregunta, indice) => {
            this.#contenedorPreguntas.append(this.#crearPregunta(pregunta, indice));
        });
        this.#seccion.append(this.#contenedorPreguntas);

        this.#boton = $("<button></button>")
            .attr("type", "button")
            .text("Comprobar respuestas")
            .on("click", () => this.#comprobar());
        this.#seccion.append(this.#boton);

        this.#resultado = $("<section></section>");
        this.#seccion.append(this.#resultado);

        $("main").append(this.#seccion);
    }

    #crearPregunta(pregunta, indice) {
        const grupo = `${this.#nombreGrupo}-${indice}`;

        const bloque = $("<li></li>").attr("data-indice", indice);
        bloque.append($("<p></p>").text(pregunta.enunciado));

        const opciones = $("<ul></ul>");
        pregunta.opciones.forEach((opcion, posicion) => {
            const radio = $("<input>")
                .attr("type", "radio")
                .attr("name", grupo)
                .attr("value", posicion);
            const etiqueta = $("<label></label>")
                .append(radio)
                .append($("<span></span>").text(" " + opcion));
            opciones.append($("<li></li>").append(etiqueta));
        });
        bloque.append(opciones);

        return bloque;
    }

    #comprobar() {
        const respuestas = this.#obtenerRespuestas();

        if (respuestas.includes(null)) {
            this.#mostrarAviso("Debes responder a todas las preguntas antes de comprobar el resultado.");
            return;
        }

        const aciertos = this.#calcularAciertos(respuestas);
        this.#mostrarResultado(aciertos, respuestas);
        this.#bloquear();
    }

    #obtenerRespuestas() {
        const respuestas = [];
        for (let i = 0; i < this.#preguntas.length; i++) {
            const seleccion = $(`input[name='${this.#nombreGrupo}-${i}']:checked`);
            respuestas.push(seleccion.length > 0 ? Number(seleccion.val()) : null);
        }
        return respuestas;
    }

    #calcularAciertos(respuestas) {
        let aciertos = 0;
        this.#preguntas.forEach((pregunta, indice) => {
            if (pregunta.esCorrecta(respuestas[indice])) {
                aciertos += 1;
            }
        });
        return aciertos;
    }

    #mostrarResultado(aciertos, respuestas) {
        const total = this.#preguntas.length;
        const calificacion = (aciertos / total) * 10;

        this.#resultado.empty();
        this.#resultado.append($("<h3></h3>").text("Resultado"));
        this.#resultado.append($("<p></p>").text(`Has acertado ${aciertos} de ${total} preguntas.`));
        this.#resultado.append($("<p></p>").text(`Tu calificación es: ${calificacion.toLocaleString("es-ES")} / 10.`));

        this.#marcarPreguntas(respuestas);

        const reiniciar = $("<button></button>")
            .attr("type", "button")
            .text("Volver a jugar")
            .on("click", () => this.#reiniciar());
        this.#resultado.append(reiniciar);
    }

    #marcarPreguntas(respuestas) {
        this.#preguntas.forEach((pregunta, indice) => {
            const bloque = this.#contenedorPreguntas.find(`li[data-indice='${indice}']`);
            const aviso = $("<p></p>");

            if (pregunta.esCorrecta(respuestas[indice])) {
                aviso.text("✓ Respuesta correcta.");
            } else {
                aviso.text(`✗ Respuesta incorrecta. La correcta es: ${pregunta.respuestaCorrecta}.`);
            }

            bloque.append(aviso);
        });
    }

    #bloquear() {
        this.#contenedorPreguntas.find("input[type='radio']").prop("disabled", true);
        this.#boton.prop("disabled", true);
    }

    #reiniciar() {
        this.#seccion.remove();
        this.iniciar();
    }

    #mostrarAviso(mensaje) {
        this.#resultado.empty();
        this.#resultado.append($("<p></p>").text(mensaje));
    }
}