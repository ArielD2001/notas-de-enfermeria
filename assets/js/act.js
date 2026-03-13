var inputs = document.querySelectorAll('input');
var texts = document.querySelectorAll('textarea')
      
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
  
      
      //Preguntas B1
      var preguntasB1 = document.querySelectorAll('.campoB1');
      var totalB1 = document.getElementById('totalB1');
      
      //preguntasB2
      var preguntasB2 = document.querySelectorAll('.campoB2');
      var pregunta5 = document.getElementById('pregunta5');
      var pregunta10 = document.getElementById('pregunta10');
      var subB2 = document.querySelectorAll('.subB2');
      var subB2a = document.querySelectorAll('.subB2a');
      var totalB2 = document.getElementById('totalB2');
  
      //SUBTOTAL B
      var subtotalB = document.getElementById('subtotalB');
  
      //Pregunta C
      var preguntasC = document.querySelectorAll('.campoC');
      var totalC = document.getElementById('totalC');
      var subtotalC = document.getElementById('subtotalC');
  
      //Preguntas D
      var preguntasD = document.querySelectorAll('.campoD');
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
      //Evento de prguntas B
      preguntasB1.forEach(item => {
          item.addEventListener('keyup', updateTotalB1)
      })
  
      preguntasB2.forEach(item => {
          item.addEventListener('keyup', updateTotalB2)
      })
      //Evento de prguntas C
      preguntasC.forEach(item => {
          item.addEventListener('keyup', updateTotalC)
      })
      //Evento de preguntas D
      preguntasD.forEach(item => {
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
  
          subtotalA.value = inner.toFixed(2);
          totalF.value = (parseFloat(subtotalA.value) + parseFloat(subtotalB.value) + parseFloat(subtotalC.value) + parseFloat(subtotalD.value)).toFixed(2);
  
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
          subtotalA.value = inner.toFixed(2);
          totalF.value = (parseFloat(subtotalA.value) + parseFloat(subtotalB.value) + parseFloat(subtotalC.value) + parseFloat(subtotalD.value)).toFixed(2);
      }
      function updateTotalB1() {
          let total = 0;
          preguntasB1.forEach(pregunta => {
  
              if (pregunta.value != '') {
                  total += parseFloat(pregunta.value);
              }
          })
          totalB1.value = (total / 9) * 0.25
          subtotalB.value = (parseFloat(totalB1.value) + parseFloat(totalB2.value)).toFixed(2);
  
          
          totalF.value = (parseFloat(subtotalA.value) + parseFloat(subtotalB.value) + parseFloat(subtotalC.value) + parseFloat(subtotalD.value)).toFixed(2);
          
  
      }
  
      subB2.forEach(i => {
          i.addEventListener('keyup', function() {
              let sub = 0;
              subB2.forEach(j => {
                  if (j.value != '') {
                      sub += parseFloat(j.value)
                  }
              })
              pregunta5.value = sub / 6;
              updateTotalB2();
          })
      })
      subB2a.forEach(i => {
          i.addEventListener('keyup', function() {
              let suba = 0;
              subB2a.forEach(j => {
                  if (j.value != '') {
                      suba += parseFloat(j.value)
                  }
              })
              pregunta10.value = suba / 5;
              updateTotalB2();
          })
      })
  
      //Eventos de preguntas 2
      
  
      function updateTotalB2() {
          let total = 0;
          preguntasB2.forEach(pregunta => {
  
              if (pregunta.value != '') {
                  total += parseFloat(pregunta.value);
              }
          })
          totalB2.value = (total / 19) * 0.35
          subtotalB.value = (parseFloat(totalB1.value) + parseFloat(totalB2.value)).toFixed(2);
  
         
          totalF.value = (parseFloat(subtotalA.value) + parseFloat(subtotalB.value) + parseFloat(subtotalC.value) + parseFloat(subtotalD.value)).toFixed(2);
  
      }
  
      function updateTotalC() {
          let total = 0;
          preguntasC.forEach(pregunta => {
  
              if (pregunta.value != '') {
                  total += parseFloat(pregunta.value);
              }
          })
          totalC.value = (total / 1) * 0.1
          let inner = parseFloat(totalC.value)
  
        
          subtotalC.value = inner;
          totalF.value = (parseFloat(subtotalA.value) + parseFloat(subtotalB.value) + parseFloat(subtotalC.value) + parseFloat(subtotalD.value)).toFixed(2);

  
      }
      function updateTotalD() {
          let total = 0;
          preguntasD.forEach(pregunta => {
  
              if (pregunta.value != '') {
                  total += parseFloat(pregunta.value);
              }
          })
          totalD.value = (total / 2) * 0.1
          let inner = parseFloat(totalD.value)
  
          subtotalD.value = inner;
          totalF.value = (parseFloat(subtotalA.value) + parseFloat(subtotalB.value) + parseFloat(subtotalC.value) + parseFloat(subtotalD.value)).toFixed(2);
  
      }


      
      window.onload = (event) => {
      
        var div = document.getElementById('respuestas').innerHTML;
        console.log(div)
    
        if (div == '') {
            console.log('vacio')
        } else {
            let json = JSON.parse(div)
            console.log(json)
        
    
            inputs[0].value = json['s1'].toFixed(2);    inputs[30].value = json['p27'].toFixed(2); 
            inputs[1].value = json['p1'].toFixed(2);    inputs[31].value = json['t3'].toFixed(2); 
            inputs[2].value = json['p2'].toFixed(2);    inputs[32].value = json['p28'].toFixed(2); 
            inputs[3].value = json['p3'].toFixed(2);    inputs[33].value = json['p29'].toFixed(2); 
            inputs[4].value = json['p4'].toFixed(2);    inputs[34].value = json['p30'].toFixed(2); 
            inputs[5].value = json['p5'].toFixed(2);    inputs[35].value = json['p31'].toFixed(2); 
            inputs[6].value = json['p6'].toFixed(2);    inputs[36].value = json['s2'].toFixed(2); 
            inputs[7].value = json['p7'].toFixed(2);    inputs[37].value = json['p32'].toFixed(2); 
            inputs[8].value = json['p8'].toFixed(2);    inputs[38].value = json['p33'].toFixed(2); 
            inputs[9].value = json['t1'].toFixed(2);    inputs[39].value = json['p34'].toFixed(2); 
            inputs[10].value = json['p9'].toFixed(2);    inputs[40].value = json['p35'].toFixed(2); 
            inputs[11].value = json['p10'].toFixed(2);    inputs[41].value = json['p36'].toFixed(2); 
            inputs[12].value = json['p11'].toFixed(2);    inputs[42].value = json['p37'].toFixed(2); 
            inputs[13].value = json['p12'].toFixed(2);    inputs[43].value = json['p38'].toFixed(2); 
            inputs[14].value = json['p13'].toFixed(2);    inputs[44].value = json['p39'].toFixed(2); 
            inputs[15].value = json['p14'].toFixed(2);    inputs[45].value = json['p40'].toFixed(2); 
            inputs[16].value = json['p15'].toFixed(2);    inputs[46].value = json['p41'].toFixed(2); 
            inputs[17].value = json['p16'].toFixed(2);    inputs[47].value = json['s3'].toFixed(2); 
            inputs[18].value = json['p17'].toFixed(2);    inputs[48].value = json['p42'].toFixed(2); 
            inputs[19].value = json['p18'].toFixed(2);    inputs[49].value = json['p43'].toFixed(2); 
            inputs[20].value = json['t2'].toFixed(2);     inputs[50].value = json['p44'].toFixed(2);
            inputs[21].value = json['st1'].toFixed(2);    inputs[51].value = json['p45'].toFixed(2);
            inputs[22].value = json['p19'].toFixed(2);    inputs[52].value = json['p46'].toFixed(2);  
            inputs[23].value = json['p20'].toFixed(2);    inputs[53].value = json['p47'].toFixed(2);                                                                                                                      
            inputs[24].value = json['p21'].toFixed(2);    inputs[54].value = json['p48'].toFixed(2);
            inputs[25].value = json['p22'].toFixed(2);    inputs[55].value = json['p49'].toFixed(2);
            inputs[26].value = json['p23'].toFixed(2);    inputs[56].value = json['p50'].toFixed(2); 
            inputs[27].value = json['p24'].toFixed(2);    inputs[57].value = json['p51'].toFixed(2);
            inputs[28].value = json['p25'].toFixed(2);    inputs[58].value = json['p52'].toFixed(2); 
            inputs[29].value = json['p26'].toFixed(2);    inputs[59].value = json['p53'].toFixed(2); 
            inputs[60].value = json['p54'].toFixed(2); 
            inputs[61].value = json['p55'].toFixed(2); 
            inputs[62].value = json['t4'].toFixed(2); 
            inputs[63].value = json['st2'].toFixed(2); 
            inputs[64].value = json['p56'].toFixed(2); 
            inputs[65].value = json['t5'].toFixed(2); 
            inputs[66].value = json['st3'].toFixed(2); 
            inputs[67].value = json['p57'].toFixed(2); 
            inputs[68].value = json['p58'].toFixed(2); 
            inputs[69].value = json['t6'].toFixed(2); 
            inputs[70].value = json['st4'].toFixed(2); 
            inputs[71].value = (json['st1']+json['st2']+json['st3']+json['st4']).toFixed(2); 

            
            texts[0].innerHTML = json['r1'] == 'n'?  '' : json['r1'];
            texts[1].innerHTML = json['r2'] == 'n'?  '' : json['r2'];
            texts[2].innerHTML = json['r3'] == 'n'?  '' : json['r3'];
            texts[3].innerHTML = json['r4'] == 'n'?  '' : json['r4'];
            texts[4].innerHTML = json['r4'] == 'n'?  '' : json['r5'];
            texts[5].innerHTML = json['r4'] == 'n'?  '' : json['r6'];
        }
    
    };
