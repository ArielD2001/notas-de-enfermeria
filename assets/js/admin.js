var agregar = document.getElementById('agregar-user');
eye = document.querySelector('#eye');
input = document.querySelector('#password');

eye.addEventListener('click', ()=>{
    if(input.type == 'password'){
        input.type = 'text'
    }else{
        input.type = 'password'
    }
})

agregar.addEventListener('click', ()=>{
    var formulario = $('#formulario-user');
    document.querySelectorAll('input').forEach(i=>{
        if(i.value == ''){
            i.style.border = '1px solid red';
        }else{
            i.style.border = '1px solid green';
        }
    })
    if(document.querySelector('select').value == ''){
        document.querySelector('select').style.border = '1px solid red'
    }else{
        document.querySelector('select').style.border = '1px solid green'
    }
    // console.log(formulario)

    $.ajax({
        url: 'config/registrar.php',
        type: "post",
        data: formulario.serialize(),

        //resultado postivo de la confirmacion
        success: function (res) {
          // Verificaion de campos vacios
          if(res == 'campos vacios'){
            $('#resultado-list').html('Por favor complete todos los campos')
          }else if(res == 'ok'){
            document.querySelectorAll('input').forEach(i=>{
                
                    i.style.border = '1px solid ligthgrey';
                
            })
            Swal.fire({
                text: "Agregado correctamente",
                confirmButtonColor: "#3085d6",
                icon: "success",
                width: 300,
              }).then(res=>{
                if(res.isConfirmed){
                  location.reload()
                }
              });
          }else if(res == 'existente'){
            $('#resultado-list').html('El correo ya tiene unacuenta existente')
          }
          else{
            console.log(res)
          }
        }
      })
})

activadores = document.querySelectorAll('#flexSwitchCheckDefault');

activadores.forEach(activador =>{
    activador.addEventListener('click', ()=>{
        id = activador.getAttribute('data-id');

        var check = activador.checked;
           
        $.ajax({
            url: 'config/estado.php',
            type: "post",
            data: {id: id, check: check},
    
            //resultado postivo de la confirmacion
            success: function (res) {
              // Verificaion de campos vacios
             if(res == 'ok'){
                swal.fire({
                    html: 'Actualizado correctamente',
                    position:'bottom-end',
                    width:200,
                    icon:'success',
                    backdrop:false, 
                    timer:2000,
                    showConfirmButton:false
                });
             }else{
                console.log(res)
             }
            }
          })
    })
})
