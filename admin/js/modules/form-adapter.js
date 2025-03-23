(function($) {
    'use strict';

    // Asegurarse de que el namespace exista
    window.EnglishLineTest = window.EnglishLineTest || {};

    // Inicialización cuando el DOM esté listo
    $(function() {
        // Esperar a que los scripts se carguen
        setTimeout(function() {
            // Verificar si estamos en modo edición
            let formId = $('#form-builder').data('form-id');

            // Si hay un ID de formulario y tenemos datos, procesarlos
            if (formId > 0 && typeof formDataFromPHP !== 'undefined') {
                
                try {
                    let data = formDataFromPHP;

                    if (typeof data === 'string') {
                        try {
                            data = JSON.parse(data);
                        } catch(e) {
                            console.log("Primer intento fallido, aplicando limpieza avanzada:", e.message);
                            
                            try {
                                // Segundo intento: limpiar escapes comunes
                                let cleanedString = data.replace(/\\"/g, '"').replace(/\\\\/g, '\\');
                                data = JSON.parse(cleanedString);
                                console.log('Parseo con limpieza básica exitoso');
                            } catch(e2) {
                                console.log("Segundo intento fallido, aplicando limpieza adicional:", e2.message);
                                
                                try {
                                    cleanedString = data.replace(/\\\\\\"/g, '"').replace(/\\\\\\/g, '\\');
                                    data = JSON.parse(cleanedString);
                                } catch(e3) {
                                    console.log("Tercer intento fallido, último intento:", e3.message);
                                    
                                    try {
                                        if (data.startsWith('"[{\\\\') && data.endsWith('\\\\]"')) {
                                            let rawString = data.substring(1, data.length - 1);
                                            rawString = rawString.replace(/\\\\/g, '\\');
                                            data = JSON.parse(rawString);
                                        }
                                    } catch(e4) {
                                        console.error('Todos los intentos de parseo fallaron. Usando array vacío como fallback');
                                        data = [];
                                    }
                                }
                            }
                        }
                    }

                    // Verificar que data es un array
                    if (!Array.isArray(data)) {
                        data = [];
                    }

                    // Si FormData está disponible, usar su método de carga
                    if (window.EnglishLineTest && window.EnglishLineTest.FormData && 
                        typeof window.EnglishLineTest.FormData.loadFormData === 'function' &&
                        window.EnglishLineTest.FormBuilder) {

                        window.EnglishLineTest.FormData.loadFormData.call(
                            window.EnglishLineTest.FormBuilder, 
                            data
                        );

                    }

                } catch (e) {
                    console.error('FormAdapter: Error general al procesar datos del formulario:', e.message);
                }
            }
        }, 500);
    });

})(jQuery);