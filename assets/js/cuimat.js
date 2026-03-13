     //PREGUNTAS A1
    var preguntasA1 = document.querySelectorAll('.campoA1')
    var pregunta1 = document.getElementById('A-1-1')
    var subA1 = document.querySelectorAll('.subA1')
    var totalA1 = document.getElementById('totalA1');

    //PREGUNTAS A2
    var preguntasA2 = document.querySelectorAll('.campoA2')
    var totalA2 = document.getElementById('totalA2');

    //SUBTOTAL A
    var subtotalA = document.getElementById('subtotalA');
    var subtotalB = document.getElementById('subtotalB');

    
    //Preguntas B1
    var preguntasB1 = document.querySelectorAll('.campoB1');
    var pregubtab5 = document.getElementById('preguntab5');
    var subB1 = document.querySelectorAll('.subB1');
    var totalB1 = document.getElementById('totalB1');
    
    //preguntasB2
    var preguntasB2 = document.querySelectorAll('.campoB2');
    var preguntab3 = document.getElementById('preguntab3');
    var subB2 = document.querySelectorAll('.subB2');
    var totalB2 = document.getElementById('totalB2');


    var preguntasc = document.querySelectorAll('.campoC');
    var totalC = document.getElementById('totalC');
    var subtotalC = document.getElementById('subtotalC');

    var preguntasd = document.querySelectorAll('.campoD');
    var totalD = document.getElementById('totalD');
    var subtotalD = document.getElementById('subtotalD');
    
    //total final
    var totalF = document.getElementById('totalF');

    //preguntas pertenecientes a la pregunta 1
    subA1.forEach(i => {
        i.addEventListener('keyup', function() {
            let sub = 0;
            subA1.forEach(j => {
                if (j.value != '') {
                    sub += parseFloat(j.value)
                }
            })
            pregunta1.value = sub / 4;
            updateTotalA1();
        })
    })

    //Eventos de preguntas 1
    preguntasA1.forEach(item => {
        item.addEventListener('keyup', updateTotalA1)
    })

    preguntasA2.forEach(item => {
        item.addEventListener('keyup', updateTotalA2)
    })

    preguntasc.forEach(item => {
        item.addEventListener('keyup', updateTotalC)
    })

    preguntasd.forEach(item => {
        item.addEventListener('keyup', updateTotalD)
    })

    function updateTotalA1() {
        let total = 0;
        preguntasA1.forEach(pregunta => {

            if (pregunta.value != '') {
                total += parseFloat(pregunta.value);
            }
        })
        totalA1.value = (total / 5) * 0.1
        let inner = parseFloat(totalA1.value) + parseFloat(totalA2.value);

        subtotalA.value = inner;
        totalF.value = parseFloat(subtotalA.value) + parseFloat(subtotalB.value)  + parseFloat(subtotalC.value) + parseFloat(subtotalD.value)

    }

    

    function updateTotalA2() {
        let total = 0;
        preguntasA2.forEach(pregunta => {

            if (pregunta.value != '') {
                total += parseFloat(pregunta.value);
            }
        })
        totalA2.value = (total / 10) * 0.1
        let inner = parseFloat(totalA1.value) + parseFloat(totalA2.value);
        subtotalA.value = inner;
        totalF.value = parseFloat(subtotalA.value) + parseFloat(subtotalB.value)  + parseFloat(subtotalC.value) + parseFloat(subtotalD.value)


    }


    function updateTotalC() {
        let total = 0;
        preguntasc.forEach(pregunta => {

            if (pregunta.value != '') {
                total += parseFloat(pregunta.value);
            }
        })
        totalC.value = (total / 10) * 0.1
        subtotalC.value = totalC.value;

        totalF.value = parseFloat(subtotalA.value) + parseFloat(subtotalB.value)  + parseFloat(subtotalC.value) + parseFloat(subtotalD.value)

    }

    function updateTotalD() {
        let total = 0;
        preguntasd.forEach(pregunta => {

            if (pregunta.value != '') {
                total += parseFloat(pregunta.value);
            }
        })
        totalD.value = (total / 10) * 0.1
        subtotalD.value = totalD.value;
        totalF.value = parseFloat(subtotalA.value) + parseFloat(subtotalB.value)  + parseFloat(subtotalC.value) + parseFloat(subtotalD.value)


    }

    
