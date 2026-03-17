document.addEventListener('DOMContentLoaded', function() {
    const catPadre = document.getElementById('cat_padre');
    const selectHijo = document.getElementById('cat_hijo');

    if (catPadre && selectHijo) {
        
        catPadre.addEventListener('change', function() {
            let padreId = this.value;
            
            selectHijo.innerHTML = '<option value="">Cargando...</option>';
            selectHijo.disabled = true;

            if (padreId) {
                fetch(`/api/categorias/${padreId}/hijos`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error al cargar subcategorías');
                        }
                        return response.json();
                    })
                    .then(data => {
                        selectHijo.innerHTML = '<option value="">Seleccione Subtipo...</option>';
                        data.forEach(cat => {
                            selectHijo.innerHTML += `<option value="${cat.id}">${cat.nombre}</option>`;
                        });
                        selectHijo.disabled = false;
                    })
                    .catch(error => {
                        console.error("Error en la carga:", error);
                        selectHijo.innerHTML = '<option value="">Error al cargar subcategorías</option>';
                    });
            } else {
                selectHijo.innerHTML = '<option value="">Primero seleccione el tipo...</option>';
                selectHijo.disabled = true;
            }
        });

        if (!catPadre.value) {
            selectHijo.innerHTML = '<option value="">Primero seleccione el tipo...</option>';
            selectHijo.disabled = true;
        }
    }
});