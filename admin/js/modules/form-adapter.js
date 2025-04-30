(function($) {
    'use strict';

    window.EnglishLineTest = window.EnglishLineTest || {};
    
    function procesarDatosEscapados(rawData) {
        // Si ya es un objeto, devolverlo tal cual
        if (typeof rawData !== 'string') {
            return rawData;
        }
        
        // Intentar parsear directamente - caso ideal
        try {
            return JSON.parse(rawData);
        } catch (e) {
            console.log('Error al parsear JSON:', e.message);
        }
        
        // Si tiene comillas al inicio y al final, intentar sin ellas
        if (rawData.charAt(0) === '"' && rawData.charAt(rawData.length - 1) === '"') {
            try {
                return JSON.parse(rawData.substr(1, rawData.length - 2));
            } catch(e) {
                console.log('Error al parsear JSON sin comillas:', e.message);
            }
        }
        
        // Si todo lo demás falla, devolver null para manejo de error
        console.warn('No se pudieron procesar los datos del formulario');
        return null;
    }

    function recuperarDatosFormulario() {
        try {
            if (typeof formDataFromPHP === 'undefined') {
                return false;
            }
            
            let datos = procesarDatosEscapados(formDataFromPHP);
            
            if (!datos) {
                console.error('No se pudieron procesar los datos del formulario');
                return false;
            }
            
            // Asegurarse de que tenemos un array de secciones
            if (!Array.isArray(datos)) {
                if (datos && typeof datos === 'object') {
                    if (datos.sections && Array.isArray(datos.sections)) {
                        datos = datos.sections;
                    } else {
                        datos = [datos];
                    }
                } else {
                    return false;
                }
            }
            
            // Cargar datos cuando el FormBuilder esté disponible
            if (window.EnglishLineTest && window.EnglishLineTest.FormBuilder && 
              typeof window.EnglishLineTest.FormBuilder.loadFormData === 'function') {
              
              try {
                  // Verificar que FormData está correctamente inicializado
                  if (!window.EnglishLineTest.FormData) {
                      console.error('FormData no está inicializado');
                      return false;
                  }
                  window.EnglishLineTest.FormBuilder.loadFormData(datos);
                  return true;
              } catch(e) {
                  console.error('Error al cargar datos en FormBuilder:', e);
                  return false;
              }
            } else {
                // Intentar más tarde si FormBuilder no está disponible aún
                setTimeout(function() {
                    recuperarDatosFormulario();
                }, 100);
                return false;
            }
        } catch(e) {
            console.error('Error en recuperarDatosFormulario:', e);
            return false;
        }
    }
    
    window.recoverForm = function() {
        return recuperarDatosFormulario();
    };

    $(function() {
        setTimeout(function() {
            let formId = $('#form-builder').data('form-id');
            if (formId > 0) {
                recuperarDatosFormulario();
            }
        }, 500);
    });

})(jQuery);